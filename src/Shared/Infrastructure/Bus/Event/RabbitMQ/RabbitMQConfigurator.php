<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event\RabbitMQ;

use AMQPQueue;
use Shared\Domain\Event\DomainEvent;
use Shared\Domain\Event\DomainEventSubscriber;
use Shared\Infrastructure\Bus\Event\MessageQueueConfigurator;
use Shared\Infrastructure\Bus\Event\MessageQueueNameFormatter;

final class RabbitMQConfigurator implements MessageQueueConfigurator
{
    public function __construct(
        private RabbitMQConnection $connection,
        private string $exchangeName
    ) {}

    public function configure(DomainEventSubscriber ...$subscribers): void
    {
        $this->declareExchange();
        $this->declareQueues(...$subscribers);
    }

    private function declareExchange(): void
    {
        $exchange = $this->connection->exchange($this->exchangeName);
        $exchange->setType(AMQP_EX_TYPE_TOPIC);
        $exchange->setFlags(AMQP_DURABLE);
        $exchange->declareExchange();
    }

    private function declareQueues(DomainEventSubscriber ...$subscribers): void
    {
        array_walk($subscribers, $this->queueDeclarer());
    }

    private function queueDeclarer(): callable
    {
        return function (DomainEventSubscriber $subscriber)
        {
            $queueName = MessageQueueNameFormatter::format($subscriber);
            $queue = $this->declareQueue($queueName);

            $queue->bind($this->exchangeName, $queueName);

            /** @var DomainEvent $eventClass */
            foreach($subscriber::subscribedTo() as $eventClass) {
                $queue->bind($this->exchangeName, $eventClass::eventName());
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
