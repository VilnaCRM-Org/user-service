<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

final class ProductionMessengerTransportConfigTest extends UnitTestCase
{
    private const SQS_TRANSPORTS = [
        'send-email',
        'failed-send-email',
        'insert-user-batch',
        'domain-events',
        'failed-domain-events',
    ];

    public function testProductionDisablesQueueAutoSetupForEverySqsTransport(): void
    {
        $transports = $this->processedConfig()['messenger']['transports'];

        self::assertSame(self::SQS_TRANSPORTS, array_keys($transports));

        foreach (self::SQS_TRANSPORTS as $transport) {
            self::assertFalse($transports[$transport]['options']['auto_setup']);
        }
    }

    public function testTestRedisTransportRetainsAutomaticSetupAndConsumerOverride(): void
    {
        $config = $this->sourceConfig();
        $testConfig = $this->processedConfig($config['when@test']['framework']);
        $transports = $testConfig['messenger']['transports'];
        $sendEmail = $transports['send-email'];

        self::assertSame('%env(REDIS_URL)%', $sendEmail['dsn']);
        self::assertTrue($sendEmail['options']['auto_setup']);
        self::assertSame(
            '%env(MESSENGER_CONSUMER_NAME)%',
            $sendEmail['options']['consumer']
        );
        self::assertSame('in-memory://', $transports['domain-events']['dsn']);
        self::assertSame('in-memory://', $transports['failed-domain-events']['dsn']);
    }

    /**
     * @param array<string, array<string, array<string, array<string, bool|string>|string>>>|null $environmentOverride
     *
     * @return array<string, array<string, array<string, array<string, array<string, bool|string>|string>>>>
     */
    private function processedConfig(?array $environmentOverride = null): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $extension = new FrameworkExtension();
        $configuration = $extension->getConfiguration([], $container);
        self::assertNotNull($configuration);

        $config = $this->sourceConfig();
        $configs = [$config['framework']];
        if ($environmentOverride !== null) {
            $configs[] = $environmentOverride;
        }

        return (new Processor())->processConfiguration($configuration, $configs);
    }

    /**
     * @return array<string, array<string, array<string, array<string, array<string, bool|string>|string>>>>
     */
    private function sourceConfig(): array
    {
        return Yaml::parseFile(dirname(__DIR__, 3) . '/config/packages/messenger.yaml');
    }
}
