<?php

namespace BitApps\SocialPro\HTTP\Services\Social\PinterestService;

use AllowDynamicProperties;
use BitApps\Social\HTTP\Services\Interfaces\SocialInterface;
use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Schedule;
use BitApps\Social\Utils\Common;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

#[AllowDynamicProperties]
class PostPublishPinterestService implements SocialInterface
{
    use Common, LoggerTrait;

    public const VERSION = 'v5/';

    private $httpHandler;

    /**
     * @var RefreshService $refreshHandler
     */
    private $refreshHandler;

    private $baseUrl = 'https://api.pinterest.com/';

    private $postUrl = 'https://www.pinterest.com/pin/';

    public function __construct()
    {
        $this->httpHandler = new HttpClient();
        $this->refreshHandler = new PinterestRefreshTokenService();
    }

    public function publishPost($data)
    {
        $post = $data['post'] ?? null;
        $post_data = [];
        $template = (object) $data['template'];
        $scheduleType = $data['schedule_type'] ?? null;
        $account_detail = $data['account_details'];
        $schedule_id = $data['schedule_id'] ?? null;
        $account_id = $account_detail->account_id;
        $account_name = $account_detail->account_name;

        $access_token = Hash::decrypt($account_detail->access_token);
        $expires_in = $account_detail->expires_in;
        $refresh_token = Hash::decrypt($account_detail->refresh_token);
        $refresh_token_expires_in = $account_detail->refresh_token_expires_in;

        $client_id = Hash::decrypt($account_detail->client_id);
        $client_secret = Hash::decrypt($account_detail->client_secret);

        $tokenUpdateData = $this->refreshHandler->tokenExpiryCheck($client_id, $client_secret, $access_token, $expires_in, $refresh_token, $refresh_token_expires_in);

        if ($tokenUpdateData->access_token !== $access_token) {
            $account_detail->access_token = Hash::encrypt($tokenUpdateData->access_token);
            $account_detail->expires_in = $tokenUpdateData->expires_in;
            $account_detail->refresh_token = Hash::encrypt($tokenUpdateData->refresh_token);
            $account_detail->refresh_token_expires_in = $tokenUpdateData->refresh_token_expires_in;
            $this->refreshHandler->saveRefreshedToken($account_detail);
        }

        if ($scheduleType === Schedule::scheduleType['DIRECT_SHARE']) {
            $templateMedia = array_map(function ($item) {
                return $item['url'];
            }, $template->media);

            $post_data['content'] = $template->content ?? null;
            $post_data['images'] = $templateMedia ?? null;
            $post_data['link'] = $template->link ?? null;

            $template->isFeaturedImage = false;
            $template->isLinkCard = false;

            if (!empty($templateMedia)) {
                $template->isFeaturedImage = true;
            }
            if (empty($templateMedia) && !empty($template->link)) {
                $template->isLinkCard = true;
            }
        } else {
            $template->platform = 'pinterest';
            $post_data = $this->replacePostContent($post['ID'], $template);
        }

        $postPublishResponse = $this->pinterestPostPublish($post_data, $account_detail, $access_token, $post['ID'] ?? null, $scheduleType);

        if (\array_key_exists('keepLogs', $data) && !$data['keepLogs']) {
            return;
        }

        if (\is_object($postPublishResponse)) {
            $pinUrl = $this->postUrl . $postPublishResponse->id;
        }

        $responseData = [
            'schedule_id' => $schedule_id,
            'details'     => [
                'account_id'   => $account_id,
                'account_name' => $account_name,
                'post_id'      => $post['ID'] ?? null,
                'response'     => $postPublishResponse,
                'post_url'     => $pinUrl ?? null
            ],
            'platform' => 'pinterest',
            'status'   => \is_object($postPublishResponse) && property_exists($postPublishResponse, 'id') ? 1 : 0
        ];
        $this->logCreate($responseData);
    }

    public function pinterestPostPublish($post_data, $account_detail, $access_token, $post_id, $scheduleType)
    {
        $titleMaxLength = 100;
        $postTitle = get_the_title($post_id);
        $postTitleTrim = substr($postTitle, 0, $titleMaxLength);

        $post_title = $postTitleTrim ?? '';
        $post_content = $post_data['content'] ?? null;
        $feature_image = $post_data['featureImage'] ?? null;

        $allImages = $post_data['allImages'] ?? null;
        $post_link = $post_data['link'] ?? null;

        if ($scheduleType === Schedule::scheduleType['DIRECT_SHARE']) {
            $allImages = $post_data['images'] ?? null;
        }

        return $this->pinCreate($account_detail, $access_token, $post_title, $post_content, $post_link, $feature_image, $allImages);
    }

    public function pinCreate($account_detail, $access_token, $post_title, $post_content, $post_link, $feature_image, $allImages)
    {
        if (!empty($feature_image)) {
            $pinImages = [$feature_image];
        }
        if (!empty($allImages)) {
            $pinImages = $allImages;
        }

        $board_id = $account_detail->account_id;

        if (empty($pinImages)) {
            return [
                'status'    => 'error',
                'error_msg' => 'Must have image for create pin in Pinterest.'
            ];
        }

        if (mb_strlen($post_content) > 500) {
            $post_content = Helper::cutText($post_content, 497);
        }

        if (\count($pinImages) > 1) {
            foreach ($pinImages as $pinImage) {
                $result[] = $this->mediaUpload($pinImage, $board_id, $post_title, $post_content, $post_link, $access_token);
            }

            return end($result);
        }

        $pinImage = $pinImages[0];

        return $this->mediaUpload($pinImage, $board_id, $post_title, $post_content, $post_link, $access_token);
    }

    public function mediaUpload($pinImage, $board_id, $post_title, $post_content, $post_link, $access_token)
    {
        $sendData = [
            'board_id'    => $board_id,
            'title'       => $post_title,
            'description' => $post_content,
            'alt_text'    => $post_title
        ];
        if (!empty($post_link)) {
            $sendData['link'] = $post_link;
        }

        $sendData['media_source']['source_type'] = 'image_url';
        $sendData['media_source']['url'] = $pinImage;

        $pinCreateUrl = $this->baseUrl . static::VERSION . 'pins';

        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $access_token
        ];

        return $this->httpHandler->request($pinCreateUrl, 'POST', json_encode($sendData), $header, null);
    }
}
