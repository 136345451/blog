<?php
/**
 * User: fangcan
 * DateTime: 2023/11/21 15:44
 */

namespace App\Common\Service\V1;

use Kafka;

class KafkaService
{
    /*
     * Produce
     */
    public static function Producer($topic, $value)
    {
        $config = Kafka\ProducerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(10000);
        $config->setMetadataBrokerList(env('KAFKA_BROKERS'));
        $config->setBrokerVersion('1.0.0');
        $config->setRequiredAck(1);
        $config->setIsAsyn(false);
        $config->setProduceInterval(500);
        $producer = new Kafka\Producer(function () use ($value, $topic) {
            return [
                [
                    'topic' => $topic,
                    'value' => $value,
                    'key' => '',
                ],
            ];
        });
        $producer->success(function ($result) use ($topic, $value) {
            _logs($topic . '添加完成', ['res' => $result, 'message' => $value], 'kafka/producer', 'logs', 1);
        });
        $producer->error(function ($errorCode) use ($topic, $value) {
            _logs($topic . '添加失败', ['message' => $value, 'errorCode' => $errorCode], 'kafka/producer', 'logs', 1);
        });
        $producer->send(true);
    }

    /*
     * Consumer
     */
    public static function consumer($topics, $callback)
    {
        $config = Kafka\ConsumerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(500);
        $config->setMetadataBrokerList(env('KAFKA_BROKERS'));
        $config->setGroupId(env('KAFKA_GROUP'));
        $config->setBrokerVersion('1.0.0');
        $config->setTopics([$topics]);
        $config->setOffsetReset('earliest');
        $consumer = new Kafka\Consumer();
//        $consumer->start(function($topic, $part, $message) {
//            echo "receive a message:".$message['message']['value']."\n";
//            app(\App\Jobs\RecordLogsProduct::class)->consumerData($message['message']['value']);//你的接收处理逻辑
//            file_put_contents("consumer.log",$message['message']['value']);
//        });
        $consumer->start(function ($topic, $part, $message) use ($callback) {
            $res = $callback($message['message']['value']);
            _logs('消费完成', ['res' => $res, 'message' => $message], 'kafka/consumer', 'logs', 1);
        });
    }
}
