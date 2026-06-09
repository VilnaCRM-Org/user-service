<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Validator\TOTPValidator;
use OTPHP\TOTP;

final class TOTPValidatorTest extends UnitTestCase
{
    private const SECRET = 'JBSWY3DPEHPK3PXP';

    public function testVerifyAcceptsCurrentAndPreviousTimeWindowsOnly(): void
    {
        $timestamp = time();
        $totp = TOTP::create(self::SECRET);
        $period = $totp->getPeriod();

        $currentCode = $totp->at($timestamp);
        $previousWindowCode = $totp->at(max(0, $timestamp - $period));
        $nextWindowCode = $totp->at($timestamp + $period);
        $twoWindowsOldCode = $totp->at(max(0, $timestamp - (2 * $period)));

        $verifier = new TOTPValidator();

        $this->assertTrue($verifier->verify(self::SECRET, $currentCode, $timestamp));
        $this->assertTrue($verifier->verify(self::SECRET, $previousWindowCode, $timestamp));
        // RFC 6238 §5.2: the future window must not be accepted.
        $this->assertFalse($verifier->verify(self::SECRET, $nextWindowCode, $timestamp));
        $this->assertFalse($verifier->verify(self::SECRET, $twoWindowsOldCode, $timestamp));
    }

    public function testVerifyReturnsFalseForInvalidSecret(): void
    {
        $verifier = new TOTPValidator();

        $this->assertFalse(
            $verifier->verify('invalid-secret-value', '123456', time())
        );
    }

    public function testVerifyUsesProvidedTimestampInsteadOfCurrentTime(): void
    {
        $timestamp = 1_700_000_000;
        $totp = TOTP::create(self::SECRET);
        $codeAtProvidedTimestamp = $totp->at($timestamp);

        $verifier = new TOTPValidator();

        $this->assertTrue(
            $verifier->verify(self::SECRET, $codeAtProvidedTimestamp, $timestamp)
        );
        $shiftedTimestamp = $timestamp + (5 * $totp->getPeriod());

        $this->assertFalse(
            $verifier->verify(self::SECRET, $codeAtProvidedTimestamp, $shiftedTimestamp)
        );
    }

    public function testVerifyAcceptsPreviousWindowAtUnixEpochBoundary(): void
    {
        $totp = TOTP::create(self::SECRET);
        $period = $totp->getPeriod();
        $timestamp = $period;
        $previousWindowCode = $totp->at(0);

        $verifier = new TOTPValidator();

        $this->assertTrue(
            $verifier->verify(self::SECRET, $previousWindowCode, $timestamp)
        );
    }

    public function testResolveAcceptedTimestepReturnsCurrentWindowTimestep(): void
    {
        $timestamp = 1_700_000_000;
        $totp = TOTP::create(self::SECRET);
        $period = $totp->getPeriod();
        $currentCode = $totp->at($timestamp);

        $verifier = new TOTPValidator();

        $this->assertSame(
            intdiv($timestamp, $period),
            $verifier->resolveAcceptedTimestep(self::SECRET, $currentCode, $timestamp)
        );
    }

    public function testResolveAcceptedTimestepReturnsPreviousWindowTimestep(): void
    {
        $timestamp = 1_700_000_000;
        $totp = TOTP::create(self::SECRET);
        $period = $totp->getPeriod();
        $previousWindowCode = $totp->at($timestamp - $period);

        $verifier = new TOTPValidator();

        $this->assertSame(
            intdiv($timestamp - $period, $period),
            $verifier->resolveAcceptedTimestep(self::SECRET, $previousWindowCode, $timestamp)
        );
    }

    public function testResolveAcceptedTimestepReturnsNullForFutureWindow(): void
    {
        $timestamp = 1_700_000_000;
        $totp = TOTP::create(self::SECRET);
        $period = $totp->getPeriod();
        $futureWindowCode = $totp->at($timestamp + $period);

        $verifier = new TOTPValidator();

        $this->assertNull(
            $verifier->resolveAcceptedTimestep(self::SECRET, $futureWindowCode, $timestamp)
        );
    }

    public function testResolveAcceptedTimestepReturnsNullForInvalidCode(): void
    {
        $verifier = new TOTPValidator();

        $this->assertNull(
            $verifier->resolveAcceptedTimestep(self::SECRET, '000000', 1_700_000_000)
        );
    }
}
