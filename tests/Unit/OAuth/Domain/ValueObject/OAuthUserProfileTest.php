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

    public function testEqualsReturnsTrueForIdenticalProfiles(): void
    {
        $email = $this->faker->email();
        $name = $this->faker->name();
        $providerId = $this->faker->numerify('######');

        $profile1 = new OAuthUserProfile($email, $name, $providerId, true);
        $profile2 = new OAuthUserProfile($email, $name, $providerId, true);

        $this->assertTrue($profile1->equals($profile2));
    }

    public function testEqualsReturnsFalseForDifferentEmail(): void
    {
        $name = $this->faker->name();
        $providerId = $this->faker->numerify('######');

        $profile1 = new OAuthUserProfile($this->faker->email(), $name, $providerId, true);
        $profile2 = new OAuthUserProfile($this->faker->email(), $name, $providerId, true);

        $this->assertFalse($profile1->equals($profile2));
    }

    public function testEqualsReturnsFalseForDifferentName(): void
    {
        $email = $this->faker->email();
        $providerId = $this->faker->numerify('######');

        $profile1 = new OAuthUserProfile($email, $this->faker->name(), $providerId, true);
        $profile2 = new OAuthUserProfile($email, $this->faker->name(), $providerId, true);

        $this->assertFalse($profile1->equals($profile2));
    }

    public function testEqualsReturnsFalseForDifferentProviderId(): void
    {
        $email = $this->faker->email();
        $name = $this->faker->name();

        $profile1 = new OAuthUserProfile($email, $name, $this->faker->numerify('######'), true);
        $profile2 = new OAuthUserProfile($email, $name, $this->faker->numerify('######'), true);

        $this->assertFalse($profile1->equals($profile2));
    }

    public function testEqualsReturnsFalseForDifferentEmailVerified(): void
    {
        $email = $this->faker->email();
        $name = $this->faker->name();
        $providerId = $this->faker->numerify('######');

        $profile1 = new OAuthUserProfile($email, $name, $providerId, true);
        $profile2 = new OAuthUserProfile($email, $name, $providerId, false);

        $this->assertFalse($profile1->equals($profile2));
    }
}
