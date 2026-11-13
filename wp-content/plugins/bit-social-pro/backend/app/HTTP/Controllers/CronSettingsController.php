<?php

namespace BitApps\SocialPro\HTTP\Controllers;

use BitApps\Social\Config as FreePluginConfig;
use BitApps\SocialPro\Config;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Response;
use BitApps\SocialPro\HTTP\Requests\ExternalCronUpdateRequest;

class CronSettingsController
{
    public const EXTERNAL_CRON_URL = 'https://wp-api.bitapps.pro/public/wp-cron';

    public const EXTERNAL_CRON_ERROR_MESSAGE = 'Make sure your site is not running on localhost or in development mode!';

    public const WP_CRON_PATH = '/wp-cron.php';

    public function updateExternalCronSetting(ExternalCronUpdateRequest $request)
    {
        if (FreePluginConfig::getEnv('DEV')) {
            return Response::error(['errors' => ['isExternalCronEnabled' => static::EXTERNAL_CRON_ERROR_MESSAGE]]);
        }

        $settings = $request->settings;

        if (!isset($settings['cron']['isExternalCronEnabled'])) {
            return Response::error(['errors' => ['isExternalCronEnabled' => 'Invalid cron request!']]);
        }

        if ($settings['cron']['isExternalCronEnabled']) {
            return $this->createExternalCron($settings);
        }

        return $this->deleteExternalCron($settings);
    }

    public function createExternalCron($settings)
    {
        $url = static::EXTERNAL_CRON_URL . '-create';

        $createCron = $this->externalCronRequest($url);

        if (!$createCron->success) {
            return Response::error(['errors' => ['isExternalCronEnabled' => $createCron->message]]);
        }

        Config::updateOption('settings', $settings);

        return Response::success($settings);
    }

    public function deleteExternalCron($settings)
    {
        $url = static::EXTERNAL_CRON_URL . '-delete';

        $deleteCron = $this->externalCronRequest($url);

        if (!$deleteCron->success) {
            return Response::error(['errors' => ['isExternalCronEnabled' => $deleteCron->message]]);
        }

        Config::updateOption('settings', $settings);

        return Response::success($settings);
    }

    public function getBodyData()
    {
        $proLicense = Config::getOption('license_data');
        $userLicense = base64_encode($proLicense['key']);

        $domain = Config::get('SITE_URL') . static::WP_CRON_PATH;

        return [
            'link'       => $domain,
            'licenseKey' => $userLicense,
        ];
    }

    public function externalCronRequest($url)
    {
        $httpClient = new HttpClient();
        $bodyData = $this->getBodyData();

        return $httpClient->request($url, 'POST', $bodyData);
    }
}
