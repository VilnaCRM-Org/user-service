# PHPInsights Troubleshooting Guide

Common PHPInsights failures and their solutions specific to this project's Symfony preset and configuration.

## Quick Diagnostic

When `make phpinsights` fails:

1. **Identify which dimension failed**:

   ```
   [CODE] 100.0 pts       ✅
   [COMPLEXITY] 89.3 pts  ❌ (requires 93%)
   [ARCHITECTURE] 100 pts ✅
   [STYLE] 97.8 pts       ❌ (requires 100%)
   ```

2. **Read the specific violations**:

   ```
   ✗ [Complexity] Line 45: Method has cyclomatic complexity of 15
   ✗ [Style] Line 120: Line exceeds 100 characters
   ```

3. **Use this guide** to find the solution for your specific error

---

## ⚠️ CRITICAL: Never Change Config, Fix Code Instead

```
╔═══════════════════════════════════════════════════════════════════════╗
║                        FORBIDDEN: Changing Config                     ║
║                                                                       ║
║  When PHPInsights fails, you MUST refactor the code.                 ║
║  NEVER lower thresholds in phpinsights.php to make checks pass.      ║
║                                                                       ║
║  ❌ DO NOT: Edit phpinsights.php to lower requirements               ║
║  ✅ DO THIS: Refactor code to meet quality standards                 ║
╚═══════════════════════════════════════════════════════════════════════╝
```

### Example: Wrong vs Right Approach

**❌ WRONG - Lowering Threshold** (FORBIDDEN):

```php
// phpinsights.php
'requirements' => [
    'min-complexity' => 89,  // ❌ Lowered from 94 to make it pass
],
```

**✅ RIGHT - Fix the Code**:

```bash
# 1. Find which classes are complex
make analyze-complexity N=10

# 2. Refactor those specific classes
# See refactoring-strategies.md for patterns

# 3. Verify improvement
make phpinsights
```

### Why This Policy Exists

- **Prevents technical debt accumulation**
- **Maintains long-term code quality**
- **Ensures consistent standards across team**
- **Makes refactoring easier in the future**
- **Reduces bugs in complex code**

**Remember**: If you're tempted to lower a threshold, the code needs refactoring, not the config.

---

## Finding Classes to Refactor

Before fixing complexity issues, identify which classes need attention.

### Use `make analyze-complexity`

**Find top 20 most complex classes** (default):

```bash
make analyze-complexity
```

**Find top 10 classes**:

```bash
make analyze-complexity N=10
```

**Export as JSON** (for tracking or CI/CD):

```bash
make analyze-complexity-json N=20 > complexity-report.json
```

**Export as CSV** (for spreadsheets):

```bash
make analyze-complexity-csv N=15 > complexity.csv
```

### Understanding the Output

The command shows for each class:

| Metric                          | What It Means                    | Critical Threshold |
| ------------------------------- | -------------------------------- | ------------------ |
| **CCN** (Cyclomatic Complexity) | Total decision points in class   | > 15               |
| **WMC** (Weighted Method Count) | Sum of all method complexities   | > 50               |
| **Methods**                     | Number of methods in class       | > 20               |
| **LLOC**                        | Logical Lines of Code            | > 200              |
| **Avg Complexity**              | CCN ÷ Methods                    | > 5                |
| **Max Complexity**              | Highest single method complexity | > 10               |
| **Maintainability Index**       | 0-100 (higher is better)         | < 65               |

### Prioritize Refactoring

**Focus on these first**:

1. **CCN > 15**: CRITICAL - Immediate refactoring required
2. **Max Complexity > 10**: HIGH - Single method too complex
3. **Avg Complexity > 5**: MEDIUM - Generally complex class
4. **Maintainability Index < 65**: LOW - Consider refactoring

### Example Workflow

```bash
# 1. Find complex classes
make analyze-complexity N=10

# Output shows:
# #1 - App\Customer\Application\CommandHandler\UpdateCustomerCommandHandler
#   🔢 CCN: 18  (CRITICAL!)
#   ⚡ Avg Complexity: 6.0
#   🔴 Max Method Complexity: 12

# 2. Focus on that file
vendor/bin/phpinsights analyse src/Customer/Application/CommandHandler/UpdateCustomerCommandHandler.php

# 3. Refactor using patterns from refactoring-strategies.md

# 4. Re-check
make analyze-complexity N=1  # Check if it improved
make phpinsights              # Verify all checks pass
```

---

## Complexity Failures (< 94%)

### ❌ "Function/Method has cyclomatic complexity of X (maximum: Y)"

**Error Example**:

