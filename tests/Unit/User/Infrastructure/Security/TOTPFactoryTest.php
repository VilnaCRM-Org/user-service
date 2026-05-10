<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Security;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Factory\TOTPFactory;

final class TOTPFactoryTest extends UnitTestCase
{
    private const EXPECTED_SECRET_LENGTH = 32;
    private const BASE32_PATTERN = '/^[A-Z2-7]+$/';
    private const OTPAUTH_PATTERN = '/^otpauth:\/\/totp\//';
    private const SECRET_SAMPLE_COUNT = 400;
    private const MAX_ALLOWED_SEVEN_RATIO = 0.045;

    private TOTPFactory $creator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->creator = new TOTPFactory();
    }

    public function testCreateReturnsSecretAndOtpauthUri(): void
    {
        $result = $this->creator->create('user@example.com', 'VilnaCRM');

        $this->assertArrayHasKey('secret', $result);
        $this->assertArrayHasKey('otpauth_uri', $result);
    }

    public function testCreateGeneratesValidBase32Secret(): void
    {
        $result = $this->creator->create('user@example.com', 'VilnaCRM');

        $this->assertSame(self::EXPECTED_SECRET_LENGTH, strlen($result['secret']));
        $this->assertMatchesRegularExpression(self::BASE32_PATTERN, $result['secret']);
    }

    public function testCreateIncludesLabelInOtpauthUri(): void
    {
        $label = 'user@example.com';
        $result = $this->creator->create($label, 'VilnaCRM');

        $this->assertStringContainsString(rawurlencode($label), $result['otpauth_uri']);
    }

    public function testCreateIncludesIssuerInOtpauthUri(): void
    {
        $issuer = 'VilnaCRM';
        $result = $this->creator->create('user@example.com', $issuer);

        $this->assertStringContainsString($issuer, $result['otpauth_uri']);
    }

    public function testCreateReturnsOtpauthUri(): void
    {
        $result = $this->creator->create('user@example.com', 'VilnaCRM');

        $this->assertMatchesRegularExpression(self::OTPAUTH_PATTERN, $result['otpauth_uri']);
    }

    public function testCreateGeneratesUniqueSecrets(): void
    {
        $first = $this->creator->create('user@example.com', 'VilnaCRM');
        $second = $this->creator->create('user@example.com', 'VilnaCRM');

        $this->assertNotSame($first['secret'], $second['secret']);
    }

    public function testCreateUsesFullBase32AlphabetDistribution(): void
    {
        $allSecrets = '';
        for ($index = 0; $index < self::SECRET_SAMPLE_COUNT; ++$index) {
            $allSecrets .= $this->creator->create('user@example.com', 'VilnaCRM')['secret'];
        }

        $this->assertStringContainsString('A', $allSecrets);
        $this->assertStringContainsString('7', $allSecrets);
        $sevenRatio = substr_count($allSecrets, '7') / strlen($allSecrets);
        $this->assertLessThan(self::MAX_ALLOWED_SEVEN_RATIO, $sevenRatio);
    }
}
