<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class RedisPasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    private const REDIS_KEY_PREFIX = 'password-reset-token-';
    private const TOKEN_VALUE_PREFIX = 'tokenValue-';
    private const USER_ID_PREFIX = 'userID-';

    private const EXPIRES_AFTER_IN_SECONDS = 3600; // 1 hour

    public function __construct(
        private CacheInterface $redisAdapter,
        private SerializerInterface $serializer
    ) {
    }

    public function save(PasswordResetTokenInterface $token): void
    {
        $this->saveForTokenValue($token);
        $this->saveForUserId($token);
    }

    public function find(string $tokenValue): ?PasswordResetTokenInterface
    {
        $key = $this->getTokenKey($tokenValue);

        return $this->getFromCache($key);
    }

    public function findByUserId(string $userID): ?PasswordResetTokenInterface
    {
        $key = $this->getUserKey($userID);

        return $this->getFromCache($key);
    }

    public function delete(PasswordResetTokenInterface $token): void
    {
        $this->deleteForTokenValue($token);
        $this->deleteForUserId($token);
    }

    private function saveForTokenValue(PasswordResetTokenInterface $token): void
    {
        $tokenValue = $token->getTokenValue();

        $serializedToken = $this->serializer->serialize(
            $token,
            JsonEncoder::FORMAT
        );

        $this->saveToCache($this->getTokenKey($tokenValue), $serializedToken);
    }

    private function saveForUserId(PasswordResetTokenInterface $token): void
    {
        $userId = $token->getUserID();

        $serializedToken = $this->serializer->serialize(
            $token,
            JsonEncoder::FORMAT
        );

        $this->saveToCache($this->getUserKey($userId), $serializedToken);
    }

    private function getFromCache(string $key): ?PasswordResetTokenInterface
    {
        $cacheItem = $this->redisAdapter->getItem($key);
        $serializedToken = $cacheItem->get();

        return $serializedToken ? $this->serializer->deserialize(
            $serializedToken,
            PasswordResetToken::class,
            JsonEncoder::FORMAT
        )
            : null;
    }

    private function saveToCache(string $key, string $value): void
    {
        $cacheItem = $this->redisAdapter->getItem($key);
        $cacheItem->set($value);
        $cacheItem->expiresAfter(self::EXPIRES_AFTER_IN_SECONDS);
        $this->redisAdapter->save($cacheItem);
    }

    private function deleteForTokenValue(PasswordResetTokenInterface $token): void
    {
        $tokenValue = $token->getTokenValue();
        $this->redisAdapter->delete($this->getTokenKey($tokenValue));
    }

    private function deleteForUserId(PasswordResetTokenInterface $token): void
    {
        $userId = $token->getUserID();
        $this->redisAdapter->delete($this->getUserKey($userId));
    }

    private function getTokenKey(string $tokenValue): string
    {
        return self::REDIS_KEY_PREFIX . self::TOKEN_VALUE_PREFIX . $tokenValue;
    }

    private function getUserKey(string $userID): string
    {
        return self::REDIS_KEY_PREFIX . self::USER_ID_PREFIX . $userID;
    }
}