<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Provider;

use App\Shared\Infrastructure\Provider\AwsKmsEndpointProvider;
use App\Tests\Unit\UnitTestCase;
use Aws\Kms\KmsClient;

final class AwsKmsEndpointProviderTest extends UnitTestCase
{
    public function testReportsTheRegionalEndpointOfTheDefaultClient(): void
    {
        $client = new KmsClient(['region' => 'eu-central-1', 'version' => 'latest']);

        self::assertSame(
            'https://kms.eu-central-1.amazonaws.com',
            (new AwsKmsEndpointProvider($client))->endpoint()
        );
    }

    public function testReportsAnExplicitLocalEndpoint(): void
    {
        $client = new KmsClient([
            'region' => 'us-east-1',
            'version' => 'latest',
            'endpoint' => 'http://localstack:4566',
        ]);

        $provider = new AwsKmsEndpointProvider($client);

        self::assertSame('http://localstack:4566', $provider->endpoint());
    }
}
