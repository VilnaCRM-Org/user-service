<?php

declare(strict_types=1);

namespace App\Tests\Builders;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\ValueObject\UuidInterface;
use App\User\Domain\Entity\User;
use Faker\Factory;

final class UserBuilder
{
    private UuidTransformer $uuidTransformer;

    private string $email;
    private string $initials;
    private string $password;
    private UuidInterface $id;

    private bool $confirmed = false;

    public function __construct()
    {
        $faker = Factory::create();
        $this->uuidTransformer = new UuidTransformer();

        $this->email = $faker->email();
        $this->initials = $faker->firstName . ' ' . $faker->lastName();
        $this->password = $faker->password();
        $this->id = $this->uuidTransformer->transformFromString($faker->uuid());
    }

    public function withEmail(string $email): self
    {
        $clone = clone $this;
        $clone->email = $email;
        return $clone;
    }

    public function withInitials(string $initials): self
    {
        $clone = clone $this;
        $clone->initials = $initials;
        return $clone;
    }

    public function withPassword(string $password): self
    {
        $clone = clone $this;
        $clone->password = $password;
        return $clone;
    }

    public function withId(string $id): self
    {
        $clone = clone $this;
        $clone->id = $this->uuidTransformer->transformFromString($id);
        return $clone;
    }

    public function confirmed(): self
    {
        $clone = clone $this;
        $clone->confirmed = true;
        return $clone;
    }

    public function build(): User
    {
        $user = new User(
            $this->email,
            $this->initials,
            $this->password,
            $this->id
        );

        $user->setConfirmed($this->confirmed);

        return $user;
    }
}
