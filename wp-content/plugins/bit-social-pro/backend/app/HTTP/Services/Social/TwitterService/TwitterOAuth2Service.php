<?php

namespace BitApps\SocialPro\HTTP\Services\Social\TwitterService;

use BitApps\Social\HTTP\Services\Interfaces\AuthServiceInterface;
use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

class TwitterOAuth2Service implements AuthServiceInterface
{
    public const AUTH_URL = 'https://api.twitter.com/2/oauth2/token';

    public const USER_INFO_URL = 'https://api.twitter.com/2/users/me?user.fields=profile_image_url,verified';

    private $httpHandler;

    public function __construct()
    {
        $this->httpHandler = new HttpClient();
    }

    public function authHandler($request)
    {
        $body = $request->all();
        $clientId = $body['payload']['client_id'];
        $clientSecret = $body['payload']['client_secret'];
        $redirectUri = $body['payload']['redirect_uri'];
        $code = $body['payload']['code'];
        $codeVerifier = $body['payload']['code_verifier'];

        $authHeader = base64_encode($clientId . ':' . $clientSecret);

        $headers = [
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Authorization' => "Basic {$authHeader}"
        ];

        $data = [
            'client_id'     => $clientId,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'code_verifier' => $codeVerifier,
            'grant_type'    => 'authorization_code',
        ];

        $authResponse = $this->httpHandler->request(static::AUTH_URL, 'POST', $data, $headers);

        if (property_exists($authResponse, 'error')) {
            return (object) ['status' => 'error', 'message' => $authResponse->error . ': Please check your credentials and try again'];
        }

        $accessToken = $authResponse->access_token;
        $refreshToken = $authResponse->refresh_token;
        $expiresIn = $authResponse->expires_in;

        return $this->getUserInfo($accessToken, $refreshToken, $expiresIn, $clientId, $clientSecret);
    }

    private function getUserInfo($accessToken, $refreshToken, $expiresIn, $clientId, $clientSecret)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken
        ];

        $userInfoResponse = $this->httpHandler->request(static::USER_INFO_URL, 'GET', [], $headers);

        if (isset($userInfoResponse->status) && $userInfoResponse->status === 429) {
            return (object) ['status' => 'error', 'message' => 'Too Many Requests'];
        }
        if (property_exists($userInfoResponse, 'error') || !property_exists($userInfoResponse, 'data')) {
            return (object) ['status' => 'error', 'message' => 'User data not found, please try again!'];
        }

        return $this->saveUserData($accessToken, $refreshToken, $userInfoResponse, $expiresIn, $clientId, $clientSecret);
    }

    private function saveUserData($accessToken, $refreshToken, $userInfo, $expiresIn, $clientId, $clientSecret)
    {
        $userId = $userInfo->data->id;
        $userName = $userInfo->data->username;
        $profileName = $userInfo->data->name;
        $verified = $userInfo->data->verified;

        if (!$userId) {
            return (object) ['status' => 'error', 'message' => 'User id not found!'];
        }

        $accountDetails = [
            'profile_id'   => $userId,
            'account_id'   => $userId,
            'account_name' => $profileName,
            'account_type' => 'profile',
            'verified'     => $verified,

            'user_name'     => $userName,
            'platform'      => 'twitter',
            'icon'          => $userInfo->data->profile_image_url,
            'client_id'     => Hash::encrypt($clientId),
            'client_secret' => Hash::encrypt($clientSecret),
            'access_token'  => Hash::encrypt($accessToken),
            'refresh_token' => Hash::encrypt($refreshToken),
            'expires_in'    => time() + $expiresIn,
            'api_version'   => '2'
        ];

        $userData['profile_id'] = $userId;
        $userData['account_id'] = $userId;
        $userData['account_name'] = $profileName;
        $userData['details'] = json_encode($accountDetails);
        $userData['platform'] = 'twitter';
        $userData['account_type'] = Account::accountType['CUSTOM'];
        $userData['status'] = Account::ACCOUNT_STATUS['active'];

        $totalAccount = Account::where('account_id', $userId)->count();
        $isConnected = true;

        if (!$totalAccount || $totalAccount === 0) {
            $isConnected = false;
        }

        $accountData[] = ['account' => $userData, 'isConnected' => $isConnected];

        return $accountData;
    }
}
