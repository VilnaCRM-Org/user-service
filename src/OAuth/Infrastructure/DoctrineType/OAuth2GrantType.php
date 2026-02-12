<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\DoctrineType;

use App\OAuth\Infrastructure\Serializer\StringableArrayNormalizer;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;

final class OAuth2GrantType extends Type
{
    use ClosureToPHP;

    public const NAME = 'oauth2_grant';

    /**
     * @return null|string[]
     *
     * @param array<Grant|\stdClass|object|string>|string|null $value
     *
     * @psalm-param list{0: Grant|\stdClass|object|string, 1?: Grant|string, 2?: Grant|string}|string|null $value
     *
     * @psalm-return list<string>|null
     */
    #[\Override]
    public function convertToDatabaseValue($value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException(
                'OAuth2GrantType expects an array of stringable values.'
            );
        }

        $normalizer = new StringableArrayNormalizer();

        return $normalizer->normalize(
            $value,
            'OAuth2GrantType expects an array of stringable values.'
        );
    }

    /**
     * @return Grant[]
     *
     * @param string|array<string>|null $value
     *
     * @psalm-param list{0: string, 1?: string, 2?: string}|string|null $value
     *
     * @psalm-return list{0?: Grant, 1?: Grant, 2?: Grant}
     */
    #[\Override]
    public function convertToPHPValue($value): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException('OAuth2GrantType expects an array of strings.');
        }

        return array_map(
            static fn (string $item): Grant => new Grant($item),
            $value
        );
    }

    /**
     * @return string
     *
     * @psalm-return 'if ($value === null) { $return = null; } elseif (is_array($value)) { $return = []; foreach ($value as $item) { if (is_string($item) || (is_object($item) && method_exists($item, "__toString"))) { $return[] = (string) $item; } else { throw new \InvalidArgumentException("OAuth2GrantType expects an array of stringable values."); } } } else { throw new \InvalidArgumentException("OAuth2GrantType expects an array of stringable values."); }'
     */
    #[\Override]
    public function closureToMongo(): string
    {
        return 'if ($value === null) { $return = null; } '
            . 'elseif (is_array($value)) { '
            . '$return = []; '
            . 'foreach ($value as $item) { '
            . 'if (is_string($item) || '
            . '(is_object($item) && method_exists($item, "__toString"))) { '
            . '$return[] = (string) $item; '
            . '} else { '
            . 'throw new \InvalidArgumentException('
            . '"OAuth2GrantType expects an array of stringable values."); '
            . '} '
            . '} '
            . '} else { '
            . 'throw new \InvalidArgumentException('
            . '"OAuth2GrantType expects an array of stringable values."); '
            . '}';
    }
}
