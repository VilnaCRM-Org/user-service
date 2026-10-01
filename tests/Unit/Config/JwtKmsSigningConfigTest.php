<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\OAuth\Application\Controller\JsonWebKeySetController;
use App\OAuth\Infrastructure\Repository\KmsAccessTokenRepository;
use App\Shared\Application\EventListener\JwtKmsConfigurationListener;
use App\Shared\Application\Provider\KmsEndpointProviderInterface;
use App\Shared\Infrastructure\Adapter\KmsJwsProvider;
use App\Shared\Infrastructure\Provider\AwsKmsEndpointProvider;
use App\Shared\Infrastructure\Provider\KmsJwtKeyProvider;
use App\Tests\Unit\UnitTestCase;
use Aws\Kms\KmsClient;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\LcobucciJWTEncoder;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

/**
 * S5.11 (FR-06): JWT and OAuth tokens are signed by the KMS JWT key; no PEM
 * key, passphrase or local signer is configured for any environment.
 */
final class JwtKmsSigningConfigTest extends UnitTestCase
{
    private const LOCAL_ENVIRONMENTS = ['dev', 'test', 'load_test', 'schemathesis'];
    private const LOCAL_ENV_FILES = [
        '.env.dev',
        '.env.test',
        '.env.load_test',
        '.env.schemathesis',
    ];
    private const CREDENTIAL_VARIABLES = [
        'AWS_ACCESS_KEY_ID',
        'AWS_SECRET_ACCESS_KEY',
        'AWS_SESSION_TOKEN',
        'AWS_PROFILE',
        'AWS_SHARED_CREDENTIALS_FILE',
        'AWS_CONTAINER_CREDENTIALS_FULL_URI',
        'AWS_WEB_IDENTITY_TOKEN_FILE',
        'AWS_ROLE_ARN',
    ];
    private const LOCAL_KEY_ALIAS = 'alias/user-service-jwt';
    private const LOCAL_KEY_ARN = 'arn:aws:kms:us-east-1:000000000000:' . self::LOCAL_KEY_ALIAS;

    public function testLexikUsesTheKmsEncoderWithoutLocalKeys(): void
    {
        $file = $this->yaml('config/packages/lexik_jwt_authentication.yaml');
        $config = $file['lexik_jwt_authentication'];

        self::assertSame(['service' => 'app.jwt.kms_encoder'], $config['encoder']);
        self::assertSame('aws-kms', $config['public_key']);
        self::assertArrayNotHasKey('secret_key', $config);
        self::assertArrayNotHasKey('pass_phrase', $config);
    }

    public function testLeagueKeysAreKmsPlaceholdersWithoutPassphrase(): void
    {
        $config = $this->yaml('config/packages/league_oauth2_server.yaml')['league_oauth2_server'];

        self::assertSame('aws-kms', $config['authorization_server']['private_key']);
        self::assertArrayNotHasKey('private_key_passphrase', $config['authorization_server']);
        self::assertSame('aws-kms', $config['resource_server']['public_key']);
    }

    public function testKmsServicesAreWiredToTheJwtKeyEnvironment(): void
    {
        $services = $this->services()['services'];

        self::assertSame(
            [
                '$currentKeyId' => '%env(JWT_KMS_KEY_ID)%',
                '$previousKeyId' => '%env(JWT_KMS_PREVIOUS_KEY_ID)%',
                '$cacheTtlSeconds' => '%env(int:JWT_KMS_PUBLIC_KEY_CACHE_TTL)%',
            ],
            $services[KmsJwtKeyProvider::class]['arguments']
        );
        self::assertSame(
            ['class' => LcobucciJWTEncoder::class, 'arguments' => ['@' . KmsJwsProvider::class]],
            $services['app.jwt.kms_encoder']
        );
        self::assertSame(
            'league.oauth2_server.repository.access_token',
            $services[KmsAccessTokenRepository::class]['decorates']
        );
    }

    public function testProductionGuardReceivesKeyIdsAndCredentialVariables(): void
    {
        $arguments = $this->services()['services'][JwtKmsConfigurationListener::class]['arguments'];

        self::assertSame('%kernel.environment%', $arguments['$appEnv']);
        self::assertSame('%env(JWT_KMS_KEY_ID)%', $arguments['$currentKeyId']);
        self::assertSame('%env(JWT_KMS_PREVIOUS_KEY_ID)%', $arguments['$previousKeyId']);
        $expected = [];
        foreach (self::CREDENTIAL_VARIABLES as $name) {
            $expected[$name] = sprintf('%%env(string:default::%s)%%', $name);
        }
        self::assertSame($expected, $arguments['$credentialEnvironment']);
    }

