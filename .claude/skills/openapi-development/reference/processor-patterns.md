# Processor Patterns for OpenAPI Development

Key patterns and techniques for maintaining low complexity in OpenAPI transformations (sanitizers/augmenters/cleaners/factories).

## 1. Constants for HTTP Operations

**Problem**: Repeating `withGet/withPost/withPut/withPatch/withDelete` creates duplication.

**Solution**: Use an operations constant + loop.

```php
private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

private function processPathItem(PathItem $pathItem): PathItem
{
    foreach (self::OPERATIONS as $operation) {
        $pathItem = $pathItem->{'with' . $operation}(
            $this->processOperation($pathItem->{'get' . $operation}())
        );
    }

    return $pathItem;
}
```

## 2. Guard Clauses (Early Returns)

**Problem**: Nested conditionals (`if` inside `if`) quickly become hard to read.

**Solution**: Use clear guard clauses + early returns.

```php
private function sanitizeOperation(?Operation $operation): ?Operation
{
    if ($operation === null) {
        return null;
    }

    if (!\is_array($operation->getParameters())) {
        return $operation;
    }

    return $operation->withParameters(
        array_map(
            fn (mixed $parameter) => $this->parameterCleaner->clean($parameter),
            $operation->getParameters()
        )
    );
}
```

## 3. Functional Array Operations

Prefer:

- `array_keys($paths->getPaths())` to iterate a stable list of paths
- `array_map` for element transforms
- `array_filter` for predicate filtering

Avoid nested loops with mutable intermediate arrays when a map/filter works.

## 4. Keep Methods Small

Common thresholds in this repo:

- Method length: ~20 lines
- Cyclomatic complexity per method: <= 10

When a method grows, extract helpers that:

- Take explicit inputs
- Return transformed output
- Do not mutate external state

## 5. Avoid `empty()`

Use explicit checks for type safety and to satisfy PHPInsights rules.

- Arrays: `$array === []`
- Strings: `$value === ''` (and handle `null` explicitly)
