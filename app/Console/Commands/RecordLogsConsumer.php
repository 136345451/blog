<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use function Symfony\Component\Translation\t;

class RecordLogsConsumer extends Command
{
    public static $queue_name = 'record_logs';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consumer:kafka';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '处理异步kafka消息';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        dump('开始监听消息...');
        app('KafkaService')->consumer(self::$queue_name, function ($message) {
            try {
                _logs('记录日志-消费者执行', ['params' => $message ?? [], 'RecordLogsProduct/recordLogConsumer'], 'RecordLogsConsumer/handle', 'logs',true);
                // 数据整理
                $data = !is_array($message) && !is_object($message) ? json_decode($message, true) : $message;
                _logs($data['msg'], $data['data'], $data['dir'], $data['path'], true);
                return true;
            } catch (\Exception $e) {
                errorLogs($e);
                return ['code' => 1300, 'msg' => config('app.exception_msg')];
            }
        });
    }
}
