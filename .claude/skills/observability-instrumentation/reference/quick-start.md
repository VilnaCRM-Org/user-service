# Quick Start: Business Metrics with AWS EMF

Add business metrics to your code in 5 minutes using AWS CloudWatch Embedded Metric Format.

## What You'll Add

- **Business metrics** - Track domain events (UsersCreated, OrdersPlaced)
- **EMF format** - Logs automatically become CloudWatch metrics
- **Low overhead** - Emit metrics in dedicated domain event subscribers

## The 3-Step Pattern

### Step 1: Create a domain event subscriber (30 seconds)

```php
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\YourContext\Application\Factory\EntitiesCreatedMetricFactoryInterface;
use Psr\Log\LoggerInterface;

final readonly class YourMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private EntitiesCreatedMetricFactoryInterface $metricFactory,
        private LoggerInterface $logger
    ) {}
}
```

### Step 2: Emit the metric from the subscriber (1 minute)

```php
public function __invoke(YourEntityCreatedEvent $event): void
{
    // Metrics are best-effort: keep business flow resilient
    $this->metricsEmitter->emit($this->metricFactory->create());
}
```

### Step 3: Add Test (2 minutes)

```php
use App\Shared\Application\Observability\Metric\MetricDimension;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;

public function testEmitsBusinessMetric(): void
{
    $metricsSpy = new BusinessMetricsEmitterSpy();
    $dimensionsFactory = new MetricDimensionsFactory();
    $metricFactory = new EntitiesCreatedMetricFactory($dimensionsFactory);
    $subscriber = new YourMetricsSubscriber($metricsSpy, $metricFactory);

    ($subscriber)(new YourEntityCreatedEvent(/* ... */));

    $metricsSpy->assertEmittedWithDimensions(
        'EntitiesCreated',
        new MetricDimension('Endpoint', 'YourEntity'),
        new MetricDimension('Operation', 'create')
    );
}
```

**Done! Your business metric will appear in CloudWatch.**

---

## Copy-Paste Template

```php
<?php

declare(strict_types=1);

namespace App\YourContext\Application\Metric;

use App\Shared\Application\Observability\Metric\EndpointOperationBusinessMetric;
use App\Shared\Application\Observability\Metric\MetricDimensionsFactoryInterface;
use App\Shared\Application\Observability\Metric\MetricUnit;

final readonly class EntitiesCreatedMetric extends EndpointOperationBusinessMetric
{
    public function __construct(
        MetricDimensionsFactoryInterface $dimensionsFactory,
        float|int $value = 1
    ) {
        parent::__construct($dimensionsFactory, $value, MetricUnit::COUNT);
    }

    public function name(): string
    {
        return 'EntitiesCreated';
    }

    protected function endpoint(): string
    {
        return 'YourEntity';
    }

    protected function operation(): string
    {
        return 'create';
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\YourContext\Application\EventSubscriber;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\YourContext\Application\Factory\EntitiesCreatedMetricFactoryInterface;
use App\YourContext\Domain\Event\YourEntityCreatedEvent;
use Psr\Log\LoggerInterface;

final readonly class YourEntityCreatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private EntitiesCreatedMetricFactoryInterface $metricFactory,
        private LoggerInterface $logger
    ) {}

    public function __invoke(YourEntityCreatedEvent $event): void
    {
        try {
            $this->metricsEmitter->emit($this->metricFactory->create());

            $this->logger->debug('Business metric emitted', [
                'metric' => 'EntitiesCreated',
                'event_id' => $event->eventId(),
            ]);
        } catch (\Throwable $e) {
            // Metrics are best-effort: don't fail business operations
            $this->logger->warning('Failed to emit business metric', [
                'metric' => 'EntitiesCreated',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [YourEntityCreatedEvent::class];
    }
}
```

---

## Common Business Metrics

### For Create Operations

```php
$this->metricsEmitter->emit($this->usersCreatedMetricFactory->create());
```

### For Update Operations

```php
$this->metricsEmitter->emit($this->usersUpdatedMetricFactory->create());
```

### For Delete Operations

```php
$this->metricsEmitter->emit($this->usersDeletedMetricFactory->create());
```

### For Business Values

```php
use App\Shared\Application\Observability\Metric\MetricCollection;

$this->metricsEmitter->emitCollection(new MetricCollection(
    $this->ordersPlacedMetricFactory->create($order->paymentMethod()),
    $this->orderValueMetricFactory->create($order->totalAmount())
));
```

---

## Automatic Endpoint Metrics

The codebase already automatically emits `EndpointInvocations` for every `/api` request via `ApiEndpointBusinessMetricsSubscriber`. You don't need to add anything for basic endpoint tracking.

Your job is to add **domain-specific business metrics** that track business events.

---

## Quick Reference

### Metric Naming

| Pattern             | Example                                       |
| ------------------- | --------------------------------------------- |
| `{Entity}{Action}`  | `UsersCreated`, `OrdersPlaced`                |
| PascalCase          | `PaymentsProcessed`, not `payments_processed` |
| Plural + Past tense | `LoginAttempts`, not `LoginAttempt`           |

### Dimensions (Low Cardinality Only)

| Good Dimensions | Bad Dimensions (Avoid!) |
| --------------- | ----------------------- |
| `Endpoint`      | `UserId`                |
| `Operation`     | `OrderId`               |
| `PaymentMethod` | `SessionId`             |
| `UserType`      | `Timestamp`             |

---

## Test Template

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\YourContext\Application\EventSubscriber;

use App\Shared\Application\Observability\Metric\MetricDimension;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use App\YourContext\Application\EventSubscriber\YourEntityCreatedMetricsSubscriber;
use App\YourContext\Domain\Event\YourEntityCreatedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class YourEntityCreatedMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsSpy;
    private LoggerInterface&MockObject $logger;
    private YourEntityCreatedMetricsSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsSpy = new BusinessMetricsEmitterSpy();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new YourEntityCreatedMetricsSubscriber(
            $this->metricsSpy,
            new MetricDimensionsFactory(),
            $this->logger
        );
    }

    public function testEmitsBusinessMetricOnSuccess(): void
    {
        $event = new YourEntityCreatedEvent(/* ... */);

        ($this->subscriber)($event);

        self::assertSame(1, $this->metricsSpy->count());

        $this->metricsSpy->assertEmittedWithDimensions(
            'EntitiesCreated',
            new MetricDimension('Endpoint', 'YourEntity'),
            new MetricDimension('Operation', 'create')
        );
    }
}
```

---

## What NOT to Track

AWS AppRunner provides infrastructure metrics automatically. Don't add:

- ❌ Request latency
- ❌ Error rates
- ❌ Response times
- ❌ RPS (requests per second)
- ❌ HTTP status codes

Focus ONLY on business events.

---

## Verification Checklist

After implementing:

- [ ] Handler injects `BusinessMetricsEmitterInterface`
- [ ] Metric uses PascalCase name (e.g., `UsersCreated`)
- [ ] Dimensions are low cardinality (no IDs)
- [ ] Unit test verifies metric emission
- [ ] Run `make test` to confirm tests pass

---

## Full Guides

- [Metrics Patterns](metrics-patterns.md) - Complete business metrics guide
- [Structured Logging](structured-logging.md) - Add correlation IDs for debugging
- [PR Evidence Guide](pr-evidence-guide.md) - How to document metrics in PRs
- [Complete Example](../examples/instrumented-command-handler.md) - Full working example
