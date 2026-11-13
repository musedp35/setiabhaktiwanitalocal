<?php

namespace BitApps\SocialPro\HTTP\Services\Social\TiktokService;

use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Common;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Response;

class TiktokOAuth2Service
{
    use Common, LoggerTrait;

    private $httpClient;

    private $baseUrl = 'https://open.tiktokapis.com';

    private $version = 'v2';

    private $accessToken;

    private $clientId;

    private $clientSecret;

    private $redirectUri;

    public function __construct()
    {
        $this->httpClient = new HttpClient();
    }

    public function authHandler($request)
    {
        $body = $request->body();
        $clientId = $body['payload']['client_id'];
        $clientSecret = $body['payload']['client_secret'];
        $redirectUri = $body['payload']['redirect_uri'];
        $code = $body['payload']['code'];

        $tokenInfo = $this->getAccessToken($clientId, $clientSecret, $redirectUri, $code);

        if (property_exists($tokenInfo, 'error') && $tokenInfo->error) {
            return Response::error($tokenInfo->error);
        }

        if (!\is_object($tokenInfo) && !isset($tokenInfo->access_token)) {
            return (object) ['status' => 'error', 'message' => 'Access token is not valid, please authorize again!'];
        }

        $this->accessToken = $tokenInfo->access_token;

        $userInfo = $this->getUserInfo();

        if (isset($userInfo->error->message) && !empty($userInfo->error->message)) {
            return (object) ['status' => 'error', 'message' => $userInfo->error->message];
        }

        return $this->userData($userInfo, $clientId, $clientSecret, $redirectUri, $tokenInfo);
    }

    public function getAccessToken($clientId, $clientSecret, $redirectUri, $code)
    {
        $accessTokenUrl = "{$this->baseUrl}/{$this->version}/oauth/token/";

        $header = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $bodyParams = [
            'client_key'    => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirectUri
        ];

        return $this->httpClient->request($accessTokenUrl, 'POST', $bodyParams, $header);
    }

    public function getUserInfo()
    {
        $header = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $userInfoUrl = "{$this->baseUrl}/{$this->version}/user/info/?";

        $params = [
            'fields' => 'open_id,union_id,avatar_url_100,display_name,username,'
        ];

        $userInfoUrlWithParams = $userInfoUrl . http_build_query($params);

        return $this->httpClient->request($userInfoUrlWithParams, 'GET', null, $header);
    }

    public function userData($userInfo, $clientId, $clientSecret, $redirectUri, $tokenDetails)
    {
        $user = $userInfo->data->user;
        $unionId = $user->union_id;

        $accountDetails = [
            'profile_id'         => $unionId,
            'account_id'         => $unionId,
            'account_name'       => $user->display_name,
            'account_type'       => 'profile',
            'user_name'          => $user->username,
            'platform'           => 'tiktok',
            'icon'               => $user->avatar_url_100,
            'client_id'          => $clientId,
            'client_secret'      => Hash::encrypt($clientSecret),
            'redirect_uri'       => $redirectUri,
            'access_token'       => Hash::encrypt($tokenDetails->access_token),
            'refresh_token'      => Hash::encrypt($tokenDetails->refresh_token),
            'expires_in'         => time() + $tokenDetails->expires_in,
            'refresh_expires_in' => time() + $tokenDetails->refresh_expires_in,
        ];

        $account['profile_id'] = $unionId;
        $account['account_id'] = $unionId;
        $account['account_name'] = $user->display_name;
        $account['details'] = json_encode($accountDetails);
        $account['platform'] = 'tiktok';
        $account['account_type'] = Account::accountType['DEFAULT'];
        $account['status'] = Account::ACCOUNT_STATUS['active'];

        $totalAccount = Account::where('account_id', $unionId)->count();
        $isConnected = true;

        if (!$totalAccount || $totalAccount === 0) {
            $isConnected = false;
        }

        $accountData[] = ['account' => $account, 'isConnected' => $isConnected];

        return $accountData;
    }

    public function saveAccount($account)
    {
        if (empty($account)) {
            return Response::error('Something went wrong');
        }

        $accountExist = Account::where('account_id', $account['account_id'])->count();

        if ($accountExist) {
            return Response::success('Account already exist');
        }

        $insertResponse = Account::insert($account);
        if ($insertResponse) {
            return Response::success('Account added successfully');
        }

        return Response::error('Something went wrong');
    }
}
