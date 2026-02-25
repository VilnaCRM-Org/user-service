<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\TwoFactor;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Generator\TOTPSecretGenerator;
use App\User\Infrastructure\TwoFactor\TotpCreator;

final class TOTPSecretGeneratorTest extends UnitTestCase
{
    public function testGenerateReturnsSecretAndOtpauthUri(): void
    {
        $generator = new TOTPSecretGenerator(new TotpCreator());
        $email = $this->faker->email();

        $result = $generator->generate($email);

        $this->assertArrayHasKey('secret', $result);
        $this->assertArrayHasKey('otpauth_uri', $result);
        $this->assertNotEmpty($result['secret']);
        $this->assertStringContainsString(
            rawurlencode($email),
            $result['otpauth_uri']
        );
        $this->assertStringContainsString(
            'issuer=VilnaCRM',
            $result['otpauth_uri']
        );
        $this->assertStringStartsWith(
            'otpauth://totp/',
            $result['otpauth_uri']
        );
    }

    public function testGenerateProducesUniqueSecretsPerCall(): void
    {
        $generator = new TOTPSecretGenerator(new TotpCreator());
        $email = $this->faker->email();

        $first = $generator->generate($email);
        $second = $generator->generate($email);

        $this->assertNotSame($first['secret'], $second['secret']);
    }
}
