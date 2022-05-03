<?php

declare(strict_types=1);

namespace TrainingRabbit\User\Application\CreateUser;

use Shared\Domain\Bus\Event\EventBus;
use TrainingRabbit\User\Domain\User;
use TrainingRabbit\User\Domain\UserId;
use TrainingRabbit\User\Domain\UserName;

final class CreateUser
{
    public function __construct(
        private EventBus $eventBus
    ) {}

    public function __invoke(
        UserId $userId,
        UserName $userName
    ): void {
        $user = User::create($userId, $userName);

        $this->eventBus->publish(...$user->pullDomainEvents());
    }
}
