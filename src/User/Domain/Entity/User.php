<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\User\Application\DTO\Email\RetryDto;
use App\User\Application\DTO\Email\RetryMutationDto;
use App\User\Application\DTO\Token\ConfirmUserDto;
use App\User\Application\DTO\User\UserInputDto;
use App\User\Application\DTO\User\UserPatchDto;
use App\User\Application\DTO\User\UserPutDto;
use App\User\Application\DTO\User\UserUpdateMutationDto;
use App\User\Infrastructure\Email\ResendEmailMutationResolver;
use App\User\Infrastructure\Email\ResendEmailProcessor;
use App\User\Infrastructure\Exception\DuplicateEmailException;
use App\User\Infrastructure\Exception\InvalidPasswordException;
use App\User\Infrastructure\Exception\TokenNotFoundException;
use App\User\Infrastructure\Exception\UserNotFoundException;
use App\User\Infrastructure\Exception\UserTimedOutException;
use App\User\Infrastructure\Token\ConfirmUserMutationResolver;
use App\User\Infrastructure\User\RegisterUserMutationResolver;
use App\User\Infrastructure\User\RegisterUserProcessor;
use App\User\Infrastructure\User\UserPatchProcessor;
use App\User\Infrastructure\User\UserPutProcessor;
use App\User\Infrastructure\User\UserUpdateMutationResolver;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['output']],
    exceptionToStatus: [InvalidPasswordException::class => 410, UserNotFoundException::class => 404,
        TokenNotFoundException::class => 404,
        DuplicateEmailException::class => 409]
)]
#[Get]
#[GetCollection(paginationClientItemsPerPage: true)]
#[Post(input: UserInputDto::class, processor: RegisterUserProcessor::class)]
#[Patch(input: UserPatchDto::class, processor: UserPatchProcessor::class)]
#[Put(input: UserPutDto::class, processor: UserPutProcessor::class)]
#[Delete]
#[Post(
    uriTemplate: '/users/{id}/resend-confirmation-email',
    exceptionToStatus: [UserTimedOutException::class => 429,
        UserNotFoundException::class => 404],
    input: RetryDto::class,
    processor: ResendEmailProcessor::class
)]
#[Mutation(
    resolver: ConfirmUserMutationResolver::class,
    input: ConfirmUserDto::class,
    name: 'confirm'
)]
#[Mutation(resolver: RegisterUserMutationResolver::class, input: UserInputDto::class, name: 'create')]
#[Mutation(
    resolver: UserUpdateMutationResolver::class,
    input: UserUpdateMutationDto::class,
    name: 'update'
)]
#[DeleteMutation(normalizationContext: ['groups' => ['deleteMutationOutput']], name: 'delete')]
#[Mutation(resolver: ResendEmailMutationResolver::class, input: RetryMutationDto::class, name: 'resendEmailTo')]
#[Query]
#[QueryCollection]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        Uuid $id,
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
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['output', 'deleteMutationOutput'])]
    private Uuid $id;

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

    public function getId(): Uuid
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
