<?php

namespace BitApps\SocialPro\HTTP\Services\Social\DiscordService;

use AllowDynamicProperties;
use BitApps\Social\HTTP\Services\Interfaces\SocialInterface;
use BitApps\Social\HTTP\Services\Traits\LoggerTrait;
use BitApps\Social\Model\Schedule;
use BitApps\Social\Utils\Common;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Client\HttpClient;

#[AllowDynamicProperties]
class PostPublishDiscordService implements SocialInterface
{
    use Common, LoggerTrait;

    public const VERSION = 'v5/';

    private $httpHandler;

    private $linkedinInstance;

    /**
     * @var RefreshService $refreshHandler
     */
    private $refreshHandler;

    private $baseURl = 'https://discord.com/';

    private $baseApiUrl;

    public function __construct()
    {
        $this->httpHandler = new HttpClient();
        $this->baseApiUrl = $this->baseURl . 'api/';
        $this->httpHandler->setBaseUri($this->baseApiUrl);
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
        $guild_id = $account_detail->build_id;
        $botToken = $account_detail->botToken;

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
            $template->platform = 'discord';
            $post_data = $this->replacePostContent($post['ID'], $template);
        }

        $postPublishResponse = $this->discordPostPublish($post_data, $account_detail, $botToken, $post['ID'] ?? null, $scheduleType);

        if (\array_key_exists('keepLogs', $data) && !$data['keepLogs']) {
            return;
        }

        $channel_id = property_exists($postPublishResponse, 'channel_id') ? $postPublishResponse->channel_id : '';
        $messageUrl = $channel_id ? "{$this->baseURl}channels/{$guild_id}/{$channel_id}" : '';

        $responseData = [
            'schedule_id' => $schedule_id,
            'details'     => [
                'account_id'   => $account_id,
                'account_name' => $account_name,
                'post_id'      => $post['ID'] ?? null,
                'response'     => $postPublishResponse,
                'post_url'     => $messageUrl
            ],
            'platform' => 'discord',
            'status'   => property_exists($postPublishResponse, 'id') ? 1 : 0
        ];
        $this->logCreate($responseData);
    }

    public function discordPostPublish($post_data, $account_detail, $botToken, $post_id, $scheduleType)
    {
        $post_title = get_the_title($post_id);
        $post_content = $post_data['content'] ?? null;
        $feature_image = $post_data['featureImage'] ?? null;
        $allImages = $post_data['allImages'] ?? null;
        $post_link = $post_data['link'] ?? null;

        if ($scheduleType === Schedule::scheduleType['DIRECT_SHARE']) {
            $allImages = $post_data['images'] ?? null;
        }

        return $this->sendMessage($account_detail, $botToken, $post_title, $post_content, $post_link, $feature_image, $allImages);
    }

    public function sendMessage($account_detail, $botToken, $post_title, $post_content, $post_link, $feature_image, $allImages)
    {
        $endpoint = "channels/{$account_detail->account_id}/messages";
        $message = !empty($post_link) ? \sprintf("%s\n%s", $post_content, $post_link) : $post_content;
        $images = $feature_image ? [$feature_image] : $allImages;

        $this->httpHandler->setHeader('Authorization', 'Bot ' . $botToken);
        $finalData = ['content' => $message];

        if (!empty($images)) {
            foreach ($images as $image) {
                $finalData['embeds'][] = ['image' => ['url' => $image]];
            }
        }

        $this->httpHandler->setContentType('application/json');
        $this->httpHandler->setBody(wp_json_encode($finalData));

        return $this->httpHandler->post($endpoint);
    }
}
