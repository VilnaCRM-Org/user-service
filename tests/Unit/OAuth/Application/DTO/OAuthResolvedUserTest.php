<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\DTO;

use App\OAuth\Application\DTO\OAuthResolvedUser;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Factory\UserFactory;

final class OAuthResolvedUserTest extends UnitTestCase
{
    public function testConstructSetsProperties(): void
    {
        $transformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();

        $user = $userFactory->create(
            $this->faker->safeEmail(),
            $this->faker->firstName(),
            $this->faker->password(),
            $transformer->transformFromString($this->faker->uuid())
        );

        $resolved = new OAuthResolvedUser($user, true);

        $this->assertSame($user, $resolved->user);
        $this->assertTrue($resolved->newlyCreated);
    }

    public function testConstructWithExistingUser(): void
    {
        $transformer = new UuidTransformer(new UuidFactory());
        $userFactory = new UserFactory();

        $user = $userFactory->create(
            $this->faker->safeEmail(),
            $this->faker->firstName(),
            $this->faker->password(),
            $transformer->transformFromString($this->faker->uuid())
        );

        $resolved = new OAuthResolvedUser($user, false);

        $this->assertSame($user, $resolved->user);
        $this->assertFalse($resolved->newlyCreated);
    }
}
