<?php

namespace BitApps\SocialPro\Providers;

class ValidationHookProvider
{
    public static function getProValidationRules($validationRules, $platform)
    {
        switch ($platform) {
            case 'twitter':
                $twitterAppValidationRules = [
                    'appName'    => ['required', 'string'],
                    'apiVersion' => ['required', 'string']
                ];

                return array_merge($validationRules, $twitterAppValidationRules);
            case 'tumblr':

                $tumblrAppValidationRules = [
                    'appName' => ['required', 'string'],
                ];

                return array_merge($validationRules, $tumblrAppValidationRules);
            default:

                return $validationRules;
        }
    }
}