```
✗ src/Customer/Application/CommandHandler/UpdateCustomerCommandHandler.php
  Line 25: Method `__invoke` has cyclomatic complexity of 12 (maximum: 10)
```

**Root Cause**: Too many decision points (if/else/switch/&&/||)

**Solutions**:

#### Solution 1: Extract Methods

```php
// Before (complexity: 12)
public function handle(UpdateCustomerCommand $command): void
{
    if ($command->email) {
        if (!$this->validator->isValid($command->email)) {
            throw new InvalidEmailException();
        }
        if ($this->repository->emailExists($command->email)) {
            throw new EmailExistsException();
        }
        $customer->setEmail($command->email);
    }
    // ... more conditions
}

// After (complexity: 3)
public function handle(UpdateCustomerCommand $command): void
{
    if ($command->email) {
        $this->updateEmail($customer, $command->email);
    }
    // ... cleaner flow
}

private function updateEmail(Customer $customer, string $email): void
{
    $this->validateEmail($email);
    $this->ensureEmailIsUnique($email);
    $customer->setEmail($email);
}
```

#### Solution 2: Early Returns

```php
// Before (complexity: 6)
public function canRefund(Order $order): bool
{
    if ($order->isPaid()) {
        if ($order->isDelivered()) {
            if ($order->daysOld() < 30) {
                return true;
            }
        }
    }
    return false;
}

// After (complexity: 4)
public function canRefund(Order $order): bool
{
    if (!$order->isPaid()) {
        return false;
    }

    if (!$order->isDelivered()) {
        return false;
    }

    return $order->daysOld() < 30;
}
```

#### Solution 3: Move to Domain Layer

```php
// Before: Logic in Application layer (CommandHandler)
// Complexity: 10 in handler

// After: Logic in Domain layer
// Handler complexity: 2
final readonly class UpdateCustomerCommandHandler
{
    public function __invoke(UpdateCustomerCommand $command): void
    {
        $customer = $this->repository->find($command->id);
        $customer->update($command->toUpdateRequest());  // Domain handles complexity
        $this->repository->save($customer);
    }
}

// Domain entity (can have higher complexity)
class Customer
{
    public function update(CustomerUpdateRequest $request): void
    {
        // Complex business logic here is acceptable
    }
}
```

**See**: [refactoring-strategies.md](../refactoring-strategies.md) for detailed patterns

---

### ❌ "Class has too many public methods (X, maximum: Y)"

**Error Example**:

```
✗ src/Customer/Domain/Repository/CustomerRepository.php
  Class has 25 public methods (maximum: 20)
```

**Root Cause**: Repository or service doing too much

**Solutions**:

#### Solution 1: Split Repository by Concern

```php
// Before: God Repository
interface CustomerRepository
{
    public function find($id);
    public function findByEmail($email);
    public function findVipCustomers();
    public function findInactiveCustomers();
    public function findByOrderCount($min);
    public function countByStatus($status);
    public function getTopSpenders($limit);
    // ... 18 more methods
}

// After: Split into specific repositories/query objects
interface CustomerRepository
{
    public function find(CustomerId $id): Customer;
    public function save(Customer $customer): void;
    public function findByEmail(Email $email): ?Customer;
}

// Separate read model for queries
interface CustomerQueryRepository
{
    public function findBySpecification(Specification $spec): array;
}

// Use specifications for complex queries
$vipCustomers = $queryRepo->findBySpecification(new VipCustomersSpec());
```

#### Solution 2: Extract to Domain Service

> ⚠️ **Important**: Only use Domain Services for cross-aggregate/cross-entity business logic. Do NOT create Application layer "Services" - use CommandHandlers, Processors, Transformers, or Validators instead.

```php
// Before: Entity with too many methods
class Customer
{
    // Domain methods
    public function changeName($name) { }
    public function changeEmail($email) { }

    // Calculation methods (should be Domain Service)
    public function calculateLifetimeValue() { }
    public function predictChurnProbability() { }
    public function recommendProducts() { }
    // ... etc
}

// After: Extract to Domain Service
class Customer
{
    public function changeName($name) { }
    public function changeEmail($email) { }
}

// Separate Domain Service (lives in Domain layer)
// src/Customer/Domain/Service/CustomerAnalyticsService.php
final readonly class CustomerAnalyticsService
{
    public function calculateLifetimeValue(Customer $customer): Money { }
    public function predictChurnProbability(Customer $customer): float { }
}
```

---

## Code Quality Failures (< 100%)

### ❌ "Function/Method lines of code (X) exceeds Y lines"

**Error Example**:

```
✗ src/Customer/Application/CommandHandler/CreateCustomerCommandHandler.php
  Line 20: Method `__invoke` lines of code (125) exceeds 100 lines
```

