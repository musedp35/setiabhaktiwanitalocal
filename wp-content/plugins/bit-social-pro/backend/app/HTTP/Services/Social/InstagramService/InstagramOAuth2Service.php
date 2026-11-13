<?php

namespace BitApps\SocialPro\HTTP\Services\Social\InstagramService;

use BitApps\Social\Deps\BitApps\WPKit\Helpers\Arr;
use BitApps\Social\Deps\BitApps\WPKit\Http\Client\HttpClient;
use BitApps\Social\Deps\BitApps\WPKit\Http\Response;
use BitApps\Social\HTTP\Services\Interfaces\AuthServiceInterface;
use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Common;

class InstagramOAuth2Service implements AuthServiceInterface
{
    use Common, LoggerTrait;

    private $httpHandler;

    private $baseUrl = 'https://graph.facebook.com/';

    private $version = 'v21.0';

    private $shortAccessTokenUrl;

    private $longTimeAccessTokenUrl;

    private $userAccountInfoUrl;

    private $getAccountsUrl;

    private $getAllGroupUrl;

    public function __construct()
    {
        $this->httpHandler = new HttpClient();
        $this->shortAccessTokenUrl = $this->baseUrl . $this->version . '/oauth/access_token?';
        $this->longTimeAccessTokenUrl = $this->baseUrl . $this->version . '/oauth/access_token?';
        $this->userAccountInfoUrl = $this->baseUrl . 'me?';
        $this->getAccountsUrl = $this->baseUrl . 'me/accounts?';
    }

    // TODO: encode only query params value. should  refactor
    public function urlParamEncode($url)
    {
        // Parse the URL to get the query string
        $queryString = parse_url($url, PHP_URL_QUERY);

        // Separate the query string into key-value pairs
        parse_str($queryString, $params);

        if (isset($params['rest_route'])) {
            // Encode each value in the parameters array
            foreach ($params as $key => $value) {
                $params[$key] = urlencode($value);
            }

            // Rebuild the query string

            $newQueryString = 'rest_route=' . $params['rest_route'];

            // Reconstruct the URL with the encoded query string
            return str_replace($queryString, $newQueryString, $url);
        }

        return $url;
    }

    public function authHandler($request)
    {
        $body = $request->body();
        $app_id = $body['payload']['client_id'];
        $client_secret = $body['payload']['client_secret'];
        $redirect_uri = $body['payload']['redirect_uri'];
        $code = $body['payload']['code'];

        $shortAccessTokenParams = [
            'client_id'     => $app_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $this->urlParamEncode($redirect_uri),
            'code'          => $code,
        ];

        $shortAccessTokenUrlWithParams = $this->shortAccessTokenUrl . http_build_query($shortAccessTokenParams);

        $shortAccessTokenResponse = $this->httpHandler->request($shortAccessTokenUrlWithParams, 'GET', []);

        $longTimeAccessTokenParams = [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $app_id,
            'client_secret'     => $client_secret,
            'fb_exchange_token' => $shortAccessTokenResponse->access_token,
            'redirect_uri'      => $this->urlParamEncode($redirect_uri),
        ];

        $longTimeAccessTokenUrlWithParams = $this->longTimeAccessTokenUrl . http_build_query($longTimeAccessTokenParams);

        $longTimeAccessTokenData = $this->httpHandler->request($longTimeAccessTokenUrlWithParams, 'GET', []);
        $longTimeAccessToken = $longTimeAccessTokenData->access_token;
        $appsecret_proof = hash_hmac(
            'sha256',
            $longTimeAccessToken,
            $client_secret
        );

        $accountsPages = $this->accountsPages($longTimeAccessToken, $appsecret_proof);

        $instagramProfiles = $this->pageToInstagramBusiness($accountsPages->data);

        if (\is_array($instagramProfiles)) {
            $allAccount = [];
            $arr = new Arr();

            $accountIds = $arr->pluck($instagramProfiles, 'account_id');
            $existAccountIds = Account::whereIn('account_id', $accountIds)->get('account_id');

            if (!\is_array($existAccountIds)) {
                $existAccountIds = [];
            }

            $existAccountIds = $arr->pluck($existAccountIds, 'account_id');

            foreach ($instagramProfiles as $item) {
                $isConnected = \in_array($item['account_id'], $existAccountIds);

                $data['profile_id'] = $item['account_id'];
                $data['account_id'] = $item['account_id'];
                $data['account_name'] = $item['account_name'];
                $data['details'] = json_encode($item);
                $data['platform'] = 'instagram';
                $data['account_type'] = Account::accountType['DEFAULT'];
                $data['status'] = Account::ACCOUNT_STATUS['active'];
                $allAccount[] = ['account' => $data, 'isConnected' => $isConnected];
            }
        }
        if (empty($allAccount)) {
            return Response::error('Something went wrong');
        }

        return $allAccount;
    }

    public function accountsPages($longTimeAccessToken, $appsecret_proof)
    {
        $accountQuery = [
            'fields'          => 'id,name,access_token,instagram_business_account',
            'access_token'    => $longTimeAccessToken,
            'appsecret_proof' => $appsecret_proof
        ];

        $getAccountsUrlWithParams = $this->getAccountsUrl . http_build_query($accountQuery);

        return $this->httpHandler->request($getAccountsUrlWithParams, 'GET', []);
    }

    public function pageToInstagramBusiness($pages)
    {
        $instagramProfiles = [];
        foreach ($pages as $page) {
            if (isset($page->instagram_business_account->id)) {
                $profileId = $page->instagram_business_account->id;
                $accessToken = $page->access_token;
                $instagramProfileInfo = $this->fetchInstagramBusinessInfo($profileId, $accessToken);
                $instagramProfiles[] = $this->organizeInstagramProfile($instagramProfileInfo, $profileId, $accessToken);
            }
        }

        return $instagramProfiles;
    }

    public function fetchInstagramBusinessInfo($profileId, $accessToken)
    {
        $params = [
            'fields'       => 'name,username,profile_picture_url',
            'access_token' => $accessToken
        ];

        $pagePicUrl = $this->baseUrl . "{$profileId}?";
        $pagePicUrlWithParams = $pagePicUrl . http_build_query($params);

        return $this->httpHandler->request($pagePicUrlWithParams, 'GET', []);
    }

    public function organizeInstagramProfile($profile, $profileId, $accessToken)
    {
        return [
            'user_name'    => $profile->username,
            'account_id'   => $profileId,
            'account_name' => $profile->name,
            'account_type' => 'profile',
            'category'     => '',
            'icon'         => $profile->profile_picture_url,
            'access_token' => $accessToken,
            'generates_on' => time()
        ];
    }
}
