<?php

namespace BitApps\SocialPro\HTTP\Services\Social\PinterestService;

use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Common;
use BitApps\SocialPro\Deps\BitApps\WPKit\Helpers\Arr;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Response;

class PinterestOAuth2Service
{
    use Common, LoggerTrait;

    private $httpHandler;

    private $apiBaseUrl = 'https://api.pinterest.com/';

    private $version = 'v5/';

    public function __construct()
    {
        $this->httpHandler = new HttpClient();
    }

    public function authHandler($request)
    {
        $body = $request->body();
        $client_id = $body['payload']['client_id'];
        $client_secret = $body['payload']['client_secret'];
        $redirect_uri = $body['payload']['redirect_uri'];
        $code = $body['payload']['code'];

        $tokenInfo = $this->getAccessToken($client_id, $client_secret, $redirect_uri, $code);

        if (property_exists($tokenInfo, 'error') && $tokenInfo->error) {
            return Response::error($tokenInfo->error);
        }

        if (!\is_object($tokenInfo) && !isset($tokenInfo->access_token)) {
            return (object) ['status' => 'error', 'message' => 'Access token is not valid, please authorize again!'];
        }
        $accessToken = $tokenInfo->access_token;

        $tokenDetail = Helper::organizeToken($client_id, $client_secret, $tokenInfo);

        $userAccounts = $this->getUserAccount($accessToken);

        $getBoards = $this->getBoards($accessToken, $userAccounts->id);

        return $this->organizeData($getBoards, $tokenDetail);
    }

    public function getAccessToken($client_id, $client_secret, $redirect_uri, $code)
    {
        $accessTokenUrl = $this->apiBaseUrl . $this->version . 'oauth/token';

        $header = [
            'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ];

        $params = [
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => $redirect_uri
        ];

        return $this->httpHandler->request($accessTokenUrl, 'POST', $params, $header);
    }

    public function getUserAccount($accessToken)
    {
        $header = [
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $userAccountUrl = $this->apiBaseUrl . $this->version . 'user_account';

        return $this->httpHandler->request($userAccountUrl, 'GET', null, $header);
    }

    public function getBoards($accessToken, $parentId)
    {
        $header = [
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $userBoardUrl = $this->apiBaseUrl . $this->version . 'boards';

        $params = [
            'page_size' => 250,
            'bookmark'  => null
        ];

        $userBoardDetails = $this->httpHandler->request($userBoardUrl, 'GET', $params, $header);
        if (! empty($userBoardDetails->items)) {
            foreach ($userBoardDetails->items as $item) {
                $board = [
                    'account_id'   => $item->id,
                    'account_type' => 'board',
                    'platform'     => 'pinterest',
                    'account_name' => $item->name,
                    'parent_id'    => $parentId,
                    'icon'         => $item->media->image_cover_url
                ];

                $boards[] = $board;
            }
        }

        return $boards;
    }

    public function organizeData($boards, $tokenDetail)
    {
        foreach ($boards as $board) {
            $allAccounts[] = array_merge($board, $tokenDetail);
        }

        $arr = new Arr();
        $accountIds = $arr->pluck($allAccounts, 'account_id');

        $existAccountIds = Account::whereIn('account_id', $accountIds)->get('account_id');

        if (!\is_array($existAccountIds)) {
            $existAccountIds = [];
        }

        $existAccountIds = $arr->pluck($existAccountIds, 'account_id');

        if (\is_array($allAccounts)) {
            foreach ($allAccounts as $account) {
                $isConnected = \in_array($account['account_id'], $existAccountIds);

                $data['profile_id'] = $account['parent_id'];
                $data['account_id'] = $account['account_id'];
                $data['account_name'] = $account['account_name'];
                $data['details'] = json_encode($account);
                $data['platform'] = 'pinterest';
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

    public function saveAccount($allAccount)
    {
        if (empty($allAccount)) {
            return Response::error('Something went wrong');
        }

        foreach ($allAccount as $index => $account) {
            $accountExist = Account::where('account_id', $account['account_id'])->count();
            if ($accountExist) {
                unset($allAccount[$index]);
            }
        }

        if (empty($allAccount)) {
            return Response::error('Account already exist');
        }
        $insertResponse = Account::insert($allAccount);
        if ($insertResponse) {
            return Response::success('Account added successfully');
        }

        return Response::error('Something went wrong');
    }
}
