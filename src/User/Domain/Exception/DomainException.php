<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use DateTimeInterface;

abstract class DomainException extends \RuntimeException
{
    abstract public function getTranslationTemplate(): string;

    /**
     * @return array<string, string|DateTimeInterface>
     */
    public function getTranslationArgs(): array
    {
        return [];
    }
}
