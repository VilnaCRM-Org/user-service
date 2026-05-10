<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\DTO\PasskeySignUpOptionsDto;
use App\User\Application\Passkey\PasskeyRegistrationServiceInterface;
use App\User\Application\Passkey\PasskeyResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<PasskeySignUpOptionsDto, Response>
 *
 * @psalm-api
 */
final readonly class PasskeySignUpOptionsProcessor implements ProcessorInterface
{
    public function __construct(
        private PasskeyRegistrationServiceInterface $registrationService,
        private PasskeyResponseFactory $responseFactory
    ) {
    }

    /**
     * @param PasskeySignUpOptionsDto $data
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
            $this->registrationService->startSignup(
                $data->email,
                $data->initials,
                $data->displayName
            )
        ));
    }
}
