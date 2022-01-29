<?php

namespace fgh151\tg;

use Amp\Loop;

class QueueListener extends DataBus
{
    /**
     * @var QueueListener
     */
    private $listener;

    public static function listen(): self
    {
        $l = new self();

        return $l;
    }

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @param string $queueUrl
     * @param callable|array $fn Функция, применяемая к каждому сообщению. Должна возвращать bool
     * Примеры сигнатуры вывоза
     * @see https://www.php.net/manual/ru/language.types.callable.php
     *
     * @return void
     */
    public function onMessage(string $queueUrl, $fn)
    {
        $result = $this->ymq->receiveMessage([
            'QueueUrl' => $queueUrl,
            'WaitTimeSeconds' => 10,
        ]);

        foreach ($result["Messages"] as $msg) {
            if (call_user_func($fn, $msg)) {
                $this->ymq->deleteMessage([
                    'QueueUrl' => $queueUrl,
                    'ReceiptHandle' => $msg['ReceiptHandle'],
                ]);
            }
        }
    }
}