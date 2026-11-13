<?php

namespace BitApps\Social\Utils;

use BitApps\Social\Model\Account;
use BitApps\Social\Model\Log;
use BitApps\Social\Model\Schedule;

/**
 * The plugin usage data in a telemetry report, and its text in the consent list.
 * WordPress.org requires consent, so the plugin must tell the admin about all
 * data it sends, which includes the data that Pro adds through filters. Keep
 * additionalData(), dataWeCollect() and the Pro filters in step.
 */
final class TelemetryData
{
    /**
     * @param mixed $additionalData
     *
     * @return array
     */
    public static function additionalData($additionalData)
    {
        $accounts = Account::get(['platform', 'account_type', 'status']);
        $accounts = \is_array($accounts) ? $accounts : [];
        $platformSummary = [];

        foreach ($accounts as $account) {
            $platformSummary[$account->platform] = ($platformSummary[$account->platform] ?? 0) + 1;
        }

        $additionalData = (array) $additionalData;
        $additionalData['accounts'] = $accounts;
        $additionalData['accountSummary'] = [
            'platformSummary' => $platformSummary,
            'totalAccounts'   => \count($accounts),
        ];
        $additionalData['activeAccountCount'] = Account::where('status', true)->count() ?? 0;
        $additionalData['publishedPostCount'] = Log::where('status', true)->count() ?? 0;
        $additionalData['activeScheduleCount'] = Schedule::where('status', true)->count() ?? 0;
        $additionalData['totalShareNowCount'] = Schedule::where('schedule_type', Schedule::scheduleType['DIRECT_SHARE'])->count() ?? 0;

        return $additionalData;
    }

    /**
     * @param mixed $collectList
     *
     * @return array
     */
    public static function dataWeCollect($collectList)
    {
        $collectList = (array) $collectList;

        $collectList[] = __('The platform, type and status of each connected social account', 'bit-social');
        $collectList[] = __('The number of active accounts, published posts, active schedules and Share Now items', 'bit-social');
        $collectList[] = __('If Bit Social Pro is active: the Pro version, license status and expiry date, AI prompt types and models, and connected AI platforms', 'bit-social');

        return $collectList;
    }
}
