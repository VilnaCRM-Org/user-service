# Code Organization Fix Examples

Real-world examples of code organization issues found in code reviews and how to fix them.

## Example 1: Class in Wrong Directory Type

### Review Comment

```text
UlidValidator should be in Validator/ directory, not Transformer/.
Transformers are for data transformation (DB <-> PHP), not validation.
```

### Before

```php
// File: src/Shared/Infrastructure/Transformer/UlidValidator.php
namespace App\Shared\Infrastructure\Transformer;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class UlidValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        // Validation logic
    }
}
```

### After

```php
// File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class UlidValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        // Validation logic
    }
}
```

### Fix Commands

```bash
# Move file
mv src/Shared/Infrastructure/Transformer/UlidValidator.php \
   src/Shared/Infrastructure/Validator/UlidValidator.php

# Find all imports to update
grep -r "use App\\Shared\\Infrastructure\\Transformer\\UlidValidator" src/ tests/

# Update all references
# (use IDE refactoring or manual update)

# Verify
make phpcsfixer
make psalm
make unit-tests
```

## Example 2: Vague Variable Names

### Review Comment

```text
Variable name `$converter` is too vague. What kind of converter?
Use specific names: `$typeConverter` for type conversion.
```

### Before

```php
final readonly class UlidType extends Type
{
    public function __construct(
        private UlidTypeConverter $converter,  // ❌ Too vague
    ) {
    }

    public function convertToPHPValue(mixed $value): ?Ulid
    {
        return $this->converter->toUlid($value);
    }
}
```

### After

```php
final readonly class UlidType extends Type
{
    public function __construct(
        private UlidTypeConverter $typeConverter,  // ✅ Specific
    ) {
    }

    public function convertToPHPValue(mixed $value): ?Ulid
    {
        return $this->typeConverter->toUlid($value);
    }
}
```

### Fix Commands

```bash
# Update variable name in class
# Update all usages in methods
# Verify with static analysis
make psalm
make unit-tests
```

## Example 3: Resolver in Wrong Directory

### Review Comment

```text
CustomerUpdateScalarResolver resolves values, not creates them.
Should be in Resolver/, not Factory/.
```

### Before

```php
// File: src/Core/Customer/Application/Factory/CustomerUpdateScalarResolver.php
namespace App\Core\Customer\Application\Factory;

final readonly class CustomerUpdateScalarResolver
{
    public function resolveScalarValue(string $key, mixed $value): mixed
    {
        // Resolution logic
    }
}
```

### After

```php
// File: src/Core/Customer/Application/Resolver/CustomerUpdateScalarResolver.php
namespace App\Core\Customer\Application\Resolver;

final readonly class CustomerUpdateScalarResolver
{
    public function resolveScalarValue(string $key, mixed $value): mixed
    {
        // Resolution logic
    }
}
```

### Fix Commands

```bash
# Move file
mv src/Core/Customer/Application/Factory/CustomerUpdateScalarResolver.php \
   src/Core/Customer/Application/Resolver/CustomerUpdateScalarResolver.php

# Update namespace in file
# Update imports in:
# - src/Core/Customer/Application/Factory/CustomerUpdateFactory.php
# - tests/Unit/Core/Customer/Application/Resolver/CustomerUpdateScalarResolverTest.php

# Verify
make phpcsfixer
make psalm
make deptrac
make unit-tests
```

## Example 4: Misleading Parameter Names

### Review Comment

```text
Parameter named `$binary` but accepts `mixed` type.
Use accurate name like `$value` to match actual type.
```

### Before

```php
public function fromBinary(mixed $binary): Ulid  // ❌ Misleading
{
    if ($binary === null) {
        throw ConversionException::conversionFailed($binary, 'ulid');
    }

    return Ulid::fromBinary((string) $binary);
}
```

### After

```php
public function fromBinary(mixed $value): Ulid  // ✅ Accurate
{
    if ($value === null) {
        throw ConversionException::conversionFailed($value, 'ulid');
    }

    return Ulid::fromBinary((string) $value);
}
```

