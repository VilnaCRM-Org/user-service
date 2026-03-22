# DTO and Validation Patterns

Patterns for creating DTOs, validation, and exception handling in API Platform 4 CRUD operations.

## DTO Patterns

### Create DTO (POST)

Used for creating new resources with all required fields.

```php
// src/Core/{Context}/Application/DTO/{Entity}Create.php
namespace App\Core\{Context}\Application\DTO;

final readonly class CustomerCreate
{
    public function __construct(
        public ?string $initials = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $type = null,      // IRI string: "/api/customer_types/{ulid}"
        public ?string $status = null,    // IRI string: "/api/customer_statuses/{ulid}"
        public ?string $leadSource = null, // IRI string: "/api/lead_sources/{ulid}"
    ) {}
}
```

**Characteristics:**

- All properties nullable for flexibility
- Validation enforced via YAML config
- IRIs used for entity references
- Read-only to prevent modification

---

### Put DTO (Full Update)

Used for full resource replacement (PUT). Same structure as Create DTO.

```php
// src/Core/{Context}/Application/DTO/{Entity}Put.php
namespace App\Core\{Context}\Application\DTO;

final readonly class CustomerPut
{
    public function __construct(
        public ?string $initials = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?string $leadSource = null,
    ) {}
}
```

**Usage Pattern:**

- Client must provide all fields
- Missing fields will be set to null (unless validation prevents it)
- Replaces entire resource state

---

### Patch DTO (Partial Update)

Used for partial updates (PATCH). Fields not provided are preserved.

```php
// src/Core/{Context}/Application/DTO/{Entity}Patch.php
namespace App\Core\{Context}\Application\DTO;

final readonly class CustomerPatch
{
    public function __construct(
        public ?string $initials = null,     // All nullable
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?string $leadSource = null,
    ) {}
}
```

**Key Difference - Processor Logic:**

```php
final readonly class PatchCustomerProcessor implements ProcessorInterface
{
    public function process(mixed $data, ...): Customer
    {
        $existingCustomer = // ... fetch from repository

        // Use null coalescing to preserve existing values
        $command = new UpdateCustomerCommand(
            id: $data->id,
            initials: $data->initials ?? $existingCustomer->getInitials(),
            email: $data->email ?? $existingCustomer->getEmail(),
            phone: $data->phone ?? $existingCustomer->getPhone(),
            // ...
        );

        return $this->commandBus->dispatch($command);
    }
}
```

---

## Validation Strategy

### External YAML Configuration (Primary Approach)

**This is the preferred validation method.** Validation rules are defined in YAML files, keeping DTOs clean:

```yaml
# config/validator/{Entity}.yaml
App\Core\{Context}\Application\DTO\{Entity}Create:
  properties:
    initials:
      - NotBlank:
          message: 'Initials are required'
      - Length:
          max: 10
          maxMessage: 'Initials cannot exceed {{ limit }} characters'

    email:
      - NotBlank: ~
      - Email:
          message: 'Invalid email format'
      - App\Shared\Application\Validator\UniqueEmail:
          message: 'Email already exists'

    phone:
      - Length: { max: 20 }

    type:
      - NotBlank:
          message: 'Customer type is required'

    status:
      - NotBlank:
          message: 'Customer status is required'
```

**Benefits:**

- DTOs remain simple POPOs (Plain Old PHP Objects)
- Validation rules centralized and versionable
- Easy to test and maintain
- Can be generated from OpenAPI specs
- Framework-provided validators cover most use cases
- Custom validators available for business rules

**Policy**: Always use framework validators (Symfony Validator component) when possible. Value Objects should only be used for validation when framework validators cannot express the business rule or when the validation is intrinsically part of domain invariants.

---

### Custom Validators

For complex business rules, create custom validators:

#### 1. Define Constraint

```php
// src/Shared/Application/Validator/UniqueEmail.php
namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueEmail extends Constraint
{
    public string $message = 'The email "{{ value }}" is already in use.';
    public string $mode = 'strict'; // 'strict' or 'loose'
}
```

#### 2. Implement Validator

