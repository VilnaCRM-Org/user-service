<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\OAuthEncryptionKeyConfigurationListener;
use App\Tests\Unit\UnitTestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class OAuthEncryptionKeyConfigurationListenerTest extends UnitTestCase
{
    public function testAllowsEmptyKeyOutsideProduction(): void
    {
        $listener = new OAuthEncryptionKeyConfigurationListener('dev', null);
        $request = Request::create('/');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelRequest($event);
        $this->addToAssertionCount(1);
    }

    public function testThrowsWhenKeyIsMissingInProduction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Set OAUTH_ENCRYPTION_KEY in production via deployment secrets.'
        );

        $listener = new OAuthEncryptionKeyConfigurationListener('prod', null);
        $request = Request::create('/');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelRequest($event);
    }

    public function testThrowsWhenKeyIsBlankInProduction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Set OAUTH_ENCRYPTION_KEY in production via deployment secrets.'
        );

        $listener = new OAuthEncryptionKeyConfigurationListener('prod', '   ');
        $request = Request::create('/');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelRequest($event);
    }

    public function testIgnoresSubRequest(): void
    {
        $listener = new OAuthEncryptionKeyConfigurationListener('prod', 'non-empty-key');
        $request = Request::create('/');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $listener->onKernelRequest($event);
        $this->addToAssertionCount(1);
    }

    public function testSubRequestDoesNotValidateMissingKeyInProduction(): void
    {
        $listener = new OAuthEncryptionKeyConfigurationListener('prod', null);
        $request = Request::create('/');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $listener->onKernelRequest($event);
        $this->addToAssertionCount(1);
    }

    public function testValidatesMainRequestInProduction(): void
    {
        $listener = new OAuthEncryptionKeyConfigurationListener('prod', 'non-empty-key');
        $request = Request::create('/');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelRequest($event);
        $this->addToAssertionCount(1);
    }

    public function testIgnoresConsoleEventWithoutCommand(): void
    {
        $listener = new OAuthEncryptionKeyConfigurationListener('prod', 'non-empty-key');
        $event = new ConsoleCommandEvent(null, new ArrayInput([]), new BufferedOutput());

        $listener->onConsoleCommand($event);
        $this->addToAssertionCount(1);
    }

    public function testConsoleEventWithoutCommandDoesNotValidateMissingKeyInProduction(): void
    {
        $listener = new OAuthEncryptionKeyConfigurationListener('prod', null);
        $event = new ConsoleCommandEvent(null, new ArrayInput([]), new BufferedOutput());

        $listener->onConsoleCommand($event);
        $this->addToAssertionCount(1);
    }

    public function testValidatesConsoleCommandInProduction(): void
    {
        $listener = new OAuthEncryptionKeyConfigurationListener('prod', 'non-empty-key');
        $command = new Command('test');
        $event = new ConsoleCommandEvent($command, new ArrayInput([]), new BufferedOutput());

        $listener->onConsoleCommand($event);
        $this->addToAssertionCount(1);
    }

    public function testConsoleCommandThrowsWhenKeyIsMissingInProduction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Set OAUTH_ENCRYPTION_KEY in production via deployment secrets.'
        );

        $listener = new OAuthEncryptionKeyConfigurationListener('prod', null);
        $command = new Command('test');
        $event = new ConsoleCommandEvent($command, new ArrayInput([]), new BufferedOutput());

        $listener->onConsoleCommand($event);
    }
}
