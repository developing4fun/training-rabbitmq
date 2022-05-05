<?php

declare(strict_types=1);

namespace Shared\Domain\Event;

interface DomainEventSubscriber
{
    public static function subscribedTo(): array;
}
