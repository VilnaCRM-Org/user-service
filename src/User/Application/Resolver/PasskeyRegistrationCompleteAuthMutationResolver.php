<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\CompletePasskeyRegistrationCommand;
use App\User\Application\DTO\PasskeyRegistrationCompleteDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Validator\MutationInputValidator;

final readonly class PasskeyRegistrationCompleteAuthMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
        private CurrentUserIdentityResolver $userIdentityResolver,
    ) {
    }

    /**
     * @param array<string, array<string, iterable|string>|mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'] ?? [];
        $dto = new PasskeyRegistrationCompleteDto(
            $args['challengeId'] ?? '',
            $this->credentialFrom($args['credential'] ?? []),
            $args['label'] ?? ''
        );
        $this->validator->validate($dto);

        $command = new CompletePasskeyRegistrationCommand(
            $dto->challengeId,
            $dto->credential,
            $dto->label,
            $this->userIdentityResolver->resolveUserId()
        );
        $this->commandBus->dispatch($command);

        return $this->authPayloadFactory->createFromPasskeyCredentialId(
            $command->getResponse()->getCredentialId()
        );
    }

    /**
     * @return array<string, scalar|array|null>
     */
    private function credentialFrom(mixed $credential): array
    {
        if (!is_array($credential)) {
            return [];
        }

        /** @psalm-var array<string, scalar|array|null> $credential */
        return $credential;
    }
}
