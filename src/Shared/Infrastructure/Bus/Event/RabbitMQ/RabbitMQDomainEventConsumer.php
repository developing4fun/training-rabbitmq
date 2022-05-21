<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event\RabbitMQ;

use AMQPEnvelope;
use AMQPQueue;
use Shared\Domain\Event\DomainEventSubscriber;
use Shared\Infrastructure\Bus\Event\DomainEventJsonDeserializer;
use Throwable;

final class RabbitMQDomainEventConsumer implements DomainEventConsumer
{
    public function __construct(
        private RabbitMQConnection $connection,
        private DomainEventJsonDeserializer $deserializer,
        private string $exchangeName,
        private int $maxRetries
    ) {}

    public function consume(
        DomainEventSubscriber $subscriber,
        string $queueName
    ): void {
        $this->connection
            ->queue($queueName)
            ->consume(
                $this->consumer($subscriber)
            );
    }

    private function consumer(DomainEventSubscriber $subscriber): callable
    {
        return function(AMQPEnvelope $envelope, AMQPQueue $queue) use ($subscriber): bool {

            $domainEvent = $this->deserializer->deserialize($envelope->getBody());

            try {
                $subscriber->__invoke($domainEvent);
            } catch (Throwable $error) {
                // TODO: handle retries
                throw $error;
            }

            $queue->ack($envelope->getDeliveryTag());
            
            return false;
        };
    }
}
