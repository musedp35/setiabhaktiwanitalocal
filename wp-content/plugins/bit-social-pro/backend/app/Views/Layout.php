<?php

// phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps

namespace BitApps\SocialPro\Views;

use BitApps\Social\Config as FreeConfig;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Config;
use BitApps\SocialPro\Deps\BitApps\WPKit\Hooks\Hooks;
use BitApps\SocialPro\Utils\Services\LicenseService;

/**
 * The admin Layout and page handler class.
 */
class Layout
{
    public function __construct()
    {
        Hooks::addAction('in_admin_header', [$this, 'RemoveAdminNotices']);
        if (class_exists(FreeConfig::class)) {
            Hooks::addFilter(FreeConfig::withPrefix('localized_script'), [$this, 'createConfigVariable']);
            Hooks::addFilter(FreeConfig::withPrefix('admin_sidebar_menu'), [$this, 'filterSideBarMenu']);
        }
    }

    public function RemoveAdminNotices()
    {
        global $plugin_page;
        if (empty($plugin_page) || strpos($plugin_page, FreeConfig::SLUG) === false) {
            return;
        }

        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }

    public function filterSideBarMenu($prevArray)
    {
        $prevArray['Home']['title'] = 'Bit Social Pro';
        $prevArray['Home']['name'] = 'Bit Social Pro';

        return $prevArray;
    }

    public function createConfigVariable($prevArray)
    {
        $licenseData = LicenseService::getLicenseData();

        $prevArray['isPro'] = Config::checkLicenseValidity($licenseData);
        $prevArray['proPluginVersion'] = Config::VERSION;
        $prevArray['proModuleUrl'] = Config::get('ASSET_URI') . '/';
        $prevArray['proSlug'] = Config::SLUG;
        $prevArray['isProExist'] = true;

        if ($prevArray['isPro']) {
            $prevArray['key'] = isset($licenseData['key']) ? Hash::encrypt($licenseData['key']) : null;
        }

        return $prevArray;
    }
}
