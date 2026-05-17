<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\CompletePasskeySignInCommand;
use App\User\Application\DTO\PasskeySignInCompleteDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Validator\MutationInputValidator;

final readonly class PasskeySignInCompleteAuthMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
    ) {
    }

    /**
     * @param array<string, array<string, iterable|string>|mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'] ?? [];
        $dto = new PasskeySignInCompleteDto(
            $args['challengeId'] ?? '',
            $this->credentialFrom($args['credential'] ?? [])
        );
        $this->validator->validate($dto);

        $request = $this->httpRequestContextResolver->resolveRequest($context['request'] ?? null);
        $command = new CompletePasskeySignInCommand(
            $dto->challengeId,
            $dto->credential,
            $this->httpRequestContextResolver->resolveIpAddress($request),
            $this->httpRequestContextResolver->resolveUserAgent($request)
        );
        $this->commandBus->dispatch($command);

        return $this->authPayloadFactory->createFromPasskeyAuthenticationResult(
            'auth-passkey-signin-complete',
            $command->getResponse()
        );
    }

    /**
     * @return array<string, scalar|array|null>
     */
    private function credentialFrom(mixed $credential): array
    {
        /** @var array<string, scalar|array|null> $normalized */
        $normalized = is_array($credential) ? $credential : [];

        return $normalized;
    }
}
