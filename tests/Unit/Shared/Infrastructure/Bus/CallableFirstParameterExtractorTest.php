<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\CallableFirstParameterExtractor;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestOtherEvent;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Assert;

final class CallableFirstParameterExtractorTest extends UnitTestCase
{
    public function testForCallablesReturnsCorrectMapping(): void
    {
        $subscriber = $this->createDomainEventSubscriber();

        $result = CallableFirstParameterExtractor::forCallables([$subscriber]);

        self::assertArrayHasKey(DomainEvent::class, $result);
        self::assertIsArray($result[DomainEvent::class]);
        self::assertSame($subscriber, $result[DomainEvent::class][0]);
    }

    public function testForPipedCallablesReturnsCorrectMapping(): void
    {
        $subscriber = $this->createMultiEventSubscriber();

        $result = CallableFirstParameterExtractor::forPipedCallables([$subscriber]);

        self::assertArrayHasKey(DomainEvent::class, $result);
        self::assertArrayHasKey(TestOtherEvent::class, $result);
        self::assertSame($subscriber, $result[DomainEvent::class][0]);
        self::assertSame($subscriber, $result[TestOtherEvent::class][0]);
    }

    private function createDomainEventSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<class-string>
             */
            #[\Override]
            public function subscribedTo(): array
            {
                return [DomainEvent::class];
            }

            public function __invoke(DomainEvent $event): void
            {
                Assert::assertNotNull($event);
            }
        };
    }

    private function createMultiEventSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<class-string>
             */
            #[\Override]
            public function subscribedTo(): array
            {
                return [DomainEvent::class, TestOtherEvent::class];
            }

            public function __invoke(DomainEvent $event): void
            {
                Assert::assertNotNull($event);
            }
        };
    }
}
