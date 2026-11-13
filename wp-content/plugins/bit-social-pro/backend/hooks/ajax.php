<?php

use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Router\Route;
use BitApps\SocialPro\HTTP\Controllers\CalendarController;
use BitApps\SocialPro\HTTP\Controllers\CronSettingsController;
use BitApps\SocialPro\HTTP\Controllers\GroupController;
use BitApps\SocialPro\HTTP\Controllers\SettingsController;
use BitApps\SocialPro\HTTP\Services\Social\GoogleBusinessProfileService\GoogleBusinessProfileOAuth2Service;
use BitApps\SocialPro\HTTP\Services\Social\TumblrService\TumblrOAuth1Service;
use BitApps\SocialPro\HTTP\Services\Social\TwitterService\TwitterOAuth1Service;
use BitApps\SocialPro\Utils\HTTP\Controllers\LicenseController;
use BitApps\SocialPro\Utils\HTTP\Controllers\PluginUpdateController;

if (!defined('ABSPATH')) {
    exit;
}

Route::group(function () {
    Route::post('twitter-request-token', [TwitterOAuth1Service::class, 'requestToken']);
    Route::post('tumblr-request-token', [TumblrOAuth1Service::class, 'requestToken']);

    Route::post('settings/external-cron/update', [CronSettingsController::class, 'updateExternalCronSetting']);
    Route::post('settings/general/update', [SettingsController::class, 'updateGeneralSettings']);
    Route::get('calendar-schedules', [CalendarController::class, 'getAllSchedules']);
    Route::post('google-business-profile/verify', [GoogleBusinessProfileOAuth2Service::class, 'verify']);
    Route::get('update-plugin', [PluginUpdateController::class, 'updatePlugin']);

    // Group

    Route::get('groups', [GroupController::class, 'index']);
    Route::post('groups', [GroupController::class, 'store']);
    Route::get('groups/{group}', [GroupController::class, 'getGroupAccounts']);
    Route::post('groups/{groupId}/update', [GroupController::class, 'update']);
    Route::post('groups/{group}/destroy', [GroupController::class, 'destroy']);
    Route::post('group-account/{groupId}/{accountId}/destroy', [GroupController::class, 'removeAccountFromGroup']);
})->middleware('nonce:admin', 'isPro');

Route::group(function () {
    Route::get('plugin/update-check', [PluginUpdateController::class, 'isPluginUpdateAvailable']);
    Route::post('license/activate', [LicenseController::class, 'activateLicense']);
    Route::post('license/deactivate', [LicenseController::class, 'deactivateLicense']);
    Route::post('license/check', [LicenseController::class, 'checkLicenseStatus']);
})->middleware('nonce:admin');
