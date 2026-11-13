<?php

namespace BitApps\SocialPro\HTTP\Services\Social\GoogleBusinessProfileService;

use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

class GoogleBusinessProfileRefreshTokenService
{
    private $httpHandler;

    private $authBaseUrl = 'https://oauth2.googleapis.com/';

    private $accountId;

    private $accountDetails;

    private $clientId;

    private $clientSecret;

    private $redirectUri;

    private $accessToken;

    private $refreshToken;

    private $generateOn;

    public function __construct($accountDetails)
    {
        $this->httpHandler = new HttpClient();

        $this->accountDetails = $accountDetails;
        $this->accountId = $accountDetails->account_id;

        $this->clientId = Hash::decrypt($accountDetails->client_id);
        $this->clientSecret = Hash::decrypt($accountDetails->client_secret);
        $this->redirectUri = Hash::decrypt($accountDetails->redirect_uri);
        $this->accessToken = Hash::decrypt($accountDetails->access_token);
        $this->refreshToken = Hash::decrypt($accountDetails->refresh_token);
        $this->generateOn = $accountDetails->generates_on;
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
        $accessTokenUrl = $this->authBaseUrl . 'token?';

        $params = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'refresh_token' => $this->refreshToken
        ];

        $accessTokenUrlWithParams = $accessTokenUrl . http_build_query($params);

        $result = $this->httpHandler->request($accessTokenUrlWithParams, 'POST', []);

        if (isset($result->access_token)) {
            $this->accessToken = $result->access_token;
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
        $accountDetails->generates_on = time();
        $account->update(['details' => $accountDetails])->save();
    }
}
