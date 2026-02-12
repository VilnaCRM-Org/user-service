<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class RedisTokenRepository implements TokenRepositoryInterface
{
    private const REDIS_KEY_PREFIX = 'token-';
    private const TOKEN_VALUE_PREFIX = 'tokenValue-';
    private const USER_ID_PREFIX = 'userID-';
    private const CACHE_MAPPINGS = [
        ['prefix' => self::TOKEN_VALUE_PREFIX, 'accessor' => 'getTokenValue'],
        ['prefix' => self::USER_ID_PREFIX, 'accessor' => 'getUserID'],
    ];

    private const EXPIRES_AFTER_IN_SECONDS = 86400; // 24 hours

    public function __construct(
        private CacheInterface $redisAdapter,
        private SerializerInterface $serializer
    ) {
    }

    #[\Override]
    public function save(object $token): void
    {
        $serializedToken = $this->serializer->serialize(
            $token,
            JsonEncoder::FORMAT
        );

        foreach (self::CACHE_MAPPINGS as $mapping) {
            $value = $this->extractTokenValue($token, $mapping['accessor']);
            $this->storeSerializedToken(
                $this->buildKey($mapping['prefix'], $value),
                $serializedToken
            );
        }
    }

    #[\Override]
    public function find(string $tokenValue): ?ConfirmationTokenInterface
    {
        return $this->getFromCache(
            $this->buildKey(self::TOKEN_VALUE_PREFIX, $tokenValue)
        );
    }

    /**
     * @return ConfirmationToken|null
     */
    #[\Override]
    public function findByUserId(string $userID): ?ConfirmationTokenInterface
    {
        return $this->getFromCache(
            $this->buildKey(self::USER_ID_PREFIX, $userID)
        );
    }

    #[\Override]
    public function delete(object $token): void
    {
        foreach (self::CACHE_MAPPINGS as $mapping) {
            $value = $this->extractTokenValue($token, $mapping['accessor']);
            $this->redisAdapter->delete(
                $this->buildKey($mapping['prefix'], $value)
            );
        }
    }

    private function getFromCache(string $key): ConfirmationToken|null
    {
        $cacheItem = $this->redisAdapter->getItem($key);
        $serializedToken = $cacheItem->get();

        return $serializedToken ? $this->serializer->deserialize(
            $serializedToken,
            ConfirmationToken::class,
            JsonEncoder::FORMAT
        )
            : null;
    }

    private function storeSerializedToken(string $key, string $value): void
    {
        $cacheItem = $this->redisAdapter->getItem($key);
        $cacheItem->set($value);
        $cacheItem->expiresAfter(self::EXPIRES_AFTER_IN_SECONDS);
        $this->redisAdapter->save($cacheItem);
    }

    private function buildKey(string $prefix, string $identifier): string
    {
        return self::REDIS_KEY_PREFIX . $prefix . $identifier;
    }

    private function extractTokenValue(object $token, string $accessor): string
    {
        return $token->$accessor();
    }
}
