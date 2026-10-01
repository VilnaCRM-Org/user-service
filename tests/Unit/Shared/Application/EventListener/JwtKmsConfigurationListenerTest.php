<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\JwtKmsConfigurationListener;
use App\Tests\Unit\UnitTestCase;
use Aws\Kms\KmsClient;
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
    private const KEY_MESSAGE = 'Set JWT_KMS_KEY_ID to the KMS key ARN or alias ARN in production.';
    private const PREVIOUS_MESSAGE
        = 'JWT_KMS_PREVIOUS_KEY_ID must be empty or a KMS key ARN or alias ARN in production.';
    private const CREDENTIALS_MESSAGE
        = 'Static AWS credentials are refused in production; the ECS task role signs JWTs.';
    private const ENDPOINT_MESSAGE
        = 'JWT signing must use the regional AWS KMS endpoint in production.';

    /**
     * @dataProvider validProductionConfigurations
     */
    public function testAcceptsTaskRoleKmsConfigurationInProduction(
        string $keyId,
        string $previousKeyId,
        KmsClient $client
    ): void {
        $this->listener('prod', $client, $keyId, $previousKeyId)
            ->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<string, array{string, string, KmsClient}>
     */
    public static function validProductionConfigurations(): iterable
    {
        yield 'key ARN, no window' => [self::KEY_ARN, '', self::regionalClient()];
        yield 'alias ARN with previous key ARN' => [
            self::ALIAS_ARN,
            self::KEY_ARN,
            self::regionalClient(),
        ];
        yield 'FIPS endpoint' => [self::KEY_ARN, '', self::fipsClient()];
    }

    /**
     * @dataProvider invalidKeyIds
     */
    public function testRejectsKeyIdThatIsNotAKmsArnInProduction(string $keyId): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::KEY_MESSAGE);

        $this->listener('prod', self::regionalClient(), $keyId)
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

        $this->listener('prod', self::regionalClient(), self::KEY_ARN, 'alias/old')
            ->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));
    }

    /**
     * @dataProvider staticCredentials
     */
    public function testRejectsStaticAwsCredentialsInProduction(
        string $accessKeyId,
        string $secretAccessKey,
        string $sessionToken
    ): void {
        $listener = new JwtKmsConfigurationListener(
            'prod',
            self::regionalClient(),
            self::KEY_ARN,
            '',
            $accessKeyId,
            $secretAccessKey,
            $sessionToken
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::CREDENTIALS_MESSAGE);

        $listener->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function staticCredentials(): iterable
    {
        yield 'access key id' => ['AKIAEXAMPLE', '', ''];
        yield 'secret access key' => ['', 'secret', ''];
        yield 'session token' => ['', '', 'token'];
    }

    /**
     * @dataProvider localEndpoints
     */
    public function testRejectsLocalOrDevSignerInProduction(string $endpoint): void
    {
        $client = new KmsClient([
            'region' => 'eu-central-1',
            'version' => 'latest',
            'endpoint' => $endpoint,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::ENDPOINT_MESSAGE);

        $this->listener('prod', $client, self::KEY_ARN)
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
    }

    public function testDoesNotValidateOutsideProduction(): void
    {
        $client = new KmsClient([
            'region' => 'us-east-1',
            'version' => 'latest',
            'endpoint' => 'http://localstack:4566',
        ]);

        $this->listener('test', $client, 'alias/user-service-jwt')
            ->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));

        $this->addToAssertionCount(1);
    }

    public function testIgnoresSubRequests(): void
    {
        $this->listener('prod', self::regionalClient(), '')
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

        $this->listener('prod', self::regionalClient(), '')->onConsoleCommand($event);
    }

    public function testIgnoresConsoleEventWithoutCommand(): void
    {
        $event = new ConsoleCommandEvent(null, new ArrayInput([]), new BufferedOutput());

        $this->listener('prod', self::regionalClient(), '')->onConsoleCommand($event);

        $this->addToAssertionCount(1);
    }

    private static function regionalClient(): KmsClient
    {
        return new KmsClient(['region' => 'eu-central-1', 'version' => 'latest']);
    }

    private static function fipsClient(): KmsClient
    {
        return new KmsClient([
            'region' => 'eu-central-1',
            'version' => 'latest',
            'use_fips_endpoint' => true,
        ]);
    }

    private function listener(
        string $environment,
        KmsClient $client,
        string $keyId,
        string $previousKeyId = ''
    ): JwtKmsConfigurationListener {
        return new JwtKmsConfigurationListener(
            $environment,
            $client,
            $keyId,
            $previousKeyId,
            '',
            '',
            ''
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
