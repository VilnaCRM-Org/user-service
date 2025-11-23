# Complete Customer CRUD Implementation Example

This example shows the complete implementation of Customer CRUD operations in this repository.

## 1. Domain Entity

```php
// src/Core/Customer/Domain/Entity/Customer.php
namespace App\Core\Customer\Domain\Entity;

use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Domain\ValueObject\Ulid;

final class Customer
{
    private Ulid $ulid;
    private string $email;
    private string $phone;
    private string $initials;
    private bool $confirmed;
    private string $leadSource;
    private CustomerType $type;
    private CustomerStatus $status;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Ulid $ulid,
        string $email,
        string $phone,
        string $initials,
        bool $confirmed,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status
    ) {
        $this->ulid = $ulid;
        $this->email = $email;
        $this->phone = $phone;
        $this->initials = $initials;
        $this->confirmed = $confirmed;
        $this->leadSource = $leadSource;
        $this->type = $type;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function update(CustomerUpdate $update): void
    {
        $this->email = $update->getEmail();
        $this->phone = $update->getPhone();
        $this->initials = $update->getInitials();
        $this->confirmed = $update->isConfirmed();
        $this->leadSource = $update->getLeadSource();
        $this->type = $update->getType();
        $this->status = $update->getStatus();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters (no setters for immutability)
    public function getUlid(): Ulid { return $this->ulid; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): string { return $this->phone; }
    public function getInitials(): string { return $this->initials; }
    public function isConfirmed(): bool { return $this->confirmed; }
    public function getLeadSource(): string { return $this->leadSource; }
    public function getType(): CustomerType { return $this->type; }
    public function getStatus(): CustomerStatus { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
```

## 2. Doctrine XML Mapping

```xml
<!-- config/doctrine/Customer.mongodb.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                         xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                         http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
    <document name="App\Core\Customer\Domain\Entity\Customer" collection="customers">
        <field name="ulid" id="true" type="ulid" strategy="NONE"/>
        <field name="email" type="string"/>
        <field name="phone" type="string"/>
        <field name="initials" type="string"/>
        <field name="confirmed" type="bool"/>
        <field name="leadSource" type="string"/>
        <field name="createdAt" type="date_immutable"/>
        <field name="updatedAt" type="date_immutable"/>
        <reference-one field="type" target-document="App\Core\Customer\Domain\Entity\CustomerType"/>
        <reference-one field="status" target-document="App\Core\Customer\Domain\Entity\CustomerStatus"/>
    </document>
</doctrine-mongo-mapping>
```

## 3. Input DTOs

```php
// src/Core/Customer/Application/DTO/CustomerCreate.php
namespace App\Core\Customer\Application\DTO;

final readonly class CustomerCreate
{
    public function __construct(
        public ?string $initials = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $leadSource = null,
        public ?string $type = null,      // IRI: "/api/customer_types/{ulid}"
        public ?string $status = null,    // IRI: "/api/customer_statuses/{ulid}"
        public ?bool $confirmed = null,
    ) {
    }
}

// src/Core/Customer/Application/DTO/CustomerPut.php
namespace App\Core\Customer\Application\DTO;

final readonly class CustomerPut
{
    public function __construct(
        public ?string $initials = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $leadSource = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?bool $confirmed = null,
    ) {
    }
}

// src/Core/Customer/Application/DTO/CustomerPatch.php
namespace App\Core\Customer\Application\DTO;

final readonly class CustomerPatch
{
    public function __construct(
        public ?string $initials = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $leadSource = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?bool $confirmed = null,
    ) {
    }
}
```

## 4. Validation Configuration

```yaml
# config/validator/Customer.yaml
App\Core\Customer\Application\DTO\CustomerCreate:
  properties:
    initials:
      - NotBlank: ~
      - Length:
          max: 255
      - App\Shared\Application\Validator\Initials: ~

    email:
      - NotBlank: ~
      - Email: ~
      - Length:
          max: 255
      - App\Shared\Application\Validator\UniqueEmail: ~

    phone:
      - NotBlank: ~
      - Length:
          max: 255

    leadSource:
      - NotBlank: ~
      - Length:
          max: 255

    type:
      - NotBlank: ~

    status:
      - NotBlank: ~

    confirmed:
      - NotNull: ~

App\Core\Customer\Application\DTO\CustomerPut:
  properties:
    initials:
      - NotBlank: ~
      - Length:
          max: 255
      - App\Shared\Application\Validator\Initials: ~

    email:
      - NotBlank: ~
      - Email: ~
      - Length:
          max: 255
      - App\Shared\Application\Validator\UniqueEmailUpdate: ~

    phone:
      - NotBlank: ~
      - Length:
          max: 255

    leadSource:
      - NotBlank: ~
      - Length:
          max: 255

    type:
      - NotBlank: ~

    status:
      - NotBlank: ~

    confirmed:
      - NotNull: ~

# CustomerPatch - all optional, validated only if provided
App\Core\Customer\Application\DTO\CustomerPatch:
  properties:
    initials:
      - Length:
          max: 255
      - App\Shared\Application\Validator\Initials: ~

    email:
      - Email: ~
      - Length:
          max: 255

    phone:
      - Length:
          max: 255

    leadSource:
      - Length:
          max: 255
```

