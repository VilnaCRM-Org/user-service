<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\TwoFactor;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\TwoFactor\TOTPCreator;

final class TOTPCreatorTest extends UnitTestCase
{
    private const EXPECTED_SECRET_LENGTH = 32;
    private const BASE32_PATTERN = '/^[A-Z2-7]+$/';
    private const OTPAUTH_PATTERN = '/^otpauth:\/\/totp\//';

    private TOTPCreator $creator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->creator = new TOTPCreator();
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
}
