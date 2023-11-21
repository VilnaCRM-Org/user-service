<?php

namespace App\User\Domain\Entity\Token;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\User\Infrastructure\Token\ConfirmEmailMutationResolver;
use App\User\Infrastructure\Token\TokenProvider;

#[Get(shortName: 'confirm', provider: TokenProvider::class)]
#[Mutation(resolver: ConfirmEmailMutationResolver::class, args: [
    'tokenValue' => [
        'type' => 'String!',
    ]
], input: ConfirmEmailInputDto::class, name: 'confirm')]
class ConfirmationToken
{
    #[ApiProperty(identifier: true)]
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
