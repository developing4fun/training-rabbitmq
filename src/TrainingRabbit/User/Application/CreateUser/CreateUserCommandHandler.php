<?php

declare(strict_types=1);

namespace TrainingRabbit\User\Application\CreateUser;

use Shared\Application\Bus\Command\CommandHandler;
use TrainingRabbit\User\Domain\UserId;
use TrainingRabbit\User\Domain\UserName;

final class CreateUserCommandHandler implements CommandHandler
{
    public function __construct(
        private CreateUser $createUser
    ) {}

    public function __invoke(CreateUserCommand $command): void
    {
        $this->createUser->__invoke(
            new UserId($command->id()),
            new UserName($command->name())
        );
    }
}
