<?php

namespace BitApps\SocialPro\HTTP\Services\Traits;

use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

trait TwitterOAuthHelperTrait
{
    public function twitterOAuth($url, $data, $requestType, $appKey, $appSecret, $accessToken = null, $accessTokenSecret = null, $screenName = null, $fileType = null, $imageData = null, $callbackUrl = null)
    {
        $oauthParams = [
            'oauth_consumer_key'     => $appKey,
            'oauth_nonce'            => wp_rand(1000000, 99999999),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => time(),
            'oauth_version'          => '1.0',
        ];

        if ($accessToken !== null) {
            $oauthParams['oauth_token'] = $accessToken;
        }

        if ($callbackUrl !== null) {
            $oauthParams['oauth_callback'] = $callbackUrl;
        }

        $params = $oauthParams;

        if ($fileType === 'image') {
            $params = array_merge($oauthParams, ['media_data' => $imageData]);
        }

        if ($screenName !== null) {
            $params = array_merge($oauthParams, ['screen_name' => $screenName]);
        }

        ksort($params);

        $baseString = $requestType . '&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($params, '', '&', PHP_QUERY_RFC3986));

        $signatureKey = rawurlencode($appSecret) . '&';
        if ($accessTokenSecret !== null) {
            $signatureKey .= rawurlencode($accessTokenSecret);
        }

        $oauthParams['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signatureKey, true));

        $oauthHeader = 'OAuth ' . implode(', ', array_map(
            function ($key, $value) {
                return rawurlencode($key) . '="' . rawurlencode($value) . '"';
            },
            array_keys($oauthParams),
            $oauthParams
        ));

        $headers = [
            'Authorization' => $oauthHeader
        ];
        if ($fileType === 'image') {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        } elseif (!empty($data)) {
            $headers['Content-Type'] = 'application/json';
        }

        $url = $screenName !== null ? $url . '?screen_name=' . rawurlencode($screenName) : $url;

        $httpClient = new HttpClient();

        return $httpClient->request($url, $requestType, $data, $headers);
    }
}
