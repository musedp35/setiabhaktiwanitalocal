<?php

namespace BitApps\SocialPro\Update;

use BitApps\SocialPro\Config;
use BitApps\SocialPro\Deps\BitApps\WPKit\Hooks\Hooks;
use stdClass;

/**
 * Helps to update plugin
 */
final class Updater
{
    private $_name;

    private $_slug;

    private $_version;

    private $_label;

    private $_author;

    private $_homepage;

    private $_freeVersion;

    private $_cacheKey;

    /**
     * Constructor of Updater class
     */
    public function __construct()
    {
        $this->_slug = 'bit-social-pro';
        $this->_name = Config::get('BASENAME');
        $this->_version = Config::VERSION;
        $this->_label = 'Bit Social Connect Wordpress Plugins And External Applications';
        $this->_author = '<a href="https://bitapps.pro">Bit Apps</a>';
        $this->_homepage = 'https://bitapps.pro';
        $this->_cacheKey = md5('btcbi_plugin_info');
        $this->registerHooks();
        $this->removeCache();
        Hooks::addAction('admin_notices', [$this, 'licenseExpirationNotice']);
    }

    public function licenseExpirationNotice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        global $pagenow;
        if ('plugins.php' !== $pagenow) {
            return;
        }
        $integrateStatus = Config::getOption('license_data', null);
        if (!empty($integrateStatus['expireIn'])) {
            $expireInDays = (strtotime($integrateStatus['expireIn']) - time()) / DAY_IN_SECONDS;
            if ($expireInDays < 25) {
                $notice = $expireInDays > 0
                    ? \sprintf(__('Bit Social License will expire in %s days', 'bit-social'), (int) $expireInDays)
                    : __('Bit Social License is expired', 'bit-social')
                ?>
<div class="notice notice-error is-dismissible">
    <p><?php echo $notice; ?></p>
</div><?php
            }
        }
    }

    public function checkUpdate($cacheData)
    {
        global $pagenow;

        if (!\is_object($cacheData)) {
            $cacheData = new \stdClass();
        }
        if ('plugins.php' === $pagenow && is_multisite()) {
            return $cacheData;
        }

        return $this->checkCacheData($cacheData);
    }

    public function shortCircuitPluginsApi($_data, $_action = '', $_args = null)
    {
        if ('plugin_information' !== $_action) {
            return $_data;
        }
        if (!isset($_args->slug) || ($_args->slug !== $this->_slug)) {
            return $_data;
        }

        $cacheKey = $this->_slug . '_api_request_' . md5(serialize($this->_slug));

        $apiResponseCache = get_site_transient($cacheKey);

        if (empty($apiResponseCache)) {
            $apiResponse = API::getUpdatedInfo();
            $apiResponseCache = $this->formatApiData($apiResponse);
            set_site_transient($cacheKey, $apiResponseCache, DAY_IN_SECONDS);
        }

        return $apiResponseCache;
    }

    public function showUpdateInfo($file, $plugin)
    {
        if ($this->_name !== $file) {
            return;
        }
        if (is_network_admin()) {
            return;
        }

        if (!is_multisite()) {
            return;
        }

        if (!current_user_can('update_plugins')) {
            return;
        }
        remove_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate']);
        $update_cache = get_site_transient('update_plugins');
        $update_cache = $this->checkCacheData($update_cache);

        set_site_transient('update_plugins', $update_cache);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate']);
    }

    public function removeCache()
    {
        global $pagenow;
        if ('update-core.php' === $pagenow && isset($_GET['force-check'])) {
            Config::deleteOption($this->_cacheKey);
        }
    }

    public function checkCacheData($cacheData)
    {
        if (!\is_object($cacheData)) {
            $cacheData = new \stdClass();
        }

        if (empty($cacheData->checked)) {
            return $cacheData;
        }

        $versionInfo = $this->getCache();

        if (\is_null($versionInfo) || $versionInfo === false) {
            $versionInfo = API::getUpdatedInfo();

            if (is_wp_error($versionInfo)) {
                $versionInfo = new \stdClass();
                $versionInfo->error = true;
            }

            $this->setCache($versionInfo);
        }
        if (!empty($versionInfo->error)) {
            return $cacheData;
        }

        // include an unmodified $wp_version
        include ABSPATH . WPINC . '/version.php';
        if (version_compare($wp_version, $versionInfo->requireWP, '<')) {
            return $cacheData;
        }

        if (!empty($this->_freeVersion) && !empty($versionInfo->requiresFree)) {
            if (version_compare($this->_freeVersion, $versionInfo->requiresFree, '<')) {
                return $cacheData;
            }
        }
        if (version_compare($this->_version, $versionInfo->version, '<')) {
            $cacheData->response[$this->_name] = $this->formatApiData($versionInfo);
        } else {
            $noUpdateInfo = (object) [
                'id'          => $this->_name,
                'slug'        => $this->_slug,
                'plugin'      => $this->_name,
                'new_version' => $this->_version,
                'url'         => '',
                'package'     => '',
                'banners'     => [
                    'high' => 'https://ps.w.org/bit-social/assets/banner-772x250.jpg?rev=2657199'
                ],
                'banners_rtl'   => [],
                'tested'        => '',
                'requires_php'  => '',
                'compatibility' => new stdClass(),
            ];
            $cacheData->no_update[$this->_name] = $noUpdateInfo;
        }

        $cacheData->last_checked = current_time('timestamp');
        $cacheData->checked[$this->_name] = $this->_version;

        return $cacheData;
    }

    private function registerHooks()
    {
        Hooks::addFilter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate']);
        Hooks::addAction('delete_site_transient_update_plugins', [$this, 'removeCache']);
        Hooks::addFilter('plugins_api', [$this, 'shortCircuitPluginsApi'], 10, 3);

        Hooks::removeAction('after_plugin_row_' . $this->_name, 'wp_plugin_update_row');
        Hooks::addAction('after_plugin_row_' . $this->_name, [$this, 'showUpdateInfo'], 10, 2);
    }

    private function getCache()
    {
        $cacheData = Config::getOption($this->_cacheKey);

        if (empty($cacheData['timeout']) || current_time('timestamp') > $cacheData['timeout']) {
            return false;
        }

        return $cacheData['value'];
    }

    private function setCache($cacheValue)
    {
        $expiration = strtotime('+12 hours', current_time('timestamp'));
        $data = [
            'timeout' => $expiration,
            'value'   => $cacheValue,
        ];

        Config::updateOption($this->_cacheKey, $data, 'no');
    }

    private function formatApiData($apiResponse)
    {
        $formattedData = new \stdClass();
        $formattedData->name = $this->_label;
        $formattedData->slug = $this->_slug;
        $formattedData->plugin = $this->_name;
        $formattedData->id = $this->_name;
        $formattedData->author = $this->_author;
        $formattedData->homepage = $this->_homepage;
        if (is_wp_error($apiResponse)) {
            $formattedData->requires = '';
            $formattedData->tested = '';
            $formattedData->new_version = $this->_version;
            $formattedData->last_updated = '';
            $formattedData->download_link = '';
            $formattedData->banners = [
                'high' => 'https://ps.w.org/bit-social/assets/banner-772x250.jpg?rev=2657199'
            ];
            $formattedData->sections = null;

            return $formattedData;
        }
        $formattedData->requires = $apiResponse->requireWP;
        $formattedData->tested = $apiResponse->tested ?? null;
        $formattedData->new_version = $apiResponse->version;
        $formattedData->last_updated = $apiResponse->updatedAt;
        $formattedData->download_link = !empty($apiResponse->downloadLink) ? $apiResponse->downloadLink . '/' . $this->_slug . '.zip' : '';
        $formattedData->package = !empty($apiResponse->downloadLink) ? $apiResponse->downloadLink . '/' . $this->_slug . '.zip' : '';
        $formattedData->banners = [
            'high' => 'https://ps.w.org/bit-social/assets/banner-772x250.jpg?rev=2657199'
        ];
        $formattedData->sections = $apiResponse->sections;

        return $formattedData;
    }
}
?>