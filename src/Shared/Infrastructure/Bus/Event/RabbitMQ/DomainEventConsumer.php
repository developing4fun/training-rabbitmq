<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event\RabbitMQ;

use Shared\Domain\Event\DomainEventSubscriber;

interface DomainEventConsumer
{
    public function consume(DomainEventSubscriber $subscriber, string $queueName): void;
}
