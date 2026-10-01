<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Security;

use League\OAuth2\Server\CryptKeyInterface;

/**
 * Stands in for league's PEM CryptKey: the JWT key lives in AWS KMS, so no
 * key path, passphrase or key material exists in the application. Anything
 * that still tried to sign or verify with it locally would fail closed.
 */
final readonly class KmsCryptKey implements CryptKeyInterface
{
    #[\Override]
    public function getKeyPath(): string
    {
        return '';
    }

    #[\Override]
    public function getPassPhrase(): ?string
    {
        return null;
    }

    #[\Override]
    public function getKeyContents(): string
    {
        return '';
    }
}
