<?php

namespace BitApps\SocialPro\HTTP\Requests;

use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Request\Request;

class ExternalCronUpdateRequest extends Request
{
    public function rules()
    {
        return [
            'settings' => ['required', 'array']
        ];
    }
}