    public function testGuardReadsTheKmsEndpointThroughInfrastructure(): void
    {
        self::assertSame(
            '@' . AwsKmsEndpointProvider::class,
            $this->services()['services'][KmsEndpointProviderInterface::class]
        );
    }

    public function testProductionKmsClientUsesTheTaskRoleAndRegionalEndpoint(): void
    {
        $options = $this->services()['services'][KmsClient::class]['arguments'][0];

        self::assertSame(['version' => 'latest', 'region' => '%env(AWS_REGION)%'], $options);
    }

    public function testLocalEnvironmentsUseLocalStackKms(): void
    {
        $config = $this->services();

        foreach (self::LOCAL_ENVIRONMENTS as $environment) {
            self::assertSame(
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

    public function testCommittedEnvShipsNoPemKeysAndAnEmptyKmsKey(): void
    {
        $env = $this->dotenv('.env');

        foreach (['OAUTH_PRIVATE_KEY', 'OAUTH_PUBLIC_KEY', 'OAUTH_PASSPHRASE'] as $retired) {
            self::assertArrayNotHasKey($retired, $env);
        }
        self::assertSame('', $env['JWT_KMS_KEY_ID']);
        self::assertSame('', $env['JWT_KMS_PREVIOUS_KEY_ID']);
        self::assertSame('300', $env['JWT_KMS_PUBLIC_KEY_CACHE_TTL']);
    }

    public function testLocalEnvironmentsSignWithTheLocalStackAliasArn(): void
    {
        foreach (self::LOCAL_ENV_FILES as $file) {
            self::assertSame(self::LOCAL_KEY_ARN, $this->dotenv($file)['JWT_KMS_KEY_ID'], $file);
        }
    }

    public function testLocalStackCreatesAnRsaSigningKeyForTheAlias(): void
    {
        $script = (string) file_get_contents($this->path('infrastructure/docker/php/init-aws.sh'));

        self::assertStringContainsString('JWT_KMS_ALIAS=' . self::LOCAL_KEY_ALIAS, $script);
        self::assertStringContainsString('--key-usage SIGN_VERIFY', $script);
        self::assertStringContainsString('--key-spec RSA_2048', $script);
    }

    public function testComposerScriptsGenerateNoLocalKeyPair(): void
    {
        $composer = (string) file_get_contents($this->path('composer.json'));

        self::assertStringNotContainsString('generate-keypair', $composer);
    }

    public function testLocalStackHealthcheckWaitsForTheJwtKeyAlias(): void
    {
        $script = (string) file_get_contents(
            $this->path('infrastructure/docker/php/localstack-healthcheck.sh')
        );

        self::assertStringContainsString('alias/user-service-jwt', $script);
        self::assertStringContainsString('TrentService.ListAliases', $script);
        self::assertStringContainsString('"AliasName\\": \\"$alias\\""', $script);
    }

    public function testJwksRouteIsPublishedUnderTheWellKnownPath(): void
    {
        $route = $this->yaml('config/routes.yaml')['jwks'];

        self::assertSame('/api/.well-known/jwks.json', $route['path']);
        self::assertSame(JsonWebKeySetController::class, $route['controller']);
        self::assertSame(['GET'], $route['methods']);
    }

    /**
     * @return array<string, array<string, array<string, array<array-key, string>|string>>>
     */
    private function services(): array
    {
        return Yaml::parseFile($this->path('config/services.yaml'), Yaml::PARSE_CUSTOM_TAGS);
    }

    /**
     * @return array<string, array<string, array<string, string>|string>>
     */
    private function yaml(string $file): array
    {
        return Yaml::parseFile($this->path($file));
    }

    /**
     * @return array<string, string>
     */
    private function dotenv(string $file): array
    {
        return (new Dotenv())->parse((string) file_get_contents($this->path($file)));
    }

    private function path(string $file): string
    {
        return dirname(__DIR__, 3) . '/' . $file;
    }
}
