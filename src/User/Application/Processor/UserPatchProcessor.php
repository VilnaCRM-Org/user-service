<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Application\Provider\Http\JsonRequestPayloadProvider;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\UserPatchDto;
use App\User\Application\DTO\UserPutDto;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Application\Resolver\UserPatchUpdateResolver;
use App\User\Application\Validator\UserPatchPayloadValidator;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\ValueObject\UserUpdate;

/**
 * @implements ProcessorInterface<UserPutDto, User>
 */
final readonly class UserPatchProcessor implements ProcessorInterface
{
    private const INVALID_JSON_MESSAGE = 'Invalid JSON body.';

    public function __construct(
        private CommandBusInterface $commandBus,
        private UpdateUserCommandFactoryInterface $updateUserCommandFactory,
        private GetUserQueryHandler $getUserQueryHandler,
        private JsonRequestPayloadProvider $payloadProvider,
        private UserPatchUpdateResolver $updateResolver,
        private UserPatchPayloadValidator $payloadValidator
    ) {
    }

    /**
     * @param UserPatchDto $data
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
        $payload = $this->payloadProvider->getPayload(
            self::INVALID_JSON_MESSAGE
        );

        $this->payloadValidator->ensureNoExplicitNulls($payload);

        $user = $this->getUserQueryHandler->handle($uriVariables['id']);
        $update = $this->updateResolver->resolve($data, $user, $payload);

        $this->dispatchCommand($user, $update);

        return $user;
    }

    private function dispatchCommand(
        UserInterface $user,
        UserUpdate $update
    ): void {
        $this->commandBus->dispatch(
            $this->updateUserCommandFactory->create(
                $user,
                $update
            )
        );
    }
}
