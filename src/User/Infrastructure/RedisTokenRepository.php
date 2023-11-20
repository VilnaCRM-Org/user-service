<?php

namespace App\User\Infrastructure;

use App\Shared\Infrastructure\TokenNotFoundError;
use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Domain\TokenRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class RedisTokenRepository implements TokenRepository
{
    private const REDIS_KEY_PREFIX = 'token-';

    public function __construct(private CacheInterface $redisAdapter, private SerializerInterface $serializer)
    {
    }

    public function save(ConfirmationToken $token): void
    {
        $key = self::REDIS_KEY_PREFIX.$token->getTokenValue();
        $serializedToken = $this->serializer->serialize($token, 'json');

        $cacheItem = $this->redisAdapter->getItem($key);
        $cacheItem->set($serializedToken);
        $this->redisAdapter->save($cacheItem);
    }

    public function find(string $token): ConfirmationToken
    {
        $key = self::REDIS_KEY_PREFIX.$token;

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
        $this->redisAdapter->delete($token->getTokenValue());
    }
}
