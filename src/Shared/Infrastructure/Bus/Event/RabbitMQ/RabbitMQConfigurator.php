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
    private const RETRY_MESSAGE_TTL = 1000;

    public function __construct(
        private RabbitMQConnection $connection,
        private string $exchangeName
    ) {}

    public function configure(DomainEventSubscriber ...$subscribers): void
    {
        $retryExchangeName = RabbitMQExchangeNameFormatter::retry($this->exchangeName);
        $deadLetterExchangeName = RabbitMQExchangeNameFormatter::deadLetter($this->exchangeName);

        $this->declareExchange($this->exchangeName);
        $this->declareExchange($retryExchangeName);
        $this->declareExchange($deadLetterExchangeName);

        $this->declareQueues(
            $this->exchangeName,
            $retryExchangeName,
            $deadLetterExchangeName,
            ...$subscribers
        );
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
        string $retryExchangeName,
        string $deadLetterExchangeName,
        DomainEventSubscriber ...$subscribers
    ): void {
        array_walk(
            $subscribers,
            $this->queueDeclarer($exchangeName, $retryExchangeName, $deadLetterExchangeName)
        );
    }

    private function queueDeclarer(string $exchangeName, string $retryExchangeName, string $deadLetterExchangeName): callable
    {
        return function (DomainEventSubscriber $subscriber) use (
            $exchangeName,
            $retryExchangeName,
            $deadLetterExchangeName
        ): void {
            $queueName = MessageQueueNameFormatter::format($subscriber);
            $retryQueueName = MessageQueueNameFormatter::formatRetry($subscriber);
            $deadLetterQueueName = MessageQueueNameFormatter::formatDeadLetter($subscriber);

            $queue = $this->declareQueue($queueName);
            $retryQueue = $this->declareQueue($retryQueueName, $exchangeName, $queueName, self::RETRY_MESSAGE_TTL);
            $deadLetterQueue = $this->declareQueue($deadLetterQueueName);

            $queue->bind($exchangeName, $queueName);
            $retryQueue->bind($retryExchangeName, $queueName);
            $deadLetterQueue->bind($deadLetterExchangeName, $queueName);

            /** @var DomainEvent $eventClass */
            foreach($subscriber::subscribedTo() as $eventClass) {
                if (DomainEvent::class === $eventClass) {
                    continue;
                }

                $queue->bind($this->exchangeName, $eventClass::eventName());
            }
        };
    }

    private function declareQueue(
        string $name,
        string $destinationExchange = null,
        string $destinationRoutingKey =null,
        int $messageTTL = null
    ): AMQPQueue
    {
        $queue = $this->connection->queue($name);

        if (null !== $destinationExchange) {
            $queue->setArgument('x-dead-letter-exchange', $destinationExchange);
        }

        if (null !== $destinationRoutingKey) {
            $queue->setArgument('x-dead-letter-routing-key', $destinationRoutingKey);
        }
        
        if (null !== $messageTTL) {
            $queue->setArgument('x-message-ttl', $messageTTL);
        }

        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();

        return $queue;
    }
}
