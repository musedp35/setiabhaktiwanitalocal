<?php

namespace BitApps\SocialPro\HTTP\Services\Social\TumblrService;

use AllowDynamicProperties;

use BitApps\Social\HTTP\Services\Interfaces\AuthServiceInterface;
use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use BitApps\SocialPro\HTTP\Services\Traits\TumblrOAuthHelperTrait;

#[AllowDynamicProperties]
class TumblrOAuth2Service implements AuthServiceInterface
{
    use TumblrOAuthHelperTrait;

    public const USER_INFO_URL = 'https://api.tumblr.com/v2/user/info';

    public const REQUEST_TOKEN_URL = 'https://www.tumblr.com/oauth/request_token';

    public const ACCESS_TOKEN_URL = 'https://www.tumblr.com/oauth/access_token';

    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new HttpClient();
    }

    public function authHandler($request)
    {
        $body = $request->body();
        $appKey = $body['payload']['client_id'];
        $appSecret = $body['payload']['client_secret'];
        $redirect_uri = $body['payload']['redirect_uri'];
        $code = $body['payload']['code'];

        $tokenInfo = $this->getAccessToken($appKey, $appSecret, $redirect_uri, $code);
        $accessToken = $tokenInfo->access_token;
        $refreshToken = $tokenInfo->refresh_token;
        $tokenExpiresIn = $tokenInfo->expires_in;

        $userInfo = $this->getUserInformation($accessToken);

        return $this->formatUserData($appKey, $appSecret, $accessToken, $refreshToken, $tokenExpiresIn, $userInfo);
    }

    public function getAccessToken($client_id, $client_secret, $redirect_uri, $code)
    {
        $accessTokenUrl = 'https://api.tumblr.com/v2/oauth2/token';

        $header = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $params = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri
        ];

        return $this->httpClient->request($accessTokenUrl, 'POST', $params, $header);
    }

    public function getUserInformation($accessToken)
    {
        $header = [
            'Authorization' => 'Bearer ' . $accessToken
        ];

        $userInfo = $this->httpClient->request(static::USER_INFO_URL, 'GET', null, $header);

        $userId = $userInfo->response->user->blogs[0]->uuid;
        $userName = $userInfo->response->user->name;

        if (!$userId && ! $userName) {
            return (object) ['status' => 'error', 'message' => 'User information request failed!'];
        }

        return $userInfo;
    }

    private function formatUserData($appKey, $appSecret, $accessToken, $refreshToken, $tokenExpiresIn, $userInfo)
    {
        $userId = $userInfo->response->user->blogs[0]->uuid;
        $userName = $userInfo->response->user->name;
        $profileName = $userName;
        $profilePictureUrl = $userInfo->response->user->blogs[0]->avatar[0]->url;

        $accountDetails = [
            'profile_id'     => $userId,
            'account_id'     => $userId,
            'account_name'   => $userName,
            'account_type'   => 'profile',
            'user_name'      => $userName,
            'platform'       => 'tumblr',
            'icon'           => $profilePictureUrl,
            'client_id'      => Hash::encrypt($appKey),
            'client_secret'  => Hash::encrypt($appSecret),
            'access_token'   => Hash::encrypt($accessToken),
            'refresh_token'  => Hash::encrypt($refreshToken),
            'tokenExpiresIn' => $tokenExpiresIn,
            'generates_on'   => time(),
            'api_version'    => '2',
        ];

        $userData['profile_id'] = $userId;
        $userData['account_id'] = $userId;
        $userData['account_name'] = $profileName;
        $userData['details'] = json_encode($accountDetails);
        $userData['platform'] = 'tumblr';
        $userData['account_type'] = Account::accountType['CUSTOM'];
        $userData['status'] = Account::ACCOUNT_STATUS['active'];

        $totalAccount = Account::where('account_id', $userId)->count();
        $isConnected = true;

        if (!$totalAccount || $totalAccount === 0) {
            $isConnected = false;
        }

        $userFormattedData[] = ['account' => $userData, 'isConnected' => $isConnected];

        return $userFormattedData;
    }

    private function explodeResponse($separator, $string)
    {
        return explode($separator, $string);
    }
}
