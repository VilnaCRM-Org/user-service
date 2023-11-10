<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource]
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
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $initials;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getInitials(): string
    {
        return $this->initials;
    }

    /**
     * @param string $initials
     */
    public function setInitials(string $initials): void
    {
        $this->initials = $initials;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }


}
