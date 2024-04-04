<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\CallableFirstParameterExtractor;
use App\Tests\Unit\UnitTestCase;

final class CallableFirstParameterExtractorTest extends UnitTestCase
{
    private CallableFirstParameterExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new CallableFirstParameterExtractor();
    }

    public function testExtractForCallables(): void
    {
        $subscriber = new class() implements DomainEventSubscriberInterface {
            public static function subscribedTo(): array
            {
                return ['MyEvent'];
            }

            public function __invoke(): void
            {
            }
        };

        $callables = [$subscriber];

        $expected = ['' => $callables];

        $extracted = $this->extractor->forCallables($callables);

        $this->assertEquals($expected, $extracted);
    }

    public function testExtractForPipedCallables(): void
    {
        $subscriber1 = new class() implements DomainEventSubscriberInterface {
            public static function subscribedTo(): array
            {
                return ['MyEvent1'];
            }

            public function __invoke(): void
            {
            }
        };

        $subscriber2 = new class() implements DomainEventSubscriberInterface {
            public static function subscribedTo(): array
            {
                return ['MyEvent2'];
            }

            public function __invoke(): void
            {
            }
        };

        $callables = [$subscriber1, $subscriber2];

        $expected = [
            'MyEvent1' => [$subscriber1],
            'MyEvent2' => [$subscriber2],
        ];

        $extracted = $this->extractor->forPipedCallables($callables);

        $this->assertEquals($expected, $extracted);
    }

    public function testExtract(): void
    {
        $subscriberClass = new class() implements DomainEventSubscriberInterface {
            public static function subscribedTo(): array
            {
                return ['ClassAbstract'];
            }

            public function __invoke(\ClassAbstract $someClass): void
            {
            }
        };

        $extracted = $this->extractor->extract($subscriberClass);

        $this->assertEquals('ClassAbstract', $extracted);
    }

    public function testExtractWithError(): void
    {
        $subscriberClass = new class() implements DomainEventSubscriberInterface {
            public static function subscribedTo(): array
            {
                return ['MyEvent'];
            }

            public function __invoke($someClass): void
            {
            }
        };

        $this->expectException(\LogicException::class);

        $this->extractor->extract($subscriberClass);
    }
}
