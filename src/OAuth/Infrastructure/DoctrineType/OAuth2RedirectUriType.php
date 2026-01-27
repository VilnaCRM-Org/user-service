<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\DoctrineType;

use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;

final class OAuth2RedirectUriType extends Type
{
    use ClosureToPHP;

    public const NAME = 'oauth2_redirect_uri';

    #[\Override]
    public function convertToDatabaseValue(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('OAuth2RedirectUriType expects an array of stringable values.');
        }

        return $this->normalizeToStrings($value);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('OAuth2RedirectUriType expects an array of strings.');
        }

        return array_map(
            static fn (string $item): RedirectUri => new RedirectUri($item),
            $value
        );
    }

    #[\Override]
    public function closureToMongo(): string
    {
        return 'if ($value === null) { $return = null; }'
            . 'elseif (is_array($value)) {'
            . '$return = [];'
            . 'foreach ($value as $item) {'
            . 'if (is_string($item) || (is_object($item) && method_exists($item, "__toString"))) { $return[] = (string) $item; }'
            . 'else { throw new \InvalidArgumentException("OAuth2RedirectUriType expects an array of stringable values."); }'
            . '}'
            . '} else { throw new \InvalidArgumentException("OAuth2RedirectUriType expects an array of stringable values."); }';
    }

    /**
     * @param array<int, mixed> $values
     *
     * @return list<string>
     */
    private function normalizeToStrings(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            if (is_string($value)) {
                $normalized[] = $value;
                continue;
            }

            if (is_object($value) && method_exists($value, '__toString')) {
                $normalized[] = (string) $value;
                continue;
            }

            throw new InvalidArgumentException('OAuth2RedirectUriType expects an array of stringable values.');
        }

        return $normalized;
    }
}
