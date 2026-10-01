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
 * libmongoc signs with URI userinfo first, then with AWS_ACCESS_KEY_ID /
 * AWS_SECRET_ACCESS_KEY (with AWS_SESSION_TOKEN), then with web identity, and only
 * then asks the ECS credentials endpoint. Any of them would silently replace the
 * task role, so the connection is refused. Connection strings that use another
 * mechanism pass through untouched.
 *
 * The query is read the way libmongoc reads it: keys are case-insensitive, keys and
 * values are percent-decoded, and any authMechanism pair naming MONGODB-AWS counts.
 */
final class DocumentDbIamDsnEnvProcessor implements EnvVarProcessorInterface
{
    private const MECHANISM = 'MONGODB-AWS';

    private const STATIC_VARIABLES = [
        'AWS_ACCESS_KEY_ID',
        'AWS_SECRET_ACCESS_KEY',
        'AWS_SESSION_TOKEN',
    ];

    private const WELL_FORMED = '#^mongodb(?:\+srv)?://[^/?]+(?:/[^?]*)?(?:\?.*)?$#i';

    #[\Override]
    public function getEnv(string $prefix, string $name, Closure $getEnv): string
    {
        $dsn = (string) $getEnv($name);

        if (stripos(rawurldecode($dsn), self::MECHANISM) === false) {
            return $dsn;
        }

        if (preg_match(self::WELL_FORMED, $dsn) !== 1) {
            throw new RuntimeException(
                'MONGODB_URL mentions MONGODB-AWS but is not a valid MongoDB URI.'
            );
        }

        if (!$this->usesIamMechanism($dsn)) {
            return $dsn;
        }

        if (preg_match('#^[^:]+://[^/?]*@#', $dsn) === 1) {
            throw new RuntimeException('A MONGODB-AWS MONGODB_URL must not carry userinfo.');
        }

        $this->assertNoOtherCredentialSource($getEnv);

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
        foreach (explode('&', (string) substr((string) strstr($dsn, '?'), 1)) as $pair) {
            [$key, $value] = explode('=', $pair, 2) + [1 => ''];

            if (
                strtolower(rawurldecode($key)) === 'authmechanism'
                && strcasecmp(rawurldecode($value), self::MECHANISM) === 0
            ) {
                return true;
            }
        }

        return false;
    }

    private function assertNoOtherCredentialSource(Closure $getEnv): void
    {
        foreach (self::STATIC_VARIABLES as $variable) {
            if ($this->isSet($getEnv, $variable)) {
                throw new RuntimeException(
                    sprintf('MONGODB_URL uses MONGODB-AWS, but %s is set.', $variable)
                );
            }
        }

        if ($this->isSet($getEnv, 'AWS_WEB_IDENTITY_TOKEN_FILE') && $this->isSet($getEnv, 'AWS_ROLE_ARN')) {
            throw new RuntimeException(
                'MONGODB_URL uses MONGODB-AWS, but web identity credentials are configured.'
            );
        }
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
