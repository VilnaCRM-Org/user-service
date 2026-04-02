<?php

declare(strict_types=1);

namespace App\OAuth\Application\Controller;

use App\OAuth\Application\Command\HandleOAuthCallbackCommand;
use App\OAuth\Application\DTO\HandleOAuthCallbackResponse;
use App\OAuth\Application\Factory\OAuthFlowCookieFactory;
use App\OAuth\Domain\Exception\MissingOAuthParametersException;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory\AuthCookieFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @psalm-api
 */
final readonly class OAuthCallbackController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private AuthCookieFactoryInterface $authCookieFactory,
    ) {
    }

    public function __invoke(string $provider, Request $request): Response
    {
        $code = $request->query->getString('code');
        $state = $request->query->getString('state');
        $flowBindingToken = $request->cookies->getString(
            OAuthFlowCookieFactory::COOKIE_NAME
        );

        if ($code === '' || $state === '' || $flowBindingToken === '') {
            throw new MissingOAuthParametersException();
        }

        $command = new HandleOAuthCallbackCommand(
            $provider,
            $code,
            $state,
            $flowBindingToken,
            $request->getClientIp() ?? '0.0.0.0',
            $request->headers->get('User-Agent', ''),
        );

        $this->commandBus->dispatch($command);

        $commandResponse = $command->getResponse();

        $response = new JsonResponse(
            $this->buildResponseBody($commandResponse),
        );

        $response->headers->set('Pragma', 'no-cache');

        if (!$commandResponse->isTwoFactorEnabled()) {
            $accessToken = $commandResponse->getAccessToken();
            if ($accessToken !== null && $accessToken !== '') {
                $response->headers->setCookie(
                    $this->authCookieFactory->create($accessToken, false)
                );
            }
        }

        return $response;
    }

    /**
     * @return array<bool|string>
     *
     * @psalm-return array{2fa_enabled: bool, access_token?: string, refresh_token?: string, pending_session_id?: string}
     */
    private function buildResponseBody(
        HandleOAuthCallbackResponse $response,
    ): array {
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
