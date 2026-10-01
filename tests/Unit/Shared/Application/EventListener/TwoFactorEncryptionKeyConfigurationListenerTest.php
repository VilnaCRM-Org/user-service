<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\TwoFactorEncryptionKeyConfigurationListener;
use App\Tests\Unit\UnitTestCase;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class TwoFactorEncryptionKeyConfigurationListenerTest extends UnitTestCase
{
    private const KEY_ARN = 'arn:aws:kms:eu-central-1:123456789012:key/two-factor';

    public function testAllowsEmptyKeyOutsideProduction(): void
    {
        $listener = new TwoFactorEncryptionKeyConfigurationListener('dev', null);
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
            'Set TWO_FACTOR_KMS_KEY_ID in production to the two-factor KMS key ARN.'
        );

        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', null);
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
            'Set TWO_FACTOR_KMS_KEY_ID in production to the two-factor KMS key ARN.'
        );

        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', '   ');
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
        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', self::KEY_ARN);
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
        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', null);
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
        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', self::KEY_ARN);
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
        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', self::KEY_ARN);
        $event = new ConsoleCommandEvent(null, new ArrayInput([]), new BufferedOutput());

        $listener->onConsoleCommand($event);
        $this->addToAssertionCount(1);
    }

    public function testConsoleEventWithoutCommandDoesNotValidateMissingKeyInProduction(): void
    {
        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', null);
        $event = new ConsoleCommandEvent(null, new ArrayInput([]), new BufferedOutput());

        $listener->onConsoleCommand($event);
        $this->addToAssertionCount(1);
    }

    public function testValidatesConsoleCommandInProduction(): void
    {
        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', self::KEY_ARN);
        $command = new Command('test');
        $event = new ConsoleCommandEvent($command, new ArrayInput([]), new BufferedOutput());

        $listener->onConsoleCommand($event);
        $this->addToAssertionCount(1);
    }

    public function testConsoleCommandThrowsWhenKeyIsMissingInProduction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Set TWO_FACTOR_KMS_KEY_ID in production to the two-factor KMS key ARN.'
        );

        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', null);
        $command = new Command('test');
        $event = new ConsoleCommandEvent($command, new ArrayInput([]), new BufferedOutput());

        $listener->onConsoleCommand($event);
    }
}
