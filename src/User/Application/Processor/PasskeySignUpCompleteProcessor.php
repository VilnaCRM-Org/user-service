<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\CompletePasskeySignUpCommand;
use App\User\Application\DTO\PasskeySignUpCompleteDto;
use App\User\Application\Factory\AuthCookieFactoryInterface;
use App\User\Application\Factory\PasskeyResponseFactory;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<PasskeySignUpCompleteDto, Response>
 *
 * @psalm-api
 */
final readonly class PasskeySignUpCompleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private PasskeyResponseFactory $responseFactory,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
        private AuthCookieFactoryInterface $authCookieFactory
    ) {
    }

    /**
     * @param PasskeySignUpCompleteDto $data
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
        $command = new CompletePasskeySignUpCommand(
            $data->challengeId,
            $data->credential,
            $data->label,
            $data->isRememberMe(),
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
