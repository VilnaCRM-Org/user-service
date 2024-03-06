<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class DuplicateEmailException extends DomainException
{
    public function __construct(private readonly string $email)
    {
        parent::__construct(
            "{$this->email} address is already registered"
        );
    }

    public function getTranslationTemplate(): string
    {
        return 'error.duplicate-email';
    }

    /**
     * @return array<string, string>
     */
    public function getTranslationArgs(): array
    {
        return ['email' => $this->email];
    }
}
