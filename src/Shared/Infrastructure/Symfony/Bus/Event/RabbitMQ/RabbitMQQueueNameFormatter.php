<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Symfony\Bus\Event\RabbitMQ;

use Shared\Domain\Bus\Event\DomainEventSubscriber;
use Shared\Domain\Utils;

final class RabbitMQQueueNameFormatter
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
