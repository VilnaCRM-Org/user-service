<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Verifier;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Transformer\TwoFactorSecretEncryptorInterface;
use App\User\Application\Validator\Verifier\TOTPVerifierInterface;
use App\User\Application\Validator\Verifier\TwoFactorCodeVerifier;
use App\User\Application\Validator\Verifier\TwoFactorCodeVerifierInterface;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class TwoFactorCodeVerifierTest extends UnitTestCase
{
    private TOTPVerifierInterface&MockObject $totpVerifier;
    private TwoFactorSecretEncryptorInterface&MockObject $encryptor;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private TwoFactorCodeVerifier $verifier;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->totpVerifier = $this->createMock(TOTPVerifierInterface::class);
        $this->encryptor = $this->createMock(TwoFactorSecretEncryptorInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);

        $this->verifier = new TwoFactorCodeVerifier(
            $this->totpVerifier,
            $this->encryptor,
            $this->recoveryCodeRepository,
        );
    }

    public function testVerifyTotpOrFailSucceeds(): void
    {
        $secret = $this->faker->sha256();
        $decryptedSecret = $this->faker->sha256();
        $code = '123456';

        $user = $this->createUserMock($secret);

        $this->encryptor->method('decrypt')->with($secret)->willReturn($decryptedSecret);
        $this->totpVerifier->method('verify')->with($decryptedSecret, $code)->willReturn(true);

        $this->verifier->verifyTotpOrFail($user, $code);
        $this->addToAssertionCount(1);
    }

    public function testVerifyTotpOrFailThrowsOnInvalidCode(): void
    {
        $secret = $this->faker->sha256();
        $decryptedSecret = $this->faker->sha256();
        $code = '000000';

        $user = $this->createUserMock($secret);

        $this->encryptor->method('decrypt')->with($secret)->willReturn($decryptedSecret);
        $this->totpVerifier->method('verify')->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $this->verifier->verifyTotpOrFail($user, $code);
    }

    public function testVerifyAndConsumeOrFailWithValidTotpCode(): void
    {
        $secret = $this->faker->sha256();
        $decryptedSecret = $this->faker->sha256();
        $code = '123456';

        $user = $this->createUserMock($secret);

        $this->encryptor->method('decrypt')->willReturn($decryptedSecret);
        $this->totpVerifier->method('verify')->willReturn(true);

        $this->verifier->verifyAndConsumeOrFail($user, $code);
        $this->addToAssertionCount(1);
    }

    public function testVerifyAndConsumeOrFailWithValidRecoveryCode(): void
    {
        $userId = $this->faker->uuid();
        $plainCode = 'ABCD-EF12';

        $user = $this->createUserMockWithId($userId);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $recoveryCode->method('isUsed')->willReturn(false);
        $recoveryCode->method('matchesCode')->with($plainCode)->willReturn(true);
        $recoveryCode->method('getId')->willReturn($this->faker->uuid());

        $this->recoveryCodeRepository->method('findByUserId')
            ->with($userId)
            ->willReturn([$recoveryCode]);
        $this->recoveryCodeRepository->method('markAsUsedIfUnused')->willReturn(true);

        $this->verifier->verifyAndConsumeOrFail($user, $plainCode);
        $this->addToAssertionCount(1);
    }

    public function testConsumeRecoveryCodeOrFailWithValidRecoveryCode(): void
    {
        $userId = $this->faker->uuid();
        $plainCode = 'ABCD-EF12';

        $user = $this->createUserMockWithId($userId);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $recoveryCode->method('isUsed')->willReturn(false);
        $recoveryCode->method('matchesCode')->with($plainCode)->willReturn(true);
        $recoveryCode->method('getId')->willReturn($this->faker->uuid());

        $this->recoveryCodeRepository->method('findByUserId')
            ->with($userId)
            ->willReturn([$recoveryCode]);
        $this->recoveryCodeRepository->method('markAsUsedIfUnused')->willReturn(true);

        $this->verifier->consumeRecoveryCodeOrFail($user, $plainCode);
        $this->addToAssertionCount(1);
    }

    public function testVerifyAndConsumeOrFailThrowsForInvalidRecoveryCode(): void
    {
        $userId = $this->faker->uuid();
        $plainCode = 'ABCD-EF12';

        $user = $this->createUserMockWithId($userId);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $recoveryCode->method('isUsed')->willReturn(false);
        $recoveryCode->method('matchesCode')->willReturn(false);

        $this->recoveryCodeRepository->method('findByUserId')->willReturn([$recoveryCode]);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $this->verifier->verifyAndConsumeOrFail($user, $plainCode);
    }

    public function testVerifyAndConsumeOrFailThrowsForUnrecognizedFormat(): void
    {
        $user = $this->createUserMock($this->faker->sha256());

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $this->verifier->verifyAndConsumeOrFail($user, 'not-a-valid-code-format!!');
    }

    public function testVerifyAndResolveMethodReturnsTotpForValidTotpCode(): void
    {
        $secret = $this->faker->sha256();
        $decryptedSecret = $this->faker->sha256();
        $code = '123456';

        $user = $this->createUserMock($secret);

        $this->encryptor->method('decrypt')->willReturn($decryptedSecret);
        $this->totpVerifier->method('verify')->willReturn(true);

        $result = $this->verifier->verifyAndResolveMethod($user, $code);

        $this->assertSame(TwoFactorCodeVerifierInterface::METHOD_TOTP, $result);
    }

    public function testVerifyAndResolveMethodReturnsNullForInvalidTotpCode(): void
    {
        $secret = $this->faker->sha256();

        $user = $this->createUserMock($secret);

        $this->encryptor->method('decrypt')->willReturn($this->faker->sha256());
        $this->totpVerifier->method('verify')->willReturn(false);

        $result = $this->verifier->verifyAndResolveMethod($user, '123456');

        $this->assertNull($result);
    }

    public function testVerifyAndResolveMethodReturnsNullWhenNoTwoFactorSecret(): void
    {
        $user = $this->createUserMock(null);

        $result = $this->verifier->verifyAndResolveMethod($user, '123456');

        $this->assertNull($result);
    }

    public function testVerifyAndResolveMethodReturnsRecoveryCodeForValidRecovery(): void
    {
        $userId = $this->faker->uuid();
        $plainCode = 'ABCD-EF12';

        $user = $this->createUserMockWithId($userId);

        $recoveryCode = $this->createMock(RecoveryCode::class);
        $recoveryCode->method('isUsed')->willReturn(false);
        $recoveryCode->method('matchesCode')->willReturn(true);

        $this->recoveryCodeRepository->method('findByUserId')->willReturn([$recoveryCode]);
        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('markAsUsedIfUnused');

        $result = $this->verifier->verifyAndResolveMethod($user, $plainCode);

        $this->assertSame(TwoFactorCodeVerifierInterface::METHOD_RECOVERY_CODE, $result);
    }

    public function testVerifyAndResolveMethodReturnsNullForInvalidRecoveryCode(): void
    {
        $userId = $this->faker->uuid();
        $plainCode = 'ABCD-EF12';

        $user = $this->createUserMockWithId($userId);

        $this->recoveryCodeRepository->method('findByUserId')->willReturn([]);

        $result = $this->verifier->verifyAndResolveMethod($user, $plainCode);

        $this->assertNull($result);
    }

    public function testVerifyAndResolveMethodReturnsNullForUnrecognizedFormat(): void
    {
        $user = $this->createUserMock($this->faker->sha256());

        $result = $this->verifier->verifyAndResolveMethod($user, 'invalid-format!!');

        $this->assertNull($result);
    }

    public function testCountRemainingCodes(): void
    {
        $userId = $this->faker->uuid();

        $usedCode = $this->createMock(RecoveryCode::class);
        $usedCode->method('isUsed')->willReturn(true);

        $unusedCode1 = $this->createMock(RecoveryCode::class);
        $unusedCode1->method('isUsed')->willReturn(false);

        $unusedCode2 = $this->createMock(RecoveryCode::class);
        $unusedCode2->method('isUsed')->willReturn(false);

        $this->recoveryCodeRepository->method('findByUserId')
            ->with($userId)
            ->willReturn([$usedCode, $unusedCode1, $unusedCode2]);

        $this->assertSame(2, $this->verifier->countRemainingCodes($userId));
    }

    public function testCountRemainingCodesReturnsZeroWhenAllUsed(): void
    {
        $userId = $this->faker->uuid();

        $usedCode = $this->createMock(RecoveryCode::class);
        $usedCode->method('isUsed')->willReturn(true);

        $this->recoveryCodeRepository->method('findByUserId')->willReturn([$usedCode]);

        $this->assertSame(0, $this->verifier->countRemainingCodes($userId));
    }

    public function testDecryptFallsBackToPlainTextOnException(): void
    {
        $plainSecret = $this->faker->sha256();
        $code = '123456';

        $user = $this->createUserMock($plainSecret);

        $this->encryptor->method('decrypt')
            ->willThrowException(new \RuntimeException('Decryption failed'));
        $this->totpVerifier->expects($this->once())
            ->method('verify')
            ->with($plainSecret, $code)
            ->willReturn(true);

        $this->verifier->verifyTotpOrFail($user, $code);
        $this->addToAssertionCount(1);
    }

    public function testVerifyAndConsumeOrFailSkipsUsedRecoveryCodes(): void
    {
        $userId = $this->faker->uuid();
        $plainCode = 'ABCD-EF12';

        $user = $this->createUserMockWithId($userId);

        $usedCode = $this->createMock(RecoveryCode::class);
        $usedCode->method('isUsed')->willReturn(true);

        $validCode = $this->createMock(RecoveryCode::class);
        $validCode->method('isUsed')->willReturn(false);
        $validCode->method('matchesCode')->with($plainCode)->willReturn(true);
        $validCode->method('getId')->willReturn($this->faker->uuid());

        $this->recoveryCodeRepository->method('findByUserId')->willReturn([$usedCode, $validCode]);
        $this->recoveryCodeRepository->method('markAsUsedIfUnused')->willReturn(true);

        $this->verifier->verifyAndConsumeOrFail($user, $plainCode);
        $this->addToAssertionCount(1);
    }

    public function testVerifyAndConsumeOrFailHandlesMarkAsUsedRace(): void
    {
        $userId = $this->faker->uuid();
        $plainCode = 'ABCD-EF12';

        $user = $this->createUserMockWithId($userId);

        $code = $this->createMock(RecoveryCode::class);
        $code->method('isUsed')->willReturn(false);
        $code->method('matchesCode')->willReturn(true);
        $code->method('getId')->willReturn($this->faker->uuid());

        $this->recoveryCodeRepository->method('findByUserId')->willReturn([$code]);
        $this->recoveryCodeRepository->method('markAsUsedIfUnused')->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $this->verifier->verifyAndConsumeOrFail($user, $plainCode);
    }

    private function createUserMock(?string $secret): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getTwoFactorSecret')->willReturn($secret);
        $user->method('getId')->willReturn($this->faker->uuid());

        return $user;
    }

    private function createUserMockWithId(string $userId): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $user->method('getTwoFactorSecret')->willReturn(null);

        return $user;
    }
}
