<?php

namespace BitApps\SocialPro\HTTP\Services\Social\InstagramService;

use AllowDynamicProperties;
use BitApps\Social\HTTP\Services\Interfaces\SocialInterface;
use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Schedule;
use BitApps\Social\Utils\Common;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

#[AllowDynamicProperties]
class PostPublishInstagramService implements SocialInterface
{
    use Common, LoggerTrait;

    private $version = 'v21.0';

    private $httpHandler;

    /**
     * @var RefreshService $refreshHandler
     */
    private $refreshHandler;

    private $accessToken;

    private $profileId;

    private $mediaUploadErrors = [];

    private $commentError = [];

    private $baseUrl = 'https://graph.facebook.com/';

    private $postUrl = 'https://www.instagram.com/p/';

    public function __construct()
    {
        $this->httpHandler = new HttpClient();
        $this->refreshHandler = new InstagramRefreshTokenService();
    }

    public function publishPost($data)
    {
        $post = $data['post'] ?? null;
        $postData = [];
        $template = (object) $data['template'];
        $scheduleType = $data['schedule_type'] ?? null;
        $accountDetails = $data['account_details'];
        $schedule_id = $data['schedule_id'] ?? null;
        $accessToken = $accountDetails->access_token;
        $account_id = $accountDetails->account_id;
        $account_name = $accountDetails->account_name;
        $generates_on = $accountDetails->generates_on;

        $tokenUpdateData = $this->refreshHandler->tokenExpiryCheck($accessToken, $generates_on);
        if ($tokenUpdateData->access_token !== $accessToken) {
            $accountDetails->access_token = $tokenUpdateData->access_token;
            $accountDetails->generates_on = $tokenUpdateData->generates_on;
            $this->refreshHandler->saveRefreshedToken($accountDetails);
        }

        $this->accessToken = $tokenUpdateData->access_token;
        $this->profileId = $accountDetails->account_id;

        if ($scheduleType === Schedule::scheduleType['DIRECT_SHARE']) {
            $templateMedia = array_map(function ($item) {
                return $item['url'];
            }, $template->media);

            $postData['content'] = $template->content ?? null;
            $postData['images'] = $templateMedia ?? null;
            $postData['link'] = $template->link ?? null;
            $postData['comment'] = $template->comment ?? null;

            $template->isFeaturedImage = false;
            $template->isLinkCard = false;

            if (!empty($templateMedia)) {
                $template->isFeaturedImage = true;
            }
            if (empty($templateMedia) && !empty($template->link)) {
                $template->isLinkCard = true;
            }
        } else {
            $template->platform = 'instagram';
            $postData = $this->replacePostContent($post['ID'], $template);
        }

        $postPublishResponse = $this->instagramPostPublish($postData, $scheduleType);

        if (\array_key_exists('keepLogs', $data) && !$data['keepLogs']) {
            return;
        }

        if (isset($postPublishResponse->shortcode)) {
            $postUrl = $this->postUrl . $postPublishResponse->shortcode;
        }

        $responseData = [
            'schedule_id' => $schedule_id,
            'details'     => [
                'account_id'   => $account_id,
                'account_name' => $account_name,
                'post_id'      => $post['ID'] ?? null,
                'response'     => $postPublishResponse,
                'post_url'     => $postUrl ?? null
            ],
            'platform' => 'instagram',
            'status'   => \is_object($postPublishResponse) && property_exists($postPublishResponse, 'id') ? 1 : 0
        ];

        if (\count($this->mediaUploadErrors)) {
            $responseData['details']['imageUploadErrors'] = $this->mediaUploadErrors;
        }
        if (\count($this->commentError)) {
            $responseData['details']['commentError'] = $this->commentError;
        }

        $this->logCreate($responseData);
    }

    public function instagramPostPublish($postData, $scheduleType)
    {
        $caption = $postData['content'] ?? null;
        $feature_image = $postData['featureImage'] ?? null;

        $allImages = $postData['allImages'] ?? null;
        $comment = $postData['comment'] ?? null;

        if ($scheduleType === Schedule::scheduleType['DIRECT_SHARE']) {
            $allImages = $postData['images'] ?? null;
        }

        return $this->postMedia($caption, $feature_image, $allImages, $comment);
    }

    public function postMedia($caption, $feature_image, $allImages, $comment)
    {
        if (!empty($feature_image)) {
            $postImages = [$feature_image];
        }
        if (!empty($allImages)) {
            $postImages = $allImages;
        }

        if (empty($postImages)) {
            return [
                'status'    => 'error',
                'error_msg' => 'Must have image for create post in Instagram.'
            ];
        }

        if (\count($postImages) > 10) {
            return [
                'status'    => 'error',
                'error_msg' => 'You have added more than 10 images. Please limit to 10 images.'
            ];
        }

        if (\count($postImages) > 1) {
            return $this->multipleMediaPost($postImages, $caption, $comment);
        }

        $mediaUrl = $postImages[0];

        return $this->singleMediaPost($mediaUrl, $caption, $comment);
    }

