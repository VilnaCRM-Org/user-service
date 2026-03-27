<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Repository;

use App\OAuth\Domain\Exception\InvalidStateException;
use App\OAuth\Domain\Exception\ProviderMismatchException;
use App\OAuth\Domain\Repository\OAuthStateRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthStatePayload;
use Redis;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @psalm-suppress UnusedClass - Used via dependency injection
 */
final class RedisOAuthStateRepository implements OAuthStateRepositoryInterface
{
    private const KEY_PREFIX = 'oauth_state:';

    private const CONSUME_LUA_SCRIPT = <<<'LUA'
        local val = redis.call('GET', KEYS[1])
        if val then
            redis.call('DEL', KEYS[1])
        end
        return val
        LUA;

    public function __construct(
        private readonly Redis $oauthRedis,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[\Override]
    public function save(
        string $state,
        OAuthStatePayload $payload,
        int $ttlSeconds,
    ): void {
        $serialized = $this->serializer->serialize(
            $payload,
            JsonEncoder::FORMAT,
        );

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
        $raw = $this->atomicConsume($state);
        $payload = $this->deserializeState($raw);
        $this->validateProvider($payload, $provider);
        $this->validateFlowBinding($payload, $flowBinding);

        return $payload;
    }

    private function atomicConsume(string $state): string
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

        return (string) $raw;
    }

    private function deserializeState(string $raw): OAuthStatePayload
    {
        return $this->serializer->deserialize(
            $raw,
            OAuthStatePayload::class,
            JsonEncoder::FORMAT,
        );
    }

    private function validateProvider(
        OAuthStatePayload $payload,
        string $provider,
    ): void {
        if ($payload->provider !== $provider) {
            throw new ProviderMismatchException(
                $payload->provider,
                $provider,
            );
        }
    }

    private function validateFlowBinding(
        OAuthStatePayload $payload,
        string $flowBinding,
    ): void {
        $flowBindingHash = hash('sha256', $flowBinding);
        if (!hash_equals($payload->flowBindingHash, $flowBindingHash)) {
            throw new InvalidStateException(
                'Flow binding verification failed'
            );
        }
    }
}
