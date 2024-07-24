<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;

final class UserTest extends UnitTestCase
{
    private UuidTransformer $uuidTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uuidTransformer = new UuidTransformer();
    }

    public function testCreate(): void
    {
        $newEmail = $this->faker->email();
        $newName = $this->faker->name();
        $newPassword = $this->faker->password;
        $newUuid = $this->uuidTransformer->transformFromString($this->faker->uuid());

        $user = new User(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $user->setEmail($newEmail);
        $user->setInitials($newName);
        $user->setPassword($newPassword);
        $user->setId($newUuid);

        $this->assertEquals($newEmail, $user->getEmail());
        $this->assertEquals($newName, $user->getInitials());
        $this->assertEquals($newPassword, $user->getPassword());
        $this->assertEquals($newUuid, $user->getId());
    }
}
