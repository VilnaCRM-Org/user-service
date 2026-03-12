<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Generator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\Generator\RecoveryCodeGenerator;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\RecoveryCodeFactoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

final class RecoveryCodeGeneratorTest extends UnitTestCase
{
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private RecoveryCodeFactoryInterface&MockObject $recoveryCodeFactory;
    private UlidFactory&MockObject $ulidFactory;
    private RecoveryCodeGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->recoveryCodeFactory = $this->createMock(RecoveryCodeFactoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);

        $this->generator = new RecoveryCodeGenerator(
            $this->recoveryCodeRepository,
            $this->recoveryCodeFactory,
            $this->ulidFactory,
        );
    }

    public function testGenerateAndStoreReturnsCorrectNumberOfCodes(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($this->faker->uuid());

        $ulid = $this->createMock(Ulid::class);
        $ulid->method('__toString')->willReturn($this->faker->uuid());
        $this->ulidFactory->method('create')->willReturn($ulid);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $this->recoveryCodeFactory->method('create')->willReturn($recoveryCode);

        $this->recoveryCodeRepository->expects($this->exactly(RecoveryCode::COUNT))
            ->method('save');

        $codes = $this->generator->generateAndStore($user);

        $this->assertCount(RecoveryCode::COUNT, $codes);
    }

    public function testGenerateAndStoreReturnsValidCodeFormat(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($this->faker->uuid());

        $ulid = $this->createMock(Ulid::class);
        $ulid->method('__toString')->willReturn($this->faker->uuid());
        $this->ulidFactory->method('create')->willReturn($ulid);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $this->recoveryCodeFactory->method('create')->willReturn($recoveryCode);

        $codes = $this->generator->generateAndStore($user);

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression(
                '/^[A-Z0-9]{4}-[A-Z0-9]{4}$/',
                $code
            );
        }
    }

    public function testGenerateAndStorePassesCorrectArgumentsToFactory(): void
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

        $this->generator->generateAndStore($user);
    }

    public function testGenerateAndStoreReturnsUniqueCodesWithHighProbability(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($this->faker->uuid());

        $ulid = $this->createMock(Ulid::class);
        $ulid->method('__toString')->willReturn($this->faker->uuid());
        $this->ulidFactory->method('create')->willReturn($ulid);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $this->recoveryCodeFactory->method('create')->willReturn($recoveryCode);

        $codes = $this->generator->generateAndStore($user);

        $this->assertSame(
            count(array_unique($codes)),
            count($codes),
            'Generated codes should be unique'
        );
    }
}
