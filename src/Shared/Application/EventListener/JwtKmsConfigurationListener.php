<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Aws\Kms\KmsClient;
use RuntimeException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Production guard for KMS JWT signing (S5.11, FR-06): refuses a local or dev
 * signer (any KMS endpoint other than the regional AWS one), static AWS
 * credentials, and JWT key ids that are not KMS key or alias ARNs.
 */
final readonly class JwtKmsConfigurationListener
{
    private const KEY_ARN_PATTERN
        = '#^arn:aws[a-z-]*:kms:[a-z0-9-]+:[0-9]{12}:(key|alias)/[A-Za-z0-9/_-]+$#';
    private const KMS_ENDPOINT_PATTERN
        = '#^https://kms(-fips)?\.[a-z0-9-]+\.amazonaws\.com$#';

    public function __construct(
        private string $appEnv,
        private KmsClient $kmsClient,
        private string $currentKeyId,
        private string $previousKeyId,
        private string $accessKeyId,
        private string $secretAccessKey,
        private string $sessionToken,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->assertConfigurationIsValid();
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        if ($event->getCommand() === null) {
            return;
        }

        $this->assertConfigurationIsValid();
    }

    private function assertConfigurationIsValid(): void
    {
        if ($this->appEnv !== 'prod') {
            return;
        }

        $this->assertKeyIds();
        $this->assertNoStaticCredentials();
        $this->assertRegionalKmsEndpoint();
    }

    private function assertKeyIds(): void
    {
        if (!$this->isKmsArn($this->currentKeyId)) {
            throw new RuntimeException(
                'Set JWT_KMS_KEY_ID to the KMS key ARN or alias ARN in production.'
            );
        }

        if ($this->previousKeyId !== '' && !$this->isKmsArn($this->previousKeyId)) {
            throw new RuntimeException(
                'JWT_KMS_PREVIOUS_KEY_ID must be empty or a KMS key ARN or alias ARN in production.'
            );
        }
    }

    private function assertNoStaticCredentials(): void
    {
        if (
            $this->accessKeyId !== ''
            || $this->secretAccessKey !== ''
            || $this->sessionToken !== ''
        ) {
            throw new RuntimeException(
                'Static AWS credentials are refused in production; the ECS task role signs JWTs.'
            );
        }
    }

    private function assertRegionalKmsEndpoint(): void
    {
        $endpoint = (string) $this->kmsClient->getEndpoint();
        if (preg_match(self::KMS_ENDPOINT_PATTERN, $endpoint) !== 1) {
            throw new RuntimeException(
                'JWT signing must use the regional AWS KMS endpoint in production.'
            );
        }
    }

    private function isKmsArn(string $keyId): bool
    {
        return preg_match(self::KEY_ARN_PATTERN, $keyId) === 1;
    }
}
