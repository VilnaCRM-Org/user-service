<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Repository;

use App\OAuth\Domain\Exception\InvalidStateException;
use App\OAuth\Domain\Exception\ProviderMismatchException;
use App\OAuth\Domain\Repository\OAuthStateRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthStatePayload;
use DateTimeImmutable;
use Redis;

/**
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class RedisOAuthStateRepository implements OAuthStateRepositoryInterface
{
    private const string KEY_PREFIX = 'oauth_state:';

    private const string CONSUME_LUA_SCRIPT = <<<'LUA'
        local val = redis.call('GET', KEYS[1])
        if val then
            redis.call('DEL', KEYS[1])
        end
        return val
        LUA;

    public function __construct(
        private readonly Redis $oauthRedis,
    ) {
    }

    #[\Override]
    public function save(
        string $state,
        OAuthStatePayload $payload,
        int $ttlSeconds,
    ): void {
        /** @var string $serialized */
        $serialized = json_encode([
            'provider' => $payload->provider,
            'code_verifier' => $payload->codeVerifier,
            'flow_binding_hash' => $payload->flowBindingHash,
            'redirect_uri' => $payload->redirectUri,
            'created_at' => $payload->createdAt->format(
                DateTimeImmutable::ATOM
            ),
        ], JSON_THROW_ON_ERROR);

        $this->oauthRedis->setex(
            self::KEY_PREFIX . $state,
            $ttlSeconds,
            $serialized,
        );
    }

    #[\Override]
    public function validateAndConsume(
        string $state,
        string $provider,
        string $flowBinding,
    ): OAuthStatePayload {
        $data = $this->atomicConsume($state);
        $this->validateProvider($data, $provider);
        $this->validateFlowBinding($data, $flowBinding);

        return new OAuthStatePayload(
            provider: $data['provider'],
            codeVerifier: $data['code_verifier'],
            flowBindingHash: $data['flow_binding_hash'],
            redirectUri: $data['redirect_uri'],
            createdAt: new DateTimeImmutable($data['created_at']),
        );
    }

    /**
     * @return array{provider: string, code_verifier: string, flow_binding_hash: string, redirect_uri: string, created_at: string}
     */
    private function atomicConsume(string $state): array
    {
        /** @var string|false $raw */
        $raw = $this->oauthRedis->eval(
            self::CONSUME_LUA_SCRIPT,
            [self::KEY_PREFIX . $state],
            1,
        );

        if ($raw === false || $raw === null) {
            throw new InvalidStateException(
                'Invalid or already consumed OAuth state'
            );
        }

        return $this->deserializeState((string) $raw);
    }

    /**
     * @infection-ignore-all - depth 512 is PHP default, not testable
     *
     * @return array{provider: string, code_verifier: string, flow_binding_hash: string, redirect_uri: string, created_at: string}
     */
    private function deserializeState(string $raw): array
    {
        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array{provider: string, code_verifier: string, flow_binding_hash: string, redirect_uri: string, created_at: string} $data
     */
    private function validateProvider(
        array $data,
        string $provider,
    ): void {
        if ($data['provider'] !== $provider) {
            throw new ProviderMismatchException(
                $data['provider'],
                $provider,
            );
        }
    }

    /**
     * @param array{provider: string, code_verifier: string, flow_binding_hash: string, redirect_uri: string, created_at: string} $data
     */
    private function validateFlowBinding(
        array $data,
        string $flowBinding,
    ): void {
        $flowBindingHash = hash('sha256', $flowBinding);
        if (!hash_equals($data['flow_binding_hash'], $flowBindingHash)) {
            throw new InvalidStateException(
                'Flow binding verification failed'
            );
        }
    }
}
