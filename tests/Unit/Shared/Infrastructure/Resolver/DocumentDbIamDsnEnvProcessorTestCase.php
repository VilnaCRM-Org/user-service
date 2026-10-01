<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver;

use App\Shared\Infrastructure\Resolver\DocumentDbIamDsnEnvProcessor;
use App\Tests\Unit\UnitTestCase;
use Closure;
use RuntimeException;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;

abstract class DocumentDbIamDsnEnvProcessorTestCase extends UnitTestCase
{
    protected const IAM_QUERY = 'tls=true&retryWrites=false&authMechanism=MONGODB-AWS';

    protected DocumentDbIamDsnEnvProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new DocumentDbIamDsnEnvProcessor();
    }

    /**
     * @param array<string, string> $environment
     */
    protected function refusalMessage(string $dsn, array $environment): string
    {
        try {
            $this->resolve($dsn, $environment);
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        }

        return '';
    }

    /**
     * @param array<string, string> $environment
     */
    protected function resolve(string $dsn, array $environment): string
    {
        return $this->processor->getEnv(
            'documentdb_iam',
            'MONGODB_URL',
            $this->environmentReader($dsn, $environment)
        );
    }

    /**
     * @param array<string, string> $environment
     */
    protected function environmentReader(string $dsn, array $environment): Closure
    {
        $values = ['MONGODB_URL' => $dsn] + $environment;

        return static function (string $name) use ($values): string {
            if (!array_key_exists($name, $values)) {
                throw new EnvNotFoundException('missing');
            }

            return $values[$name];
        };
    }

}
