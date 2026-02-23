<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Decoder\JwtTokenDecoderInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiRateLimitAuthTargetResolverTestCase extends UnitTestCase
{
    protected PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository;
    protected JwtTokenDecoderInterface $jwtDecoder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->pendingTwoFactorRepository = $this->createMock(
            PendingTwoFactorRepositoryInterface::class
        );
        $this->jwtDecoder = $this->createMock(JwtTokenDecoderInterface::class);
    }

    /**
     * @param array<string, bool|float|int|string|null> $overrides
     *
     * @return array<string, array<int, string>|bool|float|int|string|null>
     */
    protected function buildValidPayload(array $overrides = []): array
    {
        $now = time();

        /** @var array<string, array<int, string>|bool|float|int|string|null> $base */
        $base = [
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'sub' => $this->faker->uuid(),
            'nbf' => $now - 60,
            'exp' => $now + 3600,
        ];

        return array_merge($base, $overrides);
    }

    protected function stubPendingSession(string $sessionId, string $userId): void
    {
        $pendingSession = new PendingTwoFactor($sessionId, $userId, new DateTimeImmutable());
        $this->pendingTwoFactorRepository->method('findById')
            ->with($sessionId)
            ->willReturn($pendingSession);
    }

    protected function createCamelCaseTwoFaRequest(string $clientIp, string $sessionId): Request
    {
        return Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['pendingSessionId' => $sessionId], JSON_THROW_ON_ERROR)
        );
    }

    protected function createSnakeCaseTwoFaRequest(string $clientIp, string $sessionId): Request
    {
        return Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['pending_session_id' => $sessionId], JSON_THROW_ON_ERROR)
        );
    }

    protected function createSignInRequestWithAuth(
        string $clientIp,
        string $token,
        string $email
    ): Request {
        $request = Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email], JSON_THROW_ON_ERROR)
        );
        $request->headers->set('Authorization', 'Bearer ' . $token);

        return $request;
    }
}
