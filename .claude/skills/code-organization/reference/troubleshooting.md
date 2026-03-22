# Troubleshooting Code Organization Issues

Common problems and solutions when organizing code.

## Problem 1: "I don't know where my class belongs"

### Symptoms

- Class has vague name like `Helper`, `Utils`, `Manager`
- Unsure which directory to put it in
- Class seems to do multiple things

### Solution

1. **Identify primary responsibility**:

   ```text
   What is the ONE thing this class does?
   - Creates objects → Factory
   - Validates data → Validator
   - Converts types → Converter
   - Etc.
   ```

2. **If it does multiple things, split it**:

   ```php
   // ❌ Bad: One class doing everything
   class CustomerHelper
   {
       public function validate() { }
       public function convert() { }
       public function create() { }
   }

   // ✅ Good: Split into focused classes
   class CustomerValidator { public function validate() { } }
   class CustomerConverter { public function convert() { } }
   class CustomerFactory { public function create() { } }
   ```

3. **Use the decision tree** from [common-patterns.md](common-patterns.md)

---

## Problem 2: "Namespace doesn't match directory"

### Symptoms

- Psalm errors about class not found
- IDE shows wrong namespace
- Class imports fail

### Example

```php
// ❌ File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Transformer;  // WRONG!

final class UlidValidator { }
```

### Solution

Update namespace to match directory:

```php
// ✅ File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Validator;  // CORRECT!

final class UlidValidator { }
```

**Commands**:

```bash
# Check for mismatches
grep -r "^namespace" src/ --include="*.php" | grep -v "Tests"

# Fix automatically with IDE refactoring or:
make phpcsfixer
```

---

## Problem 3: "Class is in wrong directory"

### Symptoms

- Class name ends with "Validator" but is in `Transformer/` directory
- Code review feedback: "This belongs in X/"
- Directory name doesn't match class responsibility

### Example

```php
// ❌ File: src/Shared/Infrastructure/Transformer/UlidValidator.php
final class UlidValidator { }  // It's a VALIDATOR, not a Transformer!
```

### Solution

1. **Move the file**:

   ```bash
   mv src/Shared/Infrastructure/Transformer/UlidValidator.php \
      src/Shared/Infrastructure/Validator/UlidValidator.php
   ```

2. **Update namespace**:

   ```php
   // Change from:
   namespace App\Shared\Infrastructure\Transformer;

   // To:
   namespace App\Shared\Infrastructure\Validator;
   ```

3. **Find all usages**:

   ```bash
   grep -r "use.*Transformer\\UlidValidator" src/ tests/
   ```

4. **Update imports** in all files:

   ```php
   // Change from:
   use App\Shared\Infrastructure\Transformer\UlidValidator;

   // To:
   use App\Shared\Infrastructure\Validator\UlidValidator;
   ```

5. **Move test file**:

   ```bash
   mv tests/Unit/Shared/Infrastructure/Transformer/UlidValidatorTest.php \
      tests/Unit/Shared/Infrastructure/Validator/UlidValidatorTest.php
   ```

6. **Update test namespace**:

   ```php
   namespace Tests\Unit\Shared\Infrastructure\Validator;
   ```

7. **Run quality checks**:
   ```bash
   make phpcsfixer
   make psalm
   make unit-tests
   ```

---

## Problem 4: "Variable name is too vague"

### Symptoms

- Variables named `$converter`, `$resolver`, `$data`
- Not clear what they convert/resolve/contain
- Code review feedback about naming

### Example

```php
// ❌ Vague
private UlidTypeConverter $converter;      // Converter of what?
private CustomerUpdateScalarResolver $resolver;  // Resolver of what?
private array $data;  // What data?
```

### Solution

Make names specific:

```php
// ✅ Specific
private UlidTypeConverter $typeConverter;
private CustomerUpdateScalarResolver $scalarResolver;
private array $customerData;
```

**Search and replace**:

```bash
# Find vague names
grep -r "private.*\$converter;" src/
grep -r "private.*\$resolver;" src/
grep -r "private.*\$data;" src/

# Use IDE refactoring to rename
```

---

## Problem 5: "Parameter name misleading"

### Symptoms

- Parameter named `$binary` but accepts `mixed`
- Parameter named `$string` but accepts `mixed`
- Type hint doesn't match parameter name

### Example

