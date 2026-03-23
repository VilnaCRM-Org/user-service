<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use RuntimeException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final readonly class OAuthEncryptionKeyConfigurationListener
{
    public function __construct(
        private string $appEnv,
        private ?string $oauthEncryptionKey = null
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

        if ($this->oauthEncryptionKey === null || trim($this->oauthEncryptionKey) === '') {
            throw new RuntimeException(
                'Set OAUTH_ENCRYPTION_KEY in production via deployment secrets.'
            );
        }
    }
}
