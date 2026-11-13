<?php

namespace BitApps\SocialPro\HTTP\Services\Social\TumblrService;

use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

class TumblrRefreshTokenService
{
    private $httpHandler;

    private $authBaseUrl = 'https://api.tumblr.com/v2/oauth2';

    private $accountId;

    private $accountDetails;

    private $clientId;

    private $clientSecret;

    private $accessToken;

    private $refreshToken;

    private $generateOn;

    private $tokenExpiresIn;

    public function __construct($accountDetails)
    {
        $this->httpHandler = new HttpClient();

        $this->accountDetails = $accountDetails;
        $this->accountId = $accountDetails->account_id;

        $this->clientId = Hash::decrypt($accountDetails->client_id);
        $this->clientSecret = Hash::decrypt($accountDetails->client_secret);
        $this->accessToken = Hash::decrypt($accountDetails->access_token);
        $this->accessToken = Hash::decrypt($accountDetails->access_token);
        $this->refreshToken = Hash::decrypt($accountDetails->refresh_token);
        $this->generateOn = $accountDetails->generates_on;
        $this->tokenExpiresIn = $accountDetails->tokenExpiresIn;
    }

    public function tokenExpiryCheck()
    {
        if (!$this->accessToken && !$this->refreshToken) {
            return false;
        }

        if ((\intval($this->generateOn) + $this->tokenExpiresIn - 10) < time()) {
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
        $accessTokenUrl = $this->authBaseUrl . '/token';

        $params = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken
        ];

        $header = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $result = $this->httpHandler->request($accessTokenUrl, 'POST', $params, $header);

        if (isset($result->access_token)) {
            $this->accessToken = $result->access_token;
            $this->refreshToken = $result->refresh_token;
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
        $accountDetails->refresh_token = Hash::encrypt($this->refreshToken);
        $accountDetails->generates_on = time();
        $account->update(['details' => $accountDetails])->save();
    }
}
