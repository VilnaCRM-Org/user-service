<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\RefreshTokenDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Factory\RefreshTokenCommandFactoryInterface;
use App\User\Application\Validator\MutationInputValidator;

final readonly class RefreshTokenAuthMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
        private RefreshTokenCommandFactoryInterface $refreshTokenCommandFactory,
    ) {
    }

    /**
     * @param array<string, array<string, string>|mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'] ?? [];
        $dto = new RefreshTokenDto($args['refreshToken'] ?? '');
        $this->validator->validate($dto);

        $command = $this->refreshTokenCommandFactory->create(
            $dto->refreshTokenValue()
        );
        $this->commandBus->dispatch($command);

        return $this->authPayloadFactory->createFromRefreshTokenResponse(
            $command->getResponse()
        );
    }
}
