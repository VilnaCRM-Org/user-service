# API Platform CRUD Examples

This directory contains complete working examples for implementing CRUD operations with API Platform 4.

## Available Examples

### 1. Complete Customer CRUD (`complete-customer-crud.md`)

A comprehensive example showing the full implementation of Customer entity CRUD operations, including:

- Domain entity with value object updates
- Doctrine XML mapping for MongoDB
- Input DTOs for Create, Put, and Patch operations
- Validation configuration (YAML-based)
- API Platform resource configuration
- Serialization groups
- State processors for each operation
- Transformer for DTO → Entity conversion
- Commands and handlers (CQRS pattern)
- Filter configuration (search, order, date, boolean)
- Complete file list

## How to Use These Examples

1. **Start with the complete example** to understand the full flow
2. **Copy the patterns** and adapt them to your specific entity
3. **Replace placeholders** (`{Entity}`, `{Context}`, etc.) with your actual names
4. **Follow the layer responsibilities**:
   - Domain: Pure PHP entities
   - Application: DTOs, Processors, Commands, Handlers
   - Infrastructure: Repository implementations
   - Config: YAML resource definitions

## Key Patterns Demonstrated

- **DDD**: Domain entities isolated from frameworks
- **CQRS**: Separate commands for write operations
- **Hexagonal Architecture**: Ports (interfaces) and adapters (implementations)
- **API Platform**: YAML-based resource configuration
- **Validation**: External YAML configuration, not annotations

## Quick Checklist

When implementing a new CRUD resource, ensure:

- [ ] Domain entity is pure PHP (no framework imports)
- [ ] Doctrine mapping is in XML, not annotations
- [ ] DTOs are readonly immutable classes
- [ ] Validation is in YAML config files
- [ ] Resource config is in YAML (not PHP attributes)
- [ ] Processors use command bus pattern
- [ ] Handlers call repository methods
- [ ] Filters are registered as services
- [ ] Serialization groups are properly configured

## Related Documentation

- [Main SKILL.md](../SKILL.md) - Quick start guide and patterns
- [Filters Reference](../reference/filters-and-pagination.md) - Filter configuration details
- [Troubleshooting](../reference/troubleshooting.md) - Common issues and solutions
