<?php

namespace BitApps\SocialPro\HTTP\Services\Social\TumblrService;

use AllowDynamicProperties;
use BitApps\Social\HTTP\Services\Interfaces\SocialInterface;
use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Schedule;
use BitApps\Social\Utils\Common;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;
use BitApps\SocialPro\HTTP\Services\Traits\TumblrOAuthHelperTrait;

#[AllowDynamicProperties]
class PostPublishTumblrService implements SocialInterface
{
    use Common, LoggerTrait, TumblrOAuthHelperTrait;

    public const POST_BASE_URL = 'https://api.tumblr.com/v2/blog/';

    public const BASE_URL = 'https://tumblr.com';

    private $httpClient;

    private $clientId;

    private $clientSecret;

    private $accessToken;

    private $accessTokenSecret;

    public function __construct()
    {
        $this->httpClient = new HttpClient();
    }

    public function publishPost($data)
    {
        $post = $data['post'] ?? null;
        $postId = $post['ID'] ?? null;
        $postData = [];
        $scheduleType = $data['schedule_type'] ?? null;
        $template = (object) $data['template'];
        $accountDetails = $data['account_details'];
        $scheduleId = $data['schedule_id'] ?? null;
        $accountName = $accountDetails->account_name;
        $userName = $accountDetails->user_name;
        $accountId = $accountDetails->account_id;
        $blogIdentifier = $userName . '.tumblr.com';

        $tokenService = new TumblrRefreshTokenService($accountDetails);

        $this->accountId = $accountDetails->profile_id;
        $this->locationId = $accountDetails->account_id;
        $this->clientId = Hash::decrypt($accountDetails->client_id);
        $this->clientSecret = Hash::decrypt($accountDetails->client_secret);
        $this->accessToken = $tokenService->accessToken();

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
            $template->platform = 'tumblr';
            $postData = $this->replacePostContent($post['ID'], $template);
        }

        $tumblrResponse = $this->createPost($postData, $scheduleType, $blogIdentifier, $template->content);

        $this->logAndRetry($scheduleId, $accountId, $accountName, $postId, $tumblrResponse, $userName, $data);
    }

    public function encodeImageToBase64($imagePath)
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        return file_get_contents($imagePath, false, $context);
    }

    private function createPost($postData, $scheduleType, $blogIdentifier, $smartTag)
    {
        $url = static::POST_BASE_URL . $blogIdentifier . '/post';

        $featuredImageUrl = null;

        if ($scheduleType === Schedule::scheduleType['DIRECT_SHARE']) {
            if (\count($postData['images']) > 1) {
                $featuredImageUrl = $postData['images'] ?? null;
            } else {
                $featuredImageUrl = $postData['images'][0] ?? null;
            }
        } else {
            $featuredImageUrl = $postData['featureImage'];
        }

        if (!empty($postData['allImages'])) {
            $featuredImageUrl = $postData['allImages'];
        }

        $data = [
            'type' => 'text',
            'body' => $this->makeLinksClickable($postData['content'])
        ];

        if (strpos($smartTag, '{post_title}') !== false) {
            $data['title'] = $postData['title'];
        }

        if (strpos($smartTag, '{post_title}') !== false && strpos($smartTag, '{post_content_full') !== false) {
            $postData['content'] = trim(substr($postData['content'], \strlen($postData['title'])));
        }

        if ($featuredImageUrl) {
            if (\is_array($featuredImageUrl)) {
                $images = [];

                foreach ($featuredImageUrl as $image) {
                    $images[] = $this->encodeImageToBase64($image);
                }

                $data = [
                    'type'    => 'photo',
                    'data'    => $images,
                    'caption' => $this->makeLinksClickable($postData['content'])
                ];
            } else {
                $data = [
                    'type'    => 'photo',
                    'source'  => $featuredImageUrl,
                    'caption' => $this->makeLinksClickable($postData['content'])
                ];
            }
        }

        if ($postData['link']) {
            $data = [
                'type'        => 'link',
                'title'       => $postData['title'] ?? null,
                'url'         => $postData['link'],
                'description' => $this->makeLinksClickable($postData['content'])
            ];
        }

        $response = $this->tumblrOAuth(null, null, null, $url, $data, 'POST', $this->clientId, $this->clientSecret, $this->accessToken, $this->accessTokenSecret);

        return $response;
    }

    private function logAndRetry($scheduleId, $accountId, $accountName, $postId, $tumblrResponse, $userName, $data)
    {
        $responseData = [
            'schedule_id' => $scheduleId,
            'details'     => [
                'account_id'   => $accountId,
                'account_name' => $accountName,
                'post_id'      => $postId ?? null,
                'response'     => $tumblrResponse,
                'post_url'     => isset($tumblrResponse->response->id) ? static::BASE_URL . "/{$userName}/{$tumblrResponse->response->id}" : ''
            ],
            'platform' => 'tumblr',
            'status'   => isset($tumblrResponse->response->id) ? 1 : 0
        ];

        if (\array_key_exists('retry', $data) && $data['retry'] === true) {
            $this->logUpdate($responseData, $data['log_id']);

            return;
        }

        if ($responseData['status'] === 1) {
            $this->logCreate($responseData);

            return;
        }

        $this->logCreate($responseData);
    }

    private function makeLinksClickable($text)
    {
        $url_pattern = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';

        return preg_replace($url_pattern, '<a href="$0">$0</a>', $text);
    }
}
