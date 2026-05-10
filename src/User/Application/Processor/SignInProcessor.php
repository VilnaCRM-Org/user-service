<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\DTO\SignInCommandResponse;
use App\User\Application\DTO\SignInDto;
use App\User\Application\Factory\AuthCookieFactoryInterface;
use App\User\Application\Service\SignInCommandDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<SignInDto, Response>
 */
final readonly class SignInProcessor implements ProcessorInterface
{
    public function __construct(
        private SignInCommandDispatcher $signInCommandDispatcher,
        private AuthCookieFactoryInterface $authCookieFactory,
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
        $commandResponse = $this->signInCommandDispatcher->dispatch(
            $data,
            $context
        );

        $response = new JsonResponse($this->buildResponseBody($commandResponse));

        if (!$commandResponse->isTwoFactorEnabled()) {
            $accessToken = $commandResponse->getAccessToken();
            if ($accessToken !== null && $accessToken !== '') {
                $response->headers->setCookie(
                    $this->authCookieFactory->create($accessToken, $data->isRememberMe())
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
