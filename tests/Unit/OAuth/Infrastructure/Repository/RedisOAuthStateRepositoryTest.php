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

final class RedisOAuthStateRepositoryTest extends UnitTestCase
{
    private Redis $redis;
    private RedisOAuthStateRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = $this->createMock(Redis::class);
        $this->repository = new RedisOAuthStateRepository($this->redis);
    }

    public function testSaveStoresPayloadWithTtl(): void
    {
        $state = $this->faker->sha256();
        $payload = $this->createPayload('github');

        $this->redis->expects($this->once())
            ->method('setex')
            ->with(
                'oauth_state:' . $state,
                600,
                $this->callback(
                    $this->buildPayloadValidator($payload),
                ),
            );

        $this->repository->save($state, $payload, 600);
    }

    public function testValidateAndConsumeReturnsPayloadOnSuccess(): void
    {
        $state = $this->faker->sha256();
        $flowBinding = $this->faker->sha256();
        $storedData = $this->encodeStateData(
            'github',
            hash('sha256', $flowBinding),
        );
        $this->expectEvalCalledWith($state, $storedData);

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
        $storedData = $this->encodeStateData(
            'github',
            hash('sha256', $flowBinding),
        );
        $this->redis->method('eval')->willReturn($storedData);

        $this->expectException(ProviderMismatchException::class);

        $this->repository->validateAndConsume(
            $this->faker->sha256(),
            'google',
            $flowBinding,
        );
    }

    public function testValidateAndConsumeThrowsForFlowBindingMismatch(): void
    {
        $storedData = $this->encodeStateData(
            'github',
            hash('sha256', 'correct_binding'),
        );
        $this->redis->method('eval')->willReturn($storedData);

        $this->expectException(InvalidStateException::class);

        $this->repository->validateAndConsume(
            $this->faker->sha256(),
            'github',
            'wrong_binding',
        );
    }

    private function buildPayloadValidator(
        OAuthStatePayload $payload,
    ): \Closure {
        return function (string $serialized) use ($payload): bool {
            $this->assertSerializedPayload($serialized, $payload);

            return true;
        };
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

    private function assertSerializedPayload(
        string $serialized,
        OAuthStatePayload $payload,
    ): void {
        /** @var array<string, string> $data */
        $data = json_decode(
            $serialized,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertSame($payload->provider, $data['provider']);
        $this->assertSame($payload->codeVerifier, $data['code_verifier']);
        $this->assertSame($payload->flowBindingHash, $data['flow_binding_hash']);
        $this->assertSame($payload->redirectUri, $data['redirect_uri']);
        $this->assertArrayHasKey('created_at', $data);
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

    private function encodeStateData(
        string $provider,
        string $flowBindingHash,
    ): string {
        return json_encode([
            'provider' => $provider,
            'code_verifier' => 'test_verifier',
            'flow_binding_hash' => $flowBindingHash,
            'redirect_uri' => 'https://localhost/callback',
            'created_at' => (new DateTimeImmutable())->format(
                DateTimeImmutable::ATOM
            ),
        ], JSON_THROW_ON_ERROR);
    }
}
