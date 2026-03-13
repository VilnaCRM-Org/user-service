<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\Processor\Revoker\AllSessionsRevokerInterface;

/**
 * @implements CommandHandlerInterface<SignOutAllCommand, void>
 */
final readonly class SignOutAllCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private AllSessionsRevokerInterface $allSessionsRevoker,
    ) {
    }

    public function __invoke(SignOutAllCommand $command): void
    {
        $this->allSessionsRevoker->revokeAllSessions(
            $command->userId,
            'user_initiated',
        );
    }
}
