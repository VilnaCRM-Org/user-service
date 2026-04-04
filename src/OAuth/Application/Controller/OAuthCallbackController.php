<?php

declare(strict_types=1);

namespace App\OAuth\Application\Controller;

use App\OAuth\Application\Command\HandleOAuthCallbackCommand;
use App\OAuth\Application\DTO\HandleOAuthCallbackResponse;
use App\OAuth\Application\Factory\OAuthFlowCookieFactory;
use App\OAuth\Domain\Exception\MissingOAuthParametersException;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory\AuthCookieFactoryInterface;
use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * @psalm-api
 */
#[AsController]
final readonly class OAuthCallbackController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private AuthCookieFactoryInterface $authCookieFactory,
    ) {
    }

    public function __invoke(string $provider, Request $request): Response
    {
        $this->validateParameters($request);

        $commandResponse = $this->dispatchCommand($provider, $request);

        $response = new JsonResponse(
            $this->buildResponseBody($commandResponse),
        );

        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Pragma', 'no-cache');
        $this->attachAuthCookie($commandResponse, $response);

        return $response;
    }

    private function validateParameters(Request $request): void
    {
        $code = $request->query->getString('code');
        $state = $request->query->getString('state');
        $flowBindingToken = $request->cookies->getString(
            OAuthFlowCookieFactory::COOKIE_NAME
        );

        if ($code === '' || $state === '' || $flowBindingToken === '') {
            throw new MissingOAuthParametersException();
        }
    }

    private function dispatchCommand(
        string $provider,
        Request $request,
    ): HandleOAuthCallbackResponse {
        $command = new HandleOAuthCallbackCommand(
            $provider,
            $request->query->getString('code'),
            $request->query->getString('state'),
            $request->cookies->getString(OAuthFlowCookieFactory::COOKIE_NAME),
            $request->getClientIp() ?? '0.0.0.0',
            $request->headers->get('User-Agent', ''),
        );

        $this->commandBus->dispatch($command);

        return $command->getResponse();
    }

    private function attachAuthCookie(
        HandleOAuthCallbackResponse $commandResponse,
        Response $response,
    ): void {
        if ($commandResponse->isTwoFactorEnabled()) {
            return;
        }

        $accessToken = $commandResponse->getAccessToken();
        if ($accessToken !== null && $accessToken !== '') {
            $response->headers->setCookie(
                $this->authCookieFactory->create($accessToken, false)
            );
        }
    }

    /**
     * @return array<bool|string>
     *
     * @psalm-return array{2fa_enabled: bool, access_token?: string, refresh_token?: string, pending_session_id?: string}
     */
    private function buildResponseBody(
        HandleOAuthCallbackResponse $response,
    ): array {
        $body = ['2fa_enabled' => $response->isTwoFactorEnabled()];

        if ($response->isTwoFactorEnabled()) {
            return $this->buildTwoFactorBody($body, $response);
        }

        return $this->buildDirectSignInBody($body, $response);
    }

    /**
     * @param array<bool|string> $body
     *
     * @return array<bool|string>
     */
    private function buildTwoFactorBody(
        array $body,
        HandleOAuthCallbackResponse $response,
    ): array {
        $pendingSessionId = $response->getPendingSessionId();
        if ($pendingSessionId !== null) {
            $body['pending_session_id'] = $pendingSessionId;
        }

        return $body;
    }

    /**
     * @param array<bool|string> $body
     *
     * @return array<bool|string>
     */
    private function buildDirectSignInBody(
        array $body,
        HandleOAuthCallbackResponse $response,
    ): array {
        $accessToken = $response->getAccessToken();
        $refreshToken = $response->getRefreshToken();

        if (
            $accessToken === null || $accessToken === ''
            || $refreshToken === null || $refreshToken === ''
        ) {
            throw new LogicException(
                'Missing access/refresh token when 2FA is disabled.'
            );
        }

        $body['access_token'] = $accessToken;
        $body['refresh_token'] = $refreshToken;

        return $body;
    }
}
