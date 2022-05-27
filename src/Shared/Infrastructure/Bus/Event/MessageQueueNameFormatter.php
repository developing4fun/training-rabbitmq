<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Event;

use ReflectionClass;
use Shared\Domain\Event\DomainEventSubscriber;
use Shared\Domain\Utils;

final class MessageQueueNameFormatter
{
    public static function format(DomainEventSubscriber $subscriber): string
    {
        $subscriberClasspath = explode('\\', get_class($subscriber));
        $queueNameParts = [
            reset($subscriberClasspath),
            end($subscriberClasspath),
        ];

        return implode('.', array_map(fn(string $text) => Utils::toSnakeCase($text), $queueNameParts));
    }

    public static function formatRetry(DomainEventSubscriber $subscriber): string
    {
        $queueName = self::format($subscriber);

        return 'retry-' . $queueName;
    }

    public static function formatDeadLetter(DomainEventSubscriber $subscriber): string
    {
        $queueName = self::format($subscriber);

        return 'dead_letter-' . $queueName;
    }

    public static function shortFormat(DomainEventSubscriber $subscriber): string
    {
        $subscriberCamelCaseName = (new ReflectionClass($subscriber))->getShortName();

        return Utils::toSnakeCase($subscriberCamelCaseName);
    }
}
