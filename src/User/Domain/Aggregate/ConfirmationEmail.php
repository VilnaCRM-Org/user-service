<?php

declare(strict_types=1);

namespace App\User\Domain\Aggregate;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Event\ConfirmationEmailSendEvent;

class ConfirmationEmail extends AggregateRoot
{
    public function __construct(public readonly ConfirmationToken $token, public readonly User $user)
    {
    }

    public function send(): void
    {
        $this->token->incrementTimesSent();
        $this->record(new ConfirmationEmailSendEvent($this->token, $this->user->getEmail()));
    }
}
