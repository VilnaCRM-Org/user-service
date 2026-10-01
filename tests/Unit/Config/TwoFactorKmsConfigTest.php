<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Shared\Application\EventListener\TwoFactorEncryptionKeyConfigurationListener;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Infrastructure\Adapter\KmsTwoFactorSecretEncryptor;
use Aws\Kms\KmsClient;
use Symfony\Component\Yaml\Yaml;

/**
 * S5.12 (FR-06): 2FA secrets use the KMS 2FA key; the static
 * TWO_FACTOR_ENCRYPTION_KEY path is removed.
 */
final class TwoFactorKmsConfigTest extends UnitTestCase
{
    private const LOCAL_ENVIRONMENTS = ['dev', 'test', 'load_test', 'schemathesis'];
    private const LOCAL_KEY_ALIAS = 'alias/user-service-two-factor';
    private const HEALTHCHECK_PATH = '/usr/local/bin/localstack-healthcheck.sh';
    private const HEALTHCHECK_MOUNT = './infrastructure/docker/php/localstack-healthcheck.sh:'
        . self::HEALTHCHECK_PATH . ':ro';
    private const LOCALSTACK_COMPOSE_FILES = [
        'docker-compose.override.yml',
        'docker-compose.memory-tests.yml',
        'docker-compose.schemathesis.yml',
        'docker-compose.load-tests.yml',
    ];
    private const LOCAL_ENV_FILES = [
        '.env.dev',
        '.env.test',
        '.env.load_test',
        '.env.schemathesis',
    ];

    public function testEncryptorUsesTheKmsKeyIdFromTheEnvironment(): void
    {
        $services = $this->services()['services'];

        $this->assertSame(
            '@' . KmsTwoFactorSecretEncryptor::class,
            $services[TwoFactorSecretEncryptorInterface::class]
        );
        $this->assertSame(
            ['$keyId' => '%env(TWO_FACTOR_KMS_KEY_ID)%'],
            $services[KmsTwoFactorSecretEncryptor::class]['arguments']
        );
    }

    public function testProductionFailFastChecksTheKeyIdAndTheRegion(): void
    {
        $listener = $this->services()['services'][
            TwoFactorEncryptionKeyConfigurationListener::class
        ];

        $this->assertSame(
            [
                '$appEnv' => '%kernel.environment%',
                '$twoFactorKmsKeyId' => '%env(default::TWO_FACTOR_KMS_KEY_ID)%',
                '$awsRegion' => '%env(default::AWS_REGION)%',
            ],
            $listener['arguments']
        );
    }

    public function testLocalStackHealthcheckWaitsForInitAndForSqsAndKms(): void
    {
        $script = (string) file_get_contents(
            $this->path('infrastructure/docker/php/localstack-healthcheck.sh')
        );

        foreach ([
            '/_localstack/init/ready',
            '"completed": true',
            '"state": "SUCCESSFUL"',
            '"sqs": "running"',
            '"kms": "running"',
        ] as $expected) {
            $this->assertStringContainsString($expected, $script);
        }
    }

    public function testEveryLocalStackServiceRunsKmsAndUsesTheHealthcheck(): void
    {
        foreach (self::LOCALSTACK_COMPOSE_FILES as $file) {
            $localstack = Yaml::parseFile($this->path($file))['services']['localstack'];

            $this->assertContains('SERVICES=sqs,kms', $localstack['environment'], $file);
            $this->assertContains(self::HEALTHCHECK_MOUNT, $localstack['volumes'], $file);
            $this->assertSame(
                ['CMD', 'sh', self::HEALTHCHECK_PATH],
                $localstack['healthcheck']['test'],
                $file
            );
        }
    }

    public function testProductionKmsClientUsesTheTaskRoleAndTheRegionalEndpoint(): void
    {
        $config = $this->services();

        $this->assertSame(
            ['version' => 'latest', 'region' => '%env(AWS_REGION)%'],
            $config['services'][KmsClient::class]['arguments'][0]
        );
        $this->assertArrayNotHasKey('when@prod', $config);
    }

    public function testLocalEnvironmentsUseTheLocalStackKms(): void
    {
        $config = $this->services();

        foreach (self::LOCAL_ENVIRONMENTS as $environment) {
            $this->assertSame(
                [
                    'version' => 'latest',
                    'region' => '%env(AWS_KMS_LOCAL_REGION)%',
                    'endpoint' => '%env(AWS_KMS_LOCAL_ENDPOINT)%',
                    'credentials' => [
                        'key' => '%env(AWS_KMS_LOCAL_KEY)%',
                        'secret' => '%env(AWS_KMS_LOCAL_SECRET)%',
                    ],
                ],
                $config['when@' . $environment]['services'][KmsClient::class]['arguments'][0],
                $environment
            );
        }
    }

    public function testLocalEnvironmentsUseTheLocalStackKeyAndProductionHasNoDefault(): void
    {
        $this->assertSame('', $this->dotenv('.env')['TWO_FACTOR_KMS_KEY_ID']);

        foreach (self::LOCAL_ENV_FILES as $file) {
            $this->assertSame(
                self::LOCAL_KEY_ALIAS,
                $this->dotenv($file)['TWO_FACTOR_KMS_KEY_ID'],
                $file
            );
        }
    }

    public function testLocalStackCreatesTheLocalTwoFactorKey(): void
    {
        $script = (string) file_get_contents(
            $this->path('infrastructure/docker/php/init-aws.sh')
        );

        $this->assertStringContainsString('kms create-key', $script);
        $this->assertStringContainsString(self::LOCAL_KEY_ALIAS, $script);
    }

    public function testStaticTwoFactorEncryptionKeyIsRemoved(): void
    {
        foreach (['config/services.yaml', '.env', ...self::LOCAL_ENV_FILES] as $file) {
            $this->assertStringNotContainsString(
                'TWO_FACTOR_ENCRYPTION_KEY',
                (string) file_get_contents($this->path($file)),
                $file
            );
        }
    }

    /**
     * @return array<string, array<string, array<string, array|string>>>
     */
    private function services(): array
    {
        $config = Yaml::parseFile($this->path('config/services.yaml'), Yaml::PARSE_CUSTOM_TAGS);
        $this->assertIsArray($config);

        return $config;
    }

    /**
     * @return array<string, string>
     */
    private function dotenv(string $file): array
    {
        $lines = file($this->path($file), FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines, $file);
        $values = [];
        foreach ($lines as $line) {
            if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $line, $match) === 1) {
                $values[$match[1]] = $match[2];
            }
        }

        return $values;
    }

    private function path(string $relative): string
    {
        return dirname(__DIR__, 3) . '/' . $relative;
    }
}
