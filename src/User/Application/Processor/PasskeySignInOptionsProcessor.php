<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\DTO\PasskeySignInOptionsDto;
use App\User\Application\Passkey\PasskeyAuthenticationServiceInterface;
use App\User\Application\Passkey\PasskeyResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<PasskeySignInOptionsDto, Response>
 *
 * @psalm-api
 */
final readonly class PasskeySignInOptionsProcessor implements ProcessorInterface
{
    public function __construct(
        private PasskeyAuthenticationServiceInterface $authenticationService,
        private PasskeyResponseFactory $responseFactory
    ) {
    }

    /**
     * @param PasskeySignInOptionsDto $data
     * @param array<string, scalar|array|null> $uriVariables
     * @param array<string, scalar|array|null> $context
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        return new JsonResponse($this->responseFactory->createOptionsResponse(
            $this->authenticationService->start(
                $data->email,
                $data->rememberMe
            )
        ));
    }
}
