<?php

declare(strict_types=1);

use OpenSwoole\Table;
use RdKafka\KafkaConsumer;
use OpenSwoole\Http\Request;
use OpenSwoole\Process\Pool;
use Psr\Log\LoggerInterface;
use OpenSwoole\Http\Response;
use OpenSwoole\Core\Process\Manager;

require __DIR__ . '/src/bootstrap.php';

OpenSwoole\Coroutine::set([
    'max_coroutine' => 800,
]);

$table = new Table(1024);
$table->column('count', OpenSwoole\Table::TYPE_INT, 8);
$table->create();

$table->set('messages', ['count' => 1]);

$pm = new Manager();
for ($i = 0; $i < 10; $i++) {
    $pm->add(function (Pool $pool, int $workerId) use ($container, $table) {
        $log = $container->get(LoggerInterface::class);
        $conf = $container->get('consumer_config');

        $log->info("Worker #$workerId started");

        $consumer = new KafkaConsumer($conf);

        $consumer->subscribe(['wizard_test']);
        $totalMessages = $table->get('messages', 'count') ?? 0;

        while(true) {
            $message = $consumer->consume(500);
            sleep(random_int(1, 3));
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $log->info($message->payload);
                    $table->set('messages', ['count' => $totalMessages++]);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    break;
                default:
                    throw new \Exception('KAFKA ERROR: ' . $message->errstr(), $message->err);
                    break;
            }
        }
    }, true);
}
$pm->add(function (Pool $pool, int $workerId) use ($table) {
    $server = new OpenSwoole\HTTP\Server("0.0.0.0", 9501);

    // The main HTTP server request callback event, entry point for all incoming HTTP requests
    $server->on('Request', function(Request $request, Response $response) use ($table)
    {
        $response->end('<h1>Consumed messages this run: ' . $table->get('messages', 'count') ?? 0 . '</h1>');
    });

    $server->start();
});

$pm->start();
