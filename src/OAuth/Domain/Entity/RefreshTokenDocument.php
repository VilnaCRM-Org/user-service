<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Entity;

use DateTimeInterface;

/**
 * Doctrine ODM document for persisting OAuth2 RefreshToken data.
 * This ifinal final s a DTO that gets converted to/from League\Bundle\OAuth2ServerBundle\Model\RefreshToken.
 */
class RefreshTokenDocument
{
    public string $identifier;
    public DateTimeInterface $expiry;
    public bool $revoked = false;
    public ?string $accessTokenIdentifier = null;
}
