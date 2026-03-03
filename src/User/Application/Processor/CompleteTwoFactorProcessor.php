<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Attacher\AuthCookieAttacherInterface;
use App\User\Application\DTO\CompleteTwoFactorCommandResponse;
use App\User\Application\DTO\CompleteTwoFactorDto;
use App\User\Application\Factory\CompleteTwoFactorCommandFactoryInterface;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<CompleteTwoFactorDto, Response>
 */
final readonly class CompleteTwoFactorProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CompleteTwoFactorCommandFactoryInterface $completeTwoFactorCommandFactory,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
        private AuthCookieAttacherInterface $authCookieAttacher,
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
        $request = $this->httpRequestContextResolver->resolveRequest($context['request'] ?? null);

        $command = $this->completeTwoFactorCommandFactory->create(
            $data->pendingSessionId,
            $data->twoFactorCode,
            $this->httpRequestContextResolver->resolveIpAddress($request),
            $this->httpRequestContextResolver->resolveUserAgent($request)
        );

        $this->commandBus->dispatch($command);
        $commandResponse = $command->getResponse();

        $response = new JsonResponse($this->buildResponseBody($commandResponse));
        $this->authCookieAttacher->attach(
            $response,
            $commandResponse->getAccessToken(),
            $commandResponse->isRememberMe()
        );

        return $response;
    }

    /**
     * @return array<int|string|true>
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
}
