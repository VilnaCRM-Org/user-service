<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\ValueObject;

use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use App\Tests\Unit\UnitTestCase;

final class OAuthUserProfileTest extends UnitTestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $email = $this->faker->email();
        $name = $this->faker->name();
        $providerId = $this->faker->numerify('######');

        $profile = new OAuthUserProfile(
            email: $email,
            name: $name,
            providerId: $providerId,
            emailVerified: true,
        );

        $this->assertSame($email, $profile->email);
        $this->assertSame($name, $profile->name);
        $this->assertSame($providerId, $profile->providerId);
        $this->assertTrue($profile->emailVerified);
    }

    public function testUnverifiedEmail(): void
    {
        $profile = new OAuthUserProfile(
            email: $this->faker->email(),
            name: $this->faker->name(),
            providerId: $this->faker->numerify('######'),
            emailVerified: false,
        );

        $this->assertFalse($profile->emailVerified);
    }
}
