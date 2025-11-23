<?php

declare(strict_types=1);

/**
 * Example 4: Fixing Infrastructure → Application Handler Violations
 *
 * VIOLATION:
 * Infrastructure must not depend on Application (direct handler call)
 *   src/Customer/Infrastructure/EventListener/CustomerDoctrineListener.php:12
 *     uses App\Customer\Application\CommandHandler\UpdateSearchIndexHandler
 */

// ============================================================================
// BEFORE (WRONG) - Infrastructure directly depends on Application Handler
// ============================================================================

namespace App\Customer\Infrastructure\EventListener;

use App\Customer\Application\CommandHandler\UpdateSearchIndexHandler;  // VIOLATION!
use App\Customer\Application\CommandHandler\SendNotificationHandler;   // VIOLATION!
use App\Customer\Application\Command\UpdateSearchIndexCommand;
use App\Customer\Application\Command\SendNotificationCommand;
use App\Customer\Domain\Entity\Customer;
use Doctrine\Persistence\Event\LifecycleEventArgs;

final class CustomerDoctrineListenerBefore
{
    public function __construct(
        private UpdateSearchIndexHandler $searchHandler,    // VIOLATION!
        private SendNotificationHandler $notificationHandler // VIOLATION!
    ) {
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Customer) {
            // Direct handler invocation - WRONG!
            ($this->searchHandler)(
                new UpdateSearchIndexCommand($entity->id())
            );

            ($this->notificationHandler)(
                new SendNotificationCommand($entity->id(), 'customer.created')
            );
        }
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Customer) {
            ($this->searchHandler)(
                new UpdateSearchIndexCommand($entity->id())
            );
        }
    }
}

// ============================================================================
// AFTER - OPTION 1: Use Command Bus (Quick Fix)
// ============================================================================

namespace App\Customer\Infrastructure\EventListener;

use App\Customer\Application\Command\UpdateSearchIndexCommand;
use App\Customer\Application\Command\SendNotificationCommand;
use App\Customer\Domain\Entity\Customer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

final class CustomerDoctrineListenerOption1
{
    public function __construct(
        private CommandBusInterface $commandBus  // Interface, not concrete handler
    ) {
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Customer) {
            // Dispatch through bus - decoupled!
            $this->commandBus->dispatch(
                new UpdateSearchIndexCommand($entity->id())
            );

            $this->commandBus->dispatch(
                new SendNotificationCommand($entity->id(), 'customer.created')
            );
        }
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Customer) {
            $this->commandBus->dispatch(
                new UpdateSearchIndexCommand($entity->id())
            );
        }
    }
}

// ============================================================================
// AFTER - OPTION 2: Use Domain Events (Best Practice - Recommended)
// ============================================================================

// STEP 1: Domain Entity records events
namespace App\Customer\Domain\Entity;

use App\Customer\Domain\Event\CustomerCreated;
use App\Customer\Domain\Event\CustomerUpdated;
use App\Customer\Domain\ValueObject\Email;
use App\Customer\Domain\ValueObject\CustomerName;
use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\ValueObject\Ulid;

final class Customer extends AggregateRoot
{
    private Ulid $id;
    private Email $email;
    private CustomerName $name;

    public function __construct(Ulid $id, Email $email, CustomerName $name)
    {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;

        // Record domain event - will be dispatched after persistence
        $this->record(new CustomerCreated($id, $email, $name));
    }

    public function changeEmail(Email $newEmail): void
    {
        $this->email = $newEmail;

        // Record update event
        $this->record(new CustomerUpdated($this->id));
    }

    public function changeName(CustomerName $newName): void
    {
        $this->name = $newName;
        $this->record(new CustomerUpdated($this->id));
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): CustomerName
    {
        return $this->name;
    }
}

// STEP 2: Domain Events (pure domain, no framework)
namespace App\Customer\Domain\Event;

use App\Customer\Domain\ValueObject\Email;
use App\Customer\Domain\ValueObject\CustomerName;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Ulid;

final readonly class CustomerCreated extends DomainEvent
{
    public function __construct(
        private Ulid $customerId,
        private Email $email,
        private CustomerName $name,
        ?string $eventId = null,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'customer.created';
    }

    public function customerId(): Ulid
    {
        return $this->customerId;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): CustomerName
    {
        return $this->name;
    }

    public function toPrimitives(): array
    {
        return [
            'customerId' => $this->customerId->value(),
            'email' => $this->email->value(),
            'name' => $this->name->value(),
        ];
    }
}

final readonly class CustomerUpdated extends DomainEvent
{
    public function __construct(
        private Ulid $customerId,
        ?string $eventId = null,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'customer.updated';
    }

    public function customerId(): Ulid
    {
        return $this->customerId;
    }

    public function toPrimitives(): array
    {
        return [
            'customerId' => $this->customerId->value(),
        ];
    }
}

// STEP 3: Application Event Subscribers (handle the domain events)
namespace App\Customer\Application\EventSubscriber;

use App\Customer\Domain\Event\CustomerCreated;
use App\Customer\Domain\Event\CustomerUpdated;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/**
 * Updates search index when customer is created or updated
 */
