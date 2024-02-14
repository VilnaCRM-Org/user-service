<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUser;
use Faker\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthorizationUserTest extends UnitTestCase
{
    private AuthorizationUser $authUser;
    private UuidTransformer $transformer;
    private string $email;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new UuidTransformer();

        $this->email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $uuid = $this->transformer->transformFromString($this->faker->uuid());
        $confirmed = true;

        $this->authUser = new AuthorizationUser($this->email, $initials, $password, $uuid, $confirmed);
    }

    public function testGetRoles(): void
    {
        $this->assertEquals([], $this->authUser->getRoles());
    }

    public function testGetUserIdentifier(): void
    {
        $this->assertEquals($this->email, $this->authUser->getUserIdentifier());
    }
}
