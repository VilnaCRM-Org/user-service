<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\User;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\User\Domain\Entity\Email\RetryDto;
use App\User\Domain\Entity\Token\ConfirmUserDto;
use App\User\Infrastructure\Email\RetryProcessor;
use App\User\Infrastructure\Exceptions\DuplicateEmailError;
use App\User\Infrastructure\Exceptions\InvalidPasswordError;
use App\User\Infrastructure\Exceptions\TokenNotFoundError;
use App\User\Infrastructure\Exceptions\UserNotFoundError;
use App\User\Infrastructure\Token\ConfirmUserMutationResolver;
use App\User\Infrastructure\User\RegisterUserProcessor;
use App\User\Infrastructure\User\UserPatchProcessor;
use App\User\Infrastructure\User\UserPutProcessor;
use App\User\Infrastructure\User\UserUpdateMutationResolver;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(normalizationContext: ['groups' => ['output']],
    exceptionToStatus: [InvalidPasswordError::class => 410, UserNotFoundError::class => 404,
        TokenNotFoundError::class => 404])]
#[Get]
#[GetCollection(paginationClientItemsPerPage: true)]
#[Post(exceptionToStatus: [DuplicateEmailError::class => 409], input: UserInputDto::class, processor: RegisterUserProcessor::class)]
#[Patch(input: UserPatchDto::class, processor: UserPatchProcessor::class)]
#[Put(input: UserPutDto::class, processor: UserPutProcessor::class)]
#[Delete]
#[Post(uriTemplate: '/users/{id}/resend-confirmation-email', input: RetryDto::class,
    processor: RetryProcessor::class)]
#[Mutation(resolver: ConfirmUserMutationResolver::class,
    input: ConfirmUserDto::class, name: 'confirm')]
#[Mutation(name: 'create')]
#[Mutation(resolver: UserUpdateMutationResolver::class,
    input: UserUpdateMutationDto::class, name: 'update')]
#[Mutation(name: 'delete')]
#[Query]
#[QueryCollection]
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
    #[Groups(['output'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['output'])]
    private string $initials;

    #[ORM\Column(type: 'string', length: 255)]
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
        return $this->email;
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
