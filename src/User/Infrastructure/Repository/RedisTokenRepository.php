<?php

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Domain\TokenRepository;
use App\User\Infrastructure\Exceptions\TokenNotFoundError;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class RedisTokenRepository implements TokenRepository
{
    private const REDIS_KEY_PREFIX = 'token-';
    private const TOKEN_VALUE_PREFIX = 'tokenValue-';
    private const USER_ID_PREFIX = 'userID-';

    public function __construct(private CacheInterface $redisAdapter, private SerializerInterface $serializer)
    {
    }

    public function save(ConfirmationToken $token): void
    {
        $tokenValue = $token->getTokenValue();
        $userId = $token->getUserID();
        $serializedToken = $this->serializer->serialize($token, 'json');

        $cacheItem = $this->redisAdapter->getItem(self::TOKEN_VALUE_PREFIX.$tokenValue);
        $cacheItem->set($serializedToken);
        $this->redisAdapter->save($cacheItem);

        $cacheItem = $this->redisAdapter->getItem(self::USER_ID_PREFIX.$userId);
        $cacheItem->set($serializedToken);
        $this->redisAdapter->save($cacheItem);
    }

    public function findByTokenValue(string $tokenValue): ConfirmationToken
    {
        $key = self::TOKEN_VALUE_PREFIX.$tokenValue;

        $cacheItem = $this->redisAdapter->getItem($key);
        $serializedToken = $cacheItem->get();

        if (null !== $serializedToken) {
            return $this->serializer->deserialize($serializedToken, ConfirmationToken::class, 'json');
        } else {
            throw new TokenNotFoundError();
        }
    }

    public function findByUserId(string $userId): ConfirmationToken
    {
        $key = self::USER_ID_PREFIX.$userId;

        $cacheItem = $this->redisAdapter->getItem($key);
        $serializedToken = $cacheItem->get();

        if (null !== $serializedToken) {
            return $this->serializer->deserialize($serializedToken, ConfirmationToken::class, 'json');
        } else {
            throw new TokenNotFoundError();
        }
    }

    public function delete(ConfirmationToken $token): void
    {
        $tokenValue = $token->getTokenValue();
        $userId = $token->getUserID();
        $this->redisAdapter->delete(self::TOKEN_VALUE_PREFIX.$tokenValue);
        $this->redisAdapter->delete(self::USER_ID_PREFIX.$userId);
    }
}