<?php

namespace fgh151\tg;

use Amp\Loop;
use Amp\Parallel\Worker\DefaultPool;
use Amp\Parallel\Worker\Pool;
use function Amp\call;
use function Amp\Promise\all;

class QueueListener extends DataBus
{
    private $fetchFn = [];

    private $onMessageFn = [];

    /**
     * @var QueueListener
     */
    private $listener;
    /** @var Pool | DefaultPool */
    private $pool;

    private $maxPerSecond = 3;
    private $maxPerMinute = 60;

    private $channels = [];

    /** @var Buffer[] $buffer */
    private $buffer = [];

    private $timers = [];

    public static function listen(): DataBus
    {
        return self::getInstance();
    }

    public function init()
    {
        if (extension_loaded('pcntl')) {
            parent::init();
            pcntl_signal(SIGTERM, [&$this, 'halt']);
            pcntl_signal(SIGHUP, [&$this, 'halt']);
            pcntl_signal(SIGUSR1, [&$this, 'halt']);
        }
    }

    public function __destruct()
    {
        $this->halt();
    }

    /**
     * Обработка системных вызовов на перезапуск и останов демона
     * @return void
     */
    private function halt()
    {
        foreach ($this->timers as $timer) {
            Loop::unreference($timer);
        }

        $this->pool->shutdown();
    }

    /**
     * @param $interval int интервал времени в млсек, через который проверять наличие новых каналов
     * @return void
     */
    public function run($interval = 1000)
    {

        $self = $this;
        $self->pool = new DefaultPool();

        Loop::run(static function () use ($self, $interval) {

            // заполняем массив каналов, выставляем флаг нужно ли обновлять корутины
            $self->timers[] = Loop::repeat($interval, static function () use ($self, &$refreshCoroutines) {
                $buf = call_user_func($self->fetchFn['fn'], $self->fetchFn['params']);
                if ($buf !== $self->channels) {
                    $self->channels = $buf;
                    $refreshCoroutines = true;
                }
            });

            // Получаем сообщения и отправляем в буфер
            $self->timers[] = Loop::repeat($interval / 10, static function () use ($self) {

                foreach ($self->channels as $url) {

                    if (false === isset($self->buffer[$url])) {
                        $self->buffer[$url] = new Buffer($self->maxPerSecond, $self->maxPerMinute);
                    }

                    $result = $self->ymq->receiveMessage([
                        'QueueUrl' => $url,
                        'WaitTimeSeconds' => 10,
                    ]);

                    foreach ($result["Messages"] as $msg) {

                        $self->buffer[$url]->addMessage($msg['Body']);
                    }
                }
            });

            // читаем и обрабатываем буфер
            $self->timers[] = Loop::repeat($interval / 10, static function () use ($self) {
                foreach ($self->channels as $url) {
                    $processMsg = $self->buffer[$url]->pop();

                    call(function () use ($self, $url, $processMsg) {
                        if (yield $self->pool->enqueue($self->onMessageFn[$processMsg])) {
                            $self->ymq->deleteMessage([
                                'QueueUrl' => $url,
                                'ReceiptHandle' => $processMsg['ReceiptHandle'],
                            ]);
                        }
                    });
                }
            });
        });
    }

    public function fetchUrls($fn, $params): DataBus
    {
        $this->fetchFn = ['fn' => $fn, 'params' => $params];
        return $this;
    }

    /**
     * @param string $queueUrl
     * @param callable|array $fn Функция, применяемая к каждому сообщению. Должна возвращать bool
     * Примеры сигнатуры вывоза
     * @return void
     * @see https://www.php.net/manual/ru/language.types.callable.php
     *
     */
    public function onMessage(string $queueUrl, $fn): DataBus
    {
        $this->onMessageFn[$queueUrl] = $fn;
        return $this;
    }

    /**
     * @param int $maxPerSecond
     */
    public function setMaxPerSecond(int $maxPerSecond = 3): QueueListener
    {
        $this->maxPerSecond = $maxPerSecond;
        return $this;
    }

    /**
     * @param int $maxPerMinute
     */
    public function setMaxPerMinute(int $maxPerMinute = 60): QueueListener
    {
        $this->maxPerMinute = $maxPerMinute;
        return $this;
    }
}