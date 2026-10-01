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
    private const IAM_QUERY = 'tls=true&retryWrites=false&authMechanism=MONGODB-AWS';

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

    /**
     * @dataProvider mechanismSpellingProvider
     */
    public function testEveryQuerySpellingLibmongocAcceptsIsRecognisedAsIam(string $query): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A MONGODB-AWS MONGODB_URL must not carry userinfo.');

        $this->resolve('mongodb://u:p@docdb.example/app?' . $query, []);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function mechanismSpellingProvider(): array
    {
        return [
            'lower-case key' => ['authmechanism=MONGODB-AWS'],
            'upper-case key' => ['AUTHMECHANISM=MONGODB-AWS'],
            'lower-case value' => ['authMechanism=mongodb-aws'],
            'encoded value' => ['authMechanism=MONGODB%2DAWS'],
            'encoded key' => ['auth%4Dechanism=MONGODB-AWS'],
            'duplicate, other first' => ['authMechanism=SCRAM-SHA-256&authmechanism=MONGODB-AWS'],
            'duplicate, other last' => ['authmechanism=MONGODB-AWS&authMechanism=SCRAM-SHA-256'],
        ];
    }

    public function testMultiHostDsnWithoutAPortOnTheLastHostIsStillChecked(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A MONGODB-AWS MONGODB_URL must not carry userinfo.');

        $this->resolve('mongodb://AKIA:secret@h1:27017,h2/app?' . self::IAM_QUERY, []);
    }

    /**
     * @dataProvider malformedDsnProvider
     */
    public function testUnparsableDsnThatMentionsMongodbAwsIsRefused(string $dsn): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'MONGODB_URL mentions MONGODB-AWS but is not a valid MongoDB URI.'
        );

        $this->resolve($dsn, []);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedDsnProvider(): array
    {
        return [
            'garbage' => ['not a uri authMechanism=MONGODB-AWS'],
            'wrong scheme' => ['http://h/app?authMechanism=MONGODB-AWS'],
            'empty authority' => ['mongodb://?authMechanism=MONGODB-AWS'],
            'encoded garbage' => ['x authMechanism=MONGODB%2DAWS'],
        ];
    }

    public function testDsnThatOnlyMentionsMongodbAwsOutsideTheMechanismPassesThrough(): void
    {
        $dsn = 'mongodb://u:p@docdb.example/app?authMechanism=SCRAM-SHA-256&appname=mongodb-aws';

        self::assertSame($dsn, $this->resolve($dsn, ['AWS_ACCESS_KEY_ID' => 'x']));
    }

    public function testWebIdentityCredentialsAreRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'MONGODB_URL uses MONGODB-AWS, but web identity credentials are configured.'
        );

        $this->resolve('mongodb://docdb.example/app?' . self::IAM_QUERY, [
            'AWS_WEB_IDENTITY_TOKEN_FILE' => '/token',
            'AWS_ROLE_ARN' => 'arn:aws:iam::1:role/r',
        ]);
    }

    /**
     * @dataProvider harmlessAwsVariableProvider
     *
     * @param array<string, string> $environment
     */
    public function testVariablesLibmongocNeverReadsOrCannotUseAloneAreAllowed(
        array $environment
    ): void {
        $dsn = 'mongodb://docdb.example/app?' . self::IAM_QUERY;

        self::assertSame($dsn, $this->resolve($dsn, $environment));
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function harmlessAwsVariableProvider(): array
    {
        return [
            'profile' => [['AWS_PROFILE' => 'p']],
            'shared credentials file' => [['AWS_SHARED_CREDENTIALS_FILE' => '/c']],
            'token file only' => [['AWS_WEB_IDENTITY_TOKEN_FILE' => '/token']],
            'role arn only' => [['AWS_ROLE_ARN' => 'arn:aws:iam::1:role/r']],
        ];
    }

    public function testLoneSessionTokenIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MONGODB_URL uses MONGODB-AWS, but AWS_SESSION_TOKEN is set.');

        $this->resolve('mongodb://docdb.example/app?' . self::IAM_QUERY, ['AWS_SESSION_TOKEN' => 'x']);
    }

    /**
     * @dataProvider refusalProvider
     *
     * @param array<string, string> $environment
     */
    public function testNoRefusalMessageContainsTheDsnOrACredentialValue(
        string $dsn,
        array $environment
    ): void {
        $message = $this->refusalMessage($dsn, $environment);

        self::assertNotSame('', $message);
        self::assertStringNotContainsString('s3cr3t', $message);
        self::assertStringNotContainsString('docdb.example', $message);
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function refusalProvider(): array
    {
        $dsn = 'mongodb://docdb.example/app?' . self::IAM_QUERY;

        return [
            'userinfo' => ['mongodb://AKIAs3cr3t:s3cr3t@docdb.example/app?' . self::IAM_QUERY, []],
            'access key' => [$dsn, ['AWS_ACCESS_KEY_ID' => 's3cr3t']],
            'secret key' => [$dsn, ['AWS_SECRET_ACCESS_KEY' => 's3cr3t']],
            'session token' => [$dsn, ['AWS_SESSION_TOKEN' => 's3cr3t']],
            'web identity' => [
                $dsn,
                ['AWS_WEB_IDENTITY_TOKEN_FILE' => 's3cr3t', 'AWS_ROLE_ARN' => 's3cr3t'],
            ],
            'malformed' => ['docdb.example s3cr3t authMechanism=MONGODB-AWS', []],
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
        $reader = static fn (): ?string => null;

        self::assertSame('', $this->processor->getEnv('documentdb_iam', 'MONGODB_URL', $reader));
    }

    public function testNullStaticKeyIsTreatedAsUnset(): void
    {
        $dsn = 'mongodb://docdb.example/app?' . self::IAM_QUERY;
        $reader = static fn (string $name): ?string => ['MONGODB_URL' => $dsn][$name] ?? null;

        self::assertSame($dsn, $this->processor->getEnv('documentdb_iam', 'MONGODB_URL', $reader));
    }

    /**
     * @param array<string, string> $environment
     */
    private function refusalMessage(string $dsn, array $environment): string
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
