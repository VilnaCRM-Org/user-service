# Complete Examples (Pointers)

This repo already contains real-world examples under `src/Shared/Application/OpenApi/`.

Suggested starting points:

- `src/Shared/Application/OpenApi/Transformer/ServerErrorResponseTransformer.php`
- `src/Shared/Application/OpenApi/Transformer/PaginationOperationTransformer.php`
- `src/Shared/Application/OpenApi/Transformer/PaginationParameterTransformer.php`
- `src/Shared/Application/OpenApi/Transformer/ResponseContentTransformer.php`
- `src/Shared/Application/OpenApi/Factory/OpenApiFactory.php`

Use them as templates when adding new transformations. All new OpenAPI transformations MUST go into the `Transformer/` directory — do NOT create new directories like `Augmenter/`, `Enricher/`, or `Helper/`.

## Validation commands

```bash
make generate-openapi-spec
make validate-openapi-spec
make openapi-diff
make schemathesis-validate
```
