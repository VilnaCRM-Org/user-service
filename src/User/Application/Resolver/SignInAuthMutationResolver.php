<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\User\Application\DTO\SignInDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Service\SignInCommandDispatcher;
use App\User\Application\Validator\MutationInputValidator;

final readonly class SignInAuthMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private AuthPayloadFactory $authPayloadFactory,
        private SignInCommandDispatcher $signInCommandDispatcher,
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

        $response = $this->signInCommandDispatcher->dispatch($dto, $context);

        return $this->authPayloadFactory->createFromSignInResponse(
            $response
        );
    }
}
