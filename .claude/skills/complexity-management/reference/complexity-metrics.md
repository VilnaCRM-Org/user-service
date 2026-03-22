# PHPInsights Complexity Metrics Reference

Understanding what PHPInsights measures and how to interpret each metric in the context of this project's hexagonal architecture.

## Overview

PHPInsights analyzes code across four dimensions:

1. **Code Quality** (100% required)
2. **Complexity** (94% required)
3. **Architecture** (100% required)
4. **Style** (100% required)

Each dimension consists of multiple metrics and checks from various tools (PHP_CodeSniffer, PHPMD, Psalm concepts).

---

## Complexity Metrics (94% Minimum)

### Cyclomatic Complexity

**What it measures**: Number of independent paths through code (if/else/for/while/case/catch/&&/||)

**Formula**: `Edges - Nodes + 2` in the control flow graph

**Thresholds**:

- **1-4**: Simple, easy to test
- **5-7**: Moderate complexity, still manageable
- **8-10**: Complex, consider refactoring
- **11+**: Very complex, immediate refactoring needed

**Example**:

```php
// Cyclomatic Complexity: 1 (no branches)
public function getTotal(): float
{
    return $this->items->sum();
}

// Cyclomatic Complexity: 3 (2 if statements + base)
public function applyDiscount(float $discount): void
{
    if ($discount < 0) {          // +1
        throw new InvalidArgumentException();
    }

    if ($discount > 0.5) {        // +1
        $discount = 0.5;
    }

    $this->discount = $discount;
}

// Cyclomatic Complexity: 6 (5 branches + base)
public function getShippingCost(): float
{
    if ($this->total < 50) {              // +1
        return 10.00;
    } elseif ($this->total < 100) {       // +1
        return 5.00;
    } elseif ($this->isPremium()) {       // +1
        return 0.00;
    } elseif ($this->country === 'US') {  // +1
        return 7.50;
    } else {                              // +1
        return 15.00;
    }
}
```

**Project Context**:

- **Command Handlers**: Target complexity 1-3 (orchestration only)
- **Domain Entities**: Acceptable 5-10 (business logic)
- **Value Objects**: Target complexity 1-5 (validation)
- **Repositories**: Target complexity 1-5 (queries)

---

### NPath Complexity

**What it measures**: Number of possible execution paths through a function

**Difference from Cyclomatic**: Exponential rather than linear

**Formula**: Multiplicative combination of decision points

**Thresholds**:

- **< 200**: Acceptable
- **200-1000**: High, consider refactoring
- **> 1000**: Extreme, immediate action required

**Example**:

```php
// NPath: 8 (2 × 2 × 2)
public function process($a, $b, $c)
{
    if ($a) { }      // 2 paths
    if ($b) { }      // × 2 = 4 paths
    if ($c) { }      // × 2 = 8 paths
}

// NPath: 32 (4 × 4 × 2)
public function validate($status, $type, $flag)
{
    if ($status === 'pending') {        // 4 paths (4 cases)
        // ...
    } elseif ($status === 'active') {
        // ...
    } elseif ($status === 'suspended') {
        // ...
    } else {
        // ...
    }

    if ($type === 'standard') {         // × 4 = 16
        // ...
    } elseif ($type === 'premium') {
        // ...
    } elseif ($type === 'enterprise') {
        // ...
    } else {
        // ...
    }

    if ($flag) {                        // × 2 = 32
        // ...
    }
}
```

**How to reduce**: Same strategies as Cyclomatic Complexity, but more critical

---

### Cognitive Complexity

**What it measures**: How difficult code is for humans to understand

**Difference from Cyclomatic**: Penalizes nested structures more heavily

**Key factors**:

- **Nesting**: Each level of nesting adds +1
- **Breaks in linear flow**: if/else/switch/loops add +1
- **Logical operators**: && and || add +1

**Example**:

```php
// Cognitive Complexity: 1
public function isEligible(): bool
{
    return $this->age >= 18;
}

// Cognitive Complexity: 3 (1 for if + 1 for nesting + 1 for inner if)
public function calculatePrice(): float
{
    $price = $this->basePrice;

    if ($this->isPremium()) {           // +1
        if ($this->hasDiscount()) {     // +1 (nested) + 1 (for if itself) = +2
            $price *= 0.9;
        }
    }

    return $price;
}

// Cognitive Complexity: 7
public function validate($data): bool
{
    if (!isset($data['email'])) {                          // +1
        return false;
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) { // +1
        return false;
    }

    if (isset($data['phone'])) {                           // +1
        if (strlen($data['phone']) < 10) {                 // +1 (nested) + 1 (if) = +2
            return false;
        }

        if (!preg_match('/^\d+$/', $data['phone'])) {      // +1 (nested) + 1 (if) = +2
            return false;
        }
    }

    return true;
}
```

