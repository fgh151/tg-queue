<?php

namespace fgh151\tg;

use RuntimeException;

class Queue extends DataBus
{
    /**
     * @param string $channel канал отправки, например идентификатор чата или получателя
     * @param string $message Сообщение для отправки. Можно отправлять текст или сериализованные массив или объект
     * @return void
     */
    public function push($channel, $message)
    {
        $q = self:: getInstance();
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

    public function run()
    {
        throw new RuntimeException('Метод предназначен для прослушивания очереди');
    }

    public function onMessage(string $queueUrl, $fn): DataBus
    {
        throw new RuntimeException('Метод предназначен для прослушивания очереди');
    }

    public function fetchUrls($fn, $params): DataBus
    {
        throw new RuntimeException('Метод предназначен для прослушивания очереди');
    }
}