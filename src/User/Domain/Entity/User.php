<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\User\Infrastructure\UserRegisterMutationResolver;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ApiResource(graphQlOperations: [
    new Mutation(resolver: UserRegisterMutationResolver::class, args: [
        'email' => [
            'type' => 'String!',
        ],
        'initials' => [
            'type' => 'String!',
        ],
        'password' => [
            'type' => 'String!',
        ],
    ], deserialize: false, validate: false, write: false, name: 'register'),
])]
class User
{
    public function __construct(
        string $id,
        string $email,
        string $initials,
        string $password
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->initials = $initials;
        $this->password = $password;
    }

    #[ORM\Id]
    #[ORM\Column]
    #[Assert\NotBlank]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $initials;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $password;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getInitials(): string
    {
        return $this->initials;
    }

    public function setInitials(string $initials): void
    {
        $this->initials = $initials;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
