<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Repository;

use App\OAuth\Domain\Exception\InvalidStateException;
use App\OAuth\Domain\Exception\ProviderMismatchException;
use App\OAuth\Domain\ValueObject\OAuthStatePayload;
use App\OAuth\Infrastructure\Repository\RedisOAuthStateRepository;
use App\Tests\Unit\UnitTestCase;
use DateTimeImmutable;
use Redis;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

final class RedisOAuthStateRepositoryTest extends UnitTestCase
{
    private Redis $redis;
    private SerializerInterface $serializer;
    private RedisOAuthStateRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = $this->createMock(Redis::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->repository = new RedisOAuthStateRepository(
            $this->redis,
            $this->serializer,
        );
    }

    public function testSaveStoresPayloadWithTtl(): void
    {
        $state = $this->faker->sha256();
        $payload = $this->createPayload('github');
        $serialized = $this->faker->sha256();

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($payload, JsonEncoder::FORMAT)
            ->willReturn($serialized);

        $this->redis->expects($this->once())
            ->method('setex')
            ->with(
                'oauth_state:' . $state,
                600,
                $serialized,
            );

        $this->repository->save($state, $payload, 600);
    }

    public function testValidateAndConsumeReturnsPayloadOnSuccess(): void
    {
        $state = $this->faker->sha256();
        $flowBinding = $this->faker->sha256();
        $rawJson = $this->faker->sha256();
        $payload = new OAuthStatePayload(
            provider: 'github',
            codeVerifier: 'test_verifier',
            flowBindingHash: hash('sha256', $flowBinding),
            redirectUri: 'https://localhost/callback',
            createdAt: new DateTimeImmutable(),
        );

        $this->expectEvalCalledWith($state, $rawJson);
        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($rawJson, OAuthStatePayload::class, JsonEncoder::FORMAT)
            ->willReturn($payload);

        $result = $this->repository->validateAndConsume(
            $state,
            'github',
            $flowBinding,
        );

        $this->assertInstanceOf(OAuthStatePayload::class, $result);
        $this->assertSame('github', $result->provider);
        $this->assertSame('test_verifier', $result->codeVerifier);
    }

    public function testValidateAndConsumeThrowsForMissingState(): void
    {
        $this->redis->method('eval')->willReturn(false);

        $this->expectException(InvalidStateException::class);

        $this->repository->validateAndConsume(
            $this->faker->sha256(),
            'github',
            $this->faker->sha256(),
        );
    }

    public function testValidateAndConsumeThrowsForAlreadyConsumedState(): void
    {
        $this->redis->method('eval')->willReturn(null);

        $this->expectException(InvalidStateException::class);

        $this->repository->validateAndConsume(
            $this->faker->sha256(),
            'github',
            $this->faker->sha256(),
        );
    }

    public function testValidateAndConsumeThrowsForProviderMismatch(): void
    {
        $flowBinding = $this->faker->sha256();
        $rawJson = $this->faker->sha256();
        $payload = new OAuthStatePayload(
            provider: 'github',
            codeVerifier: 'test_verifier',
            flowBindingHash: hash('sha256', $flowBinding),
            redirectUri: 'https://localhost/callback',
            createdAt: new DateTimeImmutable(),
        );

        $this->redis->method('eval')->willReturn($rawJson);
        $this->serializer->method('deserialize')->willReturn($payload);

        $this->expectException(ProviderMismatchException::class);

        $this->repository->validateAndConsume(
            $this->faker->sha256(),
            'google',
            $flowBinding,
        );
    }

    public function testValidateAndConsumeThrowsForFlowBindingMismatch(): void
    {
        $rawJson = $this->faker->sha256();
        $payload = new OAuthStatePayload(
            provider: 'github',
            codeVerifier: 'test_verifier',
            flowBindingHash: hash('sha256', 'correct_binding'),
            redirectUri: 'https://localhost/callback',
            createdAt: new DateTimeImmutable(),
        );

        $this->redis->method('eval')->willReturn($rawJson);
        $this->serializer->method('deserialize')->willReturn($payload);

        $this->expectException(InvalidStateException::class);

        $this->repository->validateAndConsume(
            $this->faker->sha256(),
            'github',
            'wrong_binding',
        );
    }

    private function expectEvalCalledWith(
        string $state,
        string $returnValue,
    ): void {
        $this->redis->expects($this->once())
            ->method('eval')
            ->with(
                $this->isType('string'),
                ['oauth_state:' . $state],
                1,
            )
            ->willReturn($returnValue);
    }

    private function createPayload(string $provider): OAuthStatePayload
    {
        return new OAuthStatePayload(
            provider: $provider,
            codeVerifier: $this->faker->sha256(),
            flowBindingHash: $this->faker->sha256(),
            redirectUri: 'https://localhost/api/auth/social/' . $provider . '/callback',
            createdAt: new DateTimeImmutable(),
        );
    }
}
