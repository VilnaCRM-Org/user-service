<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use RuntimeException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Fails fast in production when the 2FA KMS key id (TWO_FACTOR_KMS_KEY_ID) or
 * the region of its KMS client (AWS_REGION) is not configured (S5.12).
 */
final readonly class TwoFactorEncryptionKeyConfigurationListener
{
    public function __construct(
        private string $appEnv,
        private ?string $twoFactorKmsKeyId = null,
        private ?string $awsRegion = null
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

        $this->assertConfigured(
            $this->twoFactorKmsKeyId,
            'Set TWO_FACTOR_KMS_KEY_ID in production to the two-factor KMS key ARN.'
        );
        $this->assertConfigured(
            $this->awsRegion,
            'Set AWS_REGION in production to the region of the two-factor KMS key.'
        );
    }

    private function assertConfigured(?string $value, string $message): void
    {
        if ($value === null || trim($value) === '') {
            throw new RuntimeException($message);
        }
    }
}
