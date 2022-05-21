<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event;

use Shared\Domain\Event\DomainEvent;
use Shared\Domain\Utils;
use Shared\Infrastructure\Bus\Event\RabbitMQ\DomainEventMapping;

final class DomainEventJsonDeserializer
{
    public function __construct(
        private DomainEventMapping $mapping
    ) {}

    public function deserialize(string $domainEvent): DomainEvent
    {
        $eventData = Utils::jsonDecode($domainEvent);
        $eventName = $eventData['data']['type'];
        $eventClass = $this->mapping->for($eventName);

        /** @var DomainEvent $eventClass */
        return $eventClass::fromPrimitives(
            $eventData['data']['attributes']['id'],
            $eventData['data']['attributes'],
            $eventData['data']['id'],
            $eventData['data']['occurredOn'],
        );
    }
}
