<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Bus\InvokeParameterExtractor;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\HandlerWithoutTypeHint;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestOtherEvent;
use App\Tests\Unit\UnitTestCase;
use LogicException;

final class InvokeParameterExtractorTest extends UnitTestCase
{
    private InvokeParameterExtractor $extractor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new InvokeParameterExtractor();
    }

    public function testExtractsClassNameFromSingleTypedInvoke(): void
    {
        $handler = new class() {
            public function __invoke(DomainEvent $event): void
            {
            }
        };

        self::assertSame(DomainEvent::class, $this->extractor->extract($handler));
    }

    public function testReturnsNullWhenNoInvokeMethod(): void
    {
        $handler = new class() {
        };

        self::assertNull($this->extractor->extract($handler));
    }

    public function testReturnsNullForUnionType(): void
    {
        $handler = new class() {
            public function __invoke(DomainEvent|TestOtherEvent $event): void
            {
            }
        };

        self::assertNull($this->extractor->extract($handler));
    }

    public function testThrowsWhenMissingTypeHint(): void
    {
        $handler = new HandlerWithoutTypeHint();

        $this->expectException(LogicException::class);
        $this->extractor->extract($handler);
    }

    public function testReturnsNullWhenHandlerHasMultipleParameters(): void
    {
        $handler = new class() {
            public function __invoke(DomainEvent $event, string $other): void
            {
            }
        };

        self::assertNull($this->extractor->extract($handler));
    }
}
