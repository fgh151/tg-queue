<?php

namespace fgh151\tg;

class Buffer
{
    /** @var array  */
    private $messages = [];
    /**
     * Массив с милисекундами- временем отправки
     * @var array
     */
    private $history = [];
    /**
     * @var float|int
     */
    private $diffSec;
    /**
     * @var int|mixed
     */
    private $maxPerMinute;


    public function __construct($maxPerSecond = 60, $maxPerMinute = 60)
    {
        $this->maxPerMinute = $maxPerMinute;
        $this->diffSec = 1000 / $maxPerSecond;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function addMessage($message)
    {
        if (false === in_array($message, $this->messages, true)) {
            $this->messages[] = $message;
        }
    }

    public function pop()
    {
        if ($this->canPop()) {
            if (count($this->history) >= $this->maxPerMinute) {
                array_pop($this->history);
            }
            return array_pop($this->messages);
        }

        return null;
    }

    private function canPop(): bool
    {
        $now = $this->time();
        $last = max($this->history);

        if ($last === false) {
            return true;
        }

        $bySecond = $now - $last >= $this->diffSec;
        $byMinute = count($this->history) < $this->maxPerMinute ?? (max($this->history) - min($this->history) > 1000 * 60);

        return $bySecond && $byMinute;
    }

    private function time()
    {
        return round(microtime(true) * 1000);
    }
}