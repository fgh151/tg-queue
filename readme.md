### Установка

```shell
composer require fgh151/tg-queue
```

### Отправка сообщения в очередь

```injectablephp

$channel = 'some_chat_id';
$payload = json_encode(['some' => 'object', 'or' => 'array']);


$result = fgh151\tg\Queue::push($channel, $payload)
```

### Обработчик очереди

```injectablephp

$l = fgh151\tg\QueueListener::getInstance();
$l->onMessage($url, $fn);

```

#### Пример обработки очереди на Code Igniter

```injectablephp
class Daemon extends CI_Controller {

    /**
    * @return string[] Массив адресов сообщений
     * Полученный при вызове функции $result = fgh151\tg\Queue::push($channel, $payload)
     * В массиве $result есть ключ 'url'
     */
    public static function getUrls(): array
    {
        return ['url1', 'url2'];
    }
    
    /**
    * @param $params Сообщение, полученное из очереди
    * @return bool
     */
    public static function handler($params): bool
    {
        var_dump($params);
        /** Тут код обработки сообщения, например отправки, сохранение в бд и т.д. */
        return true; //Если вернуть true сообщение удалиться из очереди
    }

    public function queue()
    {
        /** @see https://www.php.net/manual/ru/language.types.callable.php */
        (fgh151\tg\QueueListener::getInstance())
            ->fetchUrls(['Daemon', 'getUrls'])
            ->onMessage($url, ['Daemon', 'handler'])
            ->run();
    }
}
```

##### Запуск воркера

Запуск воркера имеет смысл делегировать на supervisor или systemd

###### Пример конфигурации Supervisor

```conf
[program:tg-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/my_project/daemon queue
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/my_project/log/daemon.log
```
###### Пример конфигурации Systemd

```conf
[Unit]
Description=Telegram queue workerr %I
After=network.target

[Service]
User=www-data
Group=www-data
ExecStart=/usr/bin/php /var/www/my_project/daemon queue
Restart=on-failure

[Install]
WantedBy=multi-user.target
```