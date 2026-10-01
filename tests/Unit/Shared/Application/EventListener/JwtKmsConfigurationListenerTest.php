<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\JwtKmsConfigurationListener;
use App\Shared\Application\Provider\KmsEndpointProviderInterface;
use App\Tests\Unit\UnitTestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class JwtKmsConfigurationListenerTest extends UnitTestCase
{
    private const KEY_ARN = 'arn:aws:kms:eu-central-1:123456789012:key/0b1c2d3e-aaaa-4bbb';
    private const ALIAS_ARN = 'arn:aws:kms:eu-central-1:123456789012:alias/user-service-jwt';
    private const REGIONAL_ENDPOINT = 'https://kms.eu-central-1.amazonaws.com';
    private const KEY_MESSAGE = 'Set JWT_KMS_KEY_ID to the KMS key ARN or alias ARN in production.';
    private const PREVIOUS_MESSAGE
        = 'JWT_KMS_PREVIOUS_KEY_ID must be empty or a KMS key ARN or alias ARN in production.';
    private const CREDENTIALS_MESSAGE
        = 'Only the ECS task role may sign JWTs in production; unset the static,'
        . ' profile, web-identity and full-URI AWS credential variables.';
    private const ENDPOINT_MESSAGE
        = 'JWT signing must use the regional AWS KMS endpoint in production.';
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

    /**
     * @dataProvider validProductionConfigurations
     */
    public function testAcceptsTaskRoleKmsConfigurationInProduction(
        string $keyId,
        string $previousKeyId,
        string $endpoint
    ): void {
        $this->listener(keyId: $keyId, previousKeyId: $previousKeyId, endpoint: $endpoint)
            ->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function validProductionConfigurations(): iterable
    {
        yield 'key ARN, no window' => [self::KEY_ARN, '', self::REGIONAL_ENDPOINT];
        yield 'alias ARN with an additional verify-only key' => [
            self::ALIAS_ARN,
            self::KEY_ARN,
            self::REGIONAL_ENDPOINT,
        ];
        yield 'FIPS endpoint' => [self::KEY_ARN, '', 'https://kms-fips.eu-central-1.amazonaws.com'];
    }

    /**
     * @dataProvider invalidKeyIds
     */
    public function testRejectsKeyIdThatIsNotAKmsArnInProduction(string $keyId): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::KEY_MESSAGE);

        $this->listener(keyId: $keyId)
            ->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidKeyIds(): iterable
    {
        yield 'empty' => [''];
        yield 'local alias name' => ['alias/user-service-jwt'];
        yield 'bare key id' => ['0b1c2d3e-aaaa-4bbb'];
        yield 'prefixed' => ['x' . self::KEY_ARN];
        yield 'suffixed' => [self::KEY_ARN . ' '];
        yield 'trailing newline' => [self::KEY_ARN . "\n"];
        yield 'other service' => ['arn:aws:sqs:eu-central-1:123456789012:key/abc'];
    }

    public function testRejectsPreviousKeyThatIsNotAKmsArnInProduction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::PREVIOUS_MESSAGE);

        $this->listener(previousKeyId: 'alias/old')
            ->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));
    }

    /**
     * @dataProvider refusedCredentials
     *
     * @param array<string, string> $credentials
     */
    public function testRejectsNonTaskRoleAwsCredentialsInProduction(array $credentials): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::CREDENTIALS_MESSAGE);

        $this->listener(credentials: $credentials)
            ->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function refusedCredentials(): iterable
    {
        yield 'access key id' => [['AWS_ACCESS_KEY_ID' => 'AKIAEXAMPLE']];
        yield 'secret access key' => [['AWS_SECRET_ACCESS_KEY' => 'secret']];
        yield 'session token' => [['AWS_SESSION_TOKEN' => 'token']];
        yield 'profile' => [['AWS_PROFILE' => 'default']];
        yield 'shared credentials file' => [['AWS_SHARED_CREDENTIALS_FILE' => '/creds']];
        yield 'full container credentials URI' => [
            ['AWS_CONTAINER_CREDENTIALS_FULL_URI' => 'http://192.0.2.10/creds'],
        ];
        yield 'web identity pair' => [[
            'AWS_WEB_IDENTITY_TOKEN_FILE' => '/token',
            'AWS_ROLE_ARN' => 'arn:aws:iam::123456789012:role/other',
        ]];
    }

    /**
     * @dataProvider incompleteWebIdentity
     *
     * @param array<string, string> $credentials
     */
    public function testAcceptsAnIncompleteWebIdentityPair(array $credentials): void
    {
        $this->listener(credentials: $credentials)
            ->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function incompleteWebIdentity(): iterable
    {
        yield 'token file only' => [['AWS_WEB_IDENTITY_TOKEN_FILE' => '/token']];
        yield 'role ARN only' => [['AWS_ROLE_ARN' => 'arn:aws:iam::123456789012:role/other']];
    }

    /**
     * @dataProvider localEndpoints
     */
    public function testRejectsLocalOrDevSignerInProduction(string $endpoint): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::ENDPOINT_MESSAGE);

        $this->listener(endpoint: $endpoint)
            ->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function localEndpoints(): iterable
    {
        yield 'LocalStack' => ['http://localstack:4566'];
        yield 'plain HTTP AWS host' => ['http://kms.eu-central-1.amazonaws.com'];
        yield 'look-alike suffix' => ['https://kms.eu-central-1.amazonaws.com.evil.example'];
        yield 'look-alike prefix' => ['https://evilkms.eu-central-1.amazonaws.com'];
        yield 'regional host inside a proxy URL' => [
            'http://proxy.local/?https://kms.eu-central-1.amazonaws.com',
        ];
        yield 'trailing newline' => [self::REGIONAL_ENDPOINT . "\n"];
    }

    public function testDoesNotValidateOutsideProduction(): void
    {
        $this->listener(
            environment: 'test',
            keyId: 'alias/user-service-jwt',
            endpoint: 'http://localstack:4566',
            credentials: ['AWS_ACCESS_KEY_ID' => 'fake']
        )->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));

        $this->addToAssertionCount(1);
    }

    public function testIgnoresSubRequests(): void
    {
        $this->listener(keyId: '')
            ->onKernelRequest($this->requestEvent(HttpKernelInterface::SUB_REQUEST));

        $this->addToAssertionCount(1);
    }

    public function testValidatesConsoleCommandsInProduction(): void
    {
        $event = new ConsoleCommandEvent(
            new Command('app:test'),
            new ArrayInput([]),
            new BufferedOutput()
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::KEY_MESSAGE);

        $this->listener(keyId: '')->onConsoleCommand($event);
    }

    public function testIgnoresConsoleEventWithoutCommand(): void
    {
        $event = new ConsoleCommandEvent(null, new ArrayInput([]), new BufferedOutput());

        $this->listener(keyId: '')->onConsoleCommand($event);

        $this->addToAssertionCount(1);
    }

    /**
     * @param array<string, string> $credentials
     */
    private function listener(
        string $environment = 'prod',
        string $keyId = self::KEY_ARN,
        string $previousKeyId = '',
        string $endpoint = self::REGIONAL_ENDPOINT,
        array $credentials = []
    ): JwtKmsConfigurationListener {
        $endpointProvider = $this->createMock(KmsEndpointProviderInterface::class);
        $endpointProvider->method('endpoint')->willReturn($endpoint);

        return new JwtKmsConfigurationListener(
            $environment,
            $endpointProvider,
            $keyId,
            $previousKeyId,
            [...array_fill_keys(self::CREDENTIAL_VARIABLES, ''), ...$credentials]
        );
    }

    private function requestEvent(int $requestType): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/'),
            $requestType
        );
    }
}
