<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use RdKafka\Conf;
use RdKafka\Producer;

$conf = new Conf();
$conf->set('log_level', (string) LOG_DEBUG);
$conf->set('debug', 'all');
$rk = new Producer($conf);
$rk->addBrokers("kafka:9092");

$topic = $rk->newTopic("wizard_test");

foreach (range(1, 100) as $i) {
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, "Message payload" . $i);
}

$rk->flush(60);