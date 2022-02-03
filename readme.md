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
    * @return string[] Массив адресов каналов
     * Полученный при вызове функции $result = fgh151\tg\Queue::push($channel, $payload)
     * В массиве $result есть ключ 'url'
     */
    public static function getUrls(): array
    {
        // тк количество каналов будет изменяться не часто, целесообразно отдавать результат из кеша
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
            ->setMaxPerSecond(3) //Максимальное количество сообщений в секунду. По умолчанию 3, можно не вызывать
            ->setMaxPerMinute(60) //Максимальное количество сообщений в минуту. По умолчанию 60, можно не вызывать
            ->run(100); // Интервал времени в млсек, через который проверять наличие новых каналов, можно не указывать, по умолчанию 1000 млс (1 сек)
    }
}
```

##### Изменение конфигурации подключения

```injectablephp

options = [];

$client = new \fgh151\tg\AwsClient(options);

(fgh151\tg\QueueListener::getInstance())
->setClient($client);
```

Массив $options конфигурирует подключение. Параметры массива можно посмотреть [тут](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_configuration.html)

В данном случае можно не использовать переменные окружения для ключей доступа:

```injectablephp
$options = [
    'version'     => 'latest',
    'region'      => 'us-west-2',
    'credentials' => [
        'key'    => 'my-access-key-id',
        'secret' => 'my-secret-access-key',
    ],
];
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