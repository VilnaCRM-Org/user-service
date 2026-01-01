<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\ConfirmationTokenInterface;
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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(RedisAdapter::class);
        $this->serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $this->mockSerializer = $this->createMock(SerializerInterface::class);

        $this->repository = new RedisTokenRepository($this->cache, $this->mockSerializer);
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
    }

    public function testSave(): void
    {
        $token = $this->confirmationTokenFactory->create($this->faker->uuid());
        $serializedToken = $this->serializer->serialize($token, JsonEncoder::FORMAT);

        $keys = $this->tokenKeys($token);
        $this->expectSavingToken($token, $serializedToken, $keys['tokenKey'], $keys['userKey']);

        $this->repository->save($token);
    }

    public function testFind(): void
    {
        $token = $this->confirmationTokenFactory->create($this->faker->uuid());
        $serializedToken = $this->serializer->serialize($token, JsonEncoder::FORMAT);
        $tokenKey = $this->tokenKey($token->getTokenValue());

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($tokenKey)
            ->willReturn($this->createCacheItem($serializedToken, $tokenKey));

        $this->mockSerializer->expects($this->once())
            ->method('deserialize')
            ->with($serializedToken, ConfirmationToken::class, JsonEncoder::FORMAT)
            ->willReturn($token);

        $result = $this->repository->find($token->getTokenValue());

        $this->assertSame($token, $result);
    }

    public function testFindByUserId(): void
    {
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);
        $serializedToken = $this->serializer->serialize($token, JsonEncoder::FORMAT);
        $userKey = $this->userKey($userId);

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($userKey)
            ->willReturn($this->createCacheItem($serializedToken, $userKey));

        $this->mockSerializer->expects($this->once())
            ->method('deserialize')
            ->with($serializedToken, ConfirmationToken::class, JsonEncoder::FORMAT)
            ->willReturn($token);

        $result = $this->repository->findByUserId($userId);

        $this->assertSame($token, $result);
    }

    public function testRemove(): void
    {
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);

        $keys = $this->tokenKeys($token);

        $this->cache->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(
                $this->expectSequential(
                    [[$keys['tokenKey']], [$keys['userKey']]],
                    [true, true]
                )
            );

        $this->repository->remove($token);
    }

    public function testRemoveByTokenValue(): void
    {
        $tokenValue = $this->faker->lexify('??????????');
        $tokenKey = $this->tokenKey($tokenValue);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with($tokenKey)
            ->willReturn(true);

        $this->repository->removeByTokenValue($tokenValue);
    }

    public function testRemoveByUserId(): void
    {
        $userId = $this->faker->uuid();
        $userKey = $this->userKey($userId);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with($userKey)
            ->willReturn(true);

        $this->repository->removeByUserId($userId);
    }

    public function testRemoveByUserIdWhenCacheDeleteFails(): void
    {
        $userId = $this->faker->uuid();
        $userKey = $this->userKey($userId);

        $this->cache->expects($this->once())
            ->method('delete')
            ->with($userKey)
            ->willReturn(false);

        $this->repository->removeByUserId($userId);
    }

    /**
     * @return array{tokenKey: string, userKey: string}
     */
    private function tokenKeys(ConfirmationTokenInterface $token): array
    {
        return [
            'tokenKey' => $this->tokenKey($token->getTokenValue()),
            'userKey' => $this->userKey($token->getUserID()),
        ];
    }

    private function tokenKey(string $tokenValue): string
    {
        return 'token-tokenValue-'.$tokenValue;
    }

    private function userKey(string $userId): string
    {
        return 'token-userID-'.$userId;
    }

    private function expectSavingToken(
        ConfirmationTokenInterface $token,
        string $serializedToken,
        string $tokenKey,
        string $userKey
    ): void {
        $this->expectCacheItemsForSave($tokenKey, $userKey);
        $this->expectTokenSerialization($token, $serializedToken);
        $this->expectCacheSavesSerializedToken($serializedToken);
    }

    private function expectCacheItemsForSave(string $tokenKey, string $userKey): void
    {
        $this->cache->expects($this->exactly(2))
            ->method('getItem')
            ->willReturnCallback(
                $this->expectSequential(
                    [[$tokenKey], [$userKey]],
                    [
                        $this->createCacheItem(key: $tokenKey),
                        $this->createCacheItem(key: $userKey),
                    ]
                )
            );
    }

    private function expectTokenSerialization(
        ConfirmationTokenInterface $token,
        string $serializedToken
    ): void {
        $this->mockSerializer->expects($this->once())
            ->method('serialize')
            ->with($token, JsonEncoder::FORMAT)
            ->willReturn($serializedToken);
    }

    private function expectCacheSavesSerializedToken(string $serializedToken): void
    {
        $this->cache->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(
                function (CacheItem $item) use ($serializedToken): bool {
                    $this->assertSame($serializedToken, $item->get());
                    $this->assertNotNull($this->expiry($item));

                    return true;
                }
            );
    }

    private function expiry(CacheItem $item): mixed
    {
        $expiry = new ReflectionProperty(CacheItem::class, 'expiry');
        $this->makeAccessible($expiry);

        return $expiry->getValue($item);
    }
}
