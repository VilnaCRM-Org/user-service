<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\Exception\NotAllowedToSendException;

final class ConfirmationToken implements ConfirmationTokenInterface
{
    private int $timesSent;
    private \DateTimeImmutable $allowedToSendAfter;

    /**
     * @var array<int, int>
     */
    private array $sendEmailAttemptsTimeInMinutes;

    public function __construct(
        private string $tokenValue,
        private string $userID
    ) {
        $this->timesSent = 0;
        $this->allowedToSendAfter = new \DateTimeImmutable();
        $this->sendEmailAttemptsTimeInMinutes = [
            1 => 1,
            2 => 3,
            3 => 4,
            4 => 1440,
        ];
    }

    public function getTimesSent(): int
    {
        return $this->timesSent;
    }

    public function setTimesSent(int $timesSent): void
    {
        $this->timesSent = $timesSent;
    }

    public function getAllowedToSendAfter(): \DateTimeImmutable
    {
        return $this->allowedToSendAfter;
    }

    public function setAllowedToSendAfter(
        \DateTimeImmutable $allowedToSendAfter
    ): void {
        $this->allowedToSendAfter = $allowedToSendAfter;
    }

    public function getTokenValue(): string
    {
        return $this->tokenValue;
    }

    public function getUserID(): string
    {
        return $this->userID;
    }

    public function setTokenValue(string $tokenValue): void
    {
        $this->tokenValue = $tokenValue;
    }

    public function setUserID(string $userID): void
    {
        $this->userID = $userID;
    }

    /**
     * @throws NotAllowedToSendException
     */
    public function send(?\DateTimeImmutable $sendAt = null): void
    {
        $datetime = $sendAt ?? new \DateTimeImmutable();

        if ($this->allowedToSendAfter >= $datetime) {
            //throw new UserTimedOutException($this->allowedToSendAfter);
            throw new NotAllowedToSendException($this->allowedToSendAfter);
        }

        $nextAllowedPeriodToSendInMinutes =
            $this->sendEmailAttemptsTimeInMinutes[$this->timesSent] ?? 0;

        $this->allowedToSendAfter =
            $datetime->modify(
                "+ {$nextAllowedPeriodToSendInMinutes} minutes"
            );

        $this->incrementTimesSent();
    }

    private function incrementTimesSent(): void
    {
        ++$this->timesSent;
    }
}
