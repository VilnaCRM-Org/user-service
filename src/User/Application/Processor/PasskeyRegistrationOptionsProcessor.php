<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Application\Validator\Http\EmptyJsonObjectRequestValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\StartPasskeyRegistrationCommand;
use App\User\Application\DTO\PasskeyRegistrationOptionsDto;
use App\User\Application\Factory\PasskeyResponseFactory;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<PasskeyRegistrationOptionsDto, Response>
 *
 * @psalm-api
 */
final readonly class PasskeyRegistrationOptionsProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private PasskeyResponseFactory $responseFactory,
        private CurrentUserIdentityResolver $userIdentityResolver,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
        private EmptyJsonObjectRequestValidator $requestValidator
    ) {
    }

    /**
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
        $request = $this->httpRequestContextResolver->resolveRequest($context['request'] ?? null);
        $this->requestValidator->assertEmptyRequestBody(
            $request,
            'This operation does not accept request body content.'
        );

        $command = new StartPasskeyRegistrationCommand(
            $this->userIdentityResolver->resolveUserId()
        );
        $this->commandBus->dispatch($command);

        return new JsonResponse($this->responseFactory->createOptionsResponse(
            $command->getResponse()
        ));
    }
}
