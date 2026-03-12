<?php

declare(strict_types=1);

namespace App\User\Application\Processor\Issuer;

use App\User\Application\DTO\IssuedSession;
use App\User\Domain\Entity\User;
use DateTimeImmutable;

interface SessionIssuerInterface
{
    public function issue(
        User $user,
        string $ipAddress,
        string $userAgent,
        bool $rememberMe,
        DateTimeImmutable $issuedAt
    ): IssuedSession;
}