## 5. API Platform Resource Configuration

```yaml
# config/api_platform/resources/customer.yaml
App\Core\Customer\Domain\Entity\Customer:
  shortName: 'Customer'
  normalizationContext:
    groups: ['output']
  paginationPartial: true
  paginationViaCursor:
    - { field: 'ulid', direction: 'desc' }
  order: { 'ulid': 'desc' }

  exceptionToStatus:
    'App\Core\Customer\Domain\Exception\CustomerNotFoundException': 404
    'App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException': 400
    'App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException': 400

  operations:
    # READ Operations
    ApiPlatform\Metadata\GetCollection:
      description: 'Retrieves the collection of Customer resources'
      filters:
        - customer.mongodb.search
        - customer.mongodb.order
        - customer.mongodb.date
        - customer.mongodb.boolean
        - mongodb.range

    ApiPlatform\Metadata\Get:
      description: 'Retrieves a Customer resource by its unique identifier'

    # CREATE Operation
    ApiPlatform\Metadata\Post:
      description: 'Creates a Customer resource'
      input: App\Core\Customer\Application\DTO\CustomerCreate
      processor: App\Core\Customer\Application\Processor\CreateCustomerProcessor
      denormalizationContext:
        allow_extra_attributes: false

    # UPDATE Operations
    ApiPlatform\Metadata\Put:
      description: 'Replaces the Customer resource'
      input: App\Core\Customer\Application\DTO\CustomerPut
      processor: App\Core\Customer\Application\Processor\CustomerPutProcessor
      denormalizationContext:
        allow_extra_attributes: false

    ApiPlatform\Metadata\Patch:
      description: 'Updates the Customer resource partially'
      input: App\Core\Customer\Application\DTO\CustomerPatch
      processor: App\Core\Customer\Application\Processor\CustomerPatchProcessor
      denormalizationContext:
        allow_extra_attributes: false

    # DELETE Operation
    ApiPlatform\Metadata\Delete:
      description: 'Removes the Customer resource'

  graphQlOperations:
    ApiPlatform\Metadata\GraphQl\Query:
      filters:
        - customer.mongodb.search
        - customer.mongodb.order
        - customer.mongodb.date
        - customer.mongodb.boolean

    ApiPlatform\Metadata\GraphQl\QueryCollection:
      filters:
        - customer.mongodb.search
        - customer.mongodb.order
        - customer.mongodb.date
        - customer.mongodb.boolean
      paginationType: cursor

    ApiPlatform\Metadata\GraphQl\Mutation:
      name: create
      resolver: App\Core\Customer\Application\Resolver\CreateCustomerMutationResolver
      deserialize: false
      extraArgs:
        initials:
          type: 'String!'
        email:
          type: 'String!'
        phone:
          type: 'String!'
        leadSource:
          type: 'String!'
        confirmed:
          type: 'Boolean!'
        type:
          type: 'String!'
        status:
          type: 'String!'

    ApiPlatform\Metadata\GraphQl\Mutation:
      name: update
      resolver: App\Core\Customer\Application\Resolver\UpdateCustomerMutationResolver
      deserialize: false
      extraArgs:
        initials:
          type: 'String!'
        email:
          type: 'String!'
        phone:
          type: 'String!'
        leadSource:
          type: 'String!'
        confirmed:
          type: 'Boolean!'
        type:
          type: 'String!'
        status:
          type: 'String!'

    ApiPlatform\Metadata\GraphQl\DeleteMutation:
      normalizationContext:
        groups: ['deleteMutationOutput']
```

## 6. Serialization Groups

