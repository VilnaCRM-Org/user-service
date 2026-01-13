# Complete Examples (Pointers)

This repo already contains real-world examples under `src/Shared/Application/OpenApi/`.

Suggested starting points:

- `src/Shared/Application/OpenApi/Sanitizer/PathParametersSanitizer.php`
- `src/Shared/Application/OpenApi/Sanitizer/PaginationQueryParametersSanitizer.php`
- `src/Shared/Application/OpenApi/Augmenter/ServerErrorResponseAugmenter.php`
- `src/Shared/Application/OpenApi/Cleaner/NoContentResponseCleaner.php`
- `src/Shared/Application/OpenApi/Factory/OpenApiFactory.php`

Use them as templates when adding new transformations.

## Validation commands

```bash
make generate-openapi-spec
make validate-openapi-spec
make openapi-diff
make schemathesis-validate
```
