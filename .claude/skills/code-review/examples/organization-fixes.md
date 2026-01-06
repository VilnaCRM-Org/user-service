# Code Organization Fix Examples

Real-world examples from PR code reviews showing how to apply the **code-organization** skill principles.

> **For complete rules, see the `code-organization` skill**
>
> Core principle: **"Directory X contains ONLY class type X"**

## PR Review Workflow for Organization Issues

When a reviewer comments on code organization:

1. **Identify the issue type** (wrong directory, vague naming, helper class, namespace mismatch)
2. **Consult `code-organization` skill** for the complete rules and decision tree
3. **Apply the fix** using examples below
4. **Commit separately** with reference to the review comment
5. **Verify** with `make phpcsfixer && make psalm && make deptrac && make unit-tests`

## Example 1: Class in Wrong Directory Type

### Review Comment

```text
UlidValidator should be in Validator/ directory, not Transformer/.
Transformers are for data transformation (DB <-> PHP), not validation.
```

### Fix

```bash
# Move file
mv src/Shared/Infrastructure/Transformer/UlidValidator.php \
   src/Shared/Infrastructure/Validator/UlidValidator.php

# Update namespace in moved file
# Update all imports: grep -r "use App\\Shared\\Infrastructure\\Transformer\\UlidValidator" src/ tests/

# Verify
make phpcsfixer && make psalm && make unit-tests
```

### Commit Message

```
Apply review suggestion: move UlidValidator to Validator/ directory

UlidValidator extends ConstraintValidator, so belongs in Validator/ not Transformer/.

Ref: https://github.com/owner/repo/pull/XX#discussion_rYYYYYYY
```

## Example 2: Vague Variable Names

### Review Comment

```text
Variable name `$converter` is too vague. What kind of converter?
Use specific names: `$typeConverter` for type conversion.
```

### Fix

```php
// Before: private UlidTypeConverter $converter;
// After:  private UlidTypeConverter $typeConverter;

// Update all usages in the class methods
```

### Commit Message

```
Apply review suggestion: rename $converter to $typeConverter

Makes variable name more specific per code-organization principles.

Ref: https://github.com/owner/repo/pull/XX#discussion_rYYYYYYY
```

## Example 3: Resolver in Wrong Directory

### Review Comment

```text
CustomerUpdateScalarResolver resolves values, not creates them.
Should be in Resolver/, not Factory/.
```

### Fix

```bash
# Move file
mv src/Core/Customer/Application/Factory/CustomerUpdateScalarResolver.php \
   src/Core/Customer/Application/Resolver/CustomerUpdateScalarResolver.php

# Update namespace and all imports
# Verify
make phpcsfixer && make psalm && make deptrac && make unit-tests
```

## Example 4: Helper Class Code Smell

### Review Comment

```text
`CustomerHelper` is a code smell. Extract specific responsibilities:
- Email validation → CustomerEmailValidator
- Name formatting → CustomerNameFormatter
- Data conversion → CustomerDataConverter
```

### Fix

1. Create specific classes in correct directories (Validator/, Formatter/, Converter/)
2. Update all usages to use new specific classes
3. Delete old Helper class
4. Run full test suite

### Commit Message

```
Apply review suggestion: split CustomerHelper into specific classes

Extracted responsibilities per code-organization principles:
- CustomerEmailValidator (Validator/)
- CustomerNameFormatter (Formatter/)
- CustomerDataConverter (Converter/)

Deleted CustomerHelper.php.

Ref: https://github.com/owner/repo/pull/XX#discussion_rYYYYYYY
```

## Verification After Any Fix

```bash
# ALWAYS run after organization changes:
make phpcsfixer  # Fix code style
make psalm       # Catch namespace/import issues
make deptrac     # Verify architecture compliance
make unit-tests  # Ensure tests pass
make ci          # Full CI check before pushing
```

## Quick Reference

**For detailed rules, decision tree, and verification checklist:**
👉 See the `code-organization` skill

**Common organization issues:**

- Class in wrong directory → Consult directory type table in `code-organization`
- Vague naming → Use specific names per `code-organization` checklist
- Helper/Util classes → Extract responsibilities per `code-organization` patterns
- Namespace mismatch → Must match directory structure exactly

**Related skills:**

- `code-organization` - Complete rules and patterns
- `deptrac-fixer` - Fix layer boundary violations
- `implementing-ddd-architecture` - DDD naming and structure