```yaml
# config/serialization/Customer.yaml
App\Core\Customer\Domain\Entity\Customer:
  attributes:
    email:
      groups: ['output', 'write:customer']
    phone:
      groups: ['output', 'write:customer']
    initials:
      groups: ['output', 'write:customer']
    confirmed:
      groups: ['output', 'write:customer']
    leadSource:
      groups: ['output', 'write:customer']
    type:
      groups: ['output', 'write:customer']
    status:
      groups: ['output', 'write:customer']
    createdAt:
      groups: ['output']
    updatedAt:
      groups: ['output']
    ulid:
      groups: ['output']
```

## 7. State Processors

### Create Processor

```php
// src/Core/Customer/Application/Processor/CreateCustomerProcessor.php
namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Application\DTO\CustomerCreate;
use App\Core\Customer\Application\Transformer\CustomerTransformer;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<CustomerCreate, Customer>
 */
final readonly class CreateCustomerProcessor implements ProcessorInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        private CommandBusInterface $commandBus,
        private CustomerTransformer $transformer,
    ) {
    }

    /**
     * @param CustomerCreate $data
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Customer {
        /** @var CustomerType $type */
        $type = $this->iriConverter->getResourceFromIri($data->type);

        /** @var CustomerStatus $status */
        $status = $this->iriConverter->getResourceFromIri($data->status);

        $customer = $this->transformer->transformFromCreate(
            $data,
            $type,
            $status
        );

        $this->commandBus->dispatch(new CreateCustomerCommand($customer));

        return $customer;
    }
}
```

### Put Processor (Full Update)

```php
// src/Core/Customer/Application/Processor/CustomerPutProcessor.php
namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\DTO\CustomerPut;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Ulid;

/**
 * @implements ProcessorInterface<CustomerPut, Customer>
 */
final readonly class CustomerPutProcessor implements ProcessorInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        private CommandBusInterface $commandBus,
        private CustomerRepositoryInterface $repository,
    ) {
    }

    /**
     * @param CustomerPut $data
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Customer {
        $customer = $this->repository->find(new Ulid($uriVariables['ulid']));

        /** @var CustomerType $type */
        $type = $this->iriConverter->getResourceFromIri($data->type);

        /** @var CustomerStatus $status */
        $status = $this->iriConverter->getResourceFromIri($data->status);

        $update = new CustomerUpdate(
            $data->email,
            $data->phone,
            $data->initials,
            $data->confirmed,
            $data->leadSource,
            $type,
            $status
        );

        $this->commandBus->dispatch(new UpdateCustomerCommand($customer, $update));

        return $customer;
    }
}
```

### Patch Processor (Partial Update)

```php
// src/Core/Customer/Application/Processor/CustomerPatchProcessor.php
namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\DTO\CustomerPatch;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Application\Validator\StringFieldValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Ulid;

/**
 * @implements ProcessorInterface<CustomerPatch, Customer>
 */
final readonly class CustomerPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        private CommandBusInterface $commandBus,
        private CustomerRepositoryInterface $repository,
        private StringFieldValidator $stringValidator,
    ) {
    }

    /**
     * @param CustomerPatch $data
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Customer {
        $customer = $this->repository->find(new Ulid($uriVariables['ulid']));

        // Resolve type only if provided, otherwise keep existing
        $type = $data->type !== null
            ? $this->iriConverter->getResourceFromIri($data->type)
            : $customer->getType();

        // Resolve status only if provided, otherwise keep existing
        $status = $data->status !== null
            ? $this->iriConverter->getResourceFromIri($data->status)
            : $customer->getStatus();

        // Merge provided fields with existing values
        $update = new CustomerUpdate(
            $this->stringValidator->getFieldValue($data->email, $customer->getEmail()),
            $this->stringValidator->getFieldValue($data->phone, $customer->getPhone()),
            $this->stringValidator->getFieldValue($data->initials, $customer->getInitials()),
            $data->confirmed ?? $customer->isConfirmed(),
            $this->stringValidator->getFieldValue($data->leadSource, $customer->getLeadSource()),
            $type,
            $status
        );

        $this->commandBus->dispatch(new UpdateCustomerCommand($customer, $update));

        return $customer;
    }
}
```

## 8. Transformer

