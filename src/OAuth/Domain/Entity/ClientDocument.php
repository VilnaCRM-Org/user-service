<?php

declare(strict_types=1);

namespace App\OAuth\Domain\Entity;

/**
 * Doctrine ODM document for persisting OAuth2 Client data.
 * This is a DTO that gets converted to/from League\Bundle\OAuth2ServerBundle\Model\Client.
 */
class ClientDocument
{
    public string $identifier;
    public string $name;
    public ?string $secret = null;
    public bool $active = true;
    public bool $allowPlainTextPkce = false;

    /** @var list<string> */
    public array $redirectUris = [];

    /** @var list<string> */
    public array $grants = [];

    /** @var list<string> */
    public array $scopes = [];
}
