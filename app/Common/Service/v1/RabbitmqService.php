<?php
/**
 * User: fangcan
 * DateTime: 2023/11/4 22:24
 */

namespace App\Common\Service\V1;


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitmqService
{
    /*
     * 获取RabbitMQ连接
     */
    public static function getConnect()
    {
        $config = [
            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => env('RABBITMQ_PORT', 5672),
            'user' => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'vhost' => env('RABBITMQ_VHOST', '/'),
        ];

        return new AMQPStreamConnection($config["host"], $config["port"], $config["user"], $config["password"], $config["vhost"]);
    }

    /**
     * Notes: 推送到消息队列
     * User: fangcan
     * DateTime: 2023/11/13 16:37
     * @param string $queue_name 队列名称
     * @param string $exchange 交换机名称
     * @param string $routing_key 路由key
     * @param string $message_body 队列数据
     * @param string $type 消息类型
     * @return bool
     */
    public static function push($queue_name, $exchange, $routing_key, $message_body, $type = 'direct')
    {
        try {
            $connection = self::getConnect();
            //构建通道（mq的数据存储与获取是通过通道进行数据传输的）
            $channel = $connection->channel();
            //监听数据,成功
            $channel->set_ack_handler(function (AMQPMessage $message) {
                _logs('数据写入成功', $message, 'rabbitmq/push');
            });
            //监听数据,失败
            $channel->set_nack_handler(function (AMQPMessage $message) {
                _logs('数据写入失败', $message, 'rabbitmq/push');
            });
            //声明一个队列
            $channel->queue_declare($queue_name, false, true, false, false);
            $channel->exchange_declare($exchange, $type, false, true, false);
            $channel->queue_bind($queue_name, $exchange, $routing_key);
            $config = [
                'content_type' => 'text/plain',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ];
            $message = new AMQPMessage($message_body, $config);
            $channel->basic_publish($message, $exchange, $routing_key);
            //监听写入
            $channel->wait_for_pending_acks();
            _logs('生产者已操作', $message, 'rabbitmq/push');
            $channel->close();
            $connection->close();
            return true;
        } catch (\Exception $e) {
            errorLogs($e);
            return false;
        }
    }

    /**
     * Notes: 取出消息
     * User: fangcan
     * DateTime: 2023/11/13 16:44
     * @param string $queue_name 队列名称
     * @param callback 回调函数
     * @return bool
     */
    public static function pop($queue, $callback)
    {
        try {
            $connection = self::getConnect();
            $channel = $connection->channel();
            $message = $channel->basic_get($queue, true);
            if (empty($message)) {
                _logs('message为空', $message, 'rabbitmq/pop');
                return false;
            }
            $res = $callback($message->getBody());
            if ($res) {
                _logs('ack验证', $message, 'rabbitmq/pop');
                $channel->basic_ack($message->getDeliveryTag());
            }
            _logs('ack消费完成', $message, 'rabbitmq/pop');
            $channel->close();
            $connection->close();
            return true;
        } catch (\Exception $e) {
            errorLogs($e);
            return false;
        }
    }
}
