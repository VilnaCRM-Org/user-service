<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use ApiPlatform\Metadata\Patch;
use App\User\Application\DTO\Token\ConfirmUserDto;
use App\User\Domain\Exception\UserTimedOutException;
use App\User\Infrastructure\Exception\TokenNotFoundException;
use App\User\Infrastructure\Token\ConfirmUserProcessor;

#[Patch(
    uriTemplate: 'users/confirm',
    exceptionToStatus: [TokenNotFoundException::class => 404],
    shortName: 'User',
    input: ConfirmUserDto::class,
    processor: ConfirmUserProcessor::class
)]
class ConfirmationToken
{
    private int $timesSent;
    private \DateTime $allowedToSendAfter;
    private array $sendEmailAttemptsTimeInMinutes;

    public function __construct(private string $tokenValue, private string $userID)
    {
        $this->timesSent = 0;
        $this->allowedToSendAfter = new \DateTime();
        $this->sendEmailAttemptsTimeInMinutes = [
            1 => 1,
            2 => 3,
            3 => 4,
            4 => 1440,
        ];
    }

    public function incrementTimesSent(): void
    {
        ++$this->timesSent;
    }

    public function getTimesSent(): int
    {
        return $this->timesSent;
    }

    public function setTimesSent(int $timesSent): void
    {
        $this->timesSent = $timesSent;
    }

    public function getAllowedToSendAfter(): \DateTime
    {
        return $this->allowedToSendAfter;
    }

    public function setAllowedToSendAfter(\DateTime $allowedToSendAfter): void
    {
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

    public function send(): void
    {
        $datetime = new \DateTime();

        if ($this->allowedToSendAfter > $datetime) {
            throw new UserTimedOutException($this->allowedToSendAfter);
        }

        $nextAllowedPeriodToSendInMinutes = $this->sendEmailAttemptsTimeInMinutes[$this->timesSent] ?? 0;

        $this->allowedToSendAfter = $datetime->modify("+ $nextAllowedPeriodToSendInMinutes minutes");
    }
}