```php
// src/Core/Customer/Application/Transformer/CustomerTransformer.php
namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Application\DTO\CustomerCreate;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\CustomerFactory;
use App\Shared\Domain\ValueObject\Ulid;

final readonly class CustomerTransformer
{
    public function transformFromCreate(
        CustomerCreate $dto,
        CustomerType $type,
        CustomerStatus $status
    ): Customer {
        return CustomerFactory::create(
            new Ulid(),
            $dto->email,
            $dto->phone,
            $dto->initials,
            $dto->confirmed,
            $dto->leadSource,
            $type,
            $status
        );
    }
}
```

## 9. Commands and Handlers

```php
// src/Core/Customer/Application/Command/CreateCustomerCommand.php
namespace App\Core\Customer\Application\Command;

use App\Core\Customer\Domain\Entity\Customer;
use App\Shared\Domain\Bus\Command\CommandInterface;

final readonly class CreateCustomerCommand implements CommandInterface
{
    public function __construct(
        private Customer $customer,
    ) {
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }
}

// src/Core/Customer/Application/CommandHandler/CreateCustomerCommandHandler.php
namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final readonly class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        $this->repository->save($command->getCustomer());
    }
}

// Similar structure for UpdateCustomerCommand and UpdateCustomerCommandHandler
```

## 10. Filter Configuration

```yaml
# config/services.yaml
services:
  # Order Filter
  app.customer.mongodb.order_filter:
    parent: 'api_platform.doctrine_mongodb.odm.order_filter'
    arguments:
      - ulid: 'desc'
        createdAt: 'desc'
        email: 'asc'
        initials: 'asc'
        type.value: 'asc'
        status.value: 'asc'
    tags:
      - { name: 'api_platform.filter', id: 'customer.mongodb.order' }

  # Search Filter
  app.customer.mongodb.search_filter:
    parent: 'api_platform.doctrine_mongodb.odm.search_filter'
    arguments:
      - initials: 'exact'
        email: 'exact'
        phone: 'exact'
        leadSource: 'exact'
        type.value: 'exact'
        status.value: 'exact'
    tags:
      - { name: 'api_platform.filter', id: 'customer.mongodb.search' }

  # Date Filter
  app.customer.mongodb.date_filter:
    parent: 'api_platform.doctrine_mongodb.odm.date_filter'
    arguments:
      - { 'createdAt': ~, 'updatedAt': ~ }
    tags:
      - { name: 'api_platform.filter', id: 'customer.mongodb.date' }

  # Boolean Filter
  app.customer.mongodb.boolean_filter:
    parent: 'api_platform.doctrine_mongodb.odm.boolean_filter'
    arguments:
      - { 'confirmed': ~ }
    tags:
      - { name: 'api_platform.filter', id: 'customer.mongodb.boolean' }
```

## Complete File List

```
Domain Layer:
├── src/Core/Customer/Domain/Entity/Customer.php
├── src/Core/Customer/Domain/Entity/CustomerType.php
├── src/Core/Customer/Domain/Entity/CustomerStatus.php
├── src/Core/Customer/Domain/ValueObject/CustomerUpdate.php
├── src/Core/Customer/Domain/Repository/CustomerRepositoryInterface.php
├── src/Core/Customer/Domain/Exception/CustomerNotFoundException.php
└── src/Core/Customer/Domain/Factory/CustomerFactory.php

Application Layer:
├── src/Core/Customer/Application/DTO/CustomerCreate.php
├── src/Core/Customer/Application/DTO/CustomerPut.php
├── src/Core/Customer/Application/DTO/CustomerPatch.php
├── src/Core/Customer/Application/Processor/CreateCustomerProcessor.php
├── src/Core/Customer/Application/Processor/CustomerPutProcessor.php
├── src/Core/Customer/Application/Processor/CustomerPatchProcessor.php
├── src/Core/Customer/Application/Transformer/CustomerTransformer.php
├── src/Core/Customer/Application/Command/CreateCustomerCommand.php
├── src/Core/Customer/Application/Command/UpdateCustomerCommand.php
├── src/Core/Customer/Application/CommandHandler/CreateCustomerCommandHandler.php
└── src/Core/Customer/Application/CommandHandler/UpdateCustomerCommandHandler.php

Infrastructure Layer:
└── src/Core/Customer/Infrastructure/Repository/MongoCustomerRepository.php

Configuration:
├── config/doctrine/Customer.mongodb.xml
├── config/api_platform/resources/customer.yaml
├── config/serialization/Customer.yaml
├── config/validator/Customer.yaml
└── config/services.yaml (filter definitions)
```

This example demonstrates the complete implementation of CRUD operations with proper separation of concerns, DDD patterns, and CQRS architecture.