    public function mediaUpload($mediaUrl, $caption, $isCarousel = false)
    {
        $params['image_url'] = $mediaUrl;
        if (isset($caption)) {
            $params['caption'] = $caption;
        }
        if ($isCarousel) {
            $params['is_carousel_item'] = 'true';
        }

        $params['access_token'] = $this->accessToken;

        $postPublishUrl = "{$this->baseUrl}{$this->version}/{$this->profileId}/media?";

        $publishPostUrlWithParams = $postPublishUrl . http_build_query($params);

        $uploadResponse = $this->httpHandler->request($publishPostUrlWithParams, 'POST', []);

        if (isset($uploadResponse->id)) {
            return $this->checkPublishSuccess($uploadResponse->id) ? $uploadResponse : false;
        }

        return $uploadResponse;
    }

    public function mediaPublish($creationId)
    {
        $postPublishUrl = "{$this->baseUrl}{$this->version}/{$this->profileId}/media_publish?";
        $params['creation_id'] = $creationId;
        $params['access_token'] = $this->accessToken;

        $publishPostUrlWithParams = $postPublishUrl . http_build_query($params);

        return $this->httpHandler->request($publishPostUrlWithParams, 'POST', []);
    }

    public function shortCode($creationId)
    {
        $postPublishUrl = "{$this->baseUrl}{$this->version}/{$creationId}?";
        $params['fields'] = 'shortcode';
        $params['access_token'] = $this->accessToken;

        $publishPostUrlWithParams = $postPublishUrl . http_build_query($params);

        return $this->httpHandler->request($publishPostUrlWithParams, 'GET', []);
    }

    public function checkPublishSuccess($uploadId)
    {
        $postPublishUrl = "{$this->baseUrl}{$this->version}/{$uploadId}?";
        $params['fields'] = 'status_code';
        $params['access_token'] = $this->accessToken;

        $publishPostUrlWithParams = $postPublishUrl . http_build_query($params);
        $retries = 0;
        while ($retries < 30) {
            $status = $this->httpHandler->request($publishPostUrlWithParams, 'GET', []);

            if (! isset($status->status_code) || \in_array($status->status_code, ['EXPIRED', 'ERROR'])) {
                return false;
            }

            if ($status->status_code == 'IN_PROGRESS') {
                sleep(3);
                $retries++;
            } else {
                break;
            }
        }

        return true;
    }

    public function createCarouselContainer($caption, $uploadIds)
    {
        if (isset($caption)) {
            $params['caption'] = $caption;
        }
        $params['media_type'] = 'CAROUSEL';

        if ($uploadIds) {
            $uploadIdsString = implode(',', $uploadIds);
            $params['children'] = $uploadIdsString;
        }

        $params['access_token'] = $this->accessToken;

        $postPublishUrl = "{$this->baseUrl}{$this->version}/{$this->profileId}/media?";

        $publishPostUrlWithParams = $postPublishUrl . http_build_query($params);

        $uploadResponse = $this->httpHandler->request($publishPostUrlWithParams, 'POST', []);

        if (isset($uploadResponse->id)) {
            return $this->checkPublishSuccess($uploadResponse->id) ? $uploadResponse->id : false;
        }

        return $uploadResponse;
    }

    public function makeComment($mediaId, $message)
    {
        $postCommentUrl = "{$this->baseUrl}{$this->version}/{$mediaId}/comments?";
        $params['message'] = $message;
        $params['access_token'] = $this->accessToken;

        $postCommentUrlWithParams = $postCommentUrl . http_build_query($params);

        return $this->httpHandler->request($postCommentUrlWithParams, 'POST', []);
    }

    public function singleMediaPost($mediaUrl, $caption, $commentMessage)
    {
        $mediaUploadResponse = $this->mediaUpload($mediaUrl, $caption);

        if (isset($mediaUploadResponse->id)) {
            $mediaPublishResponse = $this->mediaPublish($mediaUploadResponse->id);

            if (isset($mediaPublishResponse->id)) {
                if (!empty($commentMessage)) {
                    $commentResponse = $this->makeComment($mediaPublishResponse->id, $commentMessage);
                }

                if (isset($commentResponse->error)) {
                    $this->commentError[] = $commentResponse->error;
                }

                return $this->shortCode($mediaPublishResponse->id);
            }

            return $mediaPublishResponse;
        }

        if (isset($mediaUploadResponse->error)) {
            $this->mediaUploadErrors[] = $mediaUploadResponse->error;
        }
    }

    public function multipleMediaPost($postImages, $caption, $commentMessage)
    {
        foreach ($postImages as $mediaUrl) {
            $uploadResponse = $this->mediaUpload($mediaUrl, $caption, true);

            if (isset($uploadResponse->id)) {
                $uploadIds[] = $uploadResponse->id;
            }

            if (isset($uploadResponse->error)) {
                $this->mediaUploadErrors[] = $uploadResponse->error;
            }
        }

        if (!empty($uploadIds) && \count($uploadIds) > 1) {
            $containerResponse = $this->createCarouselContainer($caption, $uploadIds);
            if (!empty($containerResponse)) {
                $mediaPublishResponse = $this->mediaPublish($containerResponse);

                if (isset($mediaPublishResponse->id)) {
                    if (!empty($commentMessage)) {
                        $commentResponse = $this->makeComment($mediaPublishResponse->id, $commentMessage);
                    }

                    if (isset($commentResponse->error)) {
                        $this->commentError[] = $commentResponse->error;
                    }

                    return $this->shortCode($mediaPublishResponse->id);
                }

                return $mediaPublishResponse;
            }

            return $containerResponse;
        }

        return [
            'status'    => 'error',
            'error_msg' => 'Instagram requires all selected images to be valid and meet their guidelines. Please check your images and try again.'
        ];
    }
}
