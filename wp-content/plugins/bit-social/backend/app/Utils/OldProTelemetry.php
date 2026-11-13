<?php

namespace BitApps\Social\Utils;

use BitApps\Social\Config;

/**
 * An old Pro version keeps its own telemetry, options and consent route. Free
 * must not start telemetry while that version is active, because the Pro
 * checkbox cannot stop a Free report. Free starts when Pro updates.
 */
final class OldProTelemetry
{
    /**
     * Only a Pro version with its own telemetry has this file. Free reads the
     * file because Pro can load after Free, so a Pro class is not always ready.
     */
    public const MARKER_FILE = Config::PRO_PLUGIN_SLUG . '/backend/app/HTTP/Controllers/PluginImprovementController.php';

    /**
     * @param null|string $pluginDir the plugins folder
     *
     * @return bool
     */
    public static function isActive($pluginDir = null)
    {
        $pluginDir = $pluginDir ?? WP_PLUGIN_DIR;

        return self::isProActive() && file_exists($pluginDir . '/' . self::MARKER_FILE);
    }

    /**
     * @return bool
     */
    private static function isProActive()
    {
        $basename = Config::PRO_PLUGIN_SLUG . '/' . Config::PRO_PLUGIN_SLUG . '.php';

        if (\in_array($basename, (array) get_option('active_plugins', []), true)) {
            return true;
        }

        return is_multisite() && isset(((array) get_site_option('active_sitewide_plugins', []))[$basename]);
    }
}
