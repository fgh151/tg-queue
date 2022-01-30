<?php

namespace fgh151\tg;

use Exception;

abstract class DataBus
{
    protected static $instances = [];
    /** @var QueueClientInterface | AwsClient */
    protected $ymq;

    protected function __construct($client = AwsClient::class, $clientParams = null)
    {
        $this->ymq = new $client($clientParams);
        $this->init();
    }

    abstract public function run();
    abstract public function onMessage(string $queueUrl, $fn): DataBus;
    abstract public function fetchUrls($fn, $params): DataBus;

    public static function getInstance(): DataBus
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }

    public function setClient($client)
    {
        $this->ymq = $client;
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }

    protected function __clone()
    {
    }

    public function init(){}
}