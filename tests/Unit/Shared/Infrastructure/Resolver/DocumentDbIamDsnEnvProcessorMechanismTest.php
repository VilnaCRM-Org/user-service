<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver;

use RuntimeException;

final class DocumentDbIamDsnEnvProcessorMechanismTest extends DocumentDbIamDsnEnvProcessorTestCase
{
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
}
