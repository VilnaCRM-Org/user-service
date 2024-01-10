<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\TokenRepositoryInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class RedisTokenRepository implements TokenRepositoryInterface
{
    private const REDIS_KEY_PREFIX = 'token-';
    private const TOKEN_VALUE_PREFIX = 'tokenValue-';
    private const USER_ID_PREFIX = 'userID-';

    private const EXPIRES_AFTER = 86400; // 24 hours

    public function __construct(private CacheInterface $redisAdapter, private SerializerInterface $serializer)
    {
    }

    public function save($token): void
    {
        $tokenValue = $token->getTokenValue();
        $userId = $token->getUserID();

        $serializedToken = $this->serializer->serialize($token, JsonEncoder::FORMAT);

        $cacheItem = $this->redisAdapter->getItem(self::getTokenKey($tokenValue));
        $cacheItem->set($serializedToken);
        $cacheItem->expiresAfter(self::EXPIRES_AFTER);
        $this->redisAdapter->save($cacheItem);

        $cacheItem = $this->redisAdapter->getItem(self::getUserKey($userId));
        $cacheItem->set($serializedToken);
        $cacheItem->expiresAfter(self::EXPIRES_AFTER);
        $this->redisAdapter->save($cacheItem);
    }

    public function find($tokenValue): ?ConfirmationToken
    {
        $key = self::getTokenKey($tokenValue);

        $cacheItem = $this->redisAdapter->getItem($key);
        $serializedToken = $cacheItem->get();

        return $serializedToken ? $this->serializer->deserialize(
            $serializedToken, ConfirmationToken::class, JsonEncoder::FORMAT) : null;
    }

    public function findByUserId($userId): ?ConfirmationToken
    {
        $key = self::getUserKey($userId);

        $cacheItem = $this->redisAdapter->getItem($key);
        $serializedToken = $cacheItem->get();

        return $serializedToken ? $this->serializer->deserialize(
            $serializedToken, ConfirmationToken::class, JsonEncoder::FORMAT) : null;
    }

    public function delete($token): void
    {
        $tokenValue = $token->getTokenValue();
        $userId = $token->getUserID();
        $this->redisAdapter->delete(self::getTokenKey($tokenValue));
        $this->redisAdapter->delete(self::getUserKey($userId));
    }

    private function getTokenKey(string $tokenValue): string
    {
        return self::REDIS_KEY_PREFIX.self::TOKEN_VALUE_PREFIX.$tokenValue;
    }

    private function getUserKey(string $userId): string
    {
        return self::REDIS_KEY_PREFIX.self::USER_ID_PREFIX.$userId;
    }
}
