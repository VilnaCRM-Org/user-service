<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver;

use App\Shared\Infrastructure\Resolver\DocumentDbIamDsnEnvProcessor;
use App\Tests\Unit\UnitTestCase;
use Closure;
use RuntimeException;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;

final class DocumentDbIamDsnEnvProcessorTest extends UnitTestCase
{
    private const IAM_QUERY = 'tls=true&retryWrites=false&authSource=%24external&authMechanism=MONGODB-AWS';

    private DocumentDbIamDsnEnvProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new DocumentDbIamDsnEnvProcessor();
    }

    public function testItIsAnEnvVarProcessorForTheDocumentDbIamPrefix(): void
    {
        self::assertInstanceOf(EnvVarProcessorInterface::class, $this->processor);
        self::assertSame(
            ['documentdb_iam' => 'string'],
            DocumentDbIamDsnEnvProcessor::getProvidedTypes()
        );
    }

    public function testCredentialFreeIamDsnPassesThroughUnchanged(): void
    {
        $dsn = 'mongodb://docdb.example:27017/app?' . self::IAM_QUERY;

        self::assertSame($dsn, $this->resolve($dsn, []));
    }

    public function testMechanismNameIsMatchedCaseInsensitively(): void
    {
        $this->expectException(RuntimeException::class);

        $this->resolve(
            'mongodb://user:pass@docdb.example/app?authMechanism=mongodb-aws',
            []
        );
    }

    public function testPasswordBasedDsnPassesThroughEvenWithAwsKeysInTheEnvironment(): void
    {
        $dsn = 'mongodb://root:secret@database:27017';

        self::assertSame($dsn, $this->resolve($dsn, [
            'AWS_ACCESS_KEY_ID' => 'local',
            'AWS_SECRET_ACCESS_KEY' => 'local',
        ]));
    }

    public function testOtherAuthMechanismWithUserinfoPassesThrough(): void
    {
        $dsn = 'mongodb://u:p@docdb.example/app?authMechanism=SCRAM-SHA-256';

        self::assertSame($dsn, $this->resolve($dsn, []));
    }

    /**
     * @dataProvider userinfoDsnProvider
     */
    public function testIamDsnWithUserinfoIsRefused(string $dsn): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A MONGODB-AWS MONGODB_URL must not carry userinfo.');

        $this->resolve($dsn, []);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function userinfoDsnProvider(): array
    {
        $query = '?' . self::IAM_QUERY;

        return [
            'user and password' => ['mongodb://u:p@docdb.example:27017/app' . $query],
            'user only' => ['mongodb://u@docdb.example:27017/app' . $query],
            'multiple hosts' => ['mongodb://u:p@a.example:27017,b.example:27017/app' . $query],
            'no path' => ['mongodb://u:p@docdb.example:27017' . $query],
            'srv' => ['mongodb+srv://u:p@docdb.example/app' . $query],
            'upper-case scheme' => ['MONGODB://u:p@docdb.example/app' . $query],
        ];
    }

    public function testAtSignInTheQueryIsNotUserinfo(): void
    {
        $dsn = 'mongodb://docdb.example/app?appname=a@b&' . self::IAM_QUERY;

        self::assertSame($dsn, $this->resolve($dsn, []));
    }

    /**
     * @dataProvider staticKeyProvider
     */
    public function testIamDsnIsRefusedWhenAStaticAwsKeyIsPresent(string $variable): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'MONGODB_URL uses MONGODB-AWS, but ' . $variable . ' is set.'
        );

        $this->resolve('mongodb://docdb.example/app?' . self::IAM_QUERY, [$variable => 'x']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function staticKeyProvider(): array
    {
        return [
            'access key id' => ['AWS_ACCESS_KEY_ID'],
            'secret access key' => ['AWS_SECRET_ACCESS_KEY'],
        ];
    }

    public function testEmptyStaticKeyIsTreatedAsUnsetLikeLibmongoc(): void
    {
        $dsn = 'mongodb://docdb.example/app?' . self::IAM_QUERY;

        self::assertSame($dsn, $this->resolve($dsn, [
            'AWS_ACCESS_KEY_ID' => '',
            'AWS_SECRET_ACCESS_KEY' => '',
        ]));
    }

    public function testAnUnsetDsnVariableResolvesToAnEmptyString(): void
    {
        $reader = static fn (string $name): ?string => null;

        self::assertSame('', $this->processor->getEnv('documentdb_iam', 'MONGODB_URL', $reader));
    }

    public function testNullStaticKeyIsTreatedAsUnset(): void
    {
        $dsn = 'mongodb://docdb.example/app?' . self::IAM_QUERY;
        $reader = static fn (string $name): ?string => $name === 'MONGODB_URL' ? $dsn : null;

        self::assertSame($dsn, $this->processor->getEnv('documentdb_iam', 'MONGODB_URL', $reader));
    }

    public function testRefusalNeverEchoesTheDsn(): void
    {
        $secret = $this->faker->password(20, 24);

        try {
            $this->resolve(
                'mongodb://user:' . rawurlencode($secret) . '@h/app?' . self::IAM_QUERY,
                []
            );
            self::fail('Expected a refusal.');
        } catch (RuntimeException $exception) {
            self::assertStringNotContainsString($secret, $exception->getMessage());
        }
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolve(string $dsn, array $environment): string
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
    private function environmentReader(string $dsn, array $environment): Closure
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
