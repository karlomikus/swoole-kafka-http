<?php

declare(strict_types=1);

use RdKafka\Conf;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Monolog\Handler\ErrorLogHandler;

require __DIR__ . '/../vendor/autoload.php';

$builder = new DI\ContainerBuilder();

$builder->addDefinitions([
    LoggerInterface::class => function () {
        $log = new Logger('name');
        $log->pushHandler(new ErrorLogHandler());

        return $log;
    },
    'consumer_config' => function () {
        $conf = new Conf();
        // $conf->set('log_level', (string) LOG_DEBUG);
        // $conf->set('debug', 'all');
        $conf->set('group.id', 'swooleConsumerDemo');
        $conf->set('metadata.broker.list', 'kafka:9092');
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.partition.eof', 'true');

        return $conf;
    },
]);

$container = $builder->build();
