<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AuthTokenFactory;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use DateTimeImmutable;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

final class AuthTokenFactoryTest extends UnitTestCase
{
    private AuthTokenFactory $factory;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new AuthTokenFactory(
            new UuidFactory(),
            new UlidFactory(),
            'P1M'
        );
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testBuildJwtPayloadReturnsExpectedStructure(): void
    {
        $user = $this->createUser();
        $sessionId = 'test-session-id';
        $issuedAt = new DateTimeImmutable();

        $payload = $this->factory->buildJwtPayload($user, $sessionId, $issuedAt);

        $this->assertSame($user->getId(), $payload['sub']);
        $this->assertSame('vilnacrm-user-service', $payload['iss']);
        $this->assertSame('vilnacrm-api', $payload['aud']);
        $this->assertSame($sessionId, $payload['sid']);
        $this->assertSame(['ROLE_USER'], $payload['roles']);
        $this->assertIsInt($payload['iat']);
        $this->assertIsInt($payload['exp']);
        $this->assertIsInt($payload['nbf']);
        $this->assertSame($issuedAt->getTimestamp(), $payload['iat']);
        $this->assertSame($issuedAt->getTimestamp() + 900, $payload['exp']);
        $this->assertSame($issuedAt->getTimestamp(), $payload['nbf']);
        $this->assertIsString($payload['jti']);
        $this->assertNotEmpty($payload['jti']);
    }

    public function testBuildJwtPayloadGeneratesUniqueJtiEachCall(): void
    {
        $user = $this->createUser();
        $issuedAt = new DateTimeImmutable();

        $payload1 = $this->factory->buildJwtPayload($user, 'session-1', $issuedAt);
        $payload2 = $this->factory->buildJwtPayload($user, 'session-2', $issuedAt);

        $this->assertNotSame($payload1['jti'], $payload2['jti']);
    }

    public function testGenerateOpaqueTokenReturnsValidFormat(): void
    {
        $token = $this->factory->generateOpaqueToken();

        $this->assertSame(43, strlen($token));
        $this->assertStringNotContainsString('=', $token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $token);
    }

    public function testGenerateOpaqueTokenIsUnique(): void
    {
        $token1 = $this->factory->generateOpaqueToken();
        $token2 = $this->factory->generateOpaqueToken();

        $this->assertNotSame($token1, $token2);
    }

    public function testCreateRefreshTokenReturnsEntityWithCorrectFields(): void
    {
        $sessionId = 'test-session-id';
        $plainToken = 'plain-token-value';
        $issuedAt = new DateTimeImmutable();

        $refreshToken = $this->factory->createRefreshToken($sessionId, $plainToken, $issuedAt);

        $this->assertInstanceOf(AuthRefreshToken::class, $refreshToken);
        $this->assertSame($sessionId, $refreshToken->getSessionId());
        $this->assertNotEmpty($refreshToken->getId());
    }

    public function testCreateRefreshTokenExpiryIsOneMonthByDefault(): void
    {
        $issuedAt = new DateTimeImmutable('2026-01-01 00:00:00');

        $refreshToken = $this->factory->createRefreshToken('session-id', 'token', $issuedAt);

        $expectedExpiry = $issuedAt->modify('+1 month');
        $this->assertEquals($expectedExpiry, $refreshToken->getExpiresAt());
    }

    public function testCreateRefreshTokenRespectsCustomTtl(): void
    {
        $factory = new AuthTokenFactory(new UuidFactory(), new UlidFactory(), 'P7D');
        $issuedAt = new DateTimeImmutable('2026-01-01 00:00:00');

        $refreshToken = $factory->createRefreshToken('session-id', 'token', $issuedAt);

        $expectedExpiry = $issuedAt->modify('+7 days');
        $this->assertEquals($expectedExpiry, $refreshToken->getExpiresAt());
    }

    public function testNextEventIdReturnsNonEmptyString(): void
    {
        $eventId = $this->factory->nextEventId();

        $this->assertIsString($eventId);
        $this->assertNotEmpty($eventId);
    }

    public function testNextEventIdIsUniqueEachCall(): void
    {
        $id1 = $this->factory->nextEventId();
        $id2 = $this->factory->nextEventId();

        $this->assertNotSame($id1, $id2);
    }

    private function createUser(): User
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }
}
