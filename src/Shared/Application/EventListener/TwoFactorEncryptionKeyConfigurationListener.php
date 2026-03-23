<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use RuntimeException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final readonly class TwoFactorEncryptionKeyConfigurationListener
{
    public function __construct(
        private string $appEnv,
        private ?string $twoFactorEncryptionKey = null
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
            $this->twoFactorEncryptionKey === null
            || trim($this->twoFactorEncryptionKey) === ''
        ) {
            throw new RuntimeException(
                'Set TWO_FACTOR_ENCRYPTION_KEY in production via deployment secrets.'
            );
        }
    }
}
