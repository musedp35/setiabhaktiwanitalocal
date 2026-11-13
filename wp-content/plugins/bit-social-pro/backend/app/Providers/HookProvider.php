<?php

namespace BitApps\SocialPro\Providers;

use BitApps\SocialPro\Config;
use BitApps\SocialPro\Deps\BitApps\WPKit\Hooks\Hooks;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\RequestType;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Router\Router;
use BitApps\SocialPro\Plugin;
use BitApps\SocialPro\Utils\ProSmartTagHandler;
use FilesystemIterator;

class HookProvider
{
    private $_pluginBackend;

    public function __construct()
    {
        $this->_pluginBackend = Config::get('BASEDIR') . DIRECTORY_SEPARATOR;
        $this->loadTriggersAjax();
        $this->loadAppHooks();
        $this->loadActionsHooks();
        Hooks::addAction('rest_api_init', [$this, 'loadApi']);
        Hooks::addFilter(Config::VAR_PREFIX . 'validation_check', [ValidationHookProvider::class, 'getProValidationRules'], 10, 2);
        Hooks::addFilter(Config::VAR_PREFIX . 'get_linkedin_company_page', [LinkedInCompanyPageProvider::class, 'getLinkedinCompanyPage'], 10, 3);
        Hooks::addFilter(Config::VAR_PREFIX . 'smart_tags', [ProSmartTagHandler::class, 'getProSmartTagValue'], 10, 2);
    }

    /**
     * Helps to register integration ajax.
     */
    public function loadActionsHooks()
    {
        // $this->includeTaskHooks('Actions');
    }

    /**
     * Loads API routes.
     */
    public function loadApi()
    {
        if (
            is_readable($this->_pluginBackend . 'hooks' . DIRECTORY_SEPARATOR . 'api.php')
            && RequestType::is(RequestType::API)
        ) {
            $router = new Router(RequestType::API, Config::SLUG, 'v1');

            include $this->_pluginBackend . 'hooks' . DIRECTORY_SEPARATOR . 'api.php';
            $router->register();
        }
    }

    /**
     * Helps to register App hooks.
     */
    protected function loadAppHooks()
    {
        if (
            RequestType::is(RequestType::AJAX)
            && is_readable($this->_pluginBackend . 'hooks' . DIRECTORY_SEPARATOR . 'ajax.php')
        ) {
            $router = new Router(RequestType::AJAX, Config::VAR_PREFIX, '');
            $router->setMiddlewares(Plugin::instance()->middlewares());
            include $this->_pluginBackend . 'hooks' . DIRECTORY_SEPARATOR . 'ajax.php';
            $router->register();
        }

        if (is_readable($this->_pluginBackend . 'hooks.php')) {
            include $this->_pluginBackend . 'hooks.php';
        }
    }

    /**
     * Helps to register Triggers ajax.
     */
    protected function loadTriggersAjax()
    {
        // $this->includeTaskHooks('Triggers');
    }

    /**
     * Backend Routes and Hooks.
     *
     * @param string $taskName Triggers|Actions
     */
    private function includeTaskHooks($taskName)
    {
        $taskDir = $this->_pluginBackend . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $taskName;
        $dirs = new FilesystemIterator($taskDir);
        foreach ($dirs as $dirInfo) {
            if ($dirInfo->isDir()) {
                $taskName = basename($dirInfo);
                $taskPath = $taskDir . DIRECTORY_SEPARATOR . $taskName . DIRECTORY_SEPARATOR;
                if (is_readable($taskPath . 'Routes.php') && RequestType::is('ajax') && RequestType::is('admin')) {
                    include $taskPath . 'Routes.php';
                }

                if (is_readable($taskPath . 'Hooks.php')) {
                    include $taskPath . 'Hooks.php';
                }
            }
        }
    }
}
