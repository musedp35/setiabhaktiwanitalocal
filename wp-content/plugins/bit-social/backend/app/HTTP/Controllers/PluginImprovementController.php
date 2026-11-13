<?php

namespace BitApps\Social\HTTP\Controllers;

use BitApps\Social\Config;
use BitApps\Social\Deps\BitApps\WPKit\Http\Request\Request;
use BitApps\Social\Deps\BitApps\WPKit\Http\Response;
use BitApps\Social\Deps\BitApps\WPTelemetry\Telemetry\Telemetry;

final class PluginImprovementController
{
    public function getData()
    {
        return Response::success([
            'allowTracking' => (bool) Config::getOption('allow_tracking'),
            'dataWeCollect' => Telemetry::report()->dataWeCollect(),
        ]);
    }

    public function createOrUpdate(Request $request)
    {
        $validatedData = (object) $request->validate([
            'allowTracking' => ['required', 'boolean'],
        ]);

        // Plugin::initTelemetry() does not configure the library in development mode.
        if (Config::getEnv('DEV')) {
            return Response::error(__('Telemetry is off in development mode.', 'bit-social'));
        }

        if ($validatedData->allowTracking) {
            Telemetry::report()->trackingOptIn('settings');
        } else {
            Telemetry::report()->trackingOptOut();
        }

        return Response::success([
            'allowTracking' => $validatedData->allowTracking,
        ]);
    }
}
