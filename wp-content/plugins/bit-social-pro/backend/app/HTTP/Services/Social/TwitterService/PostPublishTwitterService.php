<?php

namespace BitApps\SocialPro\HTTP\Services\Social\TwitterService;

use AllowDynamicProperties;
use BitApps\Social\HTTP\Services\Interfaces\SocialInterface;
use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Schedule;
use BitApps\Social\Utils\Common;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use BitApps\SocialPro\HTTP\Services\Traits\TwitterOAuthHelperTrait;

#[AllowDynamicProperties]
class PostPublishTwitterService implements SocialInterface
{
    use Common, LoggerTrait, TwitterOAuthHelperTrait;

    public const OAuth1 = '1.1';

    public const OAuth2 = '2';

    public const TWEET_URL = 'https://api.twitter.com/2/tweets';

    public const UPLOAD_IMAGE_URL = 'https://upload.twitter.com/1.1/media/upload.json';

    public const POST_BASE_URL = 'https://twitter.com';

    public const USER_INFO_URL_v1 = 'https://api.twitter.com/1.1/account/verify_credentials.json';

    public const USER_INFO_URL_v2 = 'https://api.twitter.com/2/users/me?user.fields=verified';

    public const POST_LENGTH = 280;

    public const VERIFIED_POST_LENGTH = 25000;

    public const LINK_TEXT_LENGTH = 25;

    private $httpClient;

    private $refreshTokenHandler;

    private $apiVersion;

    private $accountId;

    private $verified;

    private $userName;

    private $clientId;

    private $clientSecret;

    private $accessToken;

    private $accessTokenSecret;

    public function __construct()
    {
        $this->httpClient = new HttpClient();
        $this->refreshTokenHandler = new TwitterRefreshTokenService();
    }

    public function publishPost($data)
    {
        $post = $data['post'] ?? null;
        $postId = $post['ID'] ?? null;
        $retry = $data['retry'] ?? false;
        $logId = $data['log_id'] ?? null;

        $postData = [];

        $scheduleType = $data['schedule_type'] ?? null;
        $template = (object) $data['template'];
        $accountDetails = $data['account_details'];
        $scheduleId = $data['schedule_id'] ?? null;

        $accountName = $accountDetails->account_name;
        $userName = $accountDetails->user_name;
        $accountId = $accountDetails->account_id;
        $refreshToken = property_exists($accountDetails, 'refresh_token') ? Hash::decrypt($accountDetails->refresh_token) : null;
        $expiresIn = property_exists($accountDetails, 'expires_in') ? $accountDetails->expires_in : null;

        $this->accountId = $accountId;

        $this->userName = $userName;
        $this->apiVersion = $accountDetails->api_version;
        $this->clientId = Hash::decrypt($accountDetails->client_id);
        $this->clientSecret = Hash::decrypt($accountDetails->client_secret);
        $this->accessToken = Hash::decrypt($accountDetails->access_token);

        if ($this->apiVersion === static::OAuth1) {
            $this->accessTokenSecret = property_exists($accountDetails, 'access_token_secret') ? Hash::decrypt($accountDetails->access_token_secret) : null;
        }

        if (($this->apiVersion === static::OAuth2) && (\intval($expiresIn)) < time()) {
            $tokenUpdateData = $this->refreshTokenHandler->refreshToken($this->clientId, $this->clientSecret, $refreshToken);

            if (!property_exists($tokenUpdateData, 'access_token') && !property_exists($tokenUpdateData, 'refresh_token')) {
                return $tokenUpdateData;
            }

            $this->accessToken = $tokenUpdateData->access_token;

            $accountDetails->access_token = Hash::encrypt($tokenUpdateData->access_token);
            $accountDetails->refresh_token = Hash::encrypt($tokenUpdateData->refresh_token);
            $accountDetails->expires_in = $tokenUpdateData->expires_in;

            $this->refreshTokenHandler->saveDetails($accountDetails);
        }

        $this->verified = $this->isVerified($accountDetails);

        if ($scheduleType === Schedule::scheduleType['DIRECT_SHARE']) {
            $templateMedia = array_map(function ($item) {
                return $item['url'];
            }, $template->media);

            $postData['content'] = $template->content ?? null;
            $postData['images'] = $templateMedia ?? null;
            $postData['link'] = $template->link ?? null;

            $template->isFeaturedImage = false;
            $template->isLinkCard = false;

            if (!empty($templateMedia)) {
                $template->isFeaturedImage = true;
            }
            if (!empty($template->link)) {
                $template->isLinkCard = true;
            }
        } else {
            $template->platform = 'twitter';
            $postData = $this->replacePostContent($post['ID'], $template);
        }

        $tweetResponse = $this->createTweet($postData, $scheduleType, $template);

        $this->logAndRetry($scheduleId, $accountId, $accountName, $postId, $tweetResponse, $userName, $retry, $logId);
    }

