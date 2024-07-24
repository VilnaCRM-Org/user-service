<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class UserByEmailNotFoundException extends DomainException
{
    public function __construct(
        private readonly string $email
    ) {
        parent::__construct("User with email {$email} not found");
    }

    public function getTranslationTemplate(): string
    {
        return 'error.user-by-email-not-found';
    }

    /**
     * @return array<string, string>
     */
    public function getTranslationArgs(): array
    {
        return ['email' => $this->email];
    }
}
