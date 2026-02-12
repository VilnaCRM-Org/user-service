<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Service\TOTPVerifier;
use OTPHP\TOTP;

final class TOTPVerifierTest extends UnitTestCase
{
    public function testVerifyAcceptsCurrentAndAdjacentTimeWindows(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $timestamp = time();
        $totp = TOTP::create($secret);
        $period = $totp->getPeriod();

        $currentCode = $totp->at($timestamp);
        $previousWindowCode = $totp->at(max(0, $timestamp - $period));
        $nextWindowCode = $totp->at($timestamp + $period);
        $twoWindowsOldCode = $totp->at(max(0, $timestamp - (2 * $period)));

        $verifier = new TOTPVerifier();

        $this->assertTrue($verifier->verify($secret, $currentCode, $timestamp));
        $this->assertTrue($verifier->verify($secret, $previousWindowCode, $timestamp));
        $this->assertTrue($verifier->verify($secret, $nextWindowCode, $timestamp));
        $this->assertFalse($verifier->verify($secret, $twoWindowsOldCode, $timestamp));
    }

    public function testVerifyReturnsFalseForInvalidSecret(): void
    {
        $verifier = new TOTPVerifier();

        $this->assertFalse(
            $verifier->verify('invalid-secret-value', '123456', time())
        );
    }

    public function testVerifyUsesProvidedTimestampInsteadOfCurrentTime(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $timestamp = 1_700_000_000;
        $totp = TOTP::create($secret);
        $codeAtProvidedTimestamp = $totp->at($timestamp);

        $verifier = new TOTPVerifier();

        $this->assertTrue(
            $verifier->verify($secret, $codeAtProvidedTimestamp, $timestamp)
        );
        $shiftedTimestamp = $timestamp + (5 * $totp->getPeriod());

        $this->assertFalse(
            $verifier->verify($secret, $codeAtProvidedTimestamp, $shiftedTimestamp)
        );
    }

    public function testVerifyAcceptsPreviousWindowAtUnixEpochBoundary(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $totp = TOTP::create($secret);
        $period = $totp->getPeriod();
        $timestamp = $period;
        $previousWindowCode = $totp->at(0);

        $verifier = new TOTPVerifier();

        $this->assertTrue(
            $verifier->verify($secret, $previousWindowCode, $timestamp)
        );
    }
}
