<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\SignInCommandResponse;
use App\User\Application\DTO\SignInDto;
use App\User\Application\Factory\SignInCommandFactoryInterface;
use App\User\Application\Provider\AuthCookieProviderInterface;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<SignInDto, Response>
 */
final readonly class SignInProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private SignInCommandFactoryInterface $signInCommandFactory,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
        private AuthCookieProviderInterface $authCookieProvider,
    ) {
    }

    /**
     * @param SignInDto $data
     * @param array<string,mixed> $context
     * @param array<string,string> $uriVariables
     *
     * @return JsonResponse
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $request = $this->httpRequestContextResolver->resolveRequest($context['request'] ?? null);

        $command = $this->signInCommandFactory->create(
            $data->email,
            $data->password,
            $data->isRememberMe(),
            $this->httpRequestContextResolver->resolveIpAddress($request),
            $this->httpRequestContextResolver->resolveUserAgent($request)
        );

        $this->commandBus->dispatch($command);
        $commandResponse = $command->getResponse();

        $response = new JsonResponse($this->buildResponseBody($commandResponse));

        if (!$commandResponse->isTwoFactorEnabled()) {
            $accessToken = $commandResponse->getAccessToken();
            if ($accessToken !== null) {
                $this->authCookieProvider->attach($response, $accessToken, $data->isRememberMe());
            }
        }

        return $response;
    }

    /**
     * @return array<bool|string>
     *
     * @psalm-return array{2fa_enabled: bool, access_token?: string, refresh_token?: string, pending_session_id?: string}
     */
    private function buildResponseBody(SignInCommandResponse $response): array
    {
        $body = [
            '2fa_enabled' => $response->isTwoFactorEnabled(),
        ];

        if ($response->isTwoFactorEnabled()) {
            $pendingSessionId = $response->getPendingSessionId();

            if ($pendingSessionId !== null) {
                $body['pending_session_id'] = $pendingSessionId;
            }

            return $body;
        }

        $body['access_token'] = (string) $response->getAccessToken();
        $body['refresh_token'] = (string) $response->getRefreshToken();

        return $body;
    }
}
