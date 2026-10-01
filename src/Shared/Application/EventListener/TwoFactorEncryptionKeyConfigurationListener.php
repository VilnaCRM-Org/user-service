<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use RuntimeException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Fails fast in production when the 2FA KMS key id (TWO_FACTOR_KMS_KEY_ID,
 * S5.12) is not configured.
 */
final readonly class TwoFactorEncryptionKeyConfigurationListener
{
    public function __construct(
        private string $appEnv,
        private ?string $twoFactorKmsKeyId = null
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

        if (
            $this->twoFactorKmsKeyId === null
            || trim($this->twoFactorKmsKeyId) === ''
        ) {
            throw new RuntimeException(
                'Set TWO_FACTOR_KMS_KEY_ID in production to the two-factor KMS key ARN.'
            );
        }
    }
}
