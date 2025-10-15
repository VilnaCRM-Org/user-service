<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Infrastructure\Repository\RedisTokenRepository;
use ReflectionProperty;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class RedisTokenRepositoryTest extends UnitTestCase
{
    private TokenRepositoryInterface $repository;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private CacheInterface $cache;
    private SerializerInterface $serializer;
    private SerializerInterface $mockSerializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(RedisAdapter::class);
        $this->serializer =
            new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $this->mockSerializer =
            $this->createMock(SerializerInterface::class);
        $this->repository =
            new RedisTokenRepository($this->cache, $this->mockSerializer);
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
    }

    public function testSave(): void
    {
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);
        $serializedToken =
            $this->serializer->serialize($token, JsonEncoder::FORMAT);

        $tokenValue = $token->getTokenValue();
        $userKey = 'token-userID-'.$token->getUserID();
        $tokenKey = 'token-tokenValue-'.$tokenValue;

        $this->cache->expects($this->exactly(2))
            ->method('getItem')
            ->withConsecutive([$tokenKey], [$userKey])
            ->willReturnOnConsecutiveCalls(
                $this->createCacheItem(key: $tokenKey),
                $this->createCacheItem(key: $userKey)
            );
        $this->mockSerializer->expects($this->once())
            ->method('serialize')
            ->with($token, JsonEncoder::FORMAT)
            ->willReturn($serializedToken);

        $this->cache->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function (CacheItem $item) use ($serializedToken) {
                $this->assertSame($serializedToken, $item->get());

                $expiry = new ReflectionProperty(CacheItem::class, 'expiry');
                $expiry->setAccessible(true);
                $this->assertNotNull($expiry->getValue($item));

                return true;
            });

        $this->repository->save($token);
    }

    public function testFind(): void
    {
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);
        $tokenValue = $token->getTokenValue();
        $serializedToken = $this->serializer->serialize($token, JsonEncoder::FORMAT);
        $tokenKey = 'token-tokenValue-'.$tokenValue;

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($tokenKey)
            ->willReturn($this->createCacheItem($serializedToken, $tokenKey));
        $this->mockSerializer->expects($this->once())
            ->method('deserialize')
            ->with($serializedToken, ConfirmationToken::class, JsonEncoder::FORMAT)
            ->willReturn($token);

        $result = $this->repository->find($tokenValue);

        $this->assertSame($token, $result);
    }

    public function testFindByUserId(): void
    {
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);
        $serializedToken = $this->serializer->serialize($token, JsonEncoder::FORMAT);
        $userKey = 'token-userID-'.$userId;

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($userKey)
            ->willReturn($this->createCacheItem($serializedToken, $userKey));
        $this->mockSerializer->expects($this->once())
            ->method('deserialize')
            ->with($serializedToken, ConfirmationToken::class, JsonEncoder::FORMAT)
            ->willReturn($token);

        $this->assertSame($token, $this->repository->findByUserId($userId));
    }

    public function testFindReturnsNullWhenCacheIsEmpty(): void
    {
        $tokenValue = $this->faker->uuid();
        $tokenKey = 'token-tokenValue-'.$tokenValue;

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($tokenKey)
            ->willReturn($this->createCacheItem(key: $tokenKey));
        $this->mockSerializer->expects($this->never())->method('deserialize');

        $this->assertNull($this->repository->find($tokenValue));
    }

    public function testDelete(): void
    {
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);
        $tokenKey = 'token-tokenValue-'.$token->getTokenValue();
        $userKey = 'token-userID-'.$userId;

        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->withConsecutive([$tokenKey], [$userKey])
            ->willReturn(true);

        $this->repository->delete($token);
    }

    private function createCacheItem(?string $value = null, ?string $key = null): CacheItem
    {
        $item = new CacheItem();

        if ($key !== null) {
            $property = new ReflectionProperty(CacheItem::class, 'key');
            $property->setAccessible(true);
            $property->setValue($item, $key);
        }

        if ($value !== null) {
            $item->set($value);
        }

        return $item;
    }
}
