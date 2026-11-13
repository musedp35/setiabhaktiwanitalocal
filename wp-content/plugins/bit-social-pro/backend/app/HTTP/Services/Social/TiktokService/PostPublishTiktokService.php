<?php

namespace BitApps\SocialPro\HTTP\Services\Social\TiktokService;

use AllowDynamicProperties;
use BitApps\Social\HTTP\Services\Interfaces\SocialInterface;
use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Schedule;
use BitApps\Social\Utils\Common;
use BitApps\Social\Utils\Hash;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

#[AllowDynamicProperties]
class PostPublishTiktokService implements SocialInterface
{
    use Common, LoggerTrait;

    private $httpClient;

    private $mediaUploadErrors = [];

    private $baseUrl = 'https://open.tiktokapis.com';

    private $siteUrl = 'https://www.tiktok.com';

    private $version = 'v2';

    private $userId;

    private $clientId;

    private $clientSecret;

    private $redirectUri;

    private $accessToken;

    private $refreshToken;

    private $generateOn;

    public function __construct()
    {
        $this->httpClient = new HttpClient();
    }

    public function publishPost($data)
    {
        $post = $data['post'] ?? null;
        $postId = $post['ID'] ?? null;
        $postData = [];
        $template = (object) $data['template'];
        $scheduleType = $data['schedule_type'] ?? null;
        $accountDetails = $data['account_details'];
        $scheduleId = $data['schedule_id'] ?? null;

        $tokenService = new TiktokRefreshTokenService($accountDetails);

        $this->userId = $accountDetails->profile_id;
        $this->userName = $accountDetails->user_name;
        $this->clientId = $accountDetails->client_id;
        $this->clientSecret = Hash::decrypt($accountDetails->client_secret);
        $this->redirectUri = $accountDetails->redirect_uri;
        $this->accessToken = $tokenService->accessToken();

        $postData = $this->postData($post, $postData, $scheduleType, $template);

        $postPublishResponse = $this->tiktokPostPublish($postData);

        if (\array_key_exists('keepLogs', $data) && !$data['keepLogs']) {
            return;
        }

        $this->logAndRetry($scheduleId, $accountDetails->account_id, $accountDetails->account_name, $postId, $postPublishResponse, $data);
    }

    public function postData($post, $postData, $scheduleType, $template)
    {
        if ($scheduleType === Schedule::scheduleType['DIRECT_SHARE']) {
            $postData['content'] = $template->content ?? null;
        } else {
            $template->platform = 'tiktok';
            $postData = $this->replacePostContent($post['ID'], $template);
        }

        $postData['privacy_level'] = $template->privacyLevel;
        $postData['comment_disabled'] = !$template->allowComment;
        $postData['duet_disabled'] = !$template->duet;
        $postData['stitch_disabled'] = !$template->stitch;

        $postData['video'] = $template->media[0]['url'] ?? null;

        return $postData;
    }

    public function tiktokPostPublish($postData)
    {
        $videoUrl = $postData['video'];

        $fileDetails = $this->fileDetails($videoUrl);
        $mimeType = $fileDetails['mimeType'];
        $filePath = $fileDetails['filePath'];

        $videoChunks = $this->splitVideoIntoChunks($filePath);

        $requestPostContainer = $this->initializeVideoPost($postData, $videoChunks);

        if ($requestPostContainer['status'] === 'error') {
            return $requestPostContainer;
        }

        $publishId = $requestPostContainer['data']->publish_id;
        $uploadUrl = $requestPostContainer['data']->upload_url;

        $this->uploadVideoChunks($uploadUrl, $mimeType, $videoChunks);

        return $this->checkPublishStatus($publishId, $videoChunks['file_size']);
    }

    public function getMediaDetails($mediaUrl)
    {
        $attachmentId = attachment_url_to_postid($mediaUrl);

        if (!$attachmentId) {
            return false;
        }

        $attachment = get_post($attachmentId);
        $metadata = wp_get_attachment_metadata($attachmentId);

        return [
            'ID'        => $attachmentId,
            'Title'     => $attachment->post_title,
            'File Type' => get_post_mime_type($attachmentId),
            'URL'       => wp_get_attachment_url($attachmentId),
            'Metadata'  => $metadata,
        ];
    }

