<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class RedisTokenRepository implements TokenRepositoryInterface
{
    private const REDIS_KEY_PREFIX = 'token-';
    private const TOKEN_VALUE_PREFIX = 'tokenValue-';
    private const USER_ID_PREFIX = 'userID-';

    private const EXPIRES_AFTER_IN_SECONDS = 86400; // 24 hours

    public function __construct(
        private CacheInterface $redisAdapter,
        private SerializerInterface $serializer
    ) {
    }

    public function save(object $token): void
    {
        $tokenValue = $token->getTokenValue();
        $userId = $token->getUserID();

        $serializedToken = $this->serializer->serialize(
            $token,
            JsonEncoder::FORMAT
        );

        $cacheItem = $this->redisAdapter->getItem(
            self::getTokenKey($tokenValue)
        );
        $cacheItem->set($serializedToken);
        $cacheItem->expiresAfter(self::EXPIRES_AFTER_IN_SECONDS);
        $this->redisAdapter->save($cacheItem);

        $cacheItem = $this->redisAdapter->getItem($this->getUserKey($userId));
        $cacheItem->set($serializedToken);
        $cacheItem->expiresAfter(self::EXPIRES_AFTER_IN_SECONDS);
        $this->redisAdapter->save($cacheItem);
    }

    public function find(string $tokenValue): ?ConfirmationTokenInterface
    {
        $key = $this->getTokenKey($tokenValue);

        $cacheItem = $this->redisAdapter->getItem($key);
        $serializedToken = $cacheItem->get();

        return $serializedToken ? $this->serializer->deserialize(
            $serializedToken,
            ConfirmationToken::class,
            JsonEncoder::FORMAT
        )
            : null;
    }

    public function findByUserId(string $userID): ?ConfirmationTokenInterface
    {
        $key = $this->getUserKey($userID);

        $cacheItem = $this->redisAdapter->getItem($key);
        $serializedToken = $cacheItem->get();

        return $serializedToken ? $this->serializer->deserialize(
            $serializedToken,
            ConfirmationToken::class,
            JsonEncoder::FORMAT
        )
            : null;
    }

    public function delete(object $token): void
    {
        $tokenValue = $token->getTokenValue();
        $userId = $token->getUserID();
        $this->redisAdapter->delete($this->getTokenKey($tokenValue));
        $this->redisAdapter->delete($this->getUserKey($userId));
    }

    private function getTokenKey(string $tokenValue): string
    {
        return self::REDIS_KEY_PREFIX.self::TOKEN_VALUE_PREFIX.$tokenValue;
    }

    private function getUserKey(string $userID): string
    {
        return self::REDIS_KEY_PREFIX.self::USER_ID_PREFIX.$userID;
    }
}
