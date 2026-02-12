<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\Command\CompleteTwoFactorCommandResponse;
use App\User\Application\DTO\CompleteTwoFactorDto;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<CompleteTwoFactorDto, Response>
 */
final readonly class CompleteTwoFactorProcessor implements ProcessorInterface
{
    private const AUTH_COOKIE_NAME = '__Host-auth_token';
    private const COOKIE_MAX_AGE = 900;

    public function __construct(
        private CommandBusInterface $commandBus,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @param CompleteTwoFactorDto $data
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
        $request = $this->resolveRequest($context['request'] ?? null);

        $command = new CompleteTwoFactorCommand(
            $data->pendingSessionId,
            $data->twoFactorCode,
            $this->resolveIpAddress($request),
            $this->resolveUserAgent($request)
        );

        $this->commandBus->dispatch($command);
        $commandResponse = $command->getResponse();

        $response = new JsonResponse($this->buildResponseBody($commandResponse));
        $this->attachAuthCookie($response, $commandResponse->getAccessToken());

        return $response;
    }

    /**
     * @return (int|string|true)[]
     *
     * @psalm-return array{2fa_enabled: true, access_token: string, refresh_token: string, recovery_codes_remaining?: int, warning?: string}
     */
    private function buildResponseBody(
        CompleteTwoFactorCommandResponse $response
    ): array {
        $body = [
            '2fa_enabled' => true,
            'access_token' => $response->getAccessToken(),
            'refresh_token' => $response->getRefreshToken(),
        ];

        if ($response->getRecoveryCodesRemaining() !== null) {
            $body['recovery_codes_remaining'] = $response->getRecoveryCodesRemaining();
        }

        if ($response->getWarningMessage() !== null) {
            $body['warning'] = $response->getWarningMessage();
        }

        return $body;
    }

    private function attachAuthCookie(
        Response $response,
        string $accessToken
    ): void {
        if ($accessToken === '') {
            return;
        }

        $response->headers->setCookie(
            Cookie::create(
                self::AUTH_COOKIE_NAME,
                $accessToken,
                (new DateTimeImmutable())->modify(sprintf('+%d seconds', self::COOKIE_MAX_AGE))
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
        return $request?->getClientIp() ?? '';
    }

    private function resolveUserAgent(?Request $request): string
    {
        return $request?->headers->get('User-Agent') ?? '');
    }
}
