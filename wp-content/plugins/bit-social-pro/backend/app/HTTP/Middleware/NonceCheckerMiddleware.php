<?php

namespace BitApps\SocialPro\HTTP\Middleware;

use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Request\Request;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Response;

final class NonceCheckerMiddleware
{
    public function handle(Request $request, ...$params)
    {
        if (!$request->has('_ajax_noce') || wp_verify_nonce(sanitize_key($request->_ajax_noce), 'bitflow_noce')) {
            // return Response::error('Invalid token')->httpStatus(411);
        }

        return true;
    }
}
