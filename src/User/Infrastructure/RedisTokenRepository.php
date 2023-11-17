<?php

namespace App\User\Infrastructure;

use App\Shared\Infrastructure\TokenNotFoundError;
use App\User\Domain\Entity\Token\Token;
use App\User\Domain\TokenRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class RedisTokenRepository implements TokenRepository
{
    private const REDIS_KEY_PREFIX = 'token-';

    public function __construct(private CacheInterface $redisAdapter, private SerializerInterface $serializer)
    {
    }

    public function save(Token $token): void
    {
        $key = self::REDIS_KEY_PREFIX.$token->getToken();
        $serializedToken = $this->serializer->serialize($token, 'json');

        $cacheItem = $this->redisAdapter->getItem($key);
        $cacheItem->set($serializedToken);
        $this->redisAdapter->save($cacheItem);
    }

    public function find(string $token): Token
    {
        $key = self::REDIS_KEY_PREFIX.$token;

        $cacheItem = $this->redisAdapter->getItem($key);
        $serializedToken = $cacheItem->get();

        if (null !== $serializedToken) {
            return $this->serializer->deserialize($serializedToken, Token::class, 'json');
        } else {
            throw new TokenNotFoundError();
        }
    }
}
