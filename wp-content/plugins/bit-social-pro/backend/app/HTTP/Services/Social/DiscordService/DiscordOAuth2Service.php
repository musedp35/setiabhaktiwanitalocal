<?php

namespace BitApps\SocialPro\HTTP\Services\Social\DiscordService;

use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Account;
use BitApps\Social\Utils\Common;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Helpers\Arr;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Response;

class DiscordOAuth2Service
{
    use Common, LoggerTrait;

    private $httpHandler;

    private $apiBaseUrl = 'https://discord.com/api/';

    private $version = 'v5/';

    public function __construct()
    {
        $this->httpHandler = new HttpClient();
    }

    public static function extractCredentials($details)
    {
        $credentials = Hash::decrypt($details);

        $credentialsData = json_decode($credentials);

        return [
            'appKey'      => $credentialsData->appKey,
            'appSecret'   => $credentialsData->appSecret,
            'redirectUri' => $credentialsData->redirectUri,
            'icon_url'    => isset($credentialsData->icon_url) ? $credentialsData->icon_url : '',
            'apiVersion'  => $credentialsData->apiVersion
        ];
    }

    public function authHandler($request)
    {
        $body = $request->body();
        $botToken = $body['payload']['client_secret'];
        $code = $body['payload']['code'];
        $guild_id = $body['payload']['guild_id'];

        if (!$code) {
            return (object) ['status' => 'error', 'message' => 'Authorization failed, please try again!'];
        }

        $discordServer = $this->getServer($guild_id, $botToken);

        $allChannels = $this->getChannel($discordServer, $botToken);

        return $this->organizeData($allChannels, ['botToken' => $botToken]);
    }

    public function getServer($guild_id, $botToken)
    {
        $getServerUrl = $this->apiBaseUrl . 'guilds/' . $guild_id;

        $header = ['Authorization' => 'Bot ' . $botToken];

        return $this->httpHandler->request($getServerUrl, 'GET', null, $header);
    }

    public function getChannel($discordServer, $botToken)
    {
        $serverId = $discordServer->id;
        $iconCode = $discordServer->icon;

        $getChannelUrl = $this->apiBaseUrl . "guilds/{$serverId}/channels";
        $header = ['Authorization' => 'Bot ' . $botToken];

        $server_icon_url = "https://cdn.discordapp.com/icons/{$serverId}/{$iconCode}.png";

        $channels = $this->httpHandler->request($getChannelUrl, 'GET', null, $header);

        foreach ($channels as $channel) {
            if (empty($channel) || ! isset($channel->type) || ($channel->type != '0' && $channel->type != '5')) {
                continue;
            }

            $allChannels[] = [
                'account_id'   => $channel->id,
                'account_type' => 'channel',
                'platform'     => 'discord',
                'account_name' => $channel->name,
                'parent_id'    => $channel->parent_id,
                'build_id'     => $serverId,
                'icon'         => $server_icon_url
            ];
        }

        return $allChannels;
    }

    public function organizeData($allChannels, $botToken)
    {
        foreach ($allChannels as $channel) {
            $allAccounts[] = array_merge($channel, $botToken);
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
                $data['platform'] = 'discord';
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
