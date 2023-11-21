<?php

namespace App\Jobs;

use App\Common\Service\V1\KafkaService;

class RecordLogsProduct
{
    public static $queue_name = 'record_logs';

    /**
     * Notes: 记录日志
     * User: fangcan
     * DateTime: 2023/11/21 22:56
     * @param $data
     */
    public static function recordLogsProduct($data)
    {
        try {
            KafkaService::Producer(self::$queue_name, json_encode($data));
        } catch (\Exception $e) {
            errorLogs($e);
            return ['code' => 1300, 'msg' => config('app.exception_msg')];
        }
    }
}
