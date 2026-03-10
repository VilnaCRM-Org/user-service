<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Auth\Factory;

use App\Tests\Shared\Auth\Factory\TestAccessTokenFactory;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Generator\AccessTokenGeneratorInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

final class TestAccessTokenFactoryTest extends UnitTestCase
{
    public function testCreateUserTokenBuildsPayloadWithRoleUser(): void
    {
        $subject = $this->faker->uuid();
        $sessionId = (string) new Ulid();
        $tokenValue = $this->faker->sha1();
        $generator = $this->createGeneratorMock(
            function (array $payload) use ($subject, $sessionId): bool {
                $this->assertSame($subject, $payload['sub']);
                $this->assertSame($sessionId, $payload['sid']);
                $this->assertSame(['ROLE_USER'], $payload['roles']);
                $this->assertSame('vilnacrm-user-service', $payload['iss']);
                $this->assertSame('vilnacrm-api', $payload['aud']);
                return true;
            },
            $tokenValue
        );
        $factory = $this->createTokenFactory($generator, $sessionId);
        $this->assertSame($tokenValue, $factory->createUserToken($subject));
    }

    public function testCreateServiceTokenBuildsPayloadWithRoleService(): void
    {
        $subject = sprintf('service-%s', strtolower($this->faker->lexify('????')));
        $sessionId = (string) new Ulid();
        $tokenValue = $this->faker->sha1();
        $generator = $this->createGeneratorMock(
            function (array $payload) use ($subject, $sessionId): bool {
                $this->assertSame($subject, $payload['sub']);
                $this->assertSame($sessionId, $payload['sid']);
                $this->assertSame(['ROLE_SERVICE'], $payload['roles']);
                return true;
            },
            $tokenValue
        );
        $factory = $this->createTokenFactory($generator, $sessionId);
        $this->assertSame($tokenValue, $factory->createServiceToken($subject));
    }

    public function testCreateTokenUsesProvidedSessionAndIssuedAt(): void
    {
        $subject = $this->faker->uuid();
        $sessionId = (string) new Ulid();
        $issuedAt = new DateTimeImmutable('2026-01-01 10:00:00');
        $tokenValue = $this->faker->sha1();
        $generator = $this->createGeneratorMock(
            function (array $payload) use ($subject, $sessionId, $issuedAt): bool {
                $this->assertTimestampPayload($payload, $subject, $sessionId, $issuedAt);
                return true;
            },
            $tokenValue
        );
        $factory = $this->createTokenFactoryWithoutUlidExpectation(
            $generator
        );
        $token = $factory->createToken($subject, ['ROLE_SERVICE'], $sessionId, $issuedAt);
        $this->assertSame($tokenValue, $token);
    }

    /**
     * @param callable(array<string, string|int|array<string>>): bool $assertion
     */
    private function createGeneratorMock(
        callable $assertion,
        string $returnValue
    ): MockObject {
        $generator = $this->createMock(AccessTokenGeneratorInterface::class);
        $generator->expects($this->once())->method('generate')
            ->with($this->callback($assertion))->willReturn($returnValue);
        return $generator;
    }

    private function createTokenFactory(
        MockObject $accessTokenGenerator,
        string $sessionId
    ): TestAccessTokenFactory {
        $uuidFactory = $this->createMock(UuidFactory::class);
        $uuidFactory->expects($this->once())->method('create')
            ->willReturn(Uuid::fromString($this->faker->uuid()));
        $ulidFactory = $this->createMock(UlidFactory::class);
        $ulidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new Ulid($sessionId));
        return new TestAccessTokenFactory(
            $accessTokenGenerator,
            $uuidFactory,
            $ulidFactory
        );
    }

    private function createTokenFactoryWithoutUlidExpectation(
        MockObject $accessTokenGenerator
    ): TestAccessTokenFactory {
        $uuidFactory = $this->createMock(UuidFactory::class);
        $uuidFactory->expects($this->once())->method('create')
            ->willReturn(Uuid::fromString($this->faker->uuid()));
        $ulidFactory = $this->createMock(UlidFactory::class);
        return new TestAccessTokenFactory(
            $accessTokenGenerator,
            $uuidFactory,
            $ulidFactory
        );
    }

    /**
     * @param array<string, string|int|array<string>> $payload
     */
    private function assertTimestampPayload(
        array $payload,
        string $subject,
        string $sessionId,
        DateTimeImmutable $issuedAt
    ): void {
        $timestamp = $issuedAt->getTimestamp();
        $this->assertSame($subject, $payload['sub']);
        $this->assertSame($sessionId, $payload['sid']);
        $this->assertSame(['ROLE_SERVICE'], $payload['roles']);
        $this->assertSame($timestamp, $payload['iat']);
        $this->assertSame($timestamp, $payload['nbf']);
        $this->assertSame($timestamp + 900, $payload['exp']);
    }
}
