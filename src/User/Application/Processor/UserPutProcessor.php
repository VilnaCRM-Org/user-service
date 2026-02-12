<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\UserPutDto;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserUpdate;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<UserPutDto, User>
 *
 * @infection-ignore-all
 */
final readonly class UserPutProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private UpdateUserCommandFactoryInterface $updateUserCommandFactory,
        private GetUserQueryHandler $getUserQueryHandler,
        private Security $security
    ) {
    }

    /**
     * @param UserPutDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): User {
        $user = $this->getUserQueryHandler->handle($uriVariables['id']);

        $this->commandBus->dispatch(
            $this->updateUserCommandFactory->create(
                $user,
                new UserUpdate(
                    $data->email,
                    $data->initials,
                    $data->newPassword,
                    $data->oldPassword
                ),
                $this->resolveCurrentSessionId()
            )
        );

        return $user;
    }

    private function resolveCurrentSessionId(): string
    {
        $token = $this->security->getToken();
        if ($token === null) {
            return '';
        }

        $sid = $token->getAttribute('sid');

        return is_string($sid) ? $sid : '';
    }
}
