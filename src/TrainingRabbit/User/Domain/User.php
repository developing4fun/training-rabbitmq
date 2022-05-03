<?php

declare(strict_types=1);

namespace TrainingRabbit\User\Domain;

use Shared\Domain\Aggregate\AggregateRoot;
use TrainingRabbit\User\Domain\Event\UserCreated;

final class User extends AggregateRoot
{
    public function __construct(
        private UserId $userId,
        private UserName $userName
    ) {
    }

    public static function create(
        UserId $userId,
        UserName $userName
    ): self {
        $user = new self(
            $userId,
            $userName
        );

        $user->record(UserCreated::create($user));

        return $user;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function userName(): UserName
    {
        return $this->userName;
    }
}