```php
// src/Shared/Application/Validator/UniqueEmailValidator.php
namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmail) {
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }

        if (null === $value || '' === $value) {
            return;  // Use NotBlank for required validation
        }

        if ($this->customerRepository->emailExists((string) $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
```

#### 3. Register Validator

```yaml
# config/services.yaml
services:
  App\Shared\Application\Validator\UniqueEmailValidator:
    tags:
      - { name: validator.constraint_validator }
```

---

## Validation Groups

Use groups to apply different validation rules based on context:

```yaml
App\Core\Customer\Application\DTO\CustomerCreate:
  properties:
    email:
      - NotBlank:
          groups: ['create', 'update']
      - Email:
          groups: ['create', 'update']
      - App\Shared\Application\Validator\UniqueEmail:
          groups: ['create'] # Only check uniqueness on creation
```

**In Resource Config:**

```yaml
ApiPlatform\Metadata\Post:
  input: App\Core\Customer\Application\DTO\CustomerCreate
  validationContext:
    groups: ['Default', 'create']
```

---

## Exception Handling

### Domain Exception Mapping

Map domain exceptions to HTTP status codes:

```yaml
# config/api_platform/resources/{entity}.yaml
App\Core\Customer\Domain\Entity\Customer:
  exceptionToStatus:
    'App\Core\Customer\Domain\Exception\CustomerNotFoundException': 404
    'App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException': 400
    'App\Core\Customer\Domain\Exception\InvalidCustomerDataException': 422
    'App\Core\Customer\Domain\Exception\DuplicateEmailException': 409
```

### Domain Exception Example

```php
// src/Core/Customer/Domain/Exception/CustomerNotFoundException.php
namespace App\Core\Customer\Domain\Exception;

final class CustomerNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Customer with ID "%s" not found', $id));
    }
}
```

### Usage in Handler

```php
final readonly class UpdateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __invoke(UpdateCustomerCommand $command): Customer
    {
        $customer = $this->repository->findById($command->id);

        if (null === $customer) {
            throw CustomerNotFoundException::withId($command->id);
        }

        // Update logic...
    }
}
```

---

## Error Response Format

API Platform automatically formats errors following RFC 7807 (Problem Details):

```json
{
  "@context": "/api/contexts/ConstraintViolationList",
  "@type": "ConstraintViolationList",
  "hydra:title": "An error occurred",
  "hydra:description": "email: This value is not a valid email address.",
  "violations": [
    {
      "propertyPath": "email",
      "message": "This value is not a valid email address.",
      "code": "bd79c0ab-ddba-46cc-a703-a7a4b08de310"
    }
  ]
}
```

---

## Validation Best Practices

### ✅ DO

- **Prefer framework validators** - Use external YAML configuration for all validation
- Create custom validators for business rules
- Keep DTOs simple (no logic)
- Use validation groups for context-specific rules
- Map domain exceptions to appropriate HTTP status codes
- Return descriptive error messages
- Use Symfony's built-in constraints (NotBlank, Email, Length, etc.)

### ❌ DON'T

- Put validation logic in DTOs
- Use PHP attributes for validation (use YAML)
- Use Value Objects for validation when framework validators can do the job
- Throw HTTP exceptions from domain layer
- Expose internal error details to API clients
- Duplicate validation logic across DTOs

---

## Testing Validation

### Unit Test Example

```php
final class CustomerCreateValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testInvalidEmail(): void
    {
        $dto = new CustomerCreate(
            email: 'invalid-email'
        );

        $violations = $this->validator->validate($dto);

        $this->assertCount(1, $violations);
        $this->assertEquals('email', $violations[0]->getPropertyPath());
    }
}
```

### API Test Example (Behat)

```gherkin
Scenario: Create customer with invalid email
  When I send a "POST" request to "/api/customers" with body:
    """
    {
      "email": "invalid-email",
      "type": "/api/customer_types/01HQX..."
    }
    """
  Then the response status code should be 422
  And the JSON node "violations[0].propertyPath" should be equal to "email"
```

---

See `troubleshooting.md` for common validation issues and solutions.
