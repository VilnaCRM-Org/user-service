<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\RecoveryCodeGeneratorService;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

final class RecoveryCodeGeneratorServiceTest extends UnitTestCase
{
    private RecoveryCodeRepositoryInterface $recoveryCodeRepository;
    private UlidFactory $ulidFactory;
    private UuidTransformer $uuidTransformer;
    private UserFactory $userFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->userFactory = new UserFactory();
    }

    public function testGenerateAndStoreCreatesExactlyEightCodes(): void
    {
        $user = $this->createUser();

        $this->ulidFactory->method('create')->willReturnCallback(static fn () => new Ulid());
        $this->recoveryCodeRepository->expects($this->exactly(8))->method('save');

        $codes = $this->createService()->generateAndStore($user);

        $this->assertCount(8, $codes);
    }

    public function testGenerateAndStoreReturnsCodesMatchingExpectedFormat(): void
    {
        $user = $this->createUser();

        $this->ulidFactory->method('create')->willReturnCallback(static fn () => new Ulid());
        $this->recoveryCodeRepository->method('save');

        $codes = $this->createService()->generateAndStore($user);

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/', $code);
        }
    }

    public function testGenerateAndStoreReturnsUniqueCodes(): void
    {
        $user = $this->createUser();

        $this->ulidFactory->method('create')->willReturnCallback(static fn () => new Ulid());
        $this->recoveryCodeRepository->method('save');

        $codes = $this->createService()->generateAndStore($user);

        $this->assertSame(count($codes), count(array_unique($codes)));
    }

    public function testGenerateAndStoreSavesEachCodeWithCorrectUserId(): void
    {
        $user = $this->createUser();
        $expectedUserId = $user->getId();

        $this->ulidFactory->method('create')->willReturnCallback(static fn () => new Ulid());

        $this->recoveryCodeRepository
            ->expects($this->exactly(8))
            ->method('save')
            ->willReturnCallback(function (RecoveryCode $code) use ($expectedUserId): void {
                $this->assertSame($expectedUserId, $code->getUserId());
            });

        $this->createService()->generateAndStore($user);
    }

    public function testGenerateAndStoreSavesEachCodeWithUniqueId(): void
    {
        $user = $this->createUser();
        $savedIds = [];

        $this->ulidFactory->method('create')->willReturnCallback(static fn () => new Ulid());

        $this->recoveryCodeRepository
            ->expects($this->exactly(8))
            ->method('save')
            ->willReturnCallback(static function (RecoveryCode $code) use (&$savedIds): void {
                $savedIds[] = $code->getId();
            });

        $this->createService()->generateAndStore($user);

        $this->assertSame(8, count(array_unique($savedIds)));
    }

    public function testGenerateAndStoreReturnedCodesMatchSavedHashes(): void
    {
        $user = $this->createUser();
        $savedCodes = [];

        $this->ulidFactory->method('create')->willReturnCallback(static fn () => new Ulid());

        $this->recoveryCodeRepository
            ->method('save')
            ->willReturnCallback(static function (RecoveryCode $code) use (&$savedCodes): void {
                $savedCodes[] = $code;
            });

        $plainCodes = $this->createService()->generateAndStore($user);

        $this->assertCount(8, $plainCodes);
        foreach ($plainCodes as $index => $plain) {
            $this->assertTrue($savedCodes[$index]->matchesCode($plain));
        }
    }

    public function testGenerateAndStoreSavesCodeWithUlidFromFactory(): void
    {
        $user = $this->createUser();
        $ulid = new Ulid();
        $ulidString = (string) $ulid;

        $this->ulidFactory->method('create')->willReturn($ulid);

        $this->recoveryCodeRepository
            ->method('save')
            ->willReturnCallback(function (RecoveryCode $code) use ($ulidString): void {
                $this->assertSame($ulidString, $code->getId());
            });

        $this->createService()->generateAndStore($user);
    }

    public function testGenerateAndStoreReturnsUppercaseCodes(): void
    {
        $user = $this->createUser();
        $this->ulidFactory->method('create')->willReturnCallback(static fn () => new Ulid());
        $this->recoveryCodeRepository->method('save');

        $codes = $this->createService()->generateAndStore($user);

        foreach ($codes as $code) {
            foreach (explode('-', $code) as $segment) {
                self::assertSame(strtoupper($segment), $segment);
            }
        }
    }

    private function createUser(): User
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }

    private function createService(): RecoveryCodeGeneratorService
    {
        return new RecoveryCodeGeneratorService(
            $this->recoveryCodeRepository,
            $this->ulidFactory
        );
    }
}