```php
// ❌ Misleading
public function fromBinary(mixed $binary): Ulid  // Accepts any type, not just binary!
{
    if (is_string($binary)) { /* ... */ }
    if ($binary instanceof Ulid) { /* ... */ }
    // Handles multiple types, not just binary
}
```

### Solution

Use generic name when accepting multiple types:

```php
// ✅ Accurate
public function fromBinary(mixed $value): Ulid  // Accurate: accepts any type
{
    if (is_string($value)) { /* ... */ }
    if ($value instanceof Ulid) { /* ... */ }
}
```

---

## Problem 6: "Default instantiation in constructor"

### Symptoms

- Constructor has optional parameters with `null` default
- Default instantiation using `??` operator
- Hard to test with mocks
- Psalm errors about hidden dependencies

### Example

```php
// ❌ Default instantiation
public function __construct(
    ?SomeDependency $dependency = null
) {
    $this->dependency = $dependency ?? new SomeDependency();
}
```

### Solution

Make dependencies required and inject them:

```php
// ✅ Required injection
public function __construct(
    private SomeDependency $dependency
) {
}
```

**Configure in services.yaml**:

```yaml
services:
  App\Some\Class:
    arguments:
      $dependency: '@App\Some\SomeDependency'
```

---

## Problem 7: "Static methods hard to test"

### Symptoms

- Methods defined as `static`
- Can't mock in tests
- Tight coupling
- Code review feedback about testability

### Example

```php
// ❌ Static method
final class PathsMapper
{
    public static function map(OpenApi $openApi, callable $callback): OpenApi
    {
        // ...
    }
}
```

### Solution

Convert to instance methods:

```php
// ✅ Instance method
final class PathsMapper
{
    public function map(OpenApi $openApi, callable $callback): OpenApi
    {
        // ...
    }
}
```

**Update usage**:

```php
// Before
$result = PathsMapper::map($openApi, $callback);

// After
$mapper = new PathsMapper();
$result = $mapper->map($openApi, $callback);

// Or inject in constructor
public function __construct(private PathsMapper $mapper) {}
$result = $this->mapper->map($openApi, $callback);
```

---

## Problem 8: "Not using constructor property promotion"

### Symptoms

- Properties declared separately
- Assignment in constructor body
- More boilerplate code
- Code review feedback

### Example

```php
// ❌ Old style
final class CustomerFactory
{
    private CustomerValidator $validator;
    private CustomerTransformer $transformer;

    public function __construct(
        CustomerValidator $validator,
        CustomerTransformer $transformer
    ) {
        $this->validator = $validator;
        $this->transformer = $transformer;
    }
}
```

### Solution

Use constructor property promotion:

```php
// ✅ Modern style
final readonly class CustomerFactory
{
    public function __construct(
        private CustomerValidator $validator,
        private CustomerTransformer $transformer,
    ) {
    }
}
```

---

## Problem 9: "Tests failing after moving class"

### Symptoms

- Tests pass locally but fail in CI
- Class not found errors
- Namespace errors in tests

### Checklist

- [ ] Updated class file location
- [ ] Updated class namespace
- [ ] Updated all imports in src/
- [ ] Updated all imports in tests/
- [ ] Moved test file to match new location
- [ ] Updated test file namespace
- [ ] Ran `make phpcsfixer`
- [ ] Ran `make psalm`
- [ ] Ran `make unit-tests`

### Common missed steps

1. **Test file not moved**:

   ```bash
   # Ensure test file mirrors source structure
   src/Shared/Infrastructure/Validator/UlidValidator.php
   tests/Unit/Shared/Infrastructure/Validator/UlidValidatorTest.php
   ```

2. **Test namespace not updated**:

   ```php
   // Update from:
   namespace Tests\Unit\Shared\Infrastructure\Transformer;

   // To:
   namespace Tests\Unit\Shared\Infrastructure\Validator;
   ```

3. **Imports in test not updated**:

   ```php
   // Update from:
   use App\Shared\Infrastructure\Transformer\UlidValidator;

   // To:
   use App\Shared\Infrastructure\Validator\UlidValidator;
   ```

---

## Problem 10: "PHPInsights complexity too high"

### Symptoms

- PHPInsights reports complexity score below 93%
- Methods are too complex
- Too many branches/conditions

### Solution

