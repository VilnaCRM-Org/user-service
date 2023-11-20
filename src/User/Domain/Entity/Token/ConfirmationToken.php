<?php

namespace App\User\Domain\Entity\Token;

class ConfirmationToken
{
    private string $tokenValue;

    private string $userID;

    public function __construct(string $tokenValue, string $userID)
    {
        $this->tokenValue = $tokenValue;
        $this->userID = $userID;
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
