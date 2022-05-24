<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event\RabbitMQ;

use AMQPChannel;
use AMQPConnection;
use AMQPExchange;
use AMQPQueue;

final class RabbitMQConnection
{
    private static ?AMQPConnection $connection = null;
    private static ?AMQPChannel $channel = null;
    /** @var AMQPExchange[] */
    private static array $exchanges = [];
    /** @var AMQPQueue[] */
    private static array $queues = [];

    public function __construct(
        private array $configuration
    ) {}

    public function exchange(string $name): AMQPExchange
    {
        if (empty(self::$exchanges[$name])) {
            $exchange = new AMQPExchange($this->channel());
            $exchange->setName($name);
            self::$exchanges[$name] = $exchange;
        }

        return self::$exchanges[$name];
    }

    public function queue(string $name): AMQPQueue
    {
        if (empty(self::$queues[$name])) {
            $queue = new AMQPQueue($this->channel());
            $queue->setName($name);
            self::$queues[$name] = $queue;
        }

        return self::$queues[$name];
    }

    private function channel(): AMQPChannel
    {
        if (!self::$channel?->isConnected()) {
            self::$channel = new AMQPChannel($this->connection());
        }

        return self::$channel;
    }

    private function connection(): AMQPConnection
    {
        if (null === self::$connection) {
            self::$connection = new AMQPConnection($this->configuration);
        }

        if (!self::$connection->isConnected()) {
            self::$connection->pconnect();
        }

        return self::$connection;
    }
}
