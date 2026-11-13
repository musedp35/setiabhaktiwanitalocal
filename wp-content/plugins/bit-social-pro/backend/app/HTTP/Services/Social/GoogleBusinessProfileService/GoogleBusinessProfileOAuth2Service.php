<?php

namespace BitApps\SocialPro\HTTP\Services\Social\GoogleBusinessProfileService;

use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Common;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Helpers\Arr;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Response;
use BitApps\SocialPro\HTTP\Requests\GoogleBusinessRequest;

class GoogleBusinessProfileOAuth2Service
{
    use Common, LoggerTrait;

    public const AUTH_BASE_URL = 'https://oauth2.googleapis.com/';

    public const CLIENT_SECRET_URL = 'https://auth-apps.bitapps.pro/apps/secret';

    public const BASE_URL = 'https://mybusinessaccountmanagement.googleapis.com/';

    public const VERSION = 'v1';

    private $httpHandler;

    public function __construct()
    {
        $this->httpHandler = new HttpClient();
    }

    public function authHandler($request)
    {
        $body = $request->body();
        $clientId = $body['payload']['client_id'];
        $clientSecret = $body['payload']['client_secret'] !== '' ? $body['payload']['client_secret'] : $this->getSecret('googleBusinessProfile', $clientId);
        $redirectUri = $body['payload']['redirect_uri'];
        $code = $body['payload']['code'];

        $tokenInfo = $this->getAccessToken($clientId, $clientSecret, $redirectUri, $code);

        $accessToken = $tokenInfo->access_token;
        $refreshToken = $tokenInfo->refresh_token;
        $accountName = $this->getAccountName($accessToken);
        $locations = $this->getLocations($accountName, $accessToken);

        if (!$locations) {
            return [];
        }

        $allAccount = [];
        $existAccountIds = [];
        $arr = new Arr();
        $accountIds = $arr->pluck($locations, 'name');

        if (\count($accountIds)) {
            $existAccountIds = Account::whereIn('account_id', $accountIds)->get('account_id');
        }

        $existAccountIds = $arr->pluck($existAccountIds, 'account_id');

        $verifyUrl = 'pro_google-business-profile/verify';

        foreach ($locations as $location) {
            $isConnected = \in_array($location->name, $existAccountIds);

            $data['profile_id'] = $accountName;
            $data['account_id'] = $location->name;
            $data['account_name'] = $location->title;
            $data['details'] = json_encode([
                'profile_id'    => $accountName,
                'account_id'    => $location->name,
                'account_name'  => $location->title,
                'account_type'  => $location->categories->primaryCategory->displayName,
                'icon'          => '',
                'client_id'     => Hash::encrypt($clientId),
                'client_secret' => Hash::encrypt($clientSecret),
                'redirect_uri'  => Hash::encrypt($redirectUri),
                'access_token'  => Hash::encrypt($accessToken),
                'refresh_token' => Hash::encrypt($refreshToken),
                'generates_on'  => time()
            ]);
            $data['platform'] = 'googleBusinessProfile';
            $data['account_type'] = Account::accountType['DEFAULT'];
            $data['status'] = Account::ACCOUNT_STATUS['active'];
            $allAccount[] = ['account' => $data, 'verifyUrl' => $verifyUrl, 'isConnected' => $isConnected];
        }

        if (empty($allAccount)) {
            return Response::error('Something went wrong');
        }

        return $allAccount;
    }

    public function getLocations($accountName, $accessToken)
    {
        $header = [
            'Authorization' => 'Bearer ' . $accessToken
        ];

        $baseUrl = static::BASE_URL . static::VERSION . '/' . $accountName . '/locations?readMask=';
        $readMask = 'title,name,categories.primaryCategory.displayName,storefrontAddress.addressLines';

        $allLocations = [];
        $nextPageToken = null;

        do {
            $url = $baseUrl . $readMask;
            if ($nextPageToken) {
                $url .= '&pageToken=' . urlencode($nextPageToken);
            }

            $response = $this->httpHandler->request($url, 'GET', [], $header);

            if (isset($response->locations)) {
                $allLocations = array_merge($allLocations, $response->locations);
            }

            $nextPageToken = $response->nextPageToken ?? null;
        } while ($nextPageToken);

        return $allLocations;
    }

    public function getAccountName($accessToken)
    {
        $header = [
            'Authorization' => 'Bearer ' . $accessToken
        ];
        $accountUrl = static::BASE_URL . static::VERSION . '/accounts';

        $accountInfo = $this->httpHandler->request($accountUrl, 'GET', [], $header);

        return $accountInfo->accounts[0]->name;
    }

    public function getAccessToken($clientId, $clientSecret, $redirectUri, $code)
    {
        $accessTokenUrl = static::AUTH_BASE_URL . 'token?';

        $params = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ];

        $accessTokenUrlWithParams = $accessTokenUrl . http_build_query($params);

        return $this->httpHandler->request($accessTokenUrlWithParams, 'POST', []);
    }

    public function getSecret($platform, $id)
    {
        $header = ['Content-Type' => 'application/x-www-form-urlencoded'];

        $body = [
            'platform'  => $platform,
            'client_id' => $id
        ];

        $result = $this->httpHandler->request(static::CLIENT_SECRET_URL, 'POST', json_encode($body), $header);

        return $result->clientSecret;
    }

    public function getLocationIcon($locationName, $accessToken)
    {
        $accountName = $this->getAccountName($accessToken);
        $header = [
            'Authorization' => 'Bearer ' . $accessToken
        ];

        $mediaUrl = static::BASE_URL . 'v4/' . $accountName . '/' . $locationName . '/media';
        $result = $this->httpHandler->request($mediaUrl, 'get', [], $header);
        $icon = $this->findProfileMediaUrls($result);

        return $icon;
    }

    public function findProfileMediaUrls($data)
    {
        $profileUrl = '';
        if (isset($data->mediaItems)) {
            foreach ($data->mediaItems as $media) {
                if (isset($media->locationAssociation->category) && $media->locationAssociation->category === 'PROFILE') {
                    $profileUrl = $media->googleUrl;
                }
            }
        }

        return $profileUrl;
    }

    public function verify(GoogleBusinessRequest $request)
    {
        $validatedData = (object) $request->validated();

        $accountId = $validatedData->account_id;
        $accountDetails = json_decode($validatedData->details);
        $accessToken = Hash::decrypt($accountDetails->access_token);
        $verificationsUrl = static::BASE_URL . static::VERSION . '/' . $accountId . '/verifications';

        $header = [
            'Authorization' => 'Bearer ' . $accessToken
        ];

        $result = $this->httpHandler->request($verificationsUrl, 'GET', [], $header);

        $verificationsComplete = $result->verifications[0]->state === 'COMPLETED';

        if ($verificationsComplete) {
            $icon = $this->getLocationIcon($accountId, $accessToken);
            $accountDetails->icon = $icon;
            $validatedData->details = json_encode($accountDetails);

            return Response::success(['message' => 'This location is verified. Only verified locations can create posts on Google.', 'accountDetails' => $validatedData]);
        }

        return Response::error(['message' => 'This location is not verified. Only verified locations can create posts on Google.']);
    }
}
