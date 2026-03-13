<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\TwoFactor;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\TwoFactor\TOTPCreator;

final class TOTPCreatorTest extends UnitTestCase
{
    private const EXPECTED_PERIOD = 30;
    private const EXPECTED_DIGITS = 6;
    private const EXPECTED_DIGEST = 'sha1';
    private const EXPECTED_EPOCH = 0;
    private const EXPECTED_SECRET_LENGTH = 32;
    private const BASE32_PATTERN = '/^[A-Z2-7]+$/';

    public function testCreateSetsCorrectPeriod(): void
    {
        $creator = new TOTPCreator();
        $totp = $creator->create();

        $this->assertSame(self::EXPECTED_PERIOD, $totp->getPeriod());
    }

    public function testCreateSetsCorrectDigits(): void
    {
        $creator = new TOTPCreator();
        $totp = $creator->create();

        $this->assertSame(self::EXPECTED_DIGITS, $totp->getDigits());
    }

    public function testCreateSetsCorrectDigest(): void
    {
        $creator = new TOTPCreator();
        $totp = $creator->create();

        $this->assertSame(self::EXPECTED_DIGEST, $totp->getDigest());
    }

    public function testCreateSetsCorrectEpoch(): void
    {
        $creator = new TOTPCreator();
        $totp = $creator->create();

        $this->assertSame(self::EXPECTED_EPOCH, $totp->getEpoch());
    }

    public function testCreateWithProvidedSecretUsesGivenSecret(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $creator = new TOTPCreator();
        $totp = $creator->create($secret);

        $this->assertSame($secret, $totp->getSecret());
    }

    public function testCreateWithoutSecretGeneratesValidBase32Secret(): void
    {
        $creator = new TOTPCreator();
        $totp = $creator->create();

        $secret = $totp->getSecret();
        $this->assertSame(
            self::EXPECTED_SECRET_LENGTH,
            strlen($secret)
        );
        $this->assertMatchesRegularExpression(
            self::BASE32_PATTERN,
            $secret
        );
    }

    public function testCreateGeneratesUniqueSecrets(): void
    {
        $creator = new TOTPCreator();
        $first = $creator->create();
        $second = $creator->create();

        $this->assertNotSame(
            $first->getSecret(),
            $second->getSecret()
        );
    }

    public function testCreateReturnsTOTPWithAllConfiguredProperties(): void
    {
        $creator = new TOTPCreator();
        $totp = $creator->create();

        $this->assertSame(self::EXPECTED_PERIOD, $totp->getPeriod());
        $this->assertSame(self::EXPECTED_DIGITS, $totp->getDigits());
        $this->assertSame(self::EXPECTED_DIGEST, $totp->getDigest());
        $this->assertSame(self::EXPECTED_EPOCH, $totp->getEpoch());
    }
}
