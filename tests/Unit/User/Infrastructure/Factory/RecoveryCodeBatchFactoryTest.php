<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\RecoveryCodeGenerationFailedException;
use App\User\Domain\Factory\RecoveryCodeFactoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Infrastructure\Factory\RecoveryCodeBatchFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

final class RecoveryCodeBatchFactoryTest extends UnitTestCase
{
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private RecoveryCodeFactoryInterface&MockObject $recoveryCodeFactory;
    private UlidFactory&MockObject $ulidFactory;
    private RecoveryCodeBatchFactory $factory;
    /**
     * @var list<string>
     */
    private array $randomByteChunks = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->randomByteChunks = [];

        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->recoveryCodeFactory = $this->createMock(RecoveryCodeFactoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);

        $this->factory = $this->createFactory();
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

    public function testCreateKeepsReadingCurrentRandomChunkAfterRejectedByte(): void
    {
        $user = $this->createUserWithId($this->faker->uuid());
        $this->stubRecoveryCodeCreation();

        $this->recoveryCodeRepository->expects($this->once())
            ->method('saveAll');

        $remainingCodeChunks = array_fill(
            0,
            RecoveryCode::COUNT - 1,
            str_repeat("\x08", RecoveryCode::SEGMENT_LENGTH * 2)
        );
        $this->queueRandomBytes(
            "\x00\xFC\x01\x02\x03\x04\x05\x06",
            str_repeat("\x07", RecoveryCode::SEGMENT_LENGTH * 2),
            ...$remainingCodeChunks,
        );
        $this->factory = $this->createFactory(\Closure::fromCallable([$this, 'nextRandomBytes']));

        $codes = $this->factory->create($user);

        $this->assertSame('ABCD-EFGH', $codes[0]);
    }

    public function testCreateFailsWhenRandomByteGeneratorReturnsEmptyString(): void
    {
        $user = $this->createUserWithId($this->faker->uuid());
        $this->stubRecoveryCodeCreation();
        $this->factory = $this->createFactory(static fn (int $_length): string => '');

        $this->expectException(RecoveryCodeGenerationFailedException::class);
        $this->expectExceptionMessage('Random byte generator returned no bytes.');

        $this->factory->create($user);
    }

    public function testCreateFailsWhenRandomByteGeneratorProducesOnlyRejectedBytes(): void
    {
        $user = $this->createUserWithId($this->faker->uuid());
        $this->stubRecoveryCodeCreation();
        $this->factory = $this->createFactory(
            static fn (int $length): string => str_repeat("\xFC", $length)
        );

        $this->expectException(RecoveryCodeGenerationFailedException::class);
        $this->expectExceptionMessage('Random byte generator did not produce usable bytes.');

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

    private function createUserWithId(string $userId): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        return $user;
    }

    private function stubRecoveryCodeCreation(): void
    {
        $ulid = $this->createMock(Ulid::class);
        $ulid->method('__toString')->willReturn($this->faker->uuid());
        $this->ulidFactory->method('create')->willReturn($ulid);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $this->recoveryCodeFactory->method('create')->willReturn($recoveryCode);
    }

    private function createFactory(?\Closure $randomBytes = null): RecoveryCodeBatchFactory
    {
        return new RecoveryCodeBatchFactory(
            $this->recoveryCodeRepository,
            $this->recoveryCodeFactory,
            $this->ulidFactory,
            $randomBytes,
        );
    }

    private function queueRandomBytes(string ...$byteChunks): void
    {
        $this->randomByteChunks = $byteChunks;
    }

    private function nextRandomBytes(int $length): string
    {
        $bytes = array_shift($this->randomByteChunks);
        if ($bytes === null) {
            self::fail('Random byte fixture queue is empty.');
        }

        self::assertSame(
            $length,
            strlen($bytes),
            'Random byte fixture chunk length should match the requested length.'
        );

        return $bytes;
    }
}
