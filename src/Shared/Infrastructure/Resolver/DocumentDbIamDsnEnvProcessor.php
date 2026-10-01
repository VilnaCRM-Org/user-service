<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Resolver;

use Closure;
use RuntimeException;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;

/**
 * Fails closed on a MONGODB-AWS connection string that could bypass the task role.
 *
 * libmongoc signs with URI userinfo first and with AWS_ACCESS_KEY_ID /
 * AWS_SECRET_ACCESS_KEY next, and only then asks the ECS credentials endpoint.
 * Either would silently replace the task role, so the connection is refused.
 * Connection strings that use another mechanism pass through untouched.
 */
final class DocumentDbIamDsnEnvProcessor implements EnvVarProcessorInterface
{
    private const MECHANISM = 'MONGODB-AWS';

    private const STATIC_KEY_VARIABLES = ['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY'];

    #[\Override]
    public function getEnv(string $prefix, string $name, Closure $getEnv): string
    {
        $dsn = (string) $getEnv($name);

        if (!$this->usesIamMechanism($dsn)) {
            return $dsn;
        }

        if (preg_match('#^mongodb(?:\+srv)?://[^/?]*@#i', $dsn) === 1) {
            throw new RuntimeException('A MONGODB-AWS MONGODB_URL must not carry userinfo.');
        }

        foreach (self::STATIC_KEY_VARIABLES as $variable) {
            if ($this->isSet($getEnv, $variable)) {
                throw new RuntimeException(
                    sprintf('MONGODB_URL uses MONGODB-AWS, but %s is set.', $variable)
                );
            }
        }

        return $dsn;
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public static function getProvidedTypes(): array
    {
        return ['documentdb_iam' => 'string'];
    }

    private function usesIamMechanism(string $dsn): bool
    {
        $query = (string) parse_url($dsn, PHP_URL_QUERY);
        parse_str($query, $parameters);

        return strcasecmp((string) ($parameters['authMechanism'] ?? ''), self::MECHANISM) === 0;
    }

    private function isSet(Closure $getEnv, string $variable): bool
    {
        try {
            return (string) $getEnv($variable) !== '';
        } catch (EnvNotFoundException) {
            return false;
        }
    }
}
