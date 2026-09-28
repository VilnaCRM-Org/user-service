<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;
use Aws\Sqs\SqsClient;
use Symfony\Component\Yaml\Yaml;

final class SqsClientConfigTest extends UnitTestCase
{
    public function testProductionUsesDefaultCredentialsAndRegionalEndpoint(): void
    {
        $config = Yaml::parseFile(
            dirname(__DIR__, 3) . '/config/services.yaml',
            Yaml::PARSE_CUSTOM_TAGS
        );
        $options = $config['services'][SqsClient::class]['arguments'][0];

        $this->assertSame('%env(AWS_SQS_VERSION)%', $options['version']);
        $this->assertSame('%env(AWS_SQS_REGION)%', $options['region']);
        $this->assertArrayNotHasKey('credentials', $options);
        $this->assertArrayNotHasKey('endpoint', $options);
        $this->assertArrayNotHasKey('when@prod', $config);
    }

    public function testLocalEnvironmentsKeepExplicitLocalStackConfiguration(): void
    {
        $config = Yaml::parseFile(
            dirname(__DIR__, 3) . '/config/services.yaml',
            Yaml::PARSE_CUSTOM_TAGS
        );

        foreach (['dev', 'test', 'load_test', 'schemathesis'] as $environment) {
            $environmentConfig = $config['when@' . $environment];
            $options = $environmentConfig['services'][SqsClient::class]['arguments'][0];

            $this->assertSame('%env(AWS_SQS_VERSION)%', $options['version']);
            $this->assertSame('%env(AWS_SQS_REGION)%', $options['region']);
            $this->assertSame(
                '%env(AWS_SQS_ENDPOINT_BASE)%:%env(LOCALSTACK_PORT)%',
                $options['endpoint']
            );
            $this->assertSame('%env(AWS_SQS_KEY)%', $options['credentials']['key']);
            $this->assertSame('%env(AWS_SQS_SECRET)%', $options['credentials']['secret']);
        }
    }
}
