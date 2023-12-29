<?php

namespace App\User\Domain\Entity;

use ApiPlatform\Metadata\Patch;
use App\User\Application\DTO\Token\ConfirmUserDto;
use App\User\Infrastructure\Exception\TokenNotFoundException;
use App\User\Infrastructure\Token\ConfirmUserProcessor;

#[Patch(uriTemplate: 'users/confirm', exceptionToStatus: [TokenNotFoundException::class => 404], shortName: 'User',
    input: ConfirmUserDto::class, processor: ConfirmUserProcessor::class)]
class ConfirmationToken
{
    private int $timesSent;
    private \DateTime $allowedToSendAfter;

    public function __construct(private string $tokenValue, private string $userID)
    {
        $this->timesSent = 0;
        $this->allowedToSendAfter = new \DateTime();
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

    public static function generateToken(string $userID): ConfirmationToken
    {
        return new ConfirmationToken(bin2hex(random_bytes(10)), $userID);
    }
}
