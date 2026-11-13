<?php

namespace BitApps\SocialPro;

/*
 * Main class for the plugin.
 *
 * @since 1.0.0-alpha
 */

use BitApps\Social\Config as freeConfig;
use BitApps\SocialPro\Config as proConfig;
use BitApps\SocialPro\Deps\BitApps\WPKit\Hooks\Hooks;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\RequestType;
use BitApps\SocialPro\Deps\BitApps\WPKit\Migration\MigrationHelper;
use BitApps\SocialPro\Deps\BitApps\WPKit\Utils\Capabilities;
use BitApps\SocialPro\HTTP\Middleware\IsProCheckerMiddleware;
use BitApps\SocialPro\HTTP\Middleware\NonceCheckerMiddleware;
use BitApps\SocialPro\Providers\HookProvider;
use BitApps\SocialPro\Providers\InstallerProvider;
use BitApps\SocialPro\Update\Updater;
use BitApps\SocialPro\Utils\PluginCommonConfig;
use BitApps\SocialPro\Views\Layout;

final class Plugin
{
    /**
     * Main instance of the plugin.
     *
     * @since 1.0.0-alpha
     *
     * @var null|Plugin
     */
    private static $_instance;

    private $_registeredMiddleware = [];

    /**
     * Initialize the Plugin with hooks.
     */
    public function __construct()
    {
        $this->registerInstaller();
        Hooks::addAction('plugins_loaded', [$this, 'loaded']);
        // $this->loaded();
    }

    public function registerInstaller()
    {
        $installerProvider = new InstallerProvider();
        $installerProvider->register();
    }

    /**
     * Load the plugin.
     */
    public function loaded()
    {
        Hooks::doAction(Config::withPrefix('loaded'));
        Hooks::addAction('init', [$this, 'registerProviders'], 11);
        // Hooks::addFilter('plugin_action_links_' . Config::get('BASENAME'), [$this, 'actionLinks']);
        $this->maybeMigrateDB();
        $this->setPluginUtilsConfig();
    }

    public function setPluginUtilsConfig()
    {
        PluginCommonConfig::setFreePluginVersion(freeConfig::VERSION);
        PluginCommonConfig::setProPluginVersion(proConfig::VERSION);
        PluginCommonConfig::setFreePluginSlug(freeConfig::SLUG);
        PluginCommonConfig::setProPluginSlug(proConfig::SLUG);
        PluginCommonConfig::setFreePluginPrefix(freeConfig::VAR_PREFIX);
        PluginCommonConfig::setProPluginPrefix(proConfig::VAR_PREFIX);
        PluginCommonConfig::setFreePluginTitle(freeConfig::TITLE);
        PluginCommonConfig::setApiEndPoint('https://wp-api.bitapps.pro');
    }

    public function middlewares()
    {
        return [
            'nonce' => NonceCheckerMiddleware::class,
            'isPro' => IsProCheckerMiddleware::class,
        ];
    }

    public function getMiddleware($name)
    {
        if (isset($this->_registeredMiddleware[$name])) {
            return $this->_registeredMiddleware[$name];
        }

        $middlewares = $this->middlewares();
        if (isset($middlewares[$name]) && class_exists($middlewares[$name]) && method_exists($middlewares[$name], 'handle')) {
            $this->_registeredMiddleware[$name] = new $middlewares[$name]();
        } else {
            return false;
        }

        return $this->_registeredMiddleware[$name];
    }

    /**
     * Instantiate the Provider class.
     */
    public function registerProviders()
    {
        global $wp_version;

        if (version_compare($wp_version, '6.5', '<') && !did_action(Config::FREE_PLUGIN_VAR_PREFIX . 'loaded')) {
            return PluginDependencyHandler::checkDependencyForProPlugin();
        }

        if (RequestType::is('admin')) {
            new Updater();
            new Layout();
        }

        new HookProvider();
    }

    public static function maybeMigrateDB()
    {
        if (!Capabilities::check('manage_options')) {
            return;
        }

        if (Config::getOption('db_version') !== Config::DB_VERSION) {
            MigrationHelper::migrate(InstallerProvider::migration());
        }
    }

    /**
     * Retrieves the main instance of the plugin.
     *
     * @since 1.0.0-alpha
     *
     * @return Plugin plugin main instance
     */
    public static function instance()
    {
        return static::$_instance;
    }

    /**
     * Loads the plugin main instance and initializes it.
     *
     * @return bool True if the plugin main instance could be loaded, false otherwise
     *
     * @since 1.0.0-alpha
     */
    public static function load()
    {
        if (static::$_instance !== null) {
            return false;
        }

        static::$_instance = new static();

        return true;
    }
}
