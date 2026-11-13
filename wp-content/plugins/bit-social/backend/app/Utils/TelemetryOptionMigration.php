<?php

namespace BitApps\Social\Utils;

use BitApps\Social\Config;
use BitApps\Social\Deps\BitApps\WPTelemetry\Telemetry\Telemetry;

/**
 * Telemetry lived in Pro, with options under bit_social_pro_. Free now owns it,
 * with options under bit_social_. This runs one time on each site.
 *
 * - Plugin::initTelemetry() does not call this while an old Pro version is active.
 * - A Pro choice moves to Free, so a Pro admin is not asked again.
 * - An old Free opt-in is removed. That admin did not see the data that this
 *   version sends, so the plugin must ask again.
 */
final class TelemetryOptionMigration
{
    public const DONE_OPTION = 'telemetry_migrated';

    private const OPTION_KEYS = [
        'allow_tracking',
        'tracking_last_sended_at',
        'tracking_notice_dismissed',
        'tracking_skipped',
        'tracking_opt_in_source',
    ];

    /**
     * Call this after TelemetryConfig is ready, because it can schedule the weekly report.
     */
    public static function run()
    {
        if (Config::getOption(self::DONE_OPTION)) {
            return;
        }

        Telemetry::report()->deleteData(Config::VAR_PREFIX);

        foreach (self::OPTION_KEYS as $key) {
            $proKey = Config::PRO_PLUGIN_VAR_PREFIX . $key;
            $proValue = get_option($proKey, null);

            if ($proValue === null) {
                continue;
            }

            Config::updateOption($key, $proValue);
            delete_option($proKey);
        }

        // The Pro weekly event has no listener now.
        wp_clear_scheduled_hook(Config::PRO_PLUGIN_VAR_PREFIX . 'send_tracking_event');

        // This call only restores the weekly event for a site that opted in. It sends nothing.
        if (Telemetry::report()->isTrackingAllowed()) {
            Telemetry::report()->trackingOptIn();
        }

        Config::updateOption(self::DONE_OPTION, true);
    }
}
