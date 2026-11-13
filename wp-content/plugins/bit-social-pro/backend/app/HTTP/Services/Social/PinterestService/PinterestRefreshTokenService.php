<?php

namespace BitApps\SocialPro\HTTP\Services\Social\PinterestService;

use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Common;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use stdClass;

class PinterestRefreshTokenService
{
    use Common, LoggerTrait;

    private $httpHandler;

    private $baseUrl = 'https://api.pinterest.com/';

    private $version = 'v5/';

    private $refreshTokenUrl;

    public function __construct()
    {
        $this->httpHandler = new HttpClient();
        $this->refreshTokenUrl = $this->baseUrl . $this->version . 'oauth/token';
    }

    public function tokenExpiryCheck($client_id, $client_secret, $access_token, $expires_in, $refresh_token, $refresh_token_expires_in)
    {
        if (!$access_token && !$refresh_token) {
            return false;
        }

        if ((\intval($expires_in)) < time()) {
            return $this->refreshToken($client_id, $client_secret, $access_token, $refresh_token);
        }

        $data = new stdClass();
        $data->access_token = $access_token;
        $data->expires_in = $expires_in;
        $data->refresh_token = $refresh_token;
        $data->refresh_token_expires_in = $refresh_token_expires_in;

        return $data;
    }

    public function refreshToken($client_id, $client_secret, $access_token, $refresh_token)
    {
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ];

        $params = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh_token
        ];

        $tokenResponse = $this->httpHandler->request($this->refreshTokenUrl, 'POST', $params, $headers);

        if (!property_exists($tokenResponse, 'access_token')) {
            return (object) ['status' => 'error', 'message' => 'Refresh token request failed!'];
        }

        $data = new stdClass();
        $data->access_token = $tokenResponse->access_token;
        $data->expires_in = time() + $tokenResponse->expires_in;

        return $data;
    }

    public function saveRefreshedToken($account_detail)
    {
        $accountId = $account_detail->account_id;
        if (empty($accountId)) {
            return;
        }
        $account = Account::findOne(['account_id' => $accountId]);
        $account->update(['details' => $account_detail]);
    }
}
