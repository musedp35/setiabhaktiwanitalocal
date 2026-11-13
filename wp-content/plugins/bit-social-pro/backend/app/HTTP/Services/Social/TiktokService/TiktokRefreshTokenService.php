<?php

namespace BitApps\SocialPro\HTTP\Services\Social\TiktokService;

use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

class TiktokRefreshTokenService
{
    private $httpHandler;

    private $baseUrl = 'https://open.tiktokapis.com';

    private $version = 'v2';

    private $accountId;

    private $accountDetails;

    private $clientId;

    private $clientSecret;

    private $redirectUri;

    private $accessToken;

    private $refreshToken;

    private $tokenExpiresIn;

    private $generateOn;

    public function __construct($accountDetails)
    {
        $this->httpHandler = new HttpClient();

        $this->accountDetails = $accountDetails;
        $this->accountId = $accountDetails->account_id;

        $this->clientId = $accountDetails->client_id;
        $this->clientSecret = Hash::decrypt($accountDetails->client_secret);
        $this->redirectUri = $accountDetails->redirect_uri;
        $this->accessToken = Hash::decrypt($accountDetails->access_token);
        $this->refreshToken = Hash::decrypt($accountDetails->refresh_token);
        $this->generateOn = $accountDetails->expires_in;
    }

    public function tokenExpiryCheck()
    {
        if (!$this->accessToken && !$this->refreshToken) {
            return false;
        }

        if ((\intval($this->generateOn)) < time()) {
            return $this->refreshAccessToken();
        }

        return $this->accessToken;
    }

    public function accessToken()
    {
        return $this->tokenExpiryCheck();
    }

    public function refreshAccessToken()
    {
        $accessTokenUrl = $this->baseUrl . 'token?';
        $accessTokenUrl = "{$this->baseUrl}/{$this->version}/oauth/token/";

        $header = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $bodyParams = [
            'grant_type'    => 'refresh_token',
            'client_key'    => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken
        ];

        $result = $this->httpHandler->request($accessTokenUrl, 'POST', $bodyParams, $header);

        if (isset($result->access_token)) {
            $this->accessToken = $result->access_token;
            $this->tokenExpiresIn = time() + $result->expires_in;
            $this->saveAccessToken();
        }

        return $this->accessToken;
    }

    public function saveAccessToken()
    {
        if (empty($this->accountId)) {
            return;
        }
        $account = Account::findOne(['account_id' => $this->accountId]);

        $accountDetails = $this->accountDetails;
        $accountDetails->access_token = Hash::encrypt($this->accessToken);
        $accountDetails->expires_in = $this->tokenExpiresIn;
        $account->update(['details' => $accountDetails])->save();
    }
}
