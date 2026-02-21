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

final class TwoFactorCodeVerifierServiceTest extends UnitTestCase
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

    public function testVerifyTotpOrFailSucceeds(): void
    {
        $secret = $this->faker->sha256();
        $decrypted = $this->faker->sha256();
        $code = $this->faker->numerify('######');

        $user = $this->createUser();
        $user->setTwoFactorSecret($secret);

        $this->encryptor->expects($this->once())->method('decrypt')->with($secret)->willReturn($decrypted);
        $this->totpVerifier->expects($this->once())->method('verify')->with($decrypted, $code)->willReturn(true);

        $this->createService()->verifyTotpOrFail($user, $code);
    }

    public function testVerifyTotpOrFailThrowsOnInvalidCode(): void
    {
        $secret = $this->faker->sha256();
        $decrypted = $this->faker->sha256();
        $code = $this->faker->numerify('######');

        $user = $this->createUser();
        $user->setTwoFactorSecret($secret);

        $this->encryptor->expects($this->once())->method('decrypt')->with($secret)->willReturn($decrypted);
        $this->totpVerifier->expects($this->once())->method('verify')->with($decrypted, $code)->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);
        $this->createService()->verifyTotpOrFail($user, $code);
    }

    public function testVerifyAndConsumeOrFailWithTotpCode(): void
    {
        $secret = $this->faker->sha256();
        $decrypted = $this->faker->sha256();
        $code = $this->faker->numerify('######');

        $user = $this->createUser();
        $user->setTwoFactorSecret($secret);

        $this->encryptor->expects($this->once())->method('decrypt')->willReturn($decrypted);
        $this->totpVerifier->expects($this->once())->method('verify')->with($decrypted, $code)->willReturn(true);

        $this->createService()->verifyAndConsumeOrFail($user, $code);
    }

    public function testVerifyAndConsumeOrFailWithRecoveryCode(): void
    {
        $plainCode = $this->faker->regexify('[A-Za-z0-9]{4}-[A-Za-z0-9]{4}');
        $user = $this->createUser();
        $recoveryCode = new RecoveryCode($this->faker->uuid(), $user->getId(), $plainCode);

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$recoveryCode]);
        $this->recoveryCodeRepository->expects($this->once())->method('save');

        $this->createService()->verifyAndConsumeOrFail($user, $plainCode);
    }

    public function testVerifyAndConsumeOrFailThrowsOnInvalidCode(): void
    {
        $user = $this->createUser();

        $this->expectException(UnauthorizedHttpException::class);
        $this->createService()->verifyAndConsumeOrFail($user, $this->faker->word());
    }

    public function testVerifyAndConsumeOrFailThrowsWhenRecoveryCodeNotFound(): void
    {
        $plainCode = $this->faker->regexify('[A-Za-z0-9]{4}-[A-Za-z0-9]{4}');
        $user = $this->createUser();

        $this->recoveryCodeRepository
            ->method('findByUserId')
            ->willReturn([]);

        $this->expectException(UnauthorizedHttpException::class);
        $this->createService()->verifyAndConsumeOrFail($user, $plainCode);
    }

    public function testResolveVerificationMethodReturnsTotpOnSuccess(): void
    {
        $secret = $this->faker->sha256();
        $decrypted = $this->faker->sha256();
        $code = $this->faker->numerify('######');

        $user = $this->createUser();
        $user->setTwoFactorSecret($secret);

        $this->encryptor->expects($this->once())->method('decrypt')->willReturn($decrypted);
        $this->totpVerifier->expects($this->once())->method('verify')->with($decrypted, $code)->willReturn(true);

        $this->assertSame('totp', $this->createService()->resolveVerificationMethod($user, $code));
    }

    public function testResolveVerificationMethodReturnsNullOnTotpFailure(): void
    {
        $secret = $this->faker->sha256();
        $code = $this->faker->numerify('######');

        $user = $this->createUser();
        $user->setTwoFactorSecret($secret);

        $this->encryptor->method('decrypt')->willReturn($this->faker->sha256());
        $this->totpVerifier->method('verify')->willReturn(false);

        $this->assertNull($this->createService()->resolveVerificationMethod($user, $code));
    }

    public function testResolveVerificationMethodReturnsNullWhenSecretIsNull(): void
    {
        $code = $this->faker->numerify('######');
        $user = $this->createUser();

        $this->assertNull($this->createService()->resolveVerificationMethod($user, $code));
    }

    public function testResolveVerificationMethodReturnsRecoveryCodeLabel(): void
    {
        $plainCode = $this->faker->regexify('[A-Za-z0-9]{4}-[A-Za-z0-9]{4}');
        $user = $this->createUser();
        $recoveryCode = new RecoveryCode($this->faker->uuid(), $user->getId(), $plainCode);

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->willReturn([$recoveryCode]);
        $this->recoveryCodeRepository->expects($this->once())->method('save');

        $this->assertSame('recovery_code', $this->createService()->resolveVerificationMethod($user, $plainCode));
    }

    public function testResolveVerificationMethodReturnsNullWhenRecoveryCodeNotFound(): void
    {
        $plainCode = $this->faker->regexify('[A-Za-z0-9]{4}-[A-Za-z0-9]{4}');
        $user = $this->createUser();

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->willReturn([]);

        $this->assertNull($this->createService()->resolveVerificationMethod($user, $plainCode));
    }

    public function testResolveVerificationMethodReturnsNullForUnknownFormat(): void
    {
        $user = $this->createUser();

        $this->assertNull($this->createService()->resolveVerificationMethod($user, $this->faker->word()));
    }

    public function testCountRemainingCodesCountsOnlyUnused(): void
    {
        $user = $this->createUser();
        $userId = $user->getId();

        $unusedCode1 = new RecoveryCode($this->faker->uuid(), $userId, $this->faker->regexify('[A-Za-z0-9]{4}-[A-Za-z0-9]{4}'));
        $unusedCode2 = new RecoveryCode($this->faker->uuid(), $userId, $this->faker->regexify('[A-Za-z0-9]{4}-[A-Za-z0-9]{4}'));
        $usedCode = new RecoveryCode($this->faker->uuid(), $userId, $this->faker->regexify('[A-Za-z0-9]{4}-[A-Za-z0-9]{4}'));
        $usedCode->markAsUsed();

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn([$unusedCode1, $usedCode, $unusedCode2]);

        $this->assertSame(2, $this->createService()->countRemainingCodes($userId));
    }

    public function testCountRemainingCodesReturnsZeroWhenAllUsed(): void
    {
        $userId = $this->faker->uuid();
        $usedCode = new RecoveryCode($this->faker->uuid(), $userId, $this->faker->regexify('[A-Za-z0-9]{4}-[A-Za-z0-9]{4}'));
        $usedCode->markAsUsed();

        $this->recoveryCodeRepository
            ->method('findByUserId')
            ->willReturn([$usedCode]);

        $this->assertSame(0, $this->createService()->countRemainingCodes($userId));
    }

    public function testCountRemainingCodesReturnsZeroWhenEmpty(): void
    {
        $userId = $this->faker->uuid();

        $this->recoveryCodeRepository
            ->method('findByUserId')
            ->willReturn([]);

        $this->assertSame(0, $this->createService()->countRemainingCodes($userId));
    }

    public function testDecryptSecretFallsBackToRawOnThrowable(): void
    {
        $secret = $this->faker->sha256();
        $code = $this->faker->numerify('######');

        $user = $this->createUser();
        $user->setTwoFactorSecret($secret);

        $this->encryptor
            ->expects($this->once())
            ->method('decrypt')
            ->willThrowException(new \RuntimeException('decryption failed'));
        $this->totpVerifier
            ->expects($this->once())
            ->method('verify')
            ->with($secret, $code)
            ->willReturn(true);

        $this->createService()->verifyTotpOrFail($user, $code);
    }

    public function testIsRecoveryCodeMatchesXxxxYyyyFormat(): void
    {
        $plainCode = $this->faker->regexify('[A-F0-9]{4}-[A-F0-9]{4}');
        $user = $this->createUser();
        $recoveryCode = new RecoveryCode($this->faker->uuid(), $user->getId(), $plainCode);

        $this->recoveryCodeRepository->method('findByUserId')->willReturn([$recoveryCode]);
        $this->recoveryCodeRepository->expects($this->once())->method('save');

        $this->createService()->verifyAndConsumeOrFail($user, $plainCode);
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
