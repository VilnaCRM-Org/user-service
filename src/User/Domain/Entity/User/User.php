<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\User;

use ApiPlatform\Metadata\ApiResource;
use App\User\Infrastructure\User\RegisterUserProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ApiResource(normalizationContext: ['groups' => ['output']],
    input: UserInputDto::class, processor: RegisterUserProcessor::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
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
        $this->roles = ['ROLE_USER'];
        $this->confirmed = false;
    }

    #[ORM\Id]
    #[ORM\Column]
    #[Groups(['output'])]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['output'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['output'])]
    private string $initials;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $password;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['output'])]
    private bool $confirmed;

    #[ORM\Column(type: 'json')]
    #[Groups(['output'])]
    private array $roles;

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

    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): void
    {
        $this->confirmed = $confirmed;
    }
}