    private function splitVideoIntoChunks(string $videoFilePath): array
    {
        if (!file_exists($videoFilePath) || !is_readable($videoFilePath)) {
            return ['status' => 'error', 'message' => 'File does not exist or is not readable.'];
        }

        $fileSize = filesize($videoFilePath);
        $maxChunkSize = 64 * 1024 * 1024; // 64 MB

        // Ensure at least one chunk is created
        $chunkSize = min($fileSize, $maxChunkSize);
        $totalChunkCount = max(1, ceil($fileSize / $chunkSize)); // Ensures at least one chunk

        if ($totalChunkCount > 1000) {
            return ['status' => 'error', 'message' => 'Video file is too large and exceeds the maximum allowed chunk limit (1000).'];
        }

        $chunks = [];
        $fileHandle = fopen($videoFilePath, 'rb');

        for ($chunkIndex = 0; $chunkIndex < $totalChunkCount; $chunkIndex++) {
            $startByte = $chunkIndex * $chunkSize;
            $chunkSizeActual = min($chunkSize, $fileSize - $startByte); // Handle last chunk properly

            fseek($fileHandle, $startByte);
            $chunkData = fread($fileHandle, $chunkSizeActual);

            $chunks[] = [
                'chunk_index' => $chunkIndex,
                'chunk_size'  => $chunkSizeActual,
                'start_byte'  => $startByte,
                'end_byte'    => $startByte + $chunkSizeActual - 1,
                'data'        => $chunkData,
            ];
        }

        fclose($fileHandle);

        return [
            'status'            => 'success',
            'file_size'         => $fileSize,
            'chunk_size'        => $chunkSize,
            'total_chunk_count' => $totalChunkCount,
            'chunks'            => $chunks,
        ];
    }

    private function initializeVideoPost(array $postingData, array $videoChunks): array
    {
        $postPublishUrl = "{$this->baseUrl}/{$this->version}/post/publish/video/init/";

        $data = [
            'post_info' => [
                'title'            => $postingData['content'] ?? '',
                'privacy_level'    => $postingData['privacy_level'] ?? 'PUBLIC',
                'comment_disabled' => $postingData['comment_disabled'] ?? false,
                'duet_disabled'    => $postingData['duet_disabled'] ?? false,
                'stitch_disabled'  => $postingData['stitch_disabled'] ?? false,
            ],
            'source_info' => [
                'source'            => 'FILE_UPLOAD',
                'video_size'        => $videoChunks['file_size'] ?? 0,
                'chunk_size'        => $videoChunks['chunk_size'] ?? 0,
                'total_chunk_count' => $videoChunks['total_chunk_count'] ?? 0,
            ],
        ];

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $response = $this->httpClient->request($postPublishUrl, 'POST', json_encode($data), $headers);

        if (empty($response->data->publish_id) || empty($response->data->upload_url)) {
            $errorMessage = $response->error->message ?? 'Failed to initialize video post. Unknown error occurred.';

            return ['status' => 'error', 'message' => $errorMessage];
        }

        return ['status' => 'success', 'data' => $response->data];
    }

    private function uploadVideoChunks(string $uploadUrl, string $mimeType, array $videoChunks)
    {
        foreach ($videoChunks['chunks'] as $chunk) {
            $this->httpClient->request($uploadUrl, 'PUT', $chunk['data'], [
                'Authorization'  => 'Bearer ' . $this->accessToken,
                'Content-Range'  => \sprintf('bytes %s-%s/%s', (string) $chunk['start_byte'], (string) $chunk['end_byte'], $videoChunks['file_size']),
                'Content-Length' => $chunk['chunk_size'],
                'Content-Type'   => $mimeType
            ], [], false);
        }
    }

    private function checkPublishStatus($publishId, $uploadedFileSize)
    {
        $maxRetries = 20;
        $retryInterval = 5; // in seconds
        $tries = 0;

        $statusCheckUrl = "{$this->baseUrl}/{$this->version}/post/publish/status/fetch/";

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken
        ];

        while ($tries < $maxRetries) {
            $response = $this->httpClient->request($statusCheckUrl, 'POST', json_encode([
                'publish_id' => $publishId,
            ]), $headers);

            $status = $response->data->status ?? '';
            $uploadedBytes = $response->data->uploaded_bytes ?? 0;

            if ($status === 'FAILED') {
                return ['status' => 'error', 'message' => 'Publication failed. Please try again or check your TikTok account for details.'];
            }

            if (\in_array($status, ['SEND_TO_USER_INBOX', 'PUBLISH_COMPLETE'])) {
                return ['status' => 'success', 'message' => 'Video successfully published!'];
            }

            if ($uploadedBytes != $uploadedFileSize) {
                return ['status' => 'error', 'message' => 'Upload inconsistency detected. The uploaded video size does not match the expected size.'];
            }

            $tries++;
            sleep($retryInterval);
        }

        return [
            'status'  => 'error',
            'message' => 'Unable to confirm the video publication within 100 seconds. Please check your TikTok account to verify if the video has been published successfully.'
        ];
    }

    private function logAndRetry($scheduleId, $accountId, $accountName, $postId, $postResponse, $data)
    {
        $responseData = [
            'schedule_id' => $scheduleId,
            'details'     => [
                'account_id'   => $accountId,
                'account_name' => $accountName,
                'post_id'      => $postId ?? null,
                'response'     => $postResponse['message'],
                'api_version'  => $this->version,
                'post_url'     => $postResponse['status'] === 'success' ? "{$this->siteUrl}/@{$this->userName}" : null,
            ],
            'platform' => 'tiktok',
            'status'   => $postResponse['status'] === 'success' ? 1 : 0
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

    private function fileDetails($url)
    {
        $attachmentId = attachment_url_to_postid($url);

        if (!$attachmentId) {
            return false;
        }

        return ['mimeType' => get_post_mime_type($attachmentId), 'filePath' => get_attached_file($attachmentId)];
    }
}
