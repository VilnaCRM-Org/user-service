<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Entity;

use DateTimeInterface;

/**
 * Doctrine ODM document for persisting OAuth2 AuthorizationCode data.
 * This is a DTO that gets converted to/from League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode.
 */
class AuthorizationCodeDocument
{
    public string $identifier;
    public DateTimeInterface $expiry;
    public ?string $userIdentifier = null;
    public string $clientIdentifier;
    public bool $revoked = false;

    /** @var list<string> */
    public array $scopes = [];
}
