<?php

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Infrastructure\Repository\RedisTokenRepository;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class RedisTokenRepositoryTest extends UnitTestCase
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
        $this->serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $this->mockSerializer = $this->createMock(SerializerInterface::class);
        $this->repository = new RedisTokenRepository($this->cache, $this->mockSerializer);
        $this->confirmationTokenFactory = new ConfirmationTokenFactory($this->faker->numberBetween(1, 10));
    }

    public function testSave(): void
    {
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);
        $serializedToken = $this->serializer->serialize($token, JsonEncoder::FORMAT);

        $this->cache->expects($this->exactly(2))
            ->method('getItem');
        $this->mockSerializer->expects($this->exactly(2))
            ->method('serialize')
            ->willReturn($serializedToken);

        $this->repository->save($token);
    }

    public function testFind(): void
    {
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);
        $tokenValue = $token->getTokenValue();

        $this->cache->expects($this->once())
            ->method('getItem');

        $this->repository->find($tokenValue);
    }

    public function testFindByUserId(): void
    {
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);
        $serializedToken = $this->serializer->serialize($token, JsonEncoder::FORMAT);

        $this->cache->expects($this->once())
            ->method('getItem');

        $this->repository->findByUserId($userId);
    }

    public function testDelete(): void
    {
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);

        $this->cache->expects($this->exactly(2))
            ->method('delete');

        $this->repository->delete($token);
    }
}
