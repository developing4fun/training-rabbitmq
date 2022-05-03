<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Symfony\Bus\Event\RabbitMQ;

use Shared\Domain\Bus\Event\DomainEvent;
use Shared\Domain\Bus\Event\EventBus;
use Shared\Infrastructure\Symfony\Bus\Event\DomainEventJsonSerializer;

final class RabbitMQEventBus implements EventBus
{
    public function __construct(
        private RabbitMQConnection $connection,
        private string $exchangeName
    ) {}

    public function publish(DomainEvent ...$events): void
    {
        array_walk($events, $this->publisher());
    }

    private function publisher(): callable
    {
        return function (DomainEvent $event) {
            $this->publishEvent($event);
        };
    }

    private function publishEvent(DomainEvent $event): void
    {
        $this->connection->exchange($this->exchangeName)->publish(
            DomainEventJsonSerializer::serialize($event),
            $event->eventName(),
            AMQP_NOPARAM,
            [
                'message_id' => $event->eventId(),
                'content-type' => 'application/json',
                'content-encoding' => 'utf-8',
            ]
        );
    }
}
