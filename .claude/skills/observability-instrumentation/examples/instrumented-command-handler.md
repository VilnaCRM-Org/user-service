# Complete Example: Command Handler with Business Metrics

This example demonstrates a fully instrumented command handler with AWS EMF business metrics.

## Scenario

Creating a new user with:

- Business metric emission via AWS EMF
- Proper dimension usage
- Unit test coverage

---

## Full Implementation

```php
<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\User\Application\Command\CreateUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserEmail;
use App\User\Domain\ValueObject\UserName;
use App\Shared\Domain\Bus\Event\DomainEventPublisherInterface;

final readonly class CreateUserCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private DomainEventPublisherInterface $publisher
    ) {}

    public function __invoke(CreateUserCommand $command): void
    {
        // 1. Create domain entity
        $user = User::create(
            id: $command->id,
            name: UserName::fromString($command->name),
            email: UserEmail::fromString($command->email)
        );

        // 2. Persist to repository
        $this->repository->save($user);

        // 3. Publish domain events
        $events = $user->pullDomainEvents();
        $this->publisher->publish(...$events);

        // 4. Metrics are emitted in domain event subscribers (best practice)
    }
}
```

---

## EMF Output

When this handler executes, the following EMF log is written to stdout:

```json
{
  "_aws": {
    "Timestamp": 1702425600000,
    "CloudWatchMetrics": [
      {
        "Namespace": "UserService/BusinessMetrics",
        "Dimensions": [["Endpoint", "Operation"]],
        "Metrics": [{ "Name": "UsersCreated", "Unit": "Count" }]
      }
    ]
  },
  "Endpoint": "User",
  "Operation": "create",
  "UsersCreated": 1
}
```

CloudWatch automatically extracts this as a metric in the `UserService/BusinessMetrics` namespace.

---

## Unit Test for Event Subscriber

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\User\Application\EventSubscriber\UserCreatedMetricsSubscriber;
use App\User\Domain\Event\UserCreatedEvent;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimension;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;

final class UserCreatedMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsSpy;
    private UserCreatedMetricsSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsSpy = new BusinessMetricsEmitterSpy();

        $dimensionsFactory = new MetricDimensionsFactory();
        $metricFactory = new \App\User\Application\Factory\UsersCreatedMetricFactory($dimensionsFactory);

        $this->subscriber = new UserCreatedMetricsSubscriber(
            $this->metricsSpy,
            $metricFactory
        );
    }

    public function testEmitsUserCreatedMetric(): void
    {
        $event = new UserCreatedEvent(
            userId: '01JCXYZ1234567890ABCDEFGH',
            userEmail: 'john.doe@example.com'
        );

        ($this->subscriber)($event);

        self::assertSame(1, $this->metricsSpy->count());

        foreach ($this->metricsSpy->emitted() as $metric) {
            self::assertSame('UsersCreated', $metric->name());
            self::assertSame(1, $metric->value());
            self::assertSame('User', $metric->dimensions()->values()->get('Endpoint'));
            self::assertSame('create', $metric->dimensions()->values()->get('Operation'));
        }
    }

    public function testMetricHasCorrectDimensions(): void
    {
        $event = new UserCreatedEvent(
            userId: '01JCXYZ1234567890ABCDEFGH',
            userEmail: 'john.doe@example.com'
        );

        ($this->subscriber)($event);

        $this->metricsSpy->assertEmittedWithDimensions(
            'UsersCreated',
            new MetricDimension('Endpoint', 'User'),
            new MetricDimension('Operation', 'create')
        );
    }

    public function testSubscribesToCorrectEvent(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(UserCreatedEvent::class, $subscribedEvents);
    }
}
```

---

## Example: Multiple Metrics via Event Subscriber

For operations that track multiple business values, use `MetricCollection` in an event subscriber:

```php
<?php

declare(strict_types=1);

namespace App\Core\Order\Application\EventSubscriber;

use App\Core\Order\Application\Factory\OrderItemCountMetricFactoryInterface;
use App\Core\Order\Application\Factory\OrdersPlacedMetricFactoryInterface;
use App\Core\Order\Application\Factory\OrderValueMetricFactoryInterface;
use App\Core\Order\Domain\Event\OrderPlacedEvent;
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\Collection\MetricCollection;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

