<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

abstract class DomainException extends \RuntimeException
{
    abstract public function getTranslationTemplate(): string;

    /**
     * @psalm-return array<never, never>
     */
    public function getTranslationArgs(): array
    {
        return [];
    }
}
