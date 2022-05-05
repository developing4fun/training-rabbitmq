<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event;

use Shared\Domain\Event\DomainEventSubscriber;
use Shared\Domain\Utils;

final class MessageQueueNameFormatter
{
    public static function format(DomainEventSubscriber $subscriber): string
    {
        $subscriberClasspath = explode('\\', get_class($subscriber));
        $queueNameParts = [
            $subscriberClasspath[0],
            end($subscriberClasspath),
        ];

        return implode('.', array_map(fn(string $text) => Utils::toSnakeCase($text), $queueNameParts));
    }
}
