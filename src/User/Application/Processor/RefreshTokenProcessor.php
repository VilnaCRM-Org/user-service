<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RefreshTokenCommand;
use App\User\Application\Command\RefreshTokenCommandResponse;
use App\User\Application\DTO\RefreshTokenDto;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<RefreshTokenDto, Response>
 */
final readonly class RefreshTokenProcessor implements ProcessorInterface
{
    private const AUTH_COOKIE_NAME = '__Host-auth_token';
    private const COOKIE_MAX_AGE = 900;

    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @param RefreshTokenDto $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
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
        $command = new RefreshTokenCommand($data->refreshToken);
        $this->commandBus->dispatch($command);

        $commandResponse = $command->getResponse();
        $response = new JsonResponse(
            $this->buildResponseBody($commandResponse)
        );

        $this->attachAuthCookie($response, $commandResponse->getAccessToken());

        return $response;
    }

    /**
     * @return string[]
     *
     * @psalm-return array{access_token: string, refresh_token: string}
     */
    private function buildResponseBody(
        RefreshTokenCommandResponse $response
    ): array {
        return [
            'access_token' => $response->getAccessToken(),
            'refresh_token' => $response->getRefreshToken(),
        ];
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
                (new DateTimeImmutable())->modify(
                    sprintf('+%d seconds', self::COOKIE_MAX_AGE)
                )
            )
                ->withPath('/')
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );
    }
}
