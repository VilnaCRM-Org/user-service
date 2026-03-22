# API Platform CRUD Troubleshooting Guide

## Common Issues and Solutions

### 1. Resource Not Found in API

**Symptom**: Entity doesn't appear at `/api/docs`

**Causes & Solutions**:

1. **Resource directory not registered**:

```yaml
# config/packages/api_platform.yaml
api_platform:
  resource_class_directories:
    - '%kernel.project_dir%/src/Core/Customer/Domain/Entity'
    - '%kernel.project_dir%/src/Core/NewContext/Domain/Entity' # Add this
```

2. **YAML configuration syntax error**:

```bash
php bin/console cache:clear
# Check for YAML syntax errors in output
```

3. **Missing resource configuration file**:

```bash
ls config/api_platform/resources/
# Ensure {entity}.yaml exists
```

### 2. 500 Error on POST/PUT/PATCH

**Symptom**: Internal server error on write operations

**Common Causes**:

1. **Processor not found**:

```yaml
# Ensure processor class path is correct
processor: App\Core\Context\Application\Processor\CreateEntityProcessor
```

2. **Processor not implementing correct interface**:

```php
use ApiPlatform\State\ProcessorInterface;

/**
 * @implements ProcessorInterface<InputDTO, OutputEntity>
 */
final readonly class CreateEntityProcessor implements ProcessorInterface
{
    // process() method must exist
}
```

3. **Missing service autowiring**:

```bash
make cache-clear
# Services should auto-wire, check for syntax errors
```

### 3. Empty Request Body in Swagger

**Symptom**: Swagger UI shows empty request body schema

**Solution**: Ensure DTO has public properties with serialization groups:

```php
final readonly class EntityCreate
{
    public function __construct(
        public ?string $name = null,  // Must be public
    ) {
    }
}
```

And validation configuration exists:

```yaml
# config/validator/Entity.yaml
App\Core\Context\Application\DTO\EntityCreate:
  properties:
    name:
      - NotBlank: ~
```

### 4. IRI Resolution Fails

**Symptom**: `Resource not found` when passing IRI string like `/api/entity_types/01ARZ3NDEKTSV4RRFFQ69G5FAV`

**Solutions**:

1. **Ensure entity exists**:

```bash
# Check database
```

2. **Correct IRI format**:

```php
// Processor
/** @var EntityType $type */
$type = $this->iriConverter->getResourceFromIri($data->type);
```

3. **Check resource configuration** - referenced entity must be an API resource:

```yaml
# config/api_platform/resources/entity_type.yaml
App\Core\Context\Domain\Entity\EntityType:
  shortName: 'EntityType'
  operations:
    ApiPlatform\Metadata\Get: ~
```

### 5. Validation Not Working

**Symptom**: Invalid data passes through without errors

**Solutions**:

1. **Check validation file exists**:

```bash
ls config/validator/
```

2. **Namespace matches DTO class**:

```yaml
App\Core\Context\Application\DTO\EntityCreate: # Exact namespace
  properties:
    name:
      - NotBlank: ~
```

3. **Clear cache**:

```bash
make cache-clear
```

### 6. Serialization Issues

**Symptom**: Properties missing from response or request not deserializing correctly

**Solutions**:

1. **Check serialization groups**:

```yaml
# config/serialization/Entity.yaml
App\Core\Context\Domain\Entity\Entity:
  attributes:
    name:
      groups: ['output'] # Must include 'output' for GET responses
```

2. **Ensure normalization context set**:

```yaml
# config/api_platform/resources/entity.yaml
normalizationContext:
  groups: ['output']
```

3. **Verify property visibility**:

```php
// Entity getters must be public
public function getName(): string { return $this->name; }
```

### 7. Filters Not Applying

**Symptom**: Filter parameters in URL have no effect

**Solutions**:

1. **Filter registered correctly**:

```yaml
# config/services.yaml
app.entity.mongodb.search_filter:
  tags:
    - { name: 'api_platform.filter', id: 'entity.mongodb.search' } # Correct ID
```

2. **Filter applied to operation**:

```yaml
# config/api_platform/resources/entity.yaml
ApiPlatform\Metadata\GetCollection:
  filters:
    - entity.mongodb.search # Matches filter ID
```

3. **Property name matches entity**:

```yaml
arguments:
  - name: 'exact' # Must match entity property name
```

### 8. Command Handler Not Called

**Symptom**: Processor runs but entity not persisted

**Solutions**:

1. **Handler tagged correctly** (should auto-tag):

```yaml
# config/services.yaml
_instanceof:
  App\Shared\Domain\Bus\Command\CommandHandlerInterface:
    tags: ['app.command_handler']
```

2. **Command dispatched properly**:

```php
$this->commandBus->dispatch(new CreateEntityCommand($entity));
```

3. **Handler \_\_invoke method exists**:

```php
public function __invoke(CreateEntityCommand $command): void
{
    $this->repository->save($command->getEntity());
}
```

### 9. Deptrac Violations After Adding CRUD

**Symptom**: `make deptrac` reports violations

**Common Violations**:

1. **DTO with Symfony validation attributes** → Move validation to YAML config
2. **Entity with API Platform attributes** → Use YAML resource config
3. **Entity with Doctrine annotations** → Use XML mapping

See [deptrac-fixer skill](../../deptrac-fixer/SKILL.md) for fixes.

### 10. GraphQL Mutations Not Working

**Symptom**: GraphQL mutation returns null or error

**Solutions**:

1. **Resolver registered**:

```yaml
ApiPlatform\Metadata\GraphQl\Mutation:
  name: create
  resolver: App\Core\Context\Application\Resolver\CreateEntityMutationResolver
  deserialize: false
```

2. **Resolver implements correct interface**:

```php
use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;

final class CreateEntityMutationResolver implements MutationResolverInterface
{
    public function __invoke(?object $item, array $context): object
    {
        // Return entity
    }
}
```

3. **extraArgs defined** for input fields:

```yaml
extraArgs:
  name:
    type: 'String!'
```

## Debugging Commands

```bash
# Clear all caches
make cache-clear

# Regenerate OpenAPI spec
make generate-openapi-spec

# Check Symfony container services
docker compose exec php bin/console debug:container | grep Processor

# Validate YAML syntax
docker compose exec php bin/console lint:yaml config/

# Check routes
docker compose exec php bin/console debug:router | grep api

# Validate architecture
make deptrac

# Run full CI checks
make ci
```

## Common Error Messages

### "No route found for GET /api/entities"

Resource not configured or missing operations. Check `config/api_platform/resources/`.

### "Unable to generate an IRI for..."

Entity missing `ulid` getter or not properly configured as API resource.

### "Circular reference detected"

Serialization groups missing or circular entity references. Add explicit groups to break cycle.

### "The class ... does not exist"

Typo in namespace or class not autoloaded. Run `composer dump-autoload`.

## Performance Issues

1. **Slow collection queries**: Add database indexes on filtered fields
2. **Memory issues**: Use cursor pagination instead of page-based
3. **N+1 queries**: Check Doctrine ODM eager/lazy loading configuration

## Useful Resources

- [API Platform Documentation](https://api-platform.com/docs/)
- [Symfony Validator](https://symfony.com/doc/current/validation.html)
- [Doctrine MongoDB ODM](https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/2.5/index.html)