final readonly class OrderPlacedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private OrdersPlacedMetricFactoryInterface $ordersPlacedMetricFactory,
        private OrderValueMetricFactoryInterface $orderValueMetricFactory,
        private OrderItemCountMetricFactoryInterface $orderItemCountMetricFactory
    ) {}

    public function __invoke(OrderPlacedEvent $event): void
    {
        // Error handling is automatic via DomainEventMessageHandler.
        // Subscribers are executed in async workers - failures are logged + emit metrics.
        // This ensures observability never breaks the main request (AP from CAP).
        $this->metricsEmitter->emitCollection(new MetricCollection(
            $this->ordersPlacedMetricFactory->create($event->paymentMethod()),
            $this->orderValueMetricFactory->create($event->totalAmount()),
            $this->orderItemCountMetricFactory->create($event->itemCount())
        ));
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [OrderPlacedEvent::class];
    }
}
```

### EMF Output for Multiple Metrics

```json
{
  "_aws": {
    "Timestamp": 1702425600000,
    "CloudWatchMetrics": [
      {
        "Namespace": "UserService/BusinessMetrics",
        "Dimensions": [["Endpoint", "Operation", "PaymentMethod"]],
        "Metrics": [
          { "Name": "OrdersPlaced", "Unit": "Count" },
          { "Name": "OrderValue", "Unit": "None" },
          { "Name": "OrderItemCount", "Unit": "Count" }
        ]
      }
    ]
  },
  "Endpoint": "Order",
  "Operation": "create",
  "PaymentMethod": "credit_card",
  "OrdersPlaced": 1,
  "OrderValue": 299.99,
  "OrderItemCount": 3
}
```

---

## Example: Conditional Metrics

For operations with different outcomes:

```php
<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\CommandHandler;

use App\Core\Auth\Application\Command\LoginCommand;
final readonly class LoginCommandHandler
{
    public function __construct(
        private AuthServiceInterface $authService
    ) {}

    public function __invoke(LoginCommand $command): void
    {
        $result = $this->authService->authenticate(
            $command->username,
            $command->password
        );

        // Publish a domain event and emit metrics in a dedicated subscriber (best practice)

        if (!$result->isSuccess()) {
            throw new AuthenticationFailedException();
        }
    }
}
```

---

## CloudWatch Queries

After deploying, query your business metrics:

```sql
-- Total users created
SELECT SUM(UsersCreated)
FROM "UserService/BusinessMetrics"
WHERE Endpoint = 'User'

-- Orders by payment method
SELECT SUM(OrdersPlaced), AVG(OrderValue)
FROM "UserService/BusinessMetrics"
WHERE Endpoint = 'Order'
GROUP BY PaymentMethod

-- Login success rate
SELECT SUM(LoginAttempts)
FROM "UserService/BusinessMetrics"
WHERE Endpoint = 'Auth'
GROUP BY Result
```

---

## Key Takeaways

1. **Inject `BusinessMetricsEmitterInterface`** in constructor
2. **Emit after successful operation** - metric represents completed business event
3. **Use PascalCase** for metric names
4. **Keep dimensions low cardinality** - no IDs, timestamps, or email addresses
5. **Test with `BusinessMetricsEmitterSpy`** to verify emission
6. **Focus on business value** - not infrastructure metrics

---

## What NOT to Include

AWS AppRunner already provides infrastructure metrics. Don't add:

- ❌ Operation duration/latency
- ❌ Error counters
- ❌ Request counts (use automatic `EndpointInvocations`)
- ❌ HTTP status codes
- ❌ Database query timing

These are infrastructure concerns handled by AWS AppRunner automatically.

---

## Files Reference

- Interface: `src/Shared/Application/Observability/Emitter/BusinessMetricsEmitterInterface.php`
- Implementation: `src/Shared/Infrastructure/Observability/AwsEmfBusinessMetricsEmitter.php`
- Test spy: `tests/Unit/Shared/Infrastructure/Observability/BusinessMetricsEmitterSpy.php`
- Auto metrics: `src/Shared/Infrastructure/Observability/ApiEndpointBusinessMetricsSubscriber.php`
