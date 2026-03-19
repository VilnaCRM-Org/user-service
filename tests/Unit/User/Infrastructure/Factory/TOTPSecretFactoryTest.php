<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Factory\TOTPFactory;
use App\User\Infrastructure\Factory\TOTPSecretFactory;

final class TOTPSecretFactoryTest extends UnitTestCase
{
    public function testCreateReturnsSecretAndOtpauthUri(): void
    {
        $factory = new TOTPSecretFactory(new TOTPFactory());
        $email = $this->faker->email();

        $result = $factory->create($email);

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

    public function testCreateProducesUniqueSecretsPerCall(): void
    {
        $factory = new TOTPSecretFactory(new TOTPFactory());
        $email = $this->faker->email();

        $first = $factory->create($email);
        $second = $factory->create($email);

        $this->assertNotSame($first['secret'], $second['secret']);
    }
}
