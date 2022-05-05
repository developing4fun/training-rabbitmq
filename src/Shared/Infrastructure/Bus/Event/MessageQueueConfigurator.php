<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event;

use Shared\Domain\Event\DomainEventSubscriber;

interface MessageQueueConfigurator
{
    public function configure(DomainEventSubscriber ...$subscribers) :void;
}
