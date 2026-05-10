# Structured Logging Patterns

Guide to implementing structured logging for debugging and correlation. This complements business metrics by providing detailed context for troubleshooting.

## Why Structured Logging?

**Traditional logging** (string concatenation):

```php
// Bad - Unstructured, hard to parse, and may expose PII
$logger->info("Creating user " . $userId . " with email " . $email);
```

**Structured logging** (PSR-3 context array):

> Arrays are fine here because PSR-3 requires `array $context`; for business metrics use typed metric/dimension objects and emit them from event subscribers.

```php
// Good - Structured, searchable, PII-free
$logger->info('Creating user', [
    'event_id' => $eventId,          // Non-PII correlation ID
    'operation' => 'user.create', // Operation type
]);
```

**Benefits**:

- **Searchable**: Query by specific fields
- **Parseable**: JSON format for log aggregators
- **Contextual**: Rich metadata for debugging
- **Traceable**: Correlation ID connects related logs

---

## Log Levels

Use appropriate log levels following PSR-3:

| Level       | When to Use               | Example                              |
| ----------- | ------------------------- | ------------------------------------ |
| **debug**   | Detailed diagnostic info  | Variable values, method entry/exit   |
| **info**    | Important business events | User created, order placed           |
| **warning** | Non-critical issues       | Email failed (retryable), cache miss |
| **error**   | Critical failures         | Database down, API call failed       |

### Examples

```php
// DEBUG: Detailed execution flow (avoid PII in debug logs too)
$this->logger->debug('Entering method', [
    'method' => __METHOD__,
    'has_id' => isset($userId),  // Boolean flag, not actual ID
]);

// INFO: Business event (PII-free)
$this->logger->info('User created', [
    'event_id' => $event->eventId(),   // Correlation ID (non-PII)
    'event_type' => 'user.created',
]);

// WARNING: Recoverable issue
$this->logger->warning('Cache miss, fetching from database', [
    'cache_key' => $key,
]);

// ERROR: Critical failure
$this->logger->error('Database connection failed', [
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

---

## Essential Context Fields

### Event Correlation (PII-Free)

```php
// IMPORTANT: Do NOT log entity IDs (user_id, order_id) - they are PII
// Use event_id for correlation instead (GDPR/SOC2 compliant)
$this->logger->info('Processing event', [
    'event_id' => $event->eventId(),   // Correlation identifier (non-PII)
    'event_type' => $event::class,     // Event class for filtering
]);
```

### Operation Context

```php
$this->logger->info('Database operation', [
    'operation' => 'mongodb.save',     // Operation type
    'collection' => 'users',       // Target resource
]);
```

### Error Context

```php
$this->logger->error('Operation failed', [
    'error_type' => get_class($e),
    'error_message' => $e->getMessage(),
    'error_file' => $e->getFile(),
    'error_line' => $e->getLine(),
    'trace' => $e->getTraceAsString(),
]);
```

---

## Logging in Command Handlers

Business metrics should NOT be emitted in command handlers (application layer). Publish domain events from the handler and emit metrics in dedicated event subscribers (see [Metrics Patterns](metrics-patterns.md)).

```php
final readonly class CreateUserCommandHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepositoryInterface $repository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(CreateUserCommand $command): void
    {
        // Log command start (PII-free - no user_id)
        $this->logger->info('Processing command', [
            'command' => get_class($command),
            'operation' => 'user.create',
        ]);

        try {
            // Execute operation
            $user = $this->createUser($command);
            $this->repository->save($user);

            // Log success (PII-free)
            $this->logger->info('Command processed successfully', [
                'command' => get_class($command),
                'operation' => 'user.create',
            ]);

            // Publish domain event - metrics subscriber will emit metrics
            $this->eventBus->publish(new UserCreatedEvent(
                $user->id(),
                $user->email()
            ));

        } catch (\Throwable $e) {
            // Log error
            $this->logger->error('Command processing failed', [
                'command' => get_class($command),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
```

---

## Data Sanitization

**CRITICAL**: Never log sensitive data.

### Sanitize Before Logging

```php
// NEVER log sensitive data
$this->logger->info('User authenticated', [
    'username' => $username,
    'password' => $password,              // Security violation!
    'credit_card' => $creditCard,         // PCI compliance violation!
]);

// Log with sanitization
$this->logger->info('User authenticated', [
    'username' => $username,
    'password_length' => strlen($password), // Metadata only
    'has_mfa' => $hasMfa,                   // Boolean safe
]);

// Mask sensitive fields
$this->logger->info('Payment processed', [
    'card_last_four' => substr($cardNumber, -4), // Partial data
    'amount' => $amount,                         // Non-sensitive
]);
```

### Sensitive Data Types to Avoid

| Type           | Examples             | Safe Alternative           |
| -------------- | -------------------- | -------------------------- |
| **Entity IDs** | user_id, order_id    | event_id (correlation ID)  |
| Passwords      | Plain text passwords | Password length, hash type |
| Tokens         | API keys, JWT tokens | Token prefix, expiry time  |
| Credit Cards   | Full card numbers    | Last 4 digits              |
| SSN/Tax IDs    | Full identifiers     | Last 4 digits              |
| API Keys       | Secret keys          | Key ID, key type           |
| Personal Email | Full addresses       | Email domain               |

> **IMPORTANT**: Entity IDs (user_id, order_id, user_id) are PII under GDPR/SOC2. Always use event_id for log correlation instead.

---

## Symfony/Monolog Integration

### Inject Logger

```php
use Psr\Log\LoggerInterface;

final readonly class MyService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
}
```

### JSON Formatter for Production

```yaml
# config/packages/prod/monolog.yaml
monolog:
  handlers:
    main:
      type: stream
      path: '%kernel.logs_dir%/%kernel.environment%.log'
      level: info
      formatter: 'monolog.formatter.json'
```

---

## Common Pitfalls

### Don't: String Concatenation

```php
$this->logger->info("User $userId created with email $email");
```

### Do: Structured Context (PII-Free)

```php
$this->logger->info('User created', [
    'event_id' => $eventId,           // Correlation ID (non-PII)
    'operation' => 'user.create', // Operation type
]);
```

---

### Don't: Log Inside Loops

```php
foreach ($users as $user) {
    $this->logger->debug('Processing user', ['id' => $user->id()]);
    // ... process
}
```

### Do: Log Once with Summary

```php
$this->logger->info('Processing users', ['count' => count($users)]);
foreach ($users as $user) {
    // ... process (no logging)
}
$this->logger->info('Users processed', ['count' => count($users)]);
```

---

## Logging vs Business Metrics

| Use Logging For   | Use Business Metrics For        |
| ----------------- | ------------------------------- |
| Debugging context | Business KPIs                   |
| Error details     | Domain event counts             |
| Operation flow    | Business values (order amounts) |
| Troubleshooting   | CloudWatch dashboards           |

Both complement each other:

- **Logs**: Detailed context for debugging specific issues
- **Metrics**: Aggregated counts for business intelligence

---

## Success Checklist

- ✅ All logs use structured arrays (not strings)
- ✅ Appropriate log levels used (debug, info, warning, error)
- ✅ **No PII logged** (no user_id, order_id, user_id, email)
- ✅ Use event_id for correlation (not entity IDs)
- ✅ Errors include full context and stack trace
- ✅ Operation type clearly identified

---

**Next Steps**:

- [Metrics Patterns](metrics-patterns.md) - Add business metrics with AWS EMF
- [Complete Example](../examples/instrumented-command-handler.md) - See full implementation
