<?php

namespace App\User\Domain;

use App\User\Domain\Entity\Token\Token;

interface TokenRepository
{
    public function save(Token $token): void;

    public function find(string $token): Token;
}
