### Отправка сообщения в очередь

```php

$channel = 'some_chat_id';
$payload = json_encode(['some' => 'object', 'or' => 'array']);


fgh151\tg\Queue::push($channel, $payload)
```