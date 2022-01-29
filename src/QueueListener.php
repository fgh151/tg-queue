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

    public static function listen(): DataBus
    {
        return self::getInstance();
    }

    public function run($interval = 200)
    {

        $self = $this;
        $self->pool = new DefaultPool();
        $coroutines = [];

        Loop::run(static function () use ($self, $interval, $coroutines) {


            $timer = Loop::repeat($interval, static function () use ($self, $coroutines) {
                $urls = call_user_func($self->fetchFn['fn'], $self->fetchFn['params']);


                foreach ($urls as $url) {

                    $result = $self->ymq->receiveMessage([
                        'QueueUrl' => $url,
                        'WaitTimeSeconds' => 10,
                    ]);

                    foreach ($result["Messages"] as $msg) {

//TODO: Тут обработка сообщений с вычислениями когда выпонять
//                        $coroutines[] = call(function () use ($self, $url, $msg) {
//                            if (yield $self->pool->enqueue($self->onMessageFn[$url])) {
//                                $self->ymq->deleteMessage([
//                                    'QueueUrl' => $url,
//                                    'ReceiptHandle' => $msg['ReceiptHandle'],
//                                ]);
//                            }
//                        });

                    }
                }

            });

            // TODO:  этот код надо запихнуть в https://www.php.net/manual/ru/function.pcntl-signal.php
//            Loop::unreference($timer);
//
//
//            $results = yield all($coroutines);
//
//            return yield $self->pool->shutdown();

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
}