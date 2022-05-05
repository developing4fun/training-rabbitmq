<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event;

use Shared\Domain\Event\DomainEvent;

final class DomainEventJsonSerializer
{
    public static function serialize(DomainEvent $domainEvent): string
    {
        return json_encode(
            [
                'data' => [
                    'id' => $domainEvent->eventId(),
                    'type' => $domainEvent::eventName(),
                    'occurredOn' => $domainEvent->occurredOn(),
                    'attributes'=> array_merge($domainEvent->toPrimitives(), ['id' => $domainEvent->aggregateId()]),
                ]
            ]
        );
    }
}
