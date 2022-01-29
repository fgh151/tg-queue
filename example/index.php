<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;

$ymq = new Aws\Sqs\SqsClient([
    'version' => 'latest',
    'region' => 'ru-central1',
    'endpoint' => 'https://message-queue.api.cloud.yandex.net',
]);

$result = $ymq->createQueue([
    'QueueName' => 'ymq_php_sdk_example',
]);

$queueUrl = $result["QueueUrl"];
print('Queue created, URL: ' . $queueUrl . PHP_EOL);

$result = $ymq->sendMessage([
    'QueueUrl' => $queueUrl,
    'MessageBody' => 'Test message',
]);

print("Message sent, ID: " . $result["MessageId"] . PHP_EOL);

$result = $ymq->receiveMessage([
    'QueueUrl' => $queueUrl,
    'WaitTimeSeconds' => 10,
]);

foreach ($result["Messages"] as $msg) {
    print('Message received:' . PHP_EOL);
    print('ID: ' . $msg['MessageId'] . PHP_EOL);
    print('Body: ' . $msg['Body'] . PHP_EOL);

    $ymq->deleteMessage([
        'QueueUrl' => $queueUrl,
        'ReceiptHandle' => $msg['ReceiptHandle'],
    ]);
}

$result = $ymq->deleteQueue([
    'QueueUrl' => $queueUrl,
]);

print('Queue deleted' . PHP_EOL);