final readonly class UpdateSearchIndexOnCustomerChange implements DomainEventSubscriberInterface
{
    public function __construct(
        private SearchIndexService $searchIndexService
    ) {
    }

    public static function subscribedTo(): array
    {
        return [
            CustomerCreated::class,
            CustomerUpdated::class,
        ];
    }

    public function __invoke(CustomerCreated|CustomerUpdated $event): void
    {
        $this->searchIndexService->updateIndex($event->customerId());
    }
}

/**
 * Sends notification when customer is created
 */
final readonly class SendNotificationOnCustomerCreated implements DomainEventSubscriberInterface
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    public static function subscribedTo(): array
    {
        return [CustomerCreated::class];
    }

    public function __invoke(CustomerCreated $event): void
    {
        $this->notificationService->send(
            $event->customerId(),
            'customer.created'
        );
    }
}

/**
 * Sends welcome email when customer is created
 */
final readonly class SendWelcomeEmailOnCustomerCreated implements DomainEventSubscriberInterface
{
    public function __construct(
        private EmailService $emailService
    ) {
    }

    public static function subscribedTo(): array
    {
        return [CustomerCreated::class];
    }

    public function __invoke(CustomerCreated $event): void
    {
        $this->emailService->sendWelcome(
            $event->email()->value(),
            $event->name()->value()
        );
    }
}

// STEP 4: NO Doctrine Listener Needed!
// Events are automatically dispatched by the infrastructure layer
// after the repository flushes the entity.

// ============================================================================
// INFRASTRUCTURE: Event Dispatcher (handles event publishing)
// ============================================================================

namespace App\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;

/**
 * Doctrine listener that publishes domain events after flush
 * This is Infrastructure → Domain (allowed!)
 */
final class DomainEventPublisher
{
    private array $aggregates = [];

    public function __construct(
        private EventBusInterface $eventBus
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $dm = $args->getDocumentManager();
        $uow = $dm->getUnitOfWork();

        // Collect all aggregates with events
        foreach ($uow->getScheduledDocumentInsertions() as $document) {
            if ($document instanceof AggregateRoot) {
                $this->aggregates[] = $document;
            }
        }

        foreach ($uow->getScheduledDocumentUpdates() as $document) {
            if ($document instanceof AggregateRoot) {
                $this->aggregates[] = $document;
            }
        }
    }

    public function postFlush(): void
    {
        // Publish all domain events
        foreach ($this->aggregates as $aggregate) {
            $events = $aggregate->pullDomainEvents();

            foreach ($events as $event) {
                $this->eventBus->publish($event);
            }
        }

        $this->aggregates = [];
    }
}

// ============================================================================
// BENEFITS OF OPTION 2 (Domain Events):
//
// 1. Complete decoupling - Infrastructure knows nothing about Application
// 2. Business intent is explicit - CustomerCreated, not "postPersist"
// 3. Easy to add new reactions - just create new subscriber
// 4. Testable - test entity records events, test subscribers react
// 5. Async-ready - events can be dispatched to message queue
// 6. Single Responsibility - each subscriber does one thing
// 7. No Deptrac violations - proper layer dependencies
// ============================================================================

// ============================================================================
// DOMAIN FACTORY - Encapsulates entity creation
// ============================================================================

namespace App\Customer\Domain\Factory;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\ValueObject\Email;
use App\Customer\Domain\ValueObject\CustomerName;
use App\Shared\Domain\ValueObject\Ulid;

interface CustomerFactoryInterface
{
    public function create(
        Ulid $id,
        Email $email,
        CustomerName $name
    ): Customer;
}

final readonly class CustomerFactory implements CustomerFactoryInterface
{
    public function create(
        Ulid $id,
        Email $email,
        CustomerName $name
    ): Customer {
        // Factory encapsulates the 'new' keyword
        // Entity constructor records the domain event
        return new Customer($id, $email, $name);
    }
}

// ============================================================================
// COMMAND HANDLER - Uses factory and repository (no direct Doctrine)
// ============================================================================

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\ValueObject\Email;
use App\Customer\Domain\ValueObject\CustomerName;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final readonly class CreateCustomerHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private CustomerFactoryInterface $customerFactory  // ✅ Inject factory
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        // ✅ Use factory instead of 'new' or static methods
        // Events are recorded in entity constructor
        $customer = $this->customerFactory->create(
            $command->id,
            new Email($command->email),
            new CustomerName($command->name)
        );

        // Save entity (events are dispatched after flush)
        $this->repository->save($customer);

        // No need to manually dispatch events or call services!
        // The event subscribers will handle:
        // - Search index update
        // - Notifications
        // - Welcome email
        // - Any other reactions
    }
}

// ============================================================================
// SUMMARY:
// - Infrastructure should use interfaces (CommandBusInterface, EventBusInterface)
// - Never inject concrete handlers into Infrastructure
// - Prefer Domain Events over Doctrine listeners
// - Event subscribers are Application layer (can depend on services)
// - Domain remains pure, Infrastructure stays decoupled
// ============================================================================