    public function isVerified($accountDetails)
    {
        $verified = null;

        if (isset($accountDetails->verified)) {
            return $accountDetails->verified;
        }

        if ($this->apiVersion === static::OAuth1) {
            $userDetails = $this->twitterOAuth(static::USER_INFO_URL_v1, [], 'GET', $this->clientId, $this->clientSecret, $this->accessToken, $this->accessTokenSecret, $this->userName);

            $verified = $userDetails->verified;
        } else {
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken
            ];

            $userDetails = $this->httpClient->request(static::USER_INFO_URL_v2, 'GET', [], $headers);

            $verified = $userDetails->data->verified;
        }

        $accountDetails->verified = $verified;

        $this->refreshTokenHandler->saveDetails($accountDetails);

        return $verified;
    }

    private function createTweet($postData, $scheduleType, $template)
    {
        $featuredImageUrl = null;
        $postMaxLength = $this->verified ? static::VERIFIED_POST_LENGTH : static::POST_LENGTH;

        if ($scheduleType === Schedule::scheduleType['DIRECT_SHARE']) {
            $featuredImageUrl = $postData['images'] ?? null;
        } else {
            $featuredImageUrl = $postData['featureImage'] ?? null;
        }

        if (!empty($postData['allImages'])) {
            $featuredImageUrl = $postData['allImages'];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type'  => 'application/json',
        ];

        $data['text'] = $postData['content'];

        if ($featuredImageUrl || $postData['link']) {
            $postMaxLength -= static::LINK_TEXT_LENGTH;
        }

        if (property_exists($template, 'trimMessage') && $template->trimMessage && \strlen($data['text']) > $postMaxLength) {
            $data['text'] = substr($postData['content'], 0, $postMaxLength);
        }

        if ($this->apiVersion === static::OAuth1 && $featuredImageUrl) {
            if (\is_array($featuredImageUrl)) {
                $response = [];
                foreach ($featuredImageUrl as $imageUrl) {
                    $response[] = $this->uploadImage($imageUrl);
                }

                if (!property_exists($response[0], 'media_id_string')) {
                    return $response;
                }
                $mediaId = array_column($response, 'media_id_string');
            } else {
                $response = $this->uploadImage($featuredImageUrl);

                if (!property_exists($response, 'media_id_string')) {
                    return $response;
                }

                $mediaId = [$response->media_id_string];
            }

            $data['text'] .= "\n";
            $data['media'] = ['media_ids' => $mediaId];
        } elseif ($this->apiVersion === static::OAuth2 && $featuredImageUrl) {
            $data['text'] .= "\n" . $featuredImageUrl;
        } elseif ($postData['link']) {
            $data['text'] .= "\n" . $postData['link'];
        }

        if ($this->apiVersion === static::OAuth1) {
            $response = $this->twitterOAuth(static::TWEET_URL, json_encode($data), 'POST', $this->clientId, $this->clientSecret, $this->accessToken, $this->accessTokenSecret);

            return $response;
        }

        return $this->httpClient->request(static::TWEET_URL, 'POST', json_encode($data), $headers);
    }

    private function uploadImage($imageUrl)
    {
        $filePath = ABSPATH . parse_url($imageUrl, PHP_URL_PATH);

        $imageData = base64_encode(file_get_contents($filePath));

        $data = [
            'media_data' => $imageData,
        ];

        return $this->twitterOAuth(static::UPLOAD_IMAGE_URL, $data, 'POST', $this->clientId, $this->clientSecret, $this->accessToken, $this->accessTokenSecret, null, 'image', $imageData);
    }

    private function logAndRetry($scheduleId, $accountId, $accountName, $postId, $tweetResponse, $userName, $retry, $logId)
    {
        $responseData = [
            'schedule_id' => $scheduleId,
            'details'     => [
                'account_id'   => $accountId,
                'account_name' => $accountName,
                'post_id'      => $postId ?? null,
                'response'     => $tweetResponse,
                'api_version'  => $this->apiVersion,
                'post_url'     => isset($tweetResponse->data->id) ? static::POST_BASE_URL . "/{$userName}/status/{$tweetResponse->data->id}" : ''
            ],
            'platform' => 'twitter',
            'status'   => isset($tweetResponse->data->id) ? 1 : 0
        ];

        if ($retry) {
            $this->logUpdate($responseData, $logId);

            return;
        }

        $this->logCreate($responseData);
    }
}
