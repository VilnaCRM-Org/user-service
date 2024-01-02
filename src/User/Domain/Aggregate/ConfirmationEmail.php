<?php

namespace App\User\Domain\Aggregate;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Event\ConfirmationEmailSendEvent;

readonly class ConfirmationEmail
{
    public function __construct(public ConfirmationToken $token, public User $user)
    {
    }

    public function send(): ConfirmationEmailSendEvent
    {
        $this->token->setTimesSent($this->token->getTimesSent() + 1);

        return new ConfirmationEmailSendEvent($this->token, $this->user->getEmail());
    }
}
