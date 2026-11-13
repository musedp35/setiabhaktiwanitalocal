<?php

namespace BitApps\SocialPro\HTTP\Services\AutoPost;

use BitApps\Social\HTTP\Controllers\ShareNowController;
use BitApps\Social\HTTP\Controllers\SocialTemplateController;
use BitApps\Social\Model\Schedule;
use DateTime;

class AutoPostService
{
    public static function createAutoPostDelay($post, $postId, $autoPostSettings)
    {
        $postDelay = $autoPostSettings['postDelay'];
        $postDate = $post->post_date;
        $postType = $post->post_type;
        $templates = (array) (new SocialTemplateController())->getSocialTemplates();
        $accountIds = $autoPostSettings['accounts']['accountIds'];
        $startTime = self::getModifyStartTime($postDate, $postDelay)->format('Y-m-d H:i:s');

        $scheduleData = [
            'name'   => "Auto Post - Post ID: {$postId}",
            'config' => [
                'settings'     => ['started_at' => $startTime],
                'post_filters' => ['post_type' => $postType, 'specific_postIds' => [$postId]],
                'accounts'     => ['accountIds' => $accountIds],
                'templates'    => $templates,
            ],
            'schedule_type'     => Schedule::scheduleType['SCHEDULE_SHARE'],
            'status'            => Schedule::status['ACTIVE'],
            'started_at'        => $startTime,
            'next_published_at' => $startTime,
        ];

        $scheduleId = Schedule::insert($scheduleData);
        if ($scheduleId) {
            (new ShareNowController())->createSingleEventScheduleIsNotRepeat($scheduleId);
        }
    }

    public static function getModifyStartTime($date, $interval)
    {
        $currentDate = new DateTime($date);

        /*
        'every' represents values like 1, 20, 30
        'unit' represents time units such as 'month', 'minute'
        (e.g., "+1 month", "+20 minutes")
        The function returns the modified DateTime object with the interval applied
        */

        return $currentDate->modify("+{$interval['every']} {$interval['unit']}s");
    }
}