**Root Cause**: Method doing too much

**Solutions**:

#### Extract Private Methods

```php
// Before: 125 lines
public function __invoke(CreateCustomerCommand $command): void
{
    // 30 lines of validation
    // 40 lines of entity creation
    // 30 lines of related entity creation
    // 25 lines of event publishing
}

// After: 20 lines, extracted to private methods
public function __invoke(CreateCustomerCommand $command): void
{
    $this->validateCommand($command);
    $customer = $this->createCustomer($command);
    $this->createRelatedEntities($customer, $command);
    $this->publishEvents($customer);
}

private function validateCommand(CreateCustomerCommand $command): void { /* ... */ }
private function createCustomer(CreateCustomerCommand $command): Customer { /* ... */ }
private function createRelatedEntities(Customer $customer, CreateCustomerCommand $command): void { /* ... */ }
private function publishEvents(Customer $customer): void { /* ... */ }
```

---

### ❌ "Class lines of code (X) exceeds Y lines"

**Error Example**:

```
✗ src/Customer/Domain/Entity/Customer.php
  Class lines of code (650) exceeds 400 lines
```

**Root Cause**: God class with too many responsibilities

**Solutions**:

#### Extract Value Objects

```php
// Before: All logic in entity (650 lines)
class Customer
{
    private string $email;

    public function changeEmail(string $email): void
    {
        // 30 lines of email validation
        // 20 lines of uniqueness checking
        $this->email = $email;
    }

    private string $address;

    public function changeAddress(string $address): void
    {
        // 40 lines of address validation
        // 20 lines of geocoding
        $this->address = $address;
    }

    // ... more similar patterns
}

// After: Value Objects extracted (250 lines in entity)
class Customer
{
    private Email $email;
    private Address $address;

    public function changeEmail(Email $email): void
    {
        $this->ensureEmailIsUnique($email);
        $this->email = $email;
    }

    public function changeAddress(Address $address): void
    {
        $this->address = $address;
    }
}

// Value Objects handle their own validation (separate files)
final readonly class Email { /* validation logic */ }
final readonly class Address { /* validation + geocoding */ }
```

---

## Architecture Failures (< 100%)

### ❌ "Class has coupling between objects of X (threshold: Y)"

**Error Example**:

```
✗ src/Customer/Application/CommandHandler/CreateCustomerCommandHandler.php
  Class has coupling between objects of 18 (threshold: 13)
```

**Root Cause**: Too many dependencies injected

> ⚠️ **Architecture Note**: If you see an Application layer "Service" with many dependencies, this indicates an anemic domain model anti-pattern. Refactor to use CommandHandlers (Application layer) with Domain logic in entities/aggregates.

**Solutions**:

#### Facade Pattern

```php
// Before: 18 dependencies in CommandHandler
class CreateCustomerCommandHandler
{
    public function __construct(
        private CustomerRepository $repo,
        private EmailValidator $emailValidator,
        private EmailSender $emailSender,
        private EventDispatcher $events,
        private Logger $logger,
        private Cache $cache,
        private PricingCalculator $pricing,
        private InventoryChecker $inventory,
        private ShippingCalculator $shipping,
        private TaxCalculator $tax,
        // ... 8 more
    ) {}
}

// After: Grouped dependencies via facades
class CreateCustomerCommandHandler
{
    public function __construct(
        private CustomerRepository $repo,
        private NotificationFacade $notifications,    // Email + SMS + Push
        private CalculationFacade $calculations,      // Pricing + Tax + Shipping
        private CommandBusInterface $commandBus,
        private LoggerInterface $logger,
    ) {}
}
```

#### Split CommandHandler Responsibilities

```php
// Before: One CommandHandler doing too much (ANTI-PATTERN!)
// DON'T DO THIS - This is an anemic domain model
class CustomerCommandHandler
{
    public function handleCreate() { }
    public function handleUpdate() { }
    public function handleDelete() { }
    public function sendEmail() { }
    public function calculateValue() { }
}

// After: Separate CommandHandlers (one per command)
class CreateCustomerCommandHandler { /* handles CreateCustomerCommand */ }
class UpdateCustomerCommandHandler { /* handles UpdateCustomerCommand */ }
class DeleteCustomerCommandHandler { /* handles DeleteCustomerCommand */ }

// Notifications and analytics belong elsewhere:
// - Notifications: Event Subscribers listening to domain events
// - Analytics: Domain Services or separate bounded context
```

---

### ❌ "Property X is not used"

**Error Example**:

