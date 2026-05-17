<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\User\Application\DTO\CompleteTwoFactorDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Service\CompleteTwoFactorCommandDispatcher;
use App\User\Application\Validator\MutationInputValidator;

final readonly class CompleteTwoFactorAuthMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private AuthPayloadFactory $authPayloadFactory,
        private CompleteTwoFactorCommandDispatcher $commandDispatcher,
    ) {
    }

    /**
     * @param array<string, array<string, string>|mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'] ?? [];
        $dto = new CompleteTwoFactorDto(
            $args['pendingSessionId'] ?? '',
            $args['twoFactorCode'] ?? ''
        );
        $this->validator->validate($dto);

        $response = $this->commandDispatcher->dispatch($dto, $context);

        return $this->authPayloadFactory->createFromCompleteTwoFactorResponse(
            $response
        );
    }
}
