<?php

declare(strict_types=1);

namespace TrainingRabbit\User\Application\GenerateCoupon;

use Shared\Domain\Bus\Event\DomainEventSubscriber;
use TrainingRabbit\User\Domain\Event\UserCreated;
use function file_put_contents;
use const FILE_APPEND;

final class GenerateCouponOnUserCreated implements DomainEventSubscriber
{
    public static function subscribedTo(): array
    {
        return [
            UserCreated::class,
        ];
    }

    public function __invoke(UserCreated $event): void
    {
        file_put_contents('/tmp/'.__CLASS__, $event->userName()." at ". $event->occurredOn()."\n", FILE_APPEND);
    }
}