```
✗ src/Customer/Domain/Entity/Customer.php
  Line 45: Property `createdAt` is not used
```

**Root Cause**: Property defined but never accessed

**Solutions**:

#### Remove Unused Property

```php
// If truly unused, remove it
class Customer
{
    private \DateTimeImmutable $createdAt; // ❌ Defined but never used

    // No getter, no usage in methods
}

// Solution: Remove it
```

#### Add Getter if Needed

```php
// If it should be used, add getter
class Customer
{
    private \DateTimeImmutable $createdAt;

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

---

## Style Failures (< 100%)

### ❌ "Line exceeds 100 characters"

**Error Example**:

```
✗ src/Customer/Application/Transformer/CustomerTransformer.php
  Line 45: Line exceeds 100 characters (found 127, limit 100)
```

**Root Cause**: Long lines (configured limit: 100 chars in `phpinsights.php`)

**Solutions**:

#### Break Method Calls

```php
// Before (127 chars)
$customer = $this->customerFactory->create($command->name, $command->email, $command->phone, $command->address, $command->country);

// After (multi-line)
$customer = $this->customerFactory->create(
    $command->name,
    $command->email,
    $command->phone,
    $command->address,
    $command->country
);
```

#### Break Array Definitions

```php
// Before (110 chars)
$config = ['host' => 'localhost', 'port' => 5432, 'database' => 'customers', 'username' => 'admin'];

// After
$config = [
    'host' => 'localhost',
    'port' => 5432,
    'database' => 'customers',
    'username' => 'admin',
];
```

#### Extract Variables

```php
// Before (105 chars)
return $this->repository->findBySpecification(new VipCustomersSpec(), new ActiveSubscriptionSpec());

// After
$vipSpec = new VipCustomersSpec();
$activeSpec = new ActiveSubscriptionSpec();
return $this->repository->findBySpecification($vipSpec, $activeSpec);
```

#### Use Named Parameters (PHP 8+)

```php
// Before (120 chars)
$invoice = Invoice::create($customer, $items, $taxRate, $shippingCost, $discountPercentage, $paymentTerms);

// After (multi-line with named params)
$invoice = Invoice::create(
    customer: $customer,
    items: $items,
    taxRate: $taxRate,
    shippingCost: $shippingCost,
    discountPercentage: $discountPercentage,
    paymentTerms: $paymentTerms
);
```

**Auto-fix**: Many style issues can be auto-fixed:

```bash
make phpcsfixer
make phpinsights  # Verify fixes
```

---

### ❌ "Missing visibility keyword on property/method"

**Error Example**:

```
✗ src/Customer/Domain/Entity/Customer.php
  Line 20: Property `email` must have visibility (public/protected/private)
```

**Root Cause**: PHP allows properties without visibility (defaults to public in old PHP)

**Solution**:

```php
// Before
class Customer
{
    $email;  // ❌ No visibility
}

// After
class Customer
{
    private string $email;  // ✅ Explicit visibility
}
```

---

### ❌ "Final keyword should be on classes"

**Error Example**:

```
✗ src/Customer/Application/CommandHandler/CreateCustomerCommandHandler.php
  Class should be declared final
```

**Root Cause**: Symfony preset prefers final classes (prevent inheritance)

**Solution**:

```php
// Before
class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    // ...
}

// After
final class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    // ...
}
```

**Exception**: Some classes are intentionally not final (see phpinsights.php config):

- `InMemorySymfonyCommandBus`
- `InMemorySymfonyEventBus`
- `Customer` entity (may be proxied by Doctrine)

---

## Project-Specific Issues

### ❌ "Interface name should not end with 'Interface'"

**Status**: **Disabled in this project**

This sniff is removed in `phpinsights.php`:

```php
'remove' => [
    SuperfluousInterfaceNamingSniff::class,  // Allow "Interface" suffix
],
```

**Action**: Ignore this error if it appears (shouldn't happen with config)

---

### ❌ "Exception name should not end with 'Exception'"

**Status**: **Disabled in this project**

This sniff is removed in `phpinsights.php`:

```php
'remove' => [
    SuperfluousExceptionNamingSniff::class,  // Allow "Exception" suffix
],
```

**Action**: Ignore this error if it appears (shouldn't happen with config)

---

### ❌ "Forbidden use of setter method"

**Status**: **Disabled in this project**

This sniff is removed in `phpinsights.php`:

```php
'remove' => [
    ForbiddenSetterSniff::class,  // Allow setters where needed
],
```

**Rationale**: Some Doctrine entities and DTOs need setters

**Action**: Setters are allowed - ignore if this error appears

---

### ❌ "Unused parameter in method signature"

**Status**: **Disabled in this project**

This sniff is removed in `phpinsights.php`:

```php
'remove' => [
    UnusedParameterSniff::class,  // Allow unused params in interfaces
],
```

**Rationale**: Interface methods may have unused parameters in some implementations

**Action**: Ignore - allowed by project configuration

---

## Integration Issues

### ❌ "Make command fails: vendor/bin/phpinsights: not found"

**Root Cause**: Dependencies not installed

**Solution**:

```bash
# Install dependencies
make install

