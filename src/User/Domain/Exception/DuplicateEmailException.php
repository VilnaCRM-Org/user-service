<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use function sprintf;

use Throwable;

final class DuplicateEmailException extends DomainException
{
    public function __construct(
        string $email,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Email "%s" is already registered', $email),
            0,
            $previous
        );
    }

    /**
     * @psalm-return 'email.not.unique'
     */
    #[\Override]
    public function getTranslationTemplate(): string
    {
        return 'email.not.unique';
    }
}
