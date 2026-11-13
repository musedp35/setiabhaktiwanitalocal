<?php

namespace BitApps\SocialPro\HTTP\Middleware;

use BitApps\SocialPro\Config;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Response;

final class IsProCheckerMiddleware
{
    public function handle()
    {
        if (!Config::isPro()) {
            return Response::error('Please active with valid license.')->httpStatus(403);
        }

        return true;
    }
}
