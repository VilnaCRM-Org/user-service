<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;

final class AuthorizationUserTest extends UnitTestCase
{
    private AuthorizationUserDto $authUser;
    private UuidTransformer $transformer;
    private string $email;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new UuidTransformer(new UuidFactory());

        $this->email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $uuid = $this->transformer->transformFromString($this->faker->uuid());
        $confirmed = true;

        $this->authUser = new AuthorizationUserDto(
            $this->email,
            $initials,
            $password,
            $uuid,
            $confirmed
        );
    }

    public function testGetRoles(): void
    {
        $this->assertEquals([], $this->authUser->getRoles());
    }

    public function testEraseCredentials(): void
    {
        $this->authUser->eraseCredentials();

        $this->addToAssertionCount(1);
    }

    public function testGetUserIdentifier(): void
    {
        $this->assertEquals($this->email, $this->authUser->getUserIdentifier());
    }
}
