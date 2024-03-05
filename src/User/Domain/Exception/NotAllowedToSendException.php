<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use DomainException;

final class NotAllowedToSendException extends DomainException
{
    public function __construct(public readonly \DateTimeImmutable $datetime)
    {
        parent::__construct();
    }
}
