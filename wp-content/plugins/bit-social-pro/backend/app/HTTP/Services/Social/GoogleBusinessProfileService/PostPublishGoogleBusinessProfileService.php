<?php

namespace BitApps\SocialPro\HTTP\Services\Social\GoogleBusinessProfileService;

use BitApps\Social\HTTP\Services\Interfaces\SocialInterface;
use BitApps\Social\HTTP\Services\Traits\LoggerTrait;

use BitApps\Social\Model\Schedule;
use BitApps\Social\Utils\Common;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

class PostPublishGoogleBusinessProfileService implements SocialInterface
{
    use Common, LoggerTrait;

    public const BASE_URL = 'https://mybusiness.googleapis.com/';

    public const VERSION = 'v4';

    private $httpHandler;

    private $accountId;

    private $locationId;

    private $clientId;

    private $clientSecret;

    private $redirectUri;

    private $accessToken;

    private $refreshToken;

    private $generateOn;

    private $replaceContent;

    public function __construct()
    {
        $this->httpHandler = new HttpClient();
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

        $tokenService = new GoogleBusinessProfileRefreshTokenService($accountDetails);

        $this->accountId = $accountDetails->profile_id;
        $this->locationId = $accountDetails->account_id;
        $this->clientId = Hash::decrypt($accountDetails->account_id);
        $this->clientId = Hash::decrypt($accountDetails->client_id);
        $this->clientSecret = Hash::decrypt($accountDetails->client_secret);
        $this->redirectUri = Hash::decrypt($accountDetails->redirect_uri);
        $this->accessToken = $tokenService->accessToken();

        $postData = $this->postData($post, $postData, $scheduleType, $template);

        $addUpdateResponse = $this->addUpdate($postData);

        if (\array_key_exists('keepLogs', $data) && !$data['keepLogs']) {
            return;
        }

        $this->logAndRetry($scheduleId, $this->accountId, $accountDetails->account_name, $postId, $addUpdateResponse, $data);
    }

    public function addUpdate($postData)
    {
        $addUpdateUrl = static::BASE_URL . static::VERSION . '/' . $this->accountId . '/' . $this->locationId . '/localPosts';

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken
        ];

        $data = [
            'summary'   => $postData['content'],
            'topicType' => 'STANDARD',
        ];

        if (isset($postData['featureImage'])) {
            $postData['images'] = [$postData['featureImage']];
        }

        if (isset($postData['images']) && \count($postData['images'])) {
            foreach ($postData['images'] as $image) {
                $data['media'][] = [
                    'mediaFormat' => 'PHOTO',
                    'sourceUrl'   => $image
                ];
            }
        }

        if (!empty($postData['button']) && $postData['button'] !== 'none' && !empty($postData['link'])) {
            $data['callToAction'] = [
                'actionType' => $postData['button'],
                'url'        => $postData['link']
            ];
        }

        $body = json_encode($data);

        return $this->httpHandler->request($addUpdateUrl, 'POST', $body, $header);
    }

    public function postData($post, $postData, $scheduleType, $template)
    {
        if ($scheduleType === Schedule::scheduleType['DIRECT_SHARE']) {
            $templateMedia = array_map(function ($item) {
                return $item['url'];
            }, $template->media);

            $postData['content'] = $template->content ?? null;
            $postData['images'] = $templateMedia ?? null;
            $postData['link'] = $template->link ?? null;
            $postData['button'] = $template->button ?? null;

            $template->isFeaturedImage = false;
            $template->isLinkCard = false;

            if (!empty($templateMedia)) {
                $template->isFeaturedImage = true;
            }
            if (empty($templateMedia) && !empty($template->link)) {
                $template->isLinkCard = true;
            }
        } else {
            if (!empty($template->button) && $template->button !== 'none') {
                $template->isLinkCard = true;
            }

            $template->platform = 'googleBusinessProfile';
            $postData = $this->replacePostContent($post['ID'], $template);
        }

        return $postData;
    }

    private function logAndRetry($scheduleId, $accountId, $accountName, $postId, $addUpdateResponse, $data)
    {
        $responseData = [
            'schedule_id' => $scheduleId,
            'details'     => [
                'account_id'   => $accountId,
                'account_name' => $accountName,
                'post_id'      => $postId ?? null,
                'response'     => $addUpdateResponse,
                'api_version'  => static::VERSION,
                'post_url'     => $addUpdateResponse->searchUrl ?? null,
            ],
            'platform' => 'Google Business Profile',
            'status'   => isset($addUpdateResponse->searchUrl) ? 1 : 0
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
}
