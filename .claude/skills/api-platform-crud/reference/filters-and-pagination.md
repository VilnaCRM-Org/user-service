# API Platform Filters and Pagination Reference

> Template examples use Doctrine MongoDB ODM filters. Replace `doctrine_mongodb.odm.*` with Doctrine ORM filter services (e.g., `api_platform.doctrine.orm.search_filter`) and adjust service IDs for MySQL in this service.

## MongoDB ODM Filters

### Search Filter (Exact Match)

```yaml
# config/services.yaml
app.entity.mongodb.search_filter:
  parent: 'api_platform.doctrine_mongodb.odm.search_filter'
  arguments:
    - name: 'exact'
      email: 'exact'
      status.value: 'exact' # Nested property
  tags:
    - { name: 'api_platform.filter', id: 'entity.mongodb.search' }
```

**Usage**: `GET /api/entities?name=value&email=test@example.com`

### Order Filter (Sorting)

```yaml
app.entity.mongodb.order_filter:
  parent: 'api_platform.doctrine_mongodb.odm.order_filter'
  arguments:
    - ulid: 'desc'
      createdAt: 'desc'
      name: 'asc'
  tags:
    - { name: 'api_platform.filter', id: 'entity.mongodb.order' }
```

**Usage**: `GET /api/entities?order[name]=asc&order[createdAt]=desc`

### Date Filter

```yaml
app.entity.mongodb.date_filter:
  parent: 'api_platform.doctrine_mongodb.odm.date_filter'
  arguments:
    - { 'createdAt': ~, 'updatedAt': ~ }
  tags:
    - { name: 'api_platform.filter', id: 'entity.mongodb.date' }
```

**Usage**:

- `GET /api/entities?createdAt[after]=2024-01-01`
- `GET /api/entities?createdAt[before]=2024-12-31`
- `GET /api/entities?createdAt[strictly_after]=2024-01-01T00:00:00`

### Boolean Filter

```yaml
app.entity.mongodb.boolean_filter:
  parent: 'api_platform.doctrine_mongodb.odm.boolean_filter'
  arguments:
    - { 'confirmed': ~, 'active': ~ }
  tags:
    - { name: 'api_platform.filter', id: 'entity.mongodb.boolean' }
```

**Usage**: `GET /api/entities?confirmed=true&active=false`

### Range Filter

```yaml
app.mongodb.range_filter:
  parent: 'api_platform.doctrine_mongodb.odm.range_filter'
  arguments:
    - { 'price': ~, 'quantity': ~ }
  tags:
    - { name: 'api_platform.filter', id: 'mongodb.range' }
```

**Usage**:

- `GET /api/entities?price[gt]=100&price[lt]=500`
- `GET /api/entities?quantity[gte]=10&quantity[lte]=100`

## Applying Filters to Resources

```yaml
# config/api_platform/resources/entity.yaml
App\Core\Context\Domain\Entity\Entity:
  operations:
    ApiPlatform\Metadata\GetCollection:
      filters:
        - entity.mongodb.search
        - entity.mongodb.order
        - entity.mongodb.date
        - entity.mongodb.boolean
        - mongodb.range
```

## Pagination Configuration

### Cursor-Based Pagination (Recommended for MongoDB)

```yaml
App\Core\Context\Domain\Entity\Entity:
  paginationPartial: true
  paginationViaCursor:
    - { field: 'ulid', direction: 'desc' }
  order: { 'ulid': 'desc' }
```

**Benefits:**

- Efficient for large datasets
- No offset performance penalty
- Consistent results with concurrent writes

**Response Structure:**

```json
{
  "@id": "/api/entities",
  "@type": "hydra:Collection",
  "hydra:member": [...],
  "hydra:view": {
    "@id": "/api/entities?cursor=...",
    "@type": "hydra:PartialCollectionView",
    "hydra:first": "/api/entities",
    "hydra:last": "/api/entities?cursor=...",
    "hydra:next": "/api/entities?cursor=..."
  }
}
```

### Page-Based Pagination

```yaml
App\Core\Context\Domain\Entity\Entity:
  paginationEnabled: true
  paginationItemsPerPage: 30
  paginationClientItemsPerPage: true
  paginationMaximumItemsPerPage: 100
```

**Usage**: `GET /api/entities?page=2&itemsPerPage=50`

### Partial Pagination

```yaml
paginationPartial: true
```

Improves performance by not computing total count. Suitable when total count is not needed.

## Custom Filter Properties

### Filter on Nested Properties

```yaml
app.customer.mongodb.search_filter:
  arguments:
    - type.value: 'exact' # Customer → CustomerType.value
      status.value: 'exact' # Customer → CustomerStatus.value
```

### Multiple Strategies

```yaml
app.entity.mongodb.search_filter:
  arguments:
    - name: 'partial' # Contains
      email: 'exact' # Exact match
      description: 'start' # Starts with
      code: 'end' # Ends with
      tags: 'word_start' # Word boundary start
```

## GraphQL Filter Integration

```yaml
graphQlOperations:
  ApiPlatform\Metadata\GraphQl\QueryCollection:
    filters:
      - entity.mongodb.search
      - entity.mongodb.order
      - entity.mongodb.date
      - entity.mongodb.boolean
    paginationType: cursor
```

## Global Filter Configuration

```yaml
# config/packages/api_platform.yaml
api_platform:
  defaults:
    pagination_partial: true
    paginationClientItemsPerPage: true
```

## Performance Considerations

1. **Always index filtered fields** in MongoDB
2. Use **cursor pagination** for large collections
3. Enable **partial pagination** when total count isn't needed
4. Filter on **indexed fields** first (ulid, createdAt)
5. Avoid **text search** on unindexed fields

## Filter Debugging

Check available filters in OpenAPI documentation:

```bash
make generate-openapi-spec
```

View filter parameters in Swagger UI at `https://localhost/api/docs`.
