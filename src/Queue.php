<?php

namespace fgh151\tg;


use Aws\Sqs\SqsClient;


class Queue
{

    private $ymq;

    private function __construct()
    {
        $this->ymq = new SqsClient([
            'version' => 'latest',
            'region' => 'ru-central1',
            'endpoint' => 'https://message-queue.api.cloud.yandex.net',
        ]);
    }

    public static function push($channel, $message)
    {
        $q = new self();

        $q->sendMessage($channel, $message);
    }

    public function sendMessage($channel, $message)
    {
        $result = $this->ymq->listQueues(['MaxResults' => 100]);
        if (false === $result->hasKey($channel)) {
            $result = $this->ymq->createQueue([
                'QueueName' => $channel,
            ]);
        }
        $queueUrl = $result["QueueUrl"];

        return $this->ymq->sendMessage([
            'QueueUrl' => $queueUrl,
            'MessageBody' => $message,
        ]);
    }


}