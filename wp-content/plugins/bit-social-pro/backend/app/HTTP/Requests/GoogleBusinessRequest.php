<?php

namespace BitApps\SocialPro\HTTP\Requests;

use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Request\Request;

class GoogleBusinessRequest extends Request
{
    public function rules()
    {
        return [
            'profile_id'   => ['required', 'string'],
            'account_id'   => ['required', 'string'],
            'account_name' => ['required', 'string'],
            'details'      => ['required', 'string'],
            'platform'     => ['required', 'string'],
            'account_type' => ['required', 'integer'],
            'status'       => ['required', 'integer']
        ];
    }
}
