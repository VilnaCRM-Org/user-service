<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Verifier;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Validator\TOTPValidatorInterface;
use App\User\Application\Validator\TwoFactorCodeValidator;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Covers the one-time setup-confirmation TOTP verification, which must NOT
 * advance the sign-in replay time-step so a sign-in completion using a code
 * from the same 30s window still succeeds.
 */
final class TwoFactorCodeSetupVerificationTest extends UnitTestCase
{
    private const ACCEPTED_TIMESTEP = 57_000_000;

    private TOTPValidatorInterface&MockObject $totpVerifier;
    private TwoFactorSecretEncryptorInterface&MockObject $encryptor;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private TwoFactorCodeValidator $verifier;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->totpVerifier = $this->createMock(TOTPValidatorInterface::class);
        $this->encryptor = $this->createMock(TwoFactorSecretEncryptorInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->verifier = new TwoFactorCodeValidator(
            $this->totpVerifier,
            $this->encryptor,
            $this->recoveryCodeRepository,
            $this->userRepository,
        );
    }

    public function testSucceedsWithoutConsumingTimestep(): void
    {
        $secret = $this->faker->sha256();
        $decryptedSecret = $this->faker->sha256();
        $code = '123456';

        $user = $this->createMock(User::class);
        $user->method('getTwoFactorSecret')->willReturn($secret);
        $user->expects($this->never())->method('isTotpTimestepReplay');
        $user->expects($this->never())->method('recordAcceptedTotpTimestep');

        $this->encryptor->method('decrypt')->with($secret)->willReturn($decryptedSecret);
        $this->totpVerifier->method('resolveAcceptedTimestep')
            ->with($decryptedSecret, $code)
            ->willReturn(self::ACCEPTED_TIMESTEP);
        $this->userRepository->expects($this->never())->method('save');

        $this->verifier->verifyTotpForSetupOrFail($user, $code);
        $this->addToAssertionCount(1);
    }

    public function testAcceptsAlreadyAcceptedTimestep(): void
    {
        $secret = $this->faker->sha256();

        $user = $this->createMock(User::class);
        $user->method('getTwoFactorSecret')->willReturn($secret);
        $user->expects($this->never())->method('isTotpTimestepReplay');

        $this->encryptor->method('decrypt')->willReturn($this->faker->sha256());
        $this->totpVerifier->method('resolveAcceptedTimestep')
            ->willReturn(self::ACCEPTED_TIMESTEP);
        $this->userRepository->expects($this->never())->method('save');

        $this->verifier->verifyTotpForSetupOrFail($user, '123456');
        $this->addToAssertionCount(1);
    }

    public function testThrowsOnInvalidCode(): void
    {
        $user = $this->createUserMock($this->faker->sha256());

        $this->encryptor->method('decrypt')->willReturn($this->faker->sha256());
        $this->totpVerifier->method('resolveAcceptedTimestep')->willReturn(null);
        $this->userRepository->expects($this->never())->method('save');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $this->verifier->verifyTotpForSetupOrFail($user, '000000');
    }

    public function testThrowsWhenNoTwoFactorSecret(): void
    {
        $user = $this->createUserMock(null);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $this->verifier->verifyTotpForSetupOrFail($user, '123456');
    }

    public function testRejectsNonTotpFormat(): void
    {
        $user = $this->createUserMock($this->faker->sha256());

        $this->totpVerifier->expects($this->never())->method('resolveAcceptedTimestep');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $this->verifier->verifyTotpForSetupOrFail($user, 'ABCD-EF12');
    }

    private function createUserMock(?string $secret): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getTwoFactorSecret')->willReturn($secret);
        $user->method('getId')->willReturn($this->faker->uuid());

        return $user;
    }
}
