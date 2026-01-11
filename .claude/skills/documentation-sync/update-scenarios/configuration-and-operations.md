# Configuration and Operations Documentation

## Configuration Changes

**When adding environment variables**:

**Update**: `docs/advanced-configuration.md`

````markdown
### NEW_CONFIG_OPTION

**Type**: String
**Default**: `default_value`
**Required**: No

Description of what this option does.

\```bash
NEW_CONFIG_OPTION=custom_value
\```

**Validation**: Must be between 8-64 characters.
````

**Update**: `docs/getting-started.md` if required for basic setup

## Security Changes

**When modifying auth/authorization**:

**Update**: `docs/security.md`

1. Auth flows
2. Permission changes
3. Security considerations

**Update**: `docs/api-endpoints.md` with updated auth requirements

## Testing Strategy Changes

**When adding new test types**:

**Update**: `docs/testing.md`

```markdown
### New Test Type

**Command**: \`make new-test-type\`
**Location**: \`tests/NewType/\`
**Purpose**: Description of test purpose
```

## Performance Optimizations

**When making performance improvements**:

**Update**: `docs/performance.md`

```markdown
### Optimization Name

**Impact**:

- Metric 1: Before → After
- Metric 2: Before → After

**Configuration**: Required configuration changes
```
