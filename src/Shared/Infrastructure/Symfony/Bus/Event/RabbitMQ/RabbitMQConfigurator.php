<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Symfony\Bus\Event\RabbitMQ;

use AMQPQueue;
use Shared\Domain\Bus\Event\DomainEvent;
use Shared\Domain\Bus\Event\DomainEventSubscriber;

final class RabbitMQConfigurator
{
    public function __construct(
        private RabbitMQConnection $connection
    ) {}

    public function configure(string $exchangeName, DomainEventSubscriber ...$subscribers): void
    {
        $this->declareExchange($exchangeName);
        $this->declareQueues($exchangeName, ...$subscribers);
    }

    private function declareExchange(string $exchangeName): void
    {
        $exchange = $this->connection->exchange($exchangeName);
        $exchange->setType(AMQP_EX_TYPE_TOPIC);
        $exchange->setFlags(AMQP_DURABLE);
        $exchange->declareExchange();
    }

    private function declareQueues(
        string $exchangeName,
        DomainEventSubscriber ...$subscribers
    ): void {
        array_walk($subscribers, $this->queueDeclarer($exchangeName));
    }

    private function queueDeclarer(
        string $exchangeName
    ): callable {
        return function (DomainEventSubscriber $subscriber) use (
            $exchangeName
        ) {
            $queueName = RabbitMQQueueNameFormatter::format($subscriber);
            $queue = $this->declareQueue($queueName);

            $queue->bind($exchangeName, $queueName);

            /** @var DomainEvent $eventClass */
            foreach($subscriber::subscribedTo() as $eventClass) {
                $queue->bind($exchangeName, $eventClass::eventName());
            }
        };
    }

    private function declareQueue(
        string $name,
        int $messageTTL = null
    ): AMQPQueue
    {
        $queue = $this->connection->queue($name);
        
        if (null !== $messageTTL) {
            $queue->setArgument('x-message-ttl', $messageTTL);
        }

        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();

        return $queue;
    }
}
