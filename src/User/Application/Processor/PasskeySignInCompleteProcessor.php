<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\CompletePasskeySignInCommand;
use App\User\Application\DTO\PasskeySignInCompleteDto;
use App\User\Application\Factory\AuthCookieFactoryInterface;
use App\User\Application\Factory\PasskeyResponseFactory;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<PasskeySignInCompleteDto, Response>
 *
 * @psalm-api
 */
final readonly class PasskeySignInCompleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private PasskeyResponseFactory $responseFactory,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
        private AuthCookieFactoryInterface $authCookieFactory
    ) {
    }

    /**
     * @param PasskeySignInCompleteDto $data
     * @param array<string, scalar|array|null> $uriVariables
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $request = $this->httpRequestContextResolver->resolveRequest($context['request'] ?? null);
        $command = new CompletePasskeySignInCommand(
            $data->challengeId,
            $data->credential,
            $this->httpRequestContextResolver->resolveIpAddress($request),
            $this->httpRequestContextResolver->resolveUserAgent($request)
        );
        $this->commandBus->dispatch($command);
        $result = $command->getResponse();

        $response = new JsonResponse($this->responseFactory->createTokenResponse($result));
        $response->headers->setCookie(
            $this->authCookieFactory->create($result->getAccessToken(), $result->isRememberMe())
        );

        return $response;
    }
}
