<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;

class UserFactoryTest extends UnitTestCase
{
    private UuidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new UuidTransformer();
    }

    public function testCreate(): void
    {
        $factory = new UserFactory();

        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $idValue = $this->faker->uuid();
        $id = $this->transformer->transformFromString($idValue);
        $user = $factory->create($email, $initials, $password, $id);

        $this->assertInstanceOf(UserInterface::class, $user);
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($initials, $user->getInitials());
        $this->assertSame($password, $user->getPassword());
        $this->assertSame($idValue, $user->getId());
    }
}
