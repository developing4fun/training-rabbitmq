<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event\RabbitMQ;

use RuntimeException;
use Shared\Domain\Event\DomainEventSubscriber;
use Shared\Infrastructure\Bus\Event\DomainEventSubscriberLocator;
use Shared\Infrastructure\Bus\Event\MessageQueueNameFormatter;
use Traversable;
use function iterator_to_array;
use function sprintf;

final class RabbitMQDomainEventSubscriberLocator implements DomainEventSubscriberLocator
{
    private array $mapping;

    public function __construct(Traversable $mapping)
    {
        $this->mapping = iterator_to_array($mapping);
    }

    public function withQueueName(string $queueName): DomainEventSubscriber
    {
        foreach ($this->mapping as $subscriber) {
            if (MessageQueueNameFormatter::format($subscriber) === $queueName) {
                return $subscriber;
            }
        }

        throw new RuntimeException(
            sprintf(
                'There are no subscribers for the queue: %s',
                $queueName
            )
        );
    }
    
    public function all(): array
    {
        return $this->mapping;
    }
}