**Reduction strategies**:

- Use early returns to avoid nesting
- Extract nested logic to separate methods
- Replace nested conditionals with polymorphism

---

## Code Quality Metrics (100% Required)

### Method/Function Length

**What it measures**: Lines of code per method (excluding blank lines and comments)

**Thresholds**:

- **< 20 lines**: Ideal
- **20-50 lines**: Acceptable
- **50-100 lines**: Consider refactoring
- **> 100 lines**: Immediate refactoring needed

**Project Context**:

- **Command Handlers**: Typically 10-20 lines
- **Domain Methods**: 10-50 lines
- **Test methods**: Can be longer (setup/assertions)

---

### Class Length

**What it measures**: Total lines per class

**Thresholds**:

- **< 200 lines**: Good
- **200-400 lines**: Acceptable
- **400-600 lines**: Large, consider splitting
- **> 600 lines**: God class, refactor immediately

**Project Context**:

- **Entities**: 100-300 lines (business logic concentrated)
- **Value Objects**: 50-150 lines (validation + behavior)
- **Repositories**: 100-200 lines (query methods)
- **Command Handlers**: 30-100 lines (thin orchestration)

---

### Number of Methods

**What it measures**: Total public/protected/private methods in a class

**Thresholds**:

- **< 10 methods**: Good
- **10-20 methods**: Acceptable
- **20-30 methods**: Consider splitting
- **> 30 methods**: Too many responsibilities

**Reduction strategies**:

- Split into multiple classes (Single Responsibility Principle)
- Extract related methods into separate services
- Use composition over inheritance

---

## Architecture Metrics (100% Required)

### Class Coupling (Afferent/Efferent)

**Afferent Coupling (Ca)**: Number of classes that depend on this class
**Efferent Coupling (Ce)**: Number of classes this class depends on

**Thresholds**:

- **< 10**: Good
- **10-15**: Moderate
- **> 15**: High coupling, consider refactoring

**Example**:

```php
// High Efferent Coupling (Ce = 12)
class OrderService
{
    public function __construct(
        private CustomerRepository $customers,      // 1
        private ProductRepository $products,        // 2
        private InventoryService $inventory,        // 3
        private PricingService $pricing,            // 4
        private DiscountCalculator $discounts,      // 5
        private TaxCalculator $taxes,               // 6
        private ShippingCalculator $shipping,       // 7
        private PaymentGateway $payment,            // 8
        private EmailService $mailer,               // 9
        private LoggerInterface $logger,            // 10
        private EventDispatcher $events,            // 11
        private CacheInterface $cache,              // 12
    ) {}
}

// Reduced Coupling (Ce = 3) - Extract to domain services
class OrderService
{
    public function __construct(
        private OrderFactory $factory,              // 1 (encapsulates creation logic)
        private OrderRepository $repository,        // 2
        private DomainEventPublisher $events,       // 3
    ) {}
}
```

**Project Architecture Impact**:

- **Domain Layer**: Should have LOW efferent coupling (no external dependencies)
- **Application Layer**: Moderate coupling (depends on Domain + Infrastructure)
- **Infrastructure Layer**: Can have higher coupling (technical concerns)

---

### Depth of Inheritance

**What it measures**: Levels in the inheritance tree

**Thresholds**:

- **0-2**: Good (composition over inheritance)
- **3-4**: Acceptable
- **> 4**: Too deep, consider composition

**Project Context**:

```
AggregateRoot (1)
  └── Customer (2)  ✅ Good depth

AggregateRoot (1)
  └── Entity (2)
      └── Customer (3)
          └── PremiumCustomer (4)  ⚠️ Consider composition
```

**Prefer composition**:

```php
// ❌ Deep inheritance
class PremiumCustomer extends Customer extends Entity extends AggregateRoot { }

// ✅ Composition
class Customer extends AggregateRoot
{
    private MembershipLevel $membershipLevel;  // Composed
}
```

---

### Lack of Cohesion of Methods (LCOM)

**What it measures**: How well methods in a class work together

**Calculation**: Methods that don't share instance variables indicate low cohesion

**Thresholds**:

- **Low LCOM**: Good cohesion
- **High LCOM**: Poor cohesion, split class

**Example**:

```php
// High LCOM (low cohesion)
class UserManager
{
    private $db;
    private $cache;
    private $mailer;

    public function getUser($id) { /* uses $db only */ }
    public function cacheUser($data) { /* uses $cache only */ }
    public function sendEmail($to) { /* uses $mailer only */ }
}

// Low LCOM (high cohesion)
class User
{
    private $id;
    private $name;
    private $email;

    public function changeName($name) { /* uses $this->id, $this->name */ }
    public function changeEmail($email) { /* uses $this->id, $this->email */ }
    public function toArray() { /* uses $this->id, $this->name, $this->email */ }
}
```

