<?php

namespace App\Jobs;

use App\Common\Service\V1\RabbitmqService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class IncVisitProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue_name = 'product';//队列名称
    public $exchange = 'product';//交换机名称
    public $routing_key = 'product';//路由key。默认交换机的类型为direct类型，路由键与队列名相同。fanout模式如果routing_key 有指定也不会生效
    public $type = 'direct';//交换机类型，direct直接匹配、topic模糊匹配、headers消息头的键值对匹配、fanout广播
    private $data;//队列数据

    /**
     * IncVisitProduct constructor.
     * @param array|object $data 队列数据
     */
    public function __construct($data)
    {
        $this->data = is_array($data) || is_object($data) ? json_encode($data) : $data;
        try {
            RabbitmqService::push($this->queue_name, $this->exchange, $this->routing_key, $this->data, $this->type);
        } catch (\Exception $e) {
            errorLogs($e);
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            RabbitmqService::pop($this->queue_name, function ($message) {
                _logs('添加访问量-消费者执行', ['params' => $message ?? [], 'IncVisitProduct/pop']);

                // 数据整理
                $data = !is_array($message) && !is_object($message) ? json_decode($message, true) : $message;
                $data['create_time'] = time();

                // 数据入库
                if (!DB::table('visit')->insert($data)) {
                    _logs('添加访问量-插入数据库失败', ['params' => $data ?? [], 'IncVisitProduct/pop']);
                    return false;
                }

                return true;
            });
        } catch (\Exception $e) {
            errorLogs($e);
            return ['code' => 1300, 'msg' => config('exception_msg')];
        }
    }

    /**
     * 异常扑获
     * @param \Exception $exception
     */
    public function failed(\Exception $exception)
    {
        print_r($exception->getMessage());
    }
}