# Or directly
composer install

# Verify
vendor/bin/phpinsights --version
```

---

### ❌ "Out of memory" when running PHPInsights

**Root Cause**: Large codebase analysis requires memory

**Solutions**:

#### Increase PHP Memory Limit

```bash
# Temporary (one-time)
php -d memory_limit=512M vendor/bin/phpinsights

# Permanent (update php.ini)
memory_limit = 512M
```

#### Analyze Specific Directory

```bash
# Instead of entire src/
vendor/bin/phpinsights analyse src/Customer
```

---

### ❌ PHPInsights Hangs/Freezes

**Root Cause**: Usually infinite loops in analysis or very large files

**Solution**:

#### Set Timeout

```bash
timeout 300 make phpinsights  # 5 minute timeout
```

#### Exclude Problem Files

Update `phpinsights.php`:

```php
'exclude' => [
    'vendor',
    'CLI/bats/php',
    'path/to/problematic/file.php',  // Add problematic file
],
```

---

## CI/CD-Specific Issues

### ❌ "PHPInsights passes locally but fails in CI"

**Root Cause**: Different PHP versions or missing extensions

**Solution**:

#### Check PHP Version Consistency

```bash
# Local
php -v

# CI (in GitHub Actions, check .github/workflows/)
# Ensure same version (8.3.12)
```

#### Verify Extensions

```bash
# Check required extensions
php -m | grep -E 'mbstring|xml|dom|simplexml'
```

---

## Debugging Workflow

When PHPInsights fails and the error is unclear:

### Step 1: Run with Verbosity

```bash
vendor/bin/phpinsights -v
```

### Step 2: Generate JSON Report

```bash
vendor/bin/phpinsights --format=json > report.json
cat report.json | jq '.issues[] | select(.severity == "error")'
```

### Step 3: Isolate the Issue

```bash
# Test specific file
vendor/bin/phpinsights analyse src/path/to/problematic/File.php -v

# Test specific directory
vendor/bin/phpinsights analyse src/Customer/Domain
```

### Step 4: Check Configuration

```bash
# Verify phpinsights.php is being loaded
vendor/bin/phpinsights --config-path=phpinsights.php -v
```

---

## Emergency Bypass (NOT RECOMMENDED)

**DO NOT USE UNLESS ABSOLUTELY NECESSARY**

If you must temporarily disable a specific check:

### Option 1: Inline Suppression

```php
// @phpinsights-ignore-next-line
public function complexMethod() { }
```

### Option 2: Update phpinsights.php

```php
'config' => [
    SomeSniff::class => [
        'exclude' => [
            'path/to/specific/file',
        ],
    ],
],
```

**WARNING**: Never lower the global thresholds in `requirements`:

```php
'requirements' => [
    'min-quality' => 100,      // NEVER LOWER
    'min-complexity' => 94,    // NEVER LOWER
    'min-architecture' => 100, // NEVER LOWER
    'min-style' => 100,        // NEVER LOWER
],
```

---

## Prevention Strategies

### Run PHPInsights Before Committing

```bash
# Add to pre-commit hook (captainhook.json already configured)
make phpinsights || exit 1
```

### Run on Changed Files Only

```bash
# Get changed files
git diff --name-only --diff-filter=ACMR | grep '\.php$' > changed.txt

# Analyze only changed files
cat changed.txt | xargs vendor/bin/phpinsights analyse
```

### IDE Integration

Configure PHPStorm to show PHPInsights warnings in real-time:

1. Settings → PHP → Quality Tools → PHP Insights
2. Point to `vendor/bin/phpinsights`
3. Enable inspections

---

## Getting Help

If you encounter an issue not covered here:

1. **Check PHPInsights docs**: https://phpinsights.com/
2. **Search similar issues**: GitHub issues for PHPInsights
3. **Ask the team**: Share the specific error and file
4. **Update this guide**: Document the solution for others

---

**See Also**:

- [refactoring-strategies.md](../refactoring-strategies.md) - How to fix complexity issues
- [complexity-metrics.md](complexity-metrics.md) - Understanding what metrics mean
- [monitoring.md](monitoring.md) - Tracking improvements over time
