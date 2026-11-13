<?php

namespace BitApps\SocialPro\HTTP\Controllers;

use BitApps\Social\HTTP\Controllers\WpPostController;
use BitApps\Social\Model\Schedule;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Request\Request;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Response;

final class CalendarController
{
    public function getAllSchedules(Request $request)
    {
        $monthYear = $request->get('monthYear');

        $monthYearArr = explode('/', $monthYear);

        $month = $monthYearArr[0];
        $year = $monthYearArr[1];

        $currentDate = date('Y-m-d', strtotime("{$year}-{$month}-01"));

        $schedules = Schedule::where("DATE_FORMAT(started_at, '%Y-%m-01')", '<=', $currentDate)->where('status', Schedule::status['ACTIVE'])->get(['id', 'name', 'config', 'started_at', 'schedule_type']);

        $allSchedules = [];

        if (!$schedules) {
            return Response::success($allSchedules);
        }

        foreach ($schedules as $schedule) {
            $config = $schedule->config;

            $postsCount = -1;

            if ($schedule->schedule_type === Schedule::scheduleType['SCHEDULE_SHARE']) {
                $postsFilterDays = $config['post_filters']['filter_by_days']; // 0 - all times
                $postsOrderType = $config['settings']['post_publish_order']; // 2 - randomly

                if ($postsOrderType !== '2') {
                    $postsController = new WpPostController();
                    $filteredPosts = $postsController->filterPosts((object) $config['post_filters']);
                    $postsCount = \count($filteredPosts);
                }

                if (isset($config['post_filters']['specific_postIds'])) {
                    $postsCount = \count($config['post_filters']['specific_postIds']);
                }
            }
            if ($schedule->schedule_type === Schedule::scheduleType['DIRECT_SHARE']) {
                $postRepeat = $config['settings']['repeat'];

                if (!$postRepeat) {
                    $postsCount = 1;
                }
            }

            $newSchedule = [
                'scheduleId'    => $schedule->id,
                'scheduleName'  => $schedule->name,
                'startDateTime' => $schedule->started_at,
                'scheduleType'  => $schedule->schedule_type,
                'interval'      => [
                    'every' => (int) $config['settings']['post_interval_value'],
                    'unit'  => $config['settings']['post_interval_type']
                ],
                'postsCount' => $postsCount,
            ];

            if (isset($config['settings']['sleep_time']) && !empty($config['settings']['sleep_time'])) {
                $start = $config['settings']['sleep_time'][0];
                $end = $config['settings']['sleep_time'][1];

                $newSchedule['sleepTime'] = [
                    'start' => $start,
                    'end'   => $end
                ];
            }

            if (isset($config['settings']['sleep_days']) && !empty($config['settings']['sleep_days'])) {
                $newSchedule['sleepDays'] = $config['settings']['sleep_days'];
            }

            $allSchedules[] = $newSchedule;
        }

        return Response::success($allSchedules);
    }
}
