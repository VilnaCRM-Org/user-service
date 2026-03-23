---
name: openapi-development
description: Guide for contributing to the OpenAPI layer at src/Shared/Application/OpenApi/. Use when adding endpoint factories, processors, updating OpenAPI generation logic, or fixing OpenAPI validation errors (Spectral, OpenAPI diff, Schemathesis).
---

# OpenAPI Development Skill

## Context (Input)

- Adding new OpenAPI endpoint factories under `src/Shared/Application/OpenApi/Factory/Endpoint/`
- Adding/editing request/response/schema factories under `src/Shared/Application/OpenApi/Factory/`
- Adding/editing processors that transform the generated spec
- Fixing OpenAPI validation errors from:
  - `make validate-openapi-spec` (Spectral)
  - `make openapi-diff`
  - `make schemathesis-validate`

## Task (Function)

Develop and maintain OpenAPI specification generation in a way that:

- Keeps code quality thresholds intact (complexity, style, architecture)
- Preserves immutability when working with API Platform OpenAPI models (`with*()` methods)
- Produces a spec that passes Spectral and stays diff-stable

**Success Criteria**:

- `make generate-openapi-spec` produces `.github/openapi-spec/spec.yaml`
- `make validate-openapi-spec` passes

---

## Architecture Overview

This service uses a layered OpenAPI customization approach:

```text
src/Shared/Application/OpenApi/
├── Builder/               # Build common OpenAPI pieces
├── Extractor/             # Extract example values / payload fragments
├── Factory/               # Endpoint/Request/Response/Schema/UriParameter factories
├── Transformer/           # Transform/modify OpenAPI operations, parameters, responses
├── ValueObject/ + Enum/   # Strongly typed OpenAPI-related value objects
└── Factory/OpenApiFactory.php  # Main coordinator (decorator)
```

`config/services.yaml` decorates API Platform’s OpenAPI factory with:

- `App\Shared\Application\OpenApi\Factory\OpenApiFactory`
- `!tagged_iterator ‘app.openapi_endpoint_factory’`

---

## Key Principles (Keep Complexity Low)

1. **Single Responsibility**: one class = one transformation.
2. **Immutability**: prefer `with*()` methods; avoid mutating nested arrays unless API Platform forces it.
3. **OPERATIONS constant**: avoid chaining `withGet/withPost/...` repeatedly.
4. **Readable guard clauses**: prefer early returns over deep nesting.
5. **Functional style**: `array_map`, `array_filter`, `array_keys` over procedural mutation.

See: `reference/processor-patterns.md`

---

## How to Add New Components

### Adding a New Transformer

- Create a focused class under `src/Shared/Application/OpenApi/Transformer/`.
- Implement a single public entry method, e.g. `transform(OpenApi $openApi): OpenApi`.
- Iterate paths using `array_keys($openApi->getPaths()->getPaths())`.
- Apply changes per `PathItem` using an `OPERATIONS` constant + dynamic `with/get` calls.
- **Do NOT create new directories** (e.g. `Augmenter/`, `Enricher/`, `Helper/`). New OpenAPI transformation classes belong in `Transformer/`. Existing directories (`Sanitizer/`) are in use — do not duplicate their purpose.

### Adding a New Endpoint Factory

- Implement `EndpointFactoryInterface` under `src/Shared/Application/OpenApi/Factory/Endpoint/`.
- It is auto-tagged by `_instanceof` in `config/services.yaml`.
- It will be invoked by `src/Shared/Application/OpenApi/Factory/OpenApiFactory.php`.

---

## Testing / Validation

Run locally (preferred order):

```bash
make generate-openapi-spec
make validate-openapi-spec
make openapi-diff
make schemathesis-validate
```

Notes:

- `make validate-openapi-spec` runs `./scripts/validate-openapi-spec.sh` (Spectral).
- `make schemathesis-validate` runs Examples and Coverage phases.

---

## Related Skills

- `complexity-management` for refactoring when PHPInsights/PHPMD fails
- `documentation-sync` when spec changes require docs updates
