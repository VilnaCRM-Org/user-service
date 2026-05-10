<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Application\Bus\Command\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\SignInCommandResponse;
use App\User\Application\DTO\SignInDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Factory\SignInCommandFactoryInterface;
use App\User\Application\Validator\MutationInputValidator;

final readonly class SignInAuthMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
        private SignInCommandFactoryInterface $signInCommandFactory,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
    ) {
    }

    /**
     * @param array<string, array<string, bool|string>|mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'] ?? [];

        $dto = new SignInDto(
            $args['email'] ?? '',
            $args['password'] ?? ''
        );
        $dto->setRememberMe((bool) ($args['rememberMe'] ?? false));
        $this->validator->validate($dto);

        $response = $this->dispatchCommand($dto, $context);

        return $this->authPayloadFactory->createFromSignInResponse(
            $response
        );
    }

    /**
     * @param array{request?: object|null, ...} $context
     */
    private function dispatchCommand(
        SignInDto $dto,
        array $context
    ): SignInCommandResponse {
        $request = $this->httpRequestContextResolver->resolveRequest($context['request'] ?? null);
        $command = $this->signInCommandFactory->create(
            $dto->emailValue(),
            $dto->passwordValue(),
            $dto->isRememberMe(),
            $this->httpRequestContextResolver->resolveIpAddress($request),
            $this->httpRequestContextResolver->resolveUserAgent($request)
        );

        return (new CommandResponseTypeGuard())->expect(
            $this->commandBus->dispatch($command),
            SignInCommandResponse::class
        );
    }
}