1. **Run PHPMD to identify complex methods**:

   ```bash
   make phpmd
   ```

2. **Extract methods**:

   ```php
   // ❌ Complex method (complexity: 10)
   public function validate($value): bool
   {
       if ($value === null) return false;
       if (!is_string($value)) return false;
       if (strlen($value) < 8) return false;
       if (strlen($value) > 64) return false;
       if (!preg_match('/[A-Z]/', $value)) return false;
       if (!preg_match('/[0-9]/', $value)) return false;
       return true;
   }

   // ✅ Reduced complexity (complexity: 3)
   public function validate($value): bool
   {
       if (!$this->hasValidType($value)) return false;
       if (!$this->hasValidLength($value)) return false;
       if (!$this->hasRequiredCharacters($value)) return false;
       return true;
   }

   private function hasValidType($value): bool { /* ... */ }
   private function hasValidLength($value): bool { /* ... */ }
   private function hasRequiredCharacters($value): bool { /* ... */ }
   ```

3. **Use strategy pattern** for complex conditionals:

   ```php
   // Extract validation rules into separate classes
   interface ValidationRule
   {
       public function validate($value): bool;
   }

   class LengthValidationRule implements ValidationRule { /* ... */ }
   class CharacterValidationRule implements ValidationRule { /* ... */ }
   ```

---

## Problem 11: "Circular dependencies"

### Symptoms

- Class A depends on B, B depends on A
- Service configuration errors
- Hard to test

### Example

```php
// ❌ Circular dependency
class A {
    public function __construct(private B $b) {}
}

class B {
    public function __construct(private A $a) {}
}
```

### Solution

1. **Extract interface**:

   ```php
   interface AInterface { }
   interface BInterface { }

   class A implements AInterface {
       public function __construct(private BInterface $b) {}
   }

   class B implements BInterface {
       public function __construct(private AInterface $a) {}
   }
   ```

2. **Use event system**:

   ```php
   // Instead of direct dependency, use events
   class A {
       public function __construct(private EventDispatcher $dispatcher) {}

       public function doSomething() {
           $this->dispatcher->dispatch(new SomethingHappened());
       }
   }

   class B {
       // Listens to SomethingHappened event
   }
   ```

3. **Refactor responsibilities**:
   - Often circular dependencies indicate wrong responsibilities
   - Consider extracting shared logic into a third class

---

## Quick Diagnostic Commands

### Check namespace consistency

```bash
# Find files where namespace doesn't match path
find src/ -name "*.php" -exec sh -c 'grep "^namespace" {} | grep -v "$(echo {} | sed "s|src/||" | sed "s|\.php||" | sed "s|/|\\\\|g" | sed "s|^|App\\\\|")"' \;
```

### Find vague class names

```bash
grep -r "class.*Helper" src/
grep -r "class.*Utils" src/
grep -r "class.*Manager" src/
```

### Find classes with default instantiation

```bash
grep -r "= new " src/ | grep "public function __construct"
grep -r "?? new" src/
```

### Find static methods (excluding named constructors)

```bash
grep -r "public static function" src/ | grep -v "create" | grep -v "from"
```

### Check test coverage for moved class

```bash
# Find test file for a class
CLASS="CustomerUpdateScalarResolver"
find tests/ -name "*${CLASS}Test.php"
```

---

## Prevention Tips

### Before creating a new class

1. **Choose a specific name**: Not `Helper`, `Utils`, `Manager`
2. **Identify ONE responsibility**: What does it do?
3. **Determine correct directory**: Use decision tree
4. **Ensure namespace will match**: Plan directory structure
5. **Plan dependencies**: What will it need? Will they be injected?

### Before moving a class

1. **Backup or use git**: Easy to revert if needed
2. **Find all usages first**: `grep -r "ClassName" src/ tests/`
3. **Update test file too**: Don't forget the test!
4. **Run checks after each step**: phpcsfixer, psalm, tests
5. **Commit separately**: Makes it easy to track changes

### Code review checklist

- [ ] Class in correct directory for its type?
- [ ] Namespace matches directory?
- [ ] Class name specific and clear?
- [ ] Variable names specific?
- [ ] No default instantiation in constructors?
- [ ] No static methods (except named constructors)?
- [ ] Constructor property promotion used?
- [ ] Test file location matches source file?
- [ ] All quality checks pass?
