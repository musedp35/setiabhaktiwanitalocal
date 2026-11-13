<?php

namespace BitApps\SocialPro\HTTP\Services\Social\TwitterService;

use BitApps\Social\Model\Account;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use stdClass;

class TwitterRefreshTokenService
{
    public const REFRESH_TOKEN_URL = 'https://api.twitter.com/2/oauth2/token';

    private $httpHandler;

    public function __construct()
    {
        $this->httpHandler = new HttpClient();
    }

    public function refreshToken($clientId, $clientSecret, $refreshToken)
    {
        $authHeader = base64_encode($clientId . ':' . $clientSecret);

        $headers = [
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Authorization' => "Basic {$authHeader}"
        ];

        $data = [
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ];

        $tokenResponse = $this->httpHandler->request(static::REFRESH_TOKEN_URL, 'POST', $data, $headers);

        if (!property_exists($tokenResponse, 'access_token')) {
            return (object) ['status' => 'error', 'message' => 'Refresh token request failed!'];
        }

        $data = new stdClass();
        $data->access_token = $tokenResponse->access_token;
        $data->refresh_token = $tokenResponse->refresh_token;
        $data->expires_in = time() + $tokenResponse->expires_in;

        return $data;
    }

    public function saveDetails($accountDetails)
    {
        $accountId = $accountDetails->account_id;
        if (empty($accountId)) {
            return;
        }
        $account = Account::findOne(['account_id' => $accountId]);
        $account->update(['details' => $accountDetails])->save();
    }
}
