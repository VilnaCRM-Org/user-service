<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver;

use RuntimeException;

final class DocumentDbIamDsnEnvProcessorCredentialSourceTest extends DocumentDbIamDsnEnvProcessorTestCase
{
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
}
