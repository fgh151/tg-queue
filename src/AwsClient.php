<?php

namespace fgh151\tg;

use Aws\Sqs\SqsClient;

class AwsClient implements QueueClientInterface
{

    /**
     * @var SqsClient
     */
    private $client;

    public function __construct($params = null)
    {
        if ($params === null) {
            $params = [
                'version' => 'latest',
                'region' => 'ru-central1',
                'endpoint' => 'https://message-queue.api.cloud.yandex.net',
            ];
        }

        $this->client = new SqsClient($params);
    }

    public function listQueues($params = [])
    {
        return $this->client->listQueues($params);
    }

    public function sendMessage($params = [])
    {
        return $this->client->sendMessage($params);
    }

    public function createQueue($params = [])
    {
        return $this->client->createQueue($params);
    }
}