<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;

final class SesApiTransportDsnTest extends UnitTestCase
{
    private const DSN = 'ses+api://default?region=eu-central-1';

    public function testCredentialFreeSesApiDsnBuildsTheAsyncAwsTransport(): void
    {
        $dsn = Dsn::fromString(self::DSN);

        self::assertNull($dsn->getUser());
        self::assertNull($dsn->getPassword());

        $transport = Transport::fromDsn(self::DSN);

        self::assertInstanceOf(SesApiAsyncAwsTransport::class, $transport);
    }
}
