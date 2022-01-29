<?php

namespace fgh151\tg;

use Aws\Sqs\SqsClient;

/**
 * @see SqsClient
 */
interface QueueClientInterface
{
    public function __construct($params = null);

    public function listQueues($params = []);

    public function sendMessage($params = []);

    public function createQueue($params = []);
}