## Example 5: Helper Class Code Smell

### Review Comment

```text
`CustomerHelper` is a code smell. Extract specific responsibilities:
- Email validation → CustomerEmailValidator
- Name formatting → CustomerNameFormatter
- Data conversion → CustomerDataConverter
```

### Before

```php
// File: src/Core/Customer/Application/Helper/CustomerHelper.php
namespace App\Core\Customer\Application\Helper;

final class CustomerHelper
{
    public function validateEmail(string $email): bool { }
    public function formatName(string $name): string { }
    public function convertToArray(Customer $customer): array { }
}
```

### After (Multiple Specific Classes)

```php
// File: src/Core/Customer/Application/Validator/CustomerEmailValidator.php
namespace App\Core\Customer\Application\Validator;

final class CustomerEmailValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void { }
}

// File: src/Core/Customer/Application/Formatter/CustomerNameFormatter.php
namespace App\Core\Customer\Application\Formatter;

final readonly class CustomerNameFormatter
{
    public function format(string $name): string { }
}

// File: src/Core/Customer/Application/Converter/CustomerArrayConverter.php
namespace App\Core\Customer\Application\Converter;

final readonly class CustomerArrayConverter
{
    public function toArray(Customer $customer): array { }
}
```

## Example 6: Namespace Mismatch

### Review Comment

```text
Namespace doesn't match directory structure.
File is in Validator/ but namespace says Transformer/.
```

### Before

```php
// File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Transformer;  // ❌ Wrong!

final class UlidValidator extends ConstraintValidator
{
}
```

### After

```php
// File: src/Shared/Infrastructure/Validator/UlidValidator.php
namespace App\Shared\Infrastructure\Validator;  // ✅ Correct!

final class UlidValidator extends ConstraintValidator
{
}
```

## Verification After Fixes

After applying any organization fix:

```bash
# 1. Code style
make phpcsfixer

# 2. Static analysis (catches namespace/import issues)
make psalm

# 3. Architecture compliance
make deptrac

# 4. Tests still pass
make unit-tests
make integration-tests

# 5. Full CI check
make ci  # Must show "✅ CI checks successfully passed!"
```

## Decision Tree: Where Does It Belong?

```text
What does the class DO?

├─ Converts between types (string ↔ object)? → Converter/
├─ Transforms for DB/serialization? → Transformer/
├─ Validates values? → Validator/
├─ Builds/constructs objects? → Builder/
├─ Fixes/modifies data? → Fixer/
├─ Cleans/filters data? → Cleaner/
├─ Creates complex objects? → Factory/
├─ Resolves/determines values? → Resolver/
├─ Normalizes/serializes? → Serializer/
└─ Something else? → Define specific responsibility!
```

## Common Patterns

### Pattern: Moving Class to Correct Directory

```bash
# 1. Move file
mv src/Path/OldDir/ClassName.php src/Path/NewDir/ClassName.php

# 2. Update namespace in file
sed -i 's|namespace App\\Path\\OldDir|namespace App\\Path\\NewDir|' \
    src/Path/NewDir/ClassName.php

# 3. Find and update all imports
grep -r "use App\\Path\\OldDir\\ClassName" src/ tests/
# Update each file manually or with sed

# 4. Verify
make phpcsfixer && make psalm && make unit-tests
```

### Pattern: Renaming Variable to Be Specific

```bash
# 1. Rename in constructor parameter
# 2. Rename in property
# 3. Rename in all method usages
# 4. Update tests

# Example:
# $converter → $typeConverter
# $resolver → $scalarResolver
# $transformer → $relationTransformer
```

### Pattern: Splitting Helper Class

```bash
# 1. Identify distinct responsibilities
# 2. Create specific class for each (in correct directory)
# 3. Update all usages to use new specific classes
# 4. Delete old Helper class
# 5. Run full test suite
```
