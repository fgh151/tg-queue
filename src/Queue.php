<?php

namespace fgh151\tg;

class Queue
{

    /** @var QueueClientInterface | AwsClient */
    private $ymq;

    private function __construct($client = AwsClient::class, $clientParams = null)
    {
        $this->ymq = new $client($clientParams);
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