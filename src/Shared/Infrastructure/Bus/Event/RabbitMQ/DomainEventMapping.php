<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event\RabbitMQ;

use RuntimeException;
use Shared\Domain\Event\DomainEvent;
use Shared\Domain\Event\DomainEventSubscriber;
use Traversable;
use function iterator_to_array;

final class DomainEventMapping
{
    private array $mapping;
    
    public function __construct(
        Traversable $subscribers
    ) {
        $subscribers = iterator_to_array($subscribers); 
        
        $this->mapping = $this->eventsExtractor($subscribers);
    }

    public function for(string $name): string
    {
        if (!isset($this->mapping[$name])) {
            throw new RuntimeException("The Domain Event Class for <$name> does not exist.");
        }

        return $this->mapping[$name];
    }
    
    private function eventsExtractor(array $subscribers): array
    {
        $domainEvents = [];

        /** @var DomainEventSubscriber $subscriber */
        foreach ($subscribers as $subscriber) {

            /** @var DomainEvent $domainEvent */
            foreach ($subscriber::subscribedTo() as $domainEvent) {
                $domainEvents[$domainEvent::eventName()] = $domainEvent;
            }
        }

        return $domainEvents;
    }
}
