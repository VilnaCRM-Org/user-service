<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\RecoveryCodeFactoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Infrastructure\Factory\RecoveryCodeBatchFactory;
use function array_map;
use function chr;
use Closure;
use function implode;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

final class RecoveryCodeBatchFactoryTest extends UnitTestCase
{
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private RecoveryCodeFactoryInterface&MockObject $recoveryCodeFactory;
    private UlidFactory&MockObject $ulidFactory;
    private RecoveryCodeBatchFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->recoveryCodeFactory = $this->createMock(RecoveryCodeFactoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);

        $this->factory = new RecoveryCodeBatchFactory(
            $this->recoveryCodeRepository,
            $this->recoveryCodeFactory,
            $this->ulidFactory,
        );
    }

    public function testCreateReturnsCorrectNumberOfCodes(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($this->faker->uuid());

        $ulid = $this->createMock(Ulid::class);
        $ulid->method('__toString')->willReturn($this->faker->uuid());
        $this->ulidFactory->method('create')->willReturn($ulid);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $this->recoveryCodeFactory->method('create')->willReturn($recoveryCode);

        $this->recoveryCodeRepository->expects($this->once())
            ->method('saveAll');

        $codes = $this->factory->create($user);

        $this->assertCount(RecoveryCode::COUNT, $codes);
    }

    public function testCreateReturnsValidCodeFormat(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($this->faker->uuid());

        $ulid = $this->createMock(Ulid::class);
        $ulid->method('__toString')->willReturn($this->faker->uuid());
        $this->ulidFactory->method('create')->willReturn($ulid);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $this->recoveryCodeFactory->method('create')->willReturn($recoveryCode);

        $codes = $this->factory->create($user);

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression(
                '/^[A-Z0-9]{4}-[A-Z0-9]{4}$/',
                $code
            );
        }
    }

    public function testCreatePassesCorrectArgumentsToFactory(): void
    {
        $userId = $this->faker->uuid();
        $ulidString = $this->faker->uuid();

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        $ulid = $this->createMock(Ulid::class);
        $ulid->method('__toString')->willReturn($ulidString);
        $this->ulidFactory->method('create')->willReturn($ulid);

        $recoveryCode = $this->createMock(RecoveryCode::class);

        $this->recoveryCodeFactory->expects($this->exactly(RecoveryCode::COUNT))
            ->method('create')
            ->with(
                $ulidString,
                $userId,
                $this->matchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/')
            )
            ->willReturn($recoveryCode);

        $this->recoveryCodeRepository->expects($this->once())
            ->method('saveAll');

        $this->factory->create($user);
    }

    public function testCreateReturnsUniqueCodesWithHighProbability(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($this->faker->uuid());

        $ulid = $this->createMock(Ulid::class);
        $ulid->method('__toString')->willReturn($this->faker->uuid());
        $this->ulidFactory->method('create')->willReturn($ulid);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $this->recoveryCodeFactory->method('create')->willReturn($recoveryCode);

        $codes = $this->factory->create($user);

        $this->assertSame(
            count(array_unique($codes)),
            count($codes),
            'Generated codes should be unique'
        );
    }

    public function testCreateSkipsBiasedRandomBytesWhenGeneratingCode(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($this->faker->uuid());

        $ulid = $this->createMock(Ulid::class);
        $ulid->method('__toString')->willReturn($this->faker->uuid());
        $this->ulidFactory->method('create')->willReturn($ulid);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $this->recoveryCodeFactory->method('create')->willReturn($recoveryCode);

        $factory = $this->createFactoryWithRandomBytes(
            $this->biasedThenValidRandomBytes()
        );

        $codes = $factory->create($user);

        $this->assertSame('BCDE-FGHI', $codes[0]);
    }

    /**
     * @return Closure(int): string
     */
    private function biasedThenValidRandomBytes(): Closure
    {
        $calls = 0;

        return function (int $length) use (&$calls): string {
            $this->assertSame(RecoveryCode::SEGMENT_LENGTH * 2, $length);
            $currentCall = $calls;
            $calls++;

            return match ($currentCall) {
                0 => $this->bytes(255, 1, 2, 3, 4, 5, 6, 7),
                1 => $this->bytes(8, 9, 10, 11, 12, 13, 14, 15),
                default => $this->bytes(0, 0, 0, 0, 0, 0, 0, 0),
            };
        };
    }

    /**
     * @param Closure(int): string $randomBytes
     */
    private function createFactoryWithRandomBytes(
        Closure $randomBytes
    ): RecoveryCodeBatchFactory {
        return new RecoveryCodeBatchFactory(
            $this->recoveryCodeRepository,
            $this->recoveryCodeFactory,
            $this->ulidFactory,
            $randomBytes,
        );
    }

    private function bytes(int ...$values): string
    {
        return implode('', array_map(static fn (int $value): string => chr($value), $values));
    }
}
