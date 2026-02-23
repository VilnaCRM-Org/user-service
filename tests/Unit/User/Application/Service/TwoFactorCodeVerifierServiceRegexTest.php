<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\TwoFactorCodeVerifierService;
use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class TwoFactorCodeVerifierServiceRegexTest extends UnitTestCase
{
    private TOTPVerifierInterface $totpVerifier;
    private TwoFactorSecretEncryptorInterface $encryptor;
    private RecoveryCodeRepositoryInterface $recoveryCodeRepository;
    private UuidTransformer $uuidTransformer;
    private UserFactory $userFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->totpVerifier = $this->createMock(TOTPVerifierInterface::class);
        $this->encryptor = $this->createMock(TwoFactorSecretEncryptorInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->userFactory = new UserFactory();
    }

    public function testVerifyAndConsumeOrFailThrowsForRecoveryCodeWithTrailingChars(): void
    {
        $base = $this->faker->regexify('[A-Za-z0-9]{4}-[A-Za-z0-9]{4}');
        $code = $base . 'X';
        $user = $this->createUser();
        $recoveryCode = new RecoveryCode($this->faker->uuid(), $user->getId(), $code);

        $this->recoveryCodeRepository->method('findByUserId')->willReturn([$recoveryCode]);

        $this->expectException(UnauthorizedHttpException::class);
        $this->createService()->verifyAndConsumeOrFail($user, $code);
    }

    public function testVerifyAndConsumeOrFailThrowsForRecoveryCodeWithLeadingChars(): void
    {
        $base = $this->faker->regexify('[A-Za-z0-9]{4}-[A-Za-z0-9]{4}');
        $code = 'X' . $base;
        $user = $this->createUser();
        $recoveryCode = new RecoveryCode($this->faker->uuid(), $user->getId(), $code);

        $this->recoveryCodeRepository->method('findByUserId')->willReturn([$recoveryCode]);

        $this->expectException(UnauthorizedHttpException::class);
        $this->createService()->verifyAndConsumeOrFail($user, $code);
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

    private function createService(): TwoFactorCodeVerifierService
    {
        return new TwoFactorCodeVerifierService(
            $this->totpVerifier,
            $this->encryptor,
            $this->recoveryCodeRepository
        );
    }
}
