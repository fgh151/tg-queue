<?php

namespace fgh151\tg;

abstract class DataBus
{
    /** @var QueueClientInterface | AwsClient */
    protected $ymq;

    protected function __construct($client = AwsClient::class, $clientParams = null)
    {
        $this->ymq = new $client($clientParams);
        $this->init();
    }

    /**
     * Инициализация дополнительных данных после конструктора
     * @return void
     */
    public function init()
    {

    }
}