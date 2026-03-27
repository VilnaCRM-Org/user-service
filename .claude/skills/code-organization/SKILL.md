---
name: code-organization
description: Enforce code organization principles - "Directory X contains ONLY class type X", DDD naming patterns, PHP best practices, type safety, SOLID principles, and hardcoded config extraction to .env. Use when reviewing code structure, placing classes, refactoring, fixing CI failures related to structure, or extracting hardcoded configuration values.
---

# Code Organization Skill

## Core Principle

> **Directory X contains ONLY class type X**

This is the fundamental rule for code organization in this codebase.

## Context (Input)

- Creating new classes and determining correct directory
- Moving classes to proper locations
- Reviewing code for organizational compliance
- Fixing organizational issues from code reviews
- Ensuring class names match their responsibilities
- **Refactoring code structure** (moving, renaming, splitting classes)
- **Fixing CI failures** that stem from structural/naming issues
- **Extracting hardcoded config values** (TTLs, timeouts, limits) to `.env`

## Task (Function)

Enforce strict code organization principles: proper directory structure, DDD naming conventions, specific variable names, type safety, SOLID principles, and PHP best practices.

## Directory Type Classification

Classes MUST be in directories matching their type:

| Directory          | Contains ONLY                   | Example                            |
| ------------------ | ------------------------------- | ---------------------------------- |
| `Converter/`       | Type converters                 | `UlidTypeConverter`                |
| `Transformer/`     | Data transformers (DB/serial)   | `CustomerToArrayTransformer`       |
| `Validator/`       | Validation logic                | `UlidValidator`                    |
| `Builder/`         | Object builders                 | `QueryBuilder`                     |
| `Fixer/`           | Data fixers/modifiers           | `DataFixer`                        |
| `Factory/`         | Object factories                | `CustomerFactory`                  |
| `Resolver/`        | Value resolvers                 | `CustomerUpdateScalarResolver`     |
| `Serializer/`      | Serializers/normalizers         | `CustomerNormalizer`               |
| `Formatter/`       | Data formatters                 | `CustomerNameFormatter`            |
| `Mapper/`          | Data mappers                    | `PathsMapper`                      |
| `Provider/`        | Data/service providers          | `TimestampProvider`                |
| `Processor/`       | API Platform processors         | `CreateCustomerProcessor`          |
| `EventListener/`   | Event listeners (Symfony)       | `QueryParameterValidationListener` |
| `EventSubscriber/` | Event subscribers (Symfony/App) | `SendEmailOnCustomerCreated`       |

### Directory Creation Guardrails

- **NEVER create new directories autonomously** — every new class-type directory MUST follow a well-known software engineering pattern (Factory, Builder, Processor, Validator, Provider, Resolver, etc.) AND be explicitly requested/approved by the user. When in doubt, use an existing directory.
- Do not invent ad-hoc class-type directories or suffixes. The following are **explicitly forbidden**:
  - `Applier/`, `Attacher/`, `Enricher/` — not well-known patterns
  - `Augmenter/` — not a well-known pattern
  - `Helper/`, `Util/`, `Manager/` — vague catch-all anti-patterns
  - `Service/` — leads to anemic domain models; use specific pattern names instead (Provider, Factory, Resolver, etc.)
- Any proposed new directory MUST be a **well-known software engineering pattern** (e.g. Factory, Builder, Strategy, Observer, Adapter, Decorator, Proxy, Iterator, Mediator, etc.) — not an invented verb-noun.
- Use existing DDD/CQRS directory types and naming patterns from this skill.
- Follow DDD and CQRS strictly — all class organization must align with established DDD layers and CQRS patterns.

## DDD Naming Patterns

### By Layer and Type

| Layer              | Class Type         | Naming Pattern                       | Example                           |
| ------------------ | ------------------ | ------------------------------------ | --------------------------------- |
| **Domain**         | Entity             | `{EntityName}.php`                   | `Customer.php`                    |
|                    | Value Object       | `{ConceptName}.php`                  | `Email.php`, `Money.php`          |
|                    | Domain Event       | `{Entity}{PastTenseAction}.php`      | `CustomerCreated.php`             |
|                    | Repository Iface   | `{Entity}RepositoryInterface.php`    | `CustomerRepositoryInterface.php` |
|                    | Exception          | `{SpecificError}Exception.php`       | `InvalidEmailException.php`       |
| **Application**    | Command            | `{Action}{Entity}Command.php`        | `CreateCustomerCommand.php`       |
|                    | Command Handler    | `{Action}{Entity}Handler.php`        | `CreateCustomerHandler.php`       |
|                    | Event Subscriber   | `{Action}On{Event}.php`              | `SendEmailOnCustomerCreated.php`  |
|                    | DTO                | `{Entity}{Type}.php`                 | `CustomerInput.php`               |
|                    | Processor          | `{Action}{Entity}Processor.php`      | `CreateCustomerProcessor.php`     |
|                    | Transformer        | `{From}To{To}Transformer.php`        | `CustomerToArrayTransformer.php`  |
| **Infrastructure** | Repository         | `{Technology}{Entity}Repository.php` | `MySQLCustomerRepository.php`     |
|                    | Doctrine Type      | `{ConceptName}Type.php`              | `UlidType.php`                    |
|                    | Bus Implementation | `{Framework}{Type}Bus.php`           | `SymfonyCommandBus.php`           |

### Directory Structure by Layer

```
src/{Context}/
├── Application/
│   ├── Command/          ← Commands
│   ├── CommandHandler/   ← Command Handlers
│   ├── EventSubscriber/  ← Event Subscribers
│   ├── DTO/              ← Data Transfer Objects
│   ├── Processor/        ← API Platform Processors
│   ├── Transformer/      ← Data Transformers
│   ├── Validator/        ← Validators
│   ├── Converter/        ← Type Converters
│   ├── Resolver/         ← Value Resolvers
│   ├── Factory/          ← Factories
│   ├── Builder/          ← Builders
│   ├── Formatter/        ← Formatters
│   └── MutationInput/    ← GraphQL Mutation Inputs
├── Domain/
│   ├── Entity/           ← Entities & Aggregates
│   ├── ValueObject/      ← Value Objects
│   ├── Event/            ← Domain Events
│   ├── Repository/       ← Repository Interfaces
│   └── Exception/        ← Domain Exceptions
└── Infrastructure/
    ├── Repository/       ← Repository Implementations
    ├── DoctrineType/     ← Custom Doctrine Types
    ├── EventSubscriber/  ← Infrastructure Event Subscribers
    ├── EventListener/    ← Symfony Event Listeners
    └── Bus/              ← Message Bus Implementations
```

## Verification Checklist

When creating or reviewing a class, verify:

1. ✅ **Class Type Matches Directory** (Directory X contains ONLY class type X)
   - Example: `UlidValidator` in `Validator/`, NOT `Transformer/`
2. ✅ **Class Name Follows DDD Pattern** for its type
3. ✅ **Namespace Matches Directory Structure** exactly
4. ✅ **Class Name Reflects Actual Functionality**
5. ✅ **Correct Layer** (Domain/Application/Infrastructure)
6. ✅ **Domain Layer Has NO Framework Imports** (Symfony/Doctrine/API Platform)
7. ✅ **Variable Names Are Specific** (not vague)
   - ✅ `$typeConverter`, `$scalarResolver` (specific)
   - ❌ `$converter`, `$resolver` (too vague)
8. ✅ **Parameter Names Match Actual Types**
   - ✅ `mixed $value` when accepts any type
   - ❌ `string $binary` when accepts mixed
9. ✅ **No "Helper" or "Util" Classes** (extract specific responsibilities)
10. ✅ **No ad-hoc class-type suffixes/directories** (`Applier`, `Attacher`, `Enricher`, `Augmenter`, `Helper`, `Util`, `Manager`, `Service`)
11. ✅ **New directories are explicit and standard**, not agent-invented — must be explicitly approved by the user

## PHP Best Practices

### Required Patterns

- ✅ **Constructor property promotion**
- ✅ **Inject ALL dependencies** (no default instantiation)
- ✅ **Use `readonly`** when appropriate
- ✅ **Use `final`** for classes that shouldn't be extended
- ✅ **No static methods** (except named constructors like `create()`, `from()`)

### Anti-Patterns (Forbidden)

- ❌ **Helper/Util/Service/Manager classes** - Extract specific responsibilities; `Service` leads to anemic domain models
- ❌ **Non-standard pattern directories** - No `Applier/`, `Attacher/`, `Enricher/`, `Augmenter/` — use well-known patterns (Processor, Transformer, Validator, Factory, etc.)
- ❌ **Default instantiation in constructors** - Inject dependencies
- ❌ **Vague variable names** - Be specific
- ❌ **Namespace mismatches** - Must match directory structure
- ❌ **Ad-hoc directory/class type inventions** - Use established patterns only; NEVER create new directories without explicit user approval
- ❌ **Autonomous directory creation** - Agent must NEVER create a new class-type directory on its own; any new directory must follow a well-known software engineering pattern and be approved by the user
- ❌ **Constructor defaults that instantiate collaborators** - Inject dependencies instead of using `new` in `__construct(...)` defaults. Psalm architecture guards enforce this in `src/`.
- ❌ **Direct `new OAuthProvider(...)` in production code** - Use `OAuthProvider::fromString()` instead. Psalm architecture guards enforce this in `src/`.
- ❌ **Plain `json_encode`/`json_decode`** - Use Symfony `SerializerInterface` for serialization/deserialization. Psalm `forbiddenFunctions` enforce this in `src/`; tests are excluded.
- ❌ **Bare `array` or `list` collections of OAuth providers** - Use `OAuthProviderCollection` instead of `array<string, OAuthProviderInterface>`. Internal storage inside collection classes may still use `array`.

## Factory Pattern (Maintainability & Flexibility)

> **Avoid hardcoded `new ClassName()` in production source code — use factory methods or Factory classes**

### Factory Methods on Value Objects

Value objects SHOULD provide static factory methods as named constructors:

```php
// ❌ BAD: Direct instantiation in production code
$provider = new OAuthProvider($value);

// ✅ GOOD: Factory method
$provider = OAuthProvider::fromString($value);
```

Factory methods (`fromString()`, `fromArray()`, `create()`) are the **preferred** way to instantiate value objects outside of their own class. The constructor remains public for use within named constructors and tests.

### When Factory Classes Are REQUIRED (Production Code)

1. Objects with injected dependencies (timestamp providers, config, etc.)
2. Objects requiring complex construction logic
3. Objects needing different implementations per environment
4. Objects created from external input (DTOs, metrics, etc.)

### When Direct `new` Is ACCEPTABLE

