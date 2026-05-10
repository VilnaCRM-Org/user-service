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

    private const FQCN =
        '\App\OAuth\Domain\ValueObject\OAuthProvider';

    private const ERROR_MSG =
        'OAuthProviderType expects an OAuthProvider or string.';

    #[\Override]
    public function convertToDatabaseValue(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof OAuthProvider => (string) $value,
            is_string($value) => $value,
            default => throw new InvalidArgumentException(
                self::ERROR_MSG
            ),
        };
    }

    #[\Override]
    public function convertToPHPValue(mixed $value): ?OAuthProvider
    {
        $providerFactory = $this->providerFactory();

        return match (true) {
            $value === null => null,
            $value instanceof OAuthProvider => $value,
            is_string($value) => $providerFactory($value),
            default => throw new InvalidArgumentException(
                self::ERROR_MSG
            ),
        };
    }

    /** @infection-ignore-all - Doctrine ODM closure, tested via integration */
    #[\Override]
    public function closureToMongo(): string
    {
        return implode(' ', [
            'if ($value === null) { $return = null; }',
            sprintf(
                'elseif ($value instanceof %s)',
                self::FQCN
            ),
            '{ $return = (string) $value; }',
            'elseif (is_string($value))',
            '{ $return = $value; }',
            'else { throw new \InvalidArgumentException(',
            sprintf('"%s"); }', self::ERROR_MSG),
        ]);
    }

    /** @infection-ignore-all - Doctrine ODM closure, tested via integration */
    #[\Override]
    public function closureToPHP(): string
    {
        $fqcn = self::FQCN;

        return implode(' ', [
            'if ($value === null) { $return = null; }',
            sprintf(
                'elseif ($value instanceof %s)',
                $fqcn
            ),
            '{ $return = $value; }',
            'elseif (is_string($value))',
            sprintf('{ $factory = [%s::class, "fromString"]; $return = $factory($value); }', $fqcn),
            'else { throw new \InvalidArgumentException(',
            sprintf('"%s"); }', self::ERROR_MSG),
        ]);
    }

    /**
     * @return callable(string): OAuthProvider
     */
    private function providerFactory(): callable
    {
        return [OAuthProvider::class, 'fromString'];
    }
}
