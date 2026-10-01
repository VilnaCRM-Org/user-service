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
    private const REGION = 'eu-central-1';
    private const REGION_MESSAGE =
        'Set AWS_REGION in production to the region of the two-factor KMS key.';

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
        $listener = $this->configuredProductionListener();
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
        $listener = $this->configuredProductionListener();
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
        $listener = $this->configuredProductionListener();
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
        $listener = $this->configuredProductionListener();
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

    public function testThrowsWhenRegionIsMissingInProduction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::REGION_MESSAGE);

        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', self::KEY_ARN, null);
        $listener->onKernelRequest($this->mainRequestEvent());
    }

    public function testThrowsWhenRegionIsBlankInProduction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::REGION_MESSAGE);

        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', self::KEY_ARN, ' ');
        $listener->onKernelRequest($this->mainRequestEvent());
    }

    public function testRegionDefaultsToMissingInProduction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::REGION_MESSAGE);

        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', self::KEY_ARN);
        $listener->onKernelRequest($this->mainRequestEvent());
    }

    public function testConsoleCommandThrowsWhenRegionIsMissingInProduction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::REGION_MESSAGE);

        $listener = new TwoFactorEncryptionKeyConfigurationListener('prod', self::KEY_ARN, null);
        $listener->onConsoleCommand(
            new ConsoleCommandEvent(new Command('test'), new ArrayInput([]), new BufferedOutput())
        );
    }

    public function testAllowsMissingRegionOutsideProduction(): void
    {
        $listener = new TwoFactorEncryptionKeyConfigurationListener('test', null, null);

        $listener->onKernelRequest($this->mainRequestEvent());
        $this->addToAssertionCount(1);
    }

    private function configuredProductionListener(): TwoFactorEncryptionKeyConfigurationListener
    {
        return new TwoFactorEncryptionKeyConfigurationListener('prod', self::KEY_ARN, self::REGION);
    }

    private function mainRequestEvent(): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}
