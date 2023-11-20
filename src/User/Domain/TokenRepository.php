<?php

namespace App\User\Domain;

use App\User\Domain\Entity\Token\ConfirmationToken;

interface TokenRepository
{
    public function save(ConfirmationToken $token): void;

    public function find(string $token): ConfirmationToken;

    /**
     * Deletes all confirmation tokens that was created for specific user.
     */
    public function delete(ConfirmationToken $token): void;
}
