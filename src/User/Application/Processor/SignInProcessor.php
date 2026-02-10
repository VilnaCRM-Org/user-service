<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SignInCommand;
use App\User\Application\Command\SignInCommandResponse;
use App\User\Application\DTO\SignInDto;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<SignInDto, Response>
 */
final readonly class SignInProcessor implements ProcessorInterface
{
    private const AUTH_COOKIE_NAME = '__Host-auth_token';
    private const STANDARD_COOKIE_MAX_AGE = 900;
    private const REMEMBER_ME_COOKIE_MAX_AGE = 2592000;

    public function __construct(
        private CommandBusInterface $commandBus,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @param SignInDto $data
     * @param array<string,mixed> $context
     * @param array<string,string> $uriVariables
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $request = $this->resolveRequest($context['request'] ?? null);

        $command = new SignInCommand(
            $data->email,
            $data->password,
            $data->rememberMe,
            $this->resolveIpAddress($request),
            $this->resolveUserAgent($request)
        );

        $this->commandBus->dispatch($command);
        $commandResponse = $command->getResponse();

        $response = new JsonResponse($this->buildResponseBody($commandResponse));

        if ($commandResponse->isTwoFactorEnabled()) {
            return $response;
        }

        $this->attachAuthCookie(
            $response,
            $commandResponse->getAccessToken(),
            $data->rememberMe
        );

        return $response;
    }

    /**
     * @return array<string, bool|string>
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

    private function attachAuthCookie(
        Response $response,
        ?string $accessToken,
        bool $rememberMe
    ): void {
        if ($accessToken === null || $accessToken === '') {
            return;
        }

        $maxAge = $rememberMe
            ? self::REMEMBER_ME_COOKIE_MAX_AGE
            : self::STANDARD_COOKIE_MAX_AGE;

        $response->headers->setCookie(
            Cookie::create(
                self::AUTH_COOKIE_NAME,
                $accessToken,
                (new DateTimeImmutable())->modify(sprintf('+%d seconds', $maxAge))
            )
                ->withPath('/')
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );
    }

    private function resolveRequest(mixed $contextRequest): ?Request
    {
        if ($contextRequest instanceof Request) {
            return $contextRequest;
        }

        return $this->requestStack->getCurrentRequest();
    }

    private function resolveIpAddress(?Request $request): string
    {
        return (string) ($request?->getClientIp() ?? '');
    }

    private function resolveUserAgent(?Request $request): string
    {
        return (string) ($request?->headers->get('User-Agent') ?? '');
    }
}