---

## Style Metrics (100% Required)

### PSR-12 Compliance

**What it checks**: PHP Framework Interop Group coding standards

Key rules:

- 4 spaces for indentation (no tabs)
- Opening braces on new line for classes
- Opening braces on same line for methods
- One class per file
- Namespace declarations
- Visibility keywords on all properties/methods

**Auto-fixable**: Run `make phpcsfixer`

---

### Line Length

**Project limit**: 100 characters (configured in `phpinsights.php`)

**Exceptions**: Comments can be longer (`ignoreComments: true`)

**Reduction strategies**:

```php
// ❌ Too long (>100 chars)
$result = $this->repository->findBySpecification(new ActiveCustomersSpec(), new VipCustomersSpec(), new MinimumBalanceSpec(Money::fromFloat(1000)));

// ✅ Good (split across lines)
$result = $this->repository->findBySpecification(
    new ActiveCustomersSpec(),
    new VipCustomersSpec(),
    new MinimumBalanceSpec(Money::fromFloat(1000))
);
```

---

## Interpreting PHPInsights Output

### Score Calculation

Each metric contributes to the overall score:

```
[CODE] 100.0 pts       = (Total code checks passed / Total code checks) × 100
[COMPLEXITY] 94.2 pts  = (Complexity under threshold / Total functions) × 100
[ARCHITECTURE] 100 pts = (Architecture checks passed / Total arch checks) × 100
[STYLE] 100.0 pts      = (Style checks passed / Total style checks) × 100
```

### Reading Issue Reports

```
✗ [Complexity] 3 files with complexity issues

  src/Customer/Application/CommandHandler/UpdateCustomerCommandHandler.php
    Line 25: Method `__invoke` has cyclomatic complexity of 12 (max: 10)

  src/Shared/Domain/ValueObject/Email.php
    Line 15: Method `validate` has cyclomatic complexity of 8 (max: 5 for value objects)
```

**How to prioritize**:

1. Start with highest complexity violations
2. Focus on Application layer (should be simple)
3. Then tackle Domain layer violations
4. Style issues last (often auto-fixable)

---

## Metric Relationships

### Complexity affects Quality

High complexity typically correlates with:

- Longer methods
- More parameters
- Higher coupling
- Lower cohesion

**Strategy**: Reducing complexity often improves other metrics automatically

### Architecture affects Complexity

Proper layer separation reduces complexity:

- Domain contains complex business logic (acceptable)
- Application orchestrates simply (low complexity)
- Infrastructure handles technical concerns (moderate)

**Strategy**: Move logic to appropriate layer first, then reduce complexity

---

## Project-Specific Interpretation

### Domain Layer

**Expected metrics**:

- Cyclomatic complexity: 5-10 (business logic)
- Class length: 100-300 lines
- Coupling: Low efferent, variable afferent

**When it's OK to be complex**: Pure business logic with no external dependencies

### Application Layer

**Expected metrics**:

- Cyclomatic complexity: 1-3 (orchestration)
- Class length: 30-100 lines
- Coupling: Moderate (depends on Domain + Infrastructure)

**Never OK to be complex**: Extract to Domain immediately

### Infrastructure Layer

**Expected metrics**:

- Cyclomatic complexity: 1-5 (technical)
- Class length: 50-200 lines
- Coupling: Can be higher (technical integrations)

**When it's OK to be complex**: Complex query building, external API integration

---

## Quick Reference Table

| Metric                | Target | Warning  | Critical | Action                                      |
| --------------------- | ------ | -------- | -------- | ------------------------------------------- |
| Cyclomatic Complexity | 1-7    | 8-10     | 11+      | Extract methods, Strategy pattern           |
| NPath Complexity      | <200   | 200-1000 | 1000+    | Same as Cyclomatic + aggressive refactoring |
| Cognitive Complexity  | 1-5    | 6-10     | 11+      | Reduce nesting, early returns               |
| Method Length         | <50    | 50-100   | 100+     | Extract methods                             |
| Class Length          | <250   | 250-400  | 400+     | Split responsibilities                      |
| Class Coupling        | <10    | 10-15    | 15+      | Use interfaces, dependency injection        |
| Depth of Inheritance  | 0-2    | 3-4      | 5+       | Prefer composition                          |

---

**See Also**:

- [refactoring-strategies.md](../refactoring-strategies.md) - How to reduce complexity
- [troubleshooting.md](troubleshooting.md) - Common issues and fixes
- [PHPInsights Documentation](https://phpinsights.com/get-started/)
