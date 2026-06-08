<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\CompletePasskeySignUpCommand;
use App\User\Application\DTO\PasskeySignUpCompleteDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Validator\MutationInputValidator;

final readonly class PasskeySignUpCompleteAuthMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
    ) {
    }

    /**
     * @param array<string, array<string, bool|iterable|string>|mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'] ?? [];
        $dto = $this->createDto($args);
        $this->validator->validate($dto);

        $request = $this->httpRequestContextResolver->resolveRequest($context['request'] ?? null);
        $command = new CompletePasskeySignUpCommand(
            $dto->challengeId,
            $dto->credential,
            $dto->label,
            $dto->isRememberMe(),
            $this->httpRequestContextResolver->resolveIpAddress($request),
            $this->httpRequestContextResolver->resolveUserAgent($request)
        );
        $this->commandBus->dispatch($command);

        return $this->authPayloadFactory->createFromPasskeyAuthenticationResult(
            'auth-passkey-signup-complete',
            $command->getResponse()
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

    /**
     * @param array<string, bool|iterable|string> $args
     */
    private function createDto(array $args): PasskeySignUpCompleteDto
    {
        $dto = new PasskeySignUpCompleteDto(
            $args['challengeId'] ?? '',
            $this->credentialFrom($args['credential'] ?? []),
            $args['label'] ?? ''
        );
        $dto->setRememberMe((bool) ($args['rememberMe'] ?? false));

        return $dto;
    }
}
