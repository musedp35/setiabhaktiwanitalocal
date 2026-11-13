<?php

namespace BitApps\SocialPro\HTTP\Services\Social\TumblrService;

use AllowDynamicProperties;
use BitApps\Social\HTTP\Requests\AuthorizeRequest;
use BitApps\Social\HTTP\Services\Interfaces\AuthServiceInterface;
use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use BitApps\SocialPro\HTTP\Services\Traits\TumblrOAuthHelperTrait;

#[AllowDynamicProperties]
class TumblrOAuth1Service implements AuthServiceInterface
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

    public function requestToken(AuthorizeRequest $request)
    {
        $body = $request->all();

        $appKey = $body['payload']['client_id'];
        $appSecret = $body['payload']['client_secret'];

        $responseString = $this->tumblrOAuth(null, null, null, static::REQUEST_TOKEN_URL, [], 'POST', $appKey, $appSecret);

        if (property_exists($responseString, 'errors')) {
            return (object) ['status' => 'error', 'message' => 'Authentication failed, please check your app and try again!'];
        }

        $responseChunks = $this->explodeResponse('&', $responseString);

        $response = [];
        foreach ($responseChunks as $chunk) {
            $arr = $this->explodeResponse('=', $chunk);

            $response[$arr[0]] = $arr[1];
        }

        $oauthToken = $response['oauth_token'];
        if (!$oauthToken) {
            return (object) ['status' => 'error', 'message' => 'Authentication failed, please try again!'];
        }

        return ['response' => $response];
    }

    public function authHandler($request)
    {
        $body = $request->all();
        $appKey = $body['payload']['client_id'];
        $appSecret = $body['payload']['client_secret'];
        $oauthToken = $body['payload']['oauth_token'];
        $oauthSecret = $body['payload']['oauth_secret'];
        $oauthVerifier = $body['payload']['oauth_verifier'];

        $responseString = $this->tumblrOAuth($oauthToken, $oauthVerifier, $oauthSecret, static::ACCESS_TOKEN_URL, [], 'GET', $appKey, $appSecret);

        if (!strpos($responseString, 'oauth_token') && !strpos($responseString, 'oauth_token_secret')) {
            return (object) ['status' => 'error', 'message' => 'Request token and secret token missing!'];
        }

        $explodeResponse = $this->explodeResponse('&', $responseString);

        $resultArray = [];
        foreach ($explodeResponse as $item) {
            $arr = $this->explodeResponse('=', $item);

            $resultArray[$arr[0]] = $arr[1];
        }

        $accessToken = $resultArray['oauth_token'];
        $accessTokenSecret = $resultArray['oauth_token_secret'];

        return $this->getUserInformation($appKey, $appSecret, $accessToken, $accessTokenSecret);
    }

    public function getUserInformation($appKey, $appSecret, $accessToken, $accessTokenSecret)
    {
        $userDetails = $this->tumblrOAuth(null, null, null, static::USER_INFO_URL, [], 'GET', $appKey, $appSecret, $accessToken, $accessTokenSecret);

        $userId = $userDetails->response->user->blogs[0]->uuid;
        $userName = $userDetails->response->user->name;
        $profileName = $userName;
        $profilePictureUrl = $userDetails->response->user->blogs[0]->avatar[0]->url;

        if (!$userId && ! $userName) {
            return (object) ['status' => 'error', 'message' => 'User information request failed!'];
        }

        return $this->saveUserData($appKey, $appSecret, $accessToken, $accessTokenSecret, $userId, $profileName, $userName, $profilePictureUrl);
    }

    private function saveUserData($appKey, $appSecret, $accessToken, $accessTokenSecret, $userId, $profileName, $userName, $profilePictureUrl)
    {
        $accountDetails = [
            'profile_id'          => $userId,
            'account_id'          => $userId,
            'account_name'        => $userName,
            'account_type'        => 'profile',
            'user_name'           => $userName,
            'platform'            => 'tumblr',
            'icon'                => $profilePictureUrl,
            'client_id'           => Hash::encrypt($appKey),
            'client_secret'       => Hash::encrypt($appSecret),
            'access_token'        => Hash::encrypt($accessToken),
            'access_token_secret' => Hash::encrypt($accessTokenSecret),
            'api_version'         => '2',
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

        $accountData[] = ['account' => $userData, 'isConnected' => $isConnected];

        return $accountData;
    }

    private function explodeResponse($separator, $string)
    {
        return explode($separator, $string);
    }
}
