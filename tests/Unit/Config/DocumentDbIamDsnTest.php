<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;
use MongoDB\Driver\Exception\InvalidArgumentException;
use MongoDB\Driver\Manager;
use ReflectionExtension;

/**
 * Offline evidence for the MONGODB-AWS DSN (USI S1.3 contract, V-1).
 *
 * The URI is parsed by the libmongoc that ships inside ext-mongodb, so these
 * cases pin what that parser accepts and refuses. Whether DocumentDB accepts
 * the task role is left to the live TEST check.
 */
final class DocumentDbIamDsnTest extends UnitTestCase
{
    private const DOCUMENTDB_QUERY = 'tls=true&tlsCAFile=%s&replicaSet=rs0'
        . '&readPreference=secondaryPreferred&retryWrites=false'
        . '&authSource=%%24external&authMechanism=MONGODB-AWS';

    private const CA_FILE = '/usr/local/share/ca-certificates/aws-documentdb-global-bundle.pem';

    public function testBundledLibmongocHasTheTlsAndCryptoMongodbAwsNeeds(): void
    {
        ob_start();
        (new ReflectionExtension('mongodb'))->info();
        $info = (string) ob_get_clean();

        self::assertStringContainsString('MongoDB extension version => 2.4.1', $info);
        self::assertStringContainsString('libmongoc bundled version => 2.4.0', $info);
        self::assertStringContainsString('libmongoc SSL => enabled', $info);
        self::assertStringContainsString('libmongoc crypto => enabled', $info);
    }

    public function testCredentialFreeIamDsnIsAcceptedWithTheDocumentDbTlsOptions(): void
    {
        $dsn = $this->iamDsn(sprintf(self::DOCUMENTDB_QUERY, rawurlencode(self::CA_FILE)));

        self::assertInstanceOf(Manager::class, new Manager($dsn));
    }

    public function testAuthSourceDefaultsToExternalWhenOmitted(): void
    {
        self::assertInstanceOf(
            Manager::class,
            new Manager($this->iamDsn('authMechanism=MONGODB-AWS'))
        );
    }

    public function testAuthSourceOtherThanExternalIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires "$external" authSource');

        new Manager($this->iamDsn('authMechanism=MONGODB-AWS&authSource=admin'));
    }

    public function testUsernameWithoutPasswordIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not accept a username or a password without the other');

        new Manager($this->iamDsn('authMechanism=MONGODB-AWS', $this->faker->userName()));
    }

    public function testUnsupportedMechanismPropertyIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported 'MONGODB-AWS' authentication mechanism");

        new Manager($this->iamDsn('authMechanism=MONGODB-AWS&authMechanismProperties=FOO:bar'));
    }

    public function testUserinfoPairIsParsedAsStaticAwsKeysSoTheDsnMustStayCredentialFree(): void
    {
        $userinfo = $this->faker->userName() . ':' . rawurlencode($this->faker->password());

        self::assertInstanceOf(
            Manager::class,
            new Manager($this->iamDsn('authMechanism=MONGODB-AWS', $userinfo))
        );
    }

    private function iamDsn(string $query, string $userinfo = ''): string
    {
        $credentials = $userinfo === '' ? '' : $userinfo . '@';

        return sprintf('mongodb://%sdocdb.example:27017/app?%s', $credentials, $query);
    }
}
