<?php

namespace BitApps\SocialPro\HTTP\Services\Social\AppInfo;

use BitApps\Social\HTTP\Services\Interfaces\PlatformAppInfoResolverInterface;

class TwitterAppInfoResolver implements PlatformAppInfoResolverInterface
{
    public function getAppInfo($validatedData)
    {
        if (!$validatedData->appName) {
            return (object) ['status' => 'error', 'message' => 'Invalid App Credentials!'];
        }

        return (object) ['status' => 'success', 'appName' => $validatedData->appName];
    }
}
