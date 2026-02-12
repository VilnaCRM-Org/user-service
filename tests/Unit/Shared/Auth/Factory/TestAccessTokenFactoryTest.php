<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Auth\Factory;

use App\Tests\Shared\Auth\Factory\TestAccessTokenFactory;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use DateTimeImmutable;
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

        $accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->callback(/**
             * @return true
             */
            function (array $payload) use ($subject, $sessionId): bool {
                $this->assertSame($subject, $payload['sub']);
                $this->assertSame($sessionId, $payload['sid']);
                $this->assertSame(['ROLE_USER'], $payload['roles']);
                $this->assertSame('vilnacrm-user-service', $payload['iss']);
                $this->assertSame('vilnacrm-api', $payload['aud']);

                return true;
            }))
            ->willReturn($tokenValue);

        $uuidFactory = $this->createMock(UuidFactory::class);
        $uuidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(Uuid::fromString($this->faker->uuid()));

        $ulidFactory = $this->createMock(UlidFactory::class);
        $ulidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new Ulid($sessionId));

        $factory = new TestAccessTokenFactory(
            $accessTokenGenerator,
            $uuidFactory,
            $ulidFactory
        );

        $token = $factory->createUserToken($subject);

        $this->assertSame($tokenValue, $token);
    }

    public function testCreateServiceTokenBuildsPayloadWithRoleService(): void
    {
        $subject = sprintf('service-%s', strtolower($this->faker->lexify('????')));
        $sessionId = (string) new Ulid();
        $tokenValue = $this->faker->sha1();

        $accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->callback(/**
             * @return true
             */
            function (array $payload) use ($subject, $sessionId): bool {
                $this->assertSame($subject, $payload['sub']);
                $this->assertSame($sessionId, $payload['sid']);
                $this->assertSame(['ROLE_SERVICE'], $payload['roles']);

                return true;
            }))
            ->willReturn($tokenValue);

        $uuidFactory = $this->createMock(UuidFactory::class);
        $uuidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(Uuid::fromString($this->faker->uuid()));

        $ulidFactory = $this->createMock(UlidFactory::class);
        $ulidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new Ulid($sessionId));

        $factory = new TestAccessTokenFactory(
            $accessTokenGenerator,
            $uuidFactory,
            $ulidFactory
        );

        $token = $factory->createServiceToken($subject);

        $this->assertSame($tokenValue, $token);
    }

    public function testCreateTokenUsesProvidedSessionAndIssuedAt(): void
    {
        $subject = $this->faker->uuid();
        $sessionId = (string) new Ulid();
        $issuedAt = new DateTimeImmutable('2026-01-01 10:00:00');
        $tokenValue = $this->faker->sha1();

        $accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->callback(/**
             * @return true
             */
            function (array $payload) use ($subject, $sessionId, $issuedAt): bool {
                $timestamp = $issuedAt->getTimestamp();

                $this->assertSame($subject, $payload['sub']);
                $this->assertSame($sessionId, $payload['sid']);
                $this->assertSame(['ROLE_SERVICE'], $payload['roles']);
                $this->assertSame($timestamp, $payload['iat']);
                $this->assertSame($timestamp, $payload['nbf']);
                $this->assertSame($timestamp + 900, $payload['exp']);

                return true;
            }))
            ->willReturn($tokenValue);

        $uuidFactory = $this->createMock(UuidFactory::class);
        $uuidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(Uuid::fromString($this->faker->uuid()));

        $ulidFactory = $this->createMock(UlidFactory::class);
        $ulidFactory
            ->expects($this->never())
            ->method('create');

        $factory = new TestAccessTokenFactory(
            $accessTokenGenerator,
            $uuidFactory,
            $ulidFactory
        );

        $token = $factory->createToken(
            $subject,
            ['ROLE_SERVICE'],
            $sessionId,
            $issuedAt
        );

        $this->assertSame($tokenValue, $token);
    }
}
