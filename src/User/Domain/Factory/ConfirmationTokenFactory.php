<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\ConfirmationTokenInterface;

final readonly class ConfirmationTokenFactory implements
    ConfirmationTokenFactoryInterface
{
    public function __construct(private int $tokenLength)
    {
    }

    /**
     * @return ConfirmationToken
     */
    #[\Override]
    public function create(string $userID): ConfirmationTokenInterface
    {
        return new ConfirmationToken(
            bin2hex(random_bytes($this->tokenLength)),
            $userID
        );
    }
}
