<?php

namespace App\User\Domain\Entity\Token;

use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\Patch;
use App\User\Infrastructure\Token\ConfirmEmailMutationResolver;
use App\User\Infrastructure\Token\TokenProcessor;

#[Patch(shortName: 'users_confirm_email', input: ConfirmEmailInputDto::class,
    processor: TokenProcessor::class)]
#[Mutation(resolver: ConfirmEmailMutationResolver::class, args: [
    'tokenValue' => [
        'type' => 'String!',
    ],
], input: ConfirmEmailInputDto::class, name: 'confirm')]
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
