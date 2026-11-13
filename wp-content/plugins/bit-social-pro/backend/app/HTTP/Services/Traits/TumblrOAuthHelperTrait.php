<?php

namespace BitApps\SocialPro\HTTP\Services\Traits;

use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

trait TumblrOAuthHelperTrait
{
    public function tumblrOAuth($oauthToken, $oauthVerifier, $oauthSecret, $url, $data, $requestType, $appKey, $appSecret, $accessToken = null, $accessTokenSecret = null)
    {
        $oauthParams = [
            'oauth_consumer_key'     => $appKey,
            'oauth_nonce'            => md5(wp_rand(1000000, 99999999)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => time(),
            'oauth_version'          => '1.0',
        ];

        if ($oauthToken !== null) {
            $oauthParams['oauth_token'] = $oauthToken;
        }

        if ($oauthVerifier !== null) {
            $oauthParams['oauth_verifier'] = $oauthVerifier;
        }

        if ($accessToken !== null) {
            $oauthParams['oauth_token'] = $accessToken;
        }

        $params = $oauthParams;

        $params = array_merge($params, $data);

        ksort($params);

        $baseString = $requestType . '&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($params, '', '&', PHP_QUERY_RFC3986));

        $signatureKey = rawurlencode($appSecret) . '&';

        if ($oauthSecret !== null) {
            $signatureKey .= rawurlencode($oauthSecret);
        }

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
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ];

        $httpClient = new HttpClient();

        return $httpClient->request($url, $requestType, $data, $headers);
    }
}
