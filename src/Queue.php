<?php

namespace fgh151\tg;

class Queue extends DataBus
{

    public static function push($channel, $message)
    {
        $q = new self();
        $q->sendMessage($channel, $message);
    }

    /**
     *
     * @param $channel
     * @param $message
     * @return array ['url' => 'queueUrl', 'result' => $r]
     * Детальную информацию о результате можно посмотреть
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sqs-2012-11-05.html#sendmessage
     */
    public function sendMessage($channel, $message)
    {
        $result = $this->ymq->listQueues(['MaxResults' => 100]);
        if (false === $result->hasKey($channel)) {
            $result = $this->ymq->createQueue([
                'QueueName' => $channel,
            ]);
        }
        $queueUrl = $result["QueueUrl"];

        return [
            'url' => $queueUrl,
            'result' => $this->ymq->sendMessage([
                'QueueUrl' => $queueUrl,
                'MessageBody' => $message,
            ])
        ];
    }
}