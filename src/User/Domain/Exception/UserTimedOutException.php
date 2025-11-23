<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use DateTimeInterface;

final class UserTimedOutException extends DomainException
{
    public function __construct(
        private readonly \DateTimeImmutable $timeOutTill
    ) {
        parent::__construct(
            'Cannot send new email till ' .
            $this->timeOutTill->format(DateTimeInterface::ATOM)
        );
    }

    #[\Override]
    public function getTranslationTemplate(): string
    {
        return 'error.user-timed-out';
    }

    /**
     * @return array<string, DateTimeInterface>
     */
    #[\Override]
    public function getTranslationArgs(): array
    {
        return ['datetime' => $this->timeOutTill->format(
            DateTimeInterface::ATOM
        ),
        ];
    }
}
