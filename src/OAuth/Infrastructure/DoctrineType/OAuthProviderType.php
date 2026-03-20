<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\DoctrineType;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;

final class OAuthProviderType extends Type
{
    use ClosureToPHP;

    public const NAME = 'oauth_provider';

    #[\Override]
    public function convertToDatabaseValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof OAuthProvider) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            'OAuthProviderType expects an OAuthProvider or string.'
        );
    }

    #[\Override]
    public function convertToPHPValue(mixed $value): ?OAuthProvider
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof OAuthProvider) {
            return $value;
        }

        if (is_string($value)) {
            return new OAuthProvider($value);
        }

        throw new InvalidArgumentException(
            'OAuthProviderType expects an OAuthProvider or string.'
        );
    }

    #[\Override]
    public function closureToMongo(): string
    {
        return 'if ($value === null) { $return = null; } '
            . 'elseif ($value instanceof \App\OAuth\Domain\ValueObject\OAuthProvider) { '
            . '$return = (string) $value; '
            . '} elseif (is_string($value)) { '
            . '$return = $value; '
            . '} else { '
            . 'throw new \InvalidArgumentException('
            . '"OAuthProviderType expects an OAuthProvider or string."); '
            . '}';
    }

    #[\Override]
    public function closureToPHP(): string
    {
        return 'if ($value === null) { $return = null; } '
            . 'elseif ($value instanceof \App\OAuth\Domain\ValueObject\OAuthProvider) { '
            . '$return = $value; '
            . '} elseif (is_string($value)) { '
            . '$return = new \App\OAuth\Domain\ValueObject\OAuthProvider($value); '
            . '} else { '
            . 'throw new \InvalidArgumentException('
            . '"OAuthProviderType expects an OAuthProvider or string."); '
            . '}';
    }
}
