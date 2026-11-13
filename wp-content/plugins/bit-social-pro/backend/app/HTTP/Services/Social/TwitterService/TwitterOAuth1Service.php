<?php

namespace BitApps\SocialPro\HTTP\Services\Social\TwitterService;

use BitApps\Social\HTTP\Requests\AuthorizeRequest;
use BitApps\Social\HTTP\Services\Interfaces\AuthServiceInterface;
use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use BitApps\SocialPro\HTTP\Services\Traits\TwitterOAuthHelperTrait;

class TwitterOAuth1Service implements AuthServiceInterface
{
    use TwitterOAuthHelperTrait;

    public const USER_INFO_URL = 'https://api.twitter.com/1.1/account/verify_credentials.json';

    public const REQUEST_TOKEN_URL = 'https://api.twitter.com/oauth/request_token';

    public const ACCESS_TOKEN_URL = 'https://api.twitter.com/oauth/access_token';

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
        $callbackUrl = $body['payload']['redirect_uri'];

        $responseString = $this->twitterOAuth(static::REQUEST_TOKEN_URL, [], 'POST', $appKey, $appSecret, null, null, null, null, null, $callbackUrl);

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
        $oauthVerifier = $body['payload']['oauth_verifier'];

        $params = [
            'oauth_token'    => $oauthToken,
            'oauth_verifier' => $oauthVerifier
        ];

        $queryParam = static::ACCESS_TOKEN_URL . '?' . http_build_query($params);

        $responseString = $this->httpClient->request($queryParam, 'POST', []);

        if (!strpos($responseString, 'oauth_token') && !strpos($responseString, 'oauth_token_secret') && !strpos($responseString, 'screen_name') && !strpos($responseString, 'user_id')) {
            return (object) ['status' => 'error', 'message' => 'Request token and secret token missing!'];
        }

        $explodeResponse = $this->explodeResponse('&', $responseString);

        $resultArray = [];
        foreach ($explodeResponse as $item) {
            $arr = $this->explodeResponse('=', $item);

            $resultArray[$arr[0]] = $arr[1];
        }

        $userId = $resultArray['user_id'];
        $screenName = $resultArray['screen_name'];
        $accessToken = $resultArray['oauth_token'];
        $accessTokenSecret = $resultArray['oauth_token_secret'];

        return $this->getUserInformation($appKey, $appSecret, $accessToken, $accessTokenSecret, $screenName);
    }

    public function getUserInformation($appKey, $appSecret, $accessToken, $accessTokenSecret, $screenName)
    {
        $userDetails = $this->twitterOAuth(static::USER_INFO_URL, [], 'GET', $appKey, $appSecret, $accessToken, $accessTokenSecret, $screenName);

        $userId = $userDetails->id_str;
        $userName = $userDetails->screen_name;
        $profileName = $userDetails->name;
        $profilePictureUrl = $userDetails->profile_image_url_https;
        $verified = $userDetails->verified;

        if (!$userId && ! $userName) {
            return (object) ['status' => 'error', 'message' => 'User information request failed!'];
        }

        return $this->saveUserData($appKey, $appSecret, $accessToken, $accessTokenSecret, $userId, $profileName, $userName, $profilePictureUrl, $verified);
    }

    private function saveUserData($appKey, $appSecret, $accessToken, $accessTokenSecret, $userId, $profileName, $userName, $profilePictureUrl, $verified)
    {
        $accountDetails = [
            'profile_id'          => $userId,
            'account_id'          => $userId,
            'account_name'        => $profileName,
            'account_type'        => 'profile',
            'verified'            => $verified,
            'user_name'           => $userName,
            'platform'            => 'twitter',
            'icon'                => $profilePictureUrl,
            'client_id'           => Hash::encrypt($appKey),
            'client_secret'       => Hash::encrypt($appSecret),
            'access_token'        => Hash::encrypt($accessToken),
            'access_token_secret' => Hash::encrypt($accessTokenSecret),
            'api_version'         => '1.1',
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

    private function explodeResponse($separator, $string)
    {
        return explode($separator, $string);
    }
}