- Inside factory methods and Factory classes (that's their purpose)
- In test code (simplicity over abstraction)
- For framework-required patterns (e.g., `throw new InvalidArgumentException()`)
- Inside the value object's own named constructors

### Factory Benefits

- ✅ Centralized object creation logic
- ✅ Easy to inject different implementations
- ✅ Configuration changes don't affect consumers
- ✅ Single place for validation/transformation
- ✅ Enables dependency injection for complex objects

### Example: Bad vs Good

```php
// ❌ BAD: Direct instantiation with configuration
public function emit(BusinessMetric $metric): void
{
    $timestamp = (int)(microtime(true) * 1000);
    $payload = new EmfPayload(
        new EmfAwsMetadata($timestamp, new EmfCloudWatchMetricConfig(...)),
        new EmfDimensionValueCollection(...),
        new EmfMetricValueCollection(...)
    );
    $this->logger->info($payload);
}

// ✅ GOOD: Factory handles complexity
public function emit(BusinessMetric $metric): void
{
    $payload = $this->payloadFactory->createFromMetric($metric);
    $this->logger->info($payload);
}
```

### Factory Naming Convention

- `{ObjectName}Factory` - creates `{ObjectName}` instances
- Location: Same namespace as the object being created
- Example: `EmfPayloadFactory` creates `EmfPayload`

## Type Safety: Classes Over Arrays

> **Arrays are NOT allowed for collections that already have a dedicated collection type. Use the collection class instead.**

Arrays lack type safety and self-documentation. Use concrete classes instead. Current CI guards specifically block bare OAuth provider collections in production code.

### Array vs Class Comparison

| Pattern       | Bad (Array)                               | Good (Class)                                |
| ------------- | ----------------------------------------- | ------------------------------------------- |
| Configuration | `['endpoint' => 'X', 'operation' => 'Y']` | `new EndpointOperationDimensions('X', 'Y')` |
| Return data   | `return ['name' => $n, 'value' => $v]`    | `return new MetricData($n, $v)`             |
| Method params | `function emit(array $metrics)`           | `function emit(MetricCollection $metrics)`  |
| Events data   | `['type' => 'created', 'id' => $id]`      | `new CustomerCreatedEvent($id)`             |
| Registry      | `private array $providers`                | `private OAuthProviderCollection $providers`|

### Benefits of Typed Classes

- ✅ IDE autocompletion and refactoring support
- ✅ Static analysis catches type errors
- ✅ Self-documenting code
- ✅ Encapsulation (validation in constructor)
- ✅ Single Responsibility
- ✅ Open/Closed principle (extend via new classes)

### Collection Pattern

```php
// ❌ BAD: Array of arrays
$metrics = [
    ['name' => 'OrdersPlaced', 'value' => 1],
    ['name' => 'OrderValue', 'value' => 99.99],
];

// ✅ GOOD: Typed collection of objects
$metrics = new MetricCollection(
    new OrdersPlacedMetric(value: 1),
    new OrderValueMetric(value: 99.99)
);
```

### When Arrays ARE Acceptable

- Simple key-value maps for serialization output (`toArray()` methods)
- Framework integration points requiring arrays
- Temporary internal data within a single method

## Cross-Cutting Concerns Pattern

> **Use event subscribers for cross-cutting concerns (metrics, logging), NOT direct injection into handlers**

### Anti-Pattern: Metrics in Command Handler

```php
// ❌ WRONG: Metrics in command handler
final class CreateCustomerHandler
{
    public function __construct(
        private CustomerRepository $repository,
        private BusinessMetricsEmitterInterface $metrics  // Wrong place!
    ) {}

    public function __invoke(CreateCustomerCommand $cmd): void
    {
        $customer = Customer::create(...);
        $this->repository->save($customer);
        $this->metrics->emit(new CustomersCreatedMetric());  // Violates SRP
    }
}
```

### Correct Pattern: Dedicated Event Subscriber

```php
// ✅ CORRECT: Clean command handler
final class CreateCustomerHandler
{
    public function __construct(
        private CustomerRepository $repository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(CreateCustomerCommand $cmd): void
    {
        $customer = Customer::create(...);
        $this->repository->save($customer);
        $this->eventBus->publish(...$customer->pullDomainEvents());
        // Metrics subscriber handles emission
    }
}

// ✅ CORRECT: Metrics in dedicated subscriber
final class CustomerCreatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __invoke(CustomerCreatedEvent $event): void
    {
        // Error handling is automatic via DomainEventMessageHandler.
        // Subscribers are executed in async workers - failures are logged + emit metrics.
        // This ensures observability never breaks the main request (AP from CAP).
        $this->metricsEmitter->emit($this->metricFactory->create());
    }
}
```

## Common Issues and Fixes

### Issue 1: Class in Wrong Type Directory

```bash
❌ WRONG:
src/Shared/Infrastructure/Transformer/UlidValidator.php

✅ CORRECT:
src/Shared/Infrastructure/Validator/UlidValidator.php

# Fix:
mv src/Shared/Infrastructure/Transformer/UlidValidator.php \
   src/Shared/Infrastructure/Validator/UlidValidator.php
# Update namespace and all imports
```

### Issue 2: Vague Variable Names

```php
❌ WRONG:
private UlidTypeConverter $converter;  // Converter of what?

✅ CORRECT:
private UlidTypeConverter $typeConverter;  // Specific!
```

### Issue 3: Misleading Parameter Names

```php
❌ WRONG:
public function fromBinary(mixed $binary): Ulid  // Accepts mixed, not just binary

✅ CORRECT:
public function fromBinary(mixed $value): Ulid  // Accurate!
```

### Issue 4: Helper/Util Classes

```php
❌ WRONG:
class CustomerHelper {
    public function validateEmail() {}
    public function formatName() {}
    public function convertData() {}
}

✅ CORRECT: Extract specific responsibilities
- CustomerEmailValidator (Validator/)
- CustomerNameFormatter (Formatter/)
- CustomerDataConverter (Converter/)
```

### Issue 5: Namespace Mismatch

```php
❌ WRONG:
// File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Transformer;  // Mismatch!

✅ CORRECT:
// File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Validator;  // Matches directory!
```

## Decision Tree: Where Does It Belong?

```text
What does the class DO?

├─ Converts between types (string ↔ object)? → Converter/
├─ Transforms for DB/serialization? → Transformer/
├─ Validates values? → Validator/
├─ Builds/constructs objects? → Builder/
├─ Fixes/modifies data? → Fixer/
├─ Creates complex objects? → Factory/
├─ Resolves/determines values? → Resolver/
├─ Normalizes/serializes? → Serializer/
├─ Formats data for display? → Formatter/
├─ Maps data between structures? → Mapper/
├─ Provides data/cookies/context? → Provider/
└─ Something else? → Ask the user before creating a new directory!
```

## Verification Commands

```bash
# Check namespace consistency
make phpcsfixer
make psalm

# Find organizational issues
grep -r "class.*Helper" src/      # Find Helper classes
grep -r "class.*Util" src/        # Find Util classes
grep -r "private.*\$converter;" src/  # Find vague names

# Verify architecture compliance
make deptrac  # Must show 0 violations
```

## Symfony Service Configuration: No Redundant Wiring

> **Do not add explicit interface aliases in `services.yaml` when Symfony autowiring can resolve them automatically.**

### Rule

When an interface has **exactly one implementation** in `src/`, Symfony autowiring automatically aliases the interface to that implementation. Do NOT add a manual alias — it is redundant.

### When an Explicit Alias IS Required

- The interface has **multiple implementations** (e.g., `UserRepositoryInterface` → `CachedUserRepository` vs `MongoDBUserRepository`)
- The implementation lives **outside** the autowired `src/` resource (e.g., a third-party bundle class)
- You need to alias to a **different** implementation than what autowiring would pick

### When an Explicit Alias is REDUNDANT (remove it)

- Only one class in `src/` implements the interface
- Both the interface and implementation are covered by the `App\:` resource in `services.yaml`

### Example

```yaml
# ❌ REDUNDANT: Only one implementation exists — autowiring handles this
App\OAuth\Domain\Repository\SocialIdentityRepositoryInterface:
  alias: App\OAuth\Infrastructure\Repository\MongoDBSocialIdentityRepository

# ✅ REQUIRED: Two implementations exist — must disambiguate
App\User\Domain\Repository\UserRepositoryInterface:
  alias: App\User\Infrastructure\Repository\CachedUserRepository
```

### Explicit Constructor Arguments Are Still Needed

Even when the alias is redundant, you may still need an explicit service definition for **constructor arguments** that autowiring cannot resolve (e.g., non-type-hinted parameters, named service references):

```yaml
# ✅ NEEDED: $oauthRedis is a named Redis connection, not autowirable
App\OAuth\Infrastructure\Repository\RedisOAuthStateRepository:
  arguments:
    $oauthRedis: '@oauth.redis_connection'
# ❌ NOT NEEDED: the interface alias (autowiring resolves it)
# App\OAuth\Domain\Repository\OAuthStateRepositoryInterface:
#   alias: App\OAuth\Infrastructure\Repository\RedisOAuthStateRepository
```

### Verification

```bash
# Check that autowiring resolves the interface correctly
docker compose exec php bin/console debug:container <InterfaceName>
# Should show "This service is a private alias for the service <Implementation>"
```

## Constraints (Never Do This)

**NEVER**:

- Place class in wrong type directory (violates "Directory X contains ONLY class type X")
- Allow Domain layer to import framework code (Symfony/Doctrine/API Platform)
- Use vague variable names (`$converter`, `$resolver` - be specific!)
- Create "Helper" or "Util" classes (extract specific responsibilities)
- Allow namespace to mismatch directory structure
- Use arrays for structured data when typed classes would be appropriate
- Use `array` type for collections of domain/application objects — use typed collections
- Use `json_encode`/`json_decode` — use Symfony `SerializerInterface` (enforced by Psalm `forbiddenFunctions` in `src/`)
- Use constructor defaults that instantiate collaborators — inject the dependency instead
- Use direct `new OAuthProvider(...)` in production code — use `OAuthProvider::fromString()`
- Inject cross-cutting concerns (metrics, logging) into command handlers
- Create complex objects directly without factories in production code
- Add redundant interface aliases in `services.yaml` when autowiring resolves them

**ALWAYS**:

- Verify "Directory X contains ONLY class type X" principle
- Use specific variable names (`$typeConverter`, not `$converter`)
- Use accurate parameter names (match actual types)
- Ensure namespace matches directory structure exactly
- Extract specific responsibilities from Helper/Util classes
- Prefer typed classes over arrays for structured data
- Use typed collection classes instead of arrays of objects
- Use Symfony `SerializerInterface` instead of `json_encode`/`json_decode`
- Inject dependencies instead of instantiating constructor defaults
- Use `OAuthProvider::fromString()` instead of direct `new OAuthProvider(...)`
- Use event subscribers for cross-cutting concerns
- Use factories for complex object creation in production code

## Related Skills

- **ci-workflow**: Use code-organization principles when fixing CI failures that stem from structural issues
- **code-review**: References this skill for organization verification during PR reviews
- **complexity-management**: Refactoring often requires reorganization; consult both skills together
- **implementing-ddd-architecture**: DDD patterns and layer structure
- **deptrac-fixer**: Fixes architectural boundary violations (layer moves vs. file placement)
- **quality-standards**: Maintains overall code quality metrics

## Hardcoded Configuration Values → `.env` Extraction

> **Configurable values (TTLs, timeouts, limits, sizes, batch counts) belong in `.env`, not as class constants.**

### When to Extract

Extract a constant to `.env` when it represents:

- **Time durations**: TTLs, timeouts, expiration periods, intervals
- **Rate limits**: Max requests, windows, thresholds
- **Sizes**: Batch sizes, max body sizes, token lengths
- **Retry configuration**: Delay, max attempts, backoff intervals
- **Infrastructure tunables**: Cache TTLs, queue settings, lockout parameters

### When NOT to Extract

Keep as constants when the value is:

- **Protocol/spec-defined**: HTTP status codes, cipher IV lengths, segment lengths
- **Security-critical internal**: Encryption tag lengths, HSTS header values
- **Domain invariants**: Validation rules that are part of the domain model

### Extraction Pattern (3-Step)

**Step 1**: Add env variable to `.env` and `.env.test`

```dotenv
# .env
CACHE_USER_BY_ID_TTL=600
CACHE_USER_BY_EMAIL_TTL=300

# .env.test (same or test-appropriate value)
CACHE_USER_BY_ID_TTL=600
CACHE_USER_BY_EMAIL_TTL=300
```

**Step 2**: Bind in `config/services.yaml`

```yaml
App\User\Infrastructure\Repository\CachedUserRepository:
  arguments:
    $ttlById: '%env(int:CACHE_USER_BY_ID_TTL)%'
    $ttlByEmail: '%env(int:CACHE_USER_BY_EMAIL_TTL)%'
```

**Step 3**: Replace constant with constructor parameter

```php
// ❌ BEFORE: Hardcoded constant
final class CachedUserRepository
{
    private const TTL_BY_ID = 600;
    private const TTL_BY_EMAIL = 300;
}

// ✅ AFTER: Injected from .env
final readonly class CachedUserRepository
{
    public function __construct(
        private UserRepositoryInterface $inner,
        private CacheInterface $cache,
        private int $ttlById,
        private int $ttlByEmail,
    ) {
    }
}
```

### Common Extraction Candidates

| Pattern in Source                         | Extract To `.env`                      |
| ----------------------------------------- | -------------------------------------- |
| `private const TTL_* = <seconds>`         | `CACHE_*_TTL=<seconds>`                |
| `private const EXPIRES_AFTER_* = <value>` | `TOKEN_EXPIRATION_SECONDS=<value>`     |
| `private const MAX_ATTEMPTS = <n>`        | `*_MAX_ATTEMPTS=<n>`                   |
| `private const BATCH_SIZE = <n>`          | `*_BATCH_SIZE=<n>`                     |
| `private const DEFAULT_*_SECONDS = <n>`   | `*_SECONDS=<n>`                        |
| Constructor default `= 900`               | Remove default, bind via services.yaml |

### Verification After Extraction

```bash
make phpcsfixer          # Fix code style
make psalm               # Verify type safety
make unit-tests          # Ensure tests pass (update mocks for new constructor params)
make integration-tests   # Verify runtime binding works
make ci                  # Full validation
```

## CI Integration: When CI Fails

When `make ci` fails, consult this skill if the failure involves:

| CI Failure Indicator                 | Code Organization Fix                               |
| ------------------------------------ | --------------------------------------------------- |
| Class not found / namespace mismatch | Verify namespace matches directory structure        |
| Deptrac violation after moving class | Check layer placement (Domain/Application/Infra)    |
| PHPInsights architecture score drop  | Verify "Directory X contains ONLY class type X"     |
| Psalm type errors after refactoring  | Check that imports and namespaces were all updated  |
| Test failures after class move       | Move test file too, update test namespace + imports |

### Refactoring Checklist (Before Running CI)

When moving, renaming, or restructuring classes:

- [ ] Class in correct directory for its type (see Decision Tree above)
- [ ] Namespace matches directory structure exactly
- [ ] All `use` imports updated in `src/` and `tests/`
- [ ] Test file moved to mirror source structure
- [ ] Test namespace updated
- [ ] `config/services.yaml` references updated (if service was explicitly configured)
- [ ] `config/doctrine/*.mongodb.xml` mappings updated (if entity moved)
- [ ] `config/validator/*.yaml` references updated (if validator target moved)
- [ ] Hardcoded config values extracted to `.env` if applicable
- [ ] `make phpcsfixer && make psalm && make deptrac && make unit-tests` pass

## Related Documentation

See `reference/troubleshooting.md` for detailed troubleshooting and `examples/organization-fixes.md` for real-world examples.
