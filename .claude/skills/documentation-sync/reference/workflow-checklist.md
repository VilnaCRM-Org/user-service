# Documentation Update Workflow Checklist

## Pre-Development Phase

- [ ] Review existing documentation structure
- [ ] Identify which docs will need updates
- [ ] Plan documentation alongside code changes

## During Development

- [ ] Update docs in same branch as code
- [ ] Write/update examples as you code
- [ ] Add new terms to glossary
- [ ] Update architecture diagrams if needed

## Before Committing

### Impact Assessment

- [ ] Identify all affected documentation files
- [ ] Check for cross-file dependencies
- [ ] List all sections requiring updates

### Content Updates

#### API Changes

- [ ] Update `docs/api-endpoints.md`
- [ ] Generate OpenAPI spec: `make generate-openapi-spec`
- [ ] Generate GraphQL spec: `make generate-graphql-spec` (if applicable)
- [ ] Update `docs/user-guide.md` with usage examples

#### Database Changes

- [ ] Update `docs/design-and-architecture.md`
- [ ] Update entity relationships
- [ ] Document migration considerations
- [ ] Update `docs/developer-guide.md` with repository patterns

#### Configuration Changes

- [ ] Update `docs/advanced-configuration.md`
- [ ] Add environment variable documentation
- [ ] Show configuration examples
- [ ] Document default values and validation

#### Security Changes

- [ ] Update `docs/security.md`
- [ ] Document auth flow changes
- [ ] Update permission requirements
- [ ] Add security considerations

#### Testing Changes

- [ ] Update `docs/testing.md`
- [ ] Document new test types
- [ ] Add test commands
- [ ] Update coverage requirements

#### Performance Changes

- [ ] Update `docs/performance.md`
- [ ] Document optimization impact
- [ ] Add benchmark results
- [ ] Include configuration changes

#### Domain Changes

- [ ] Update `docs/design-and-architecture.md`
- [ ] Document new aggregates/commands/events
- [ ] Update bounded context interactions
- [ ] Add terms to `docs/glossary.md`

### Validation

- [ ] Test all code examples
- [ ] Verify all internal links work
- [ ] Check cross-references
- [ ] Validate terminology consistency with glossary
- [ ] Ensure formatting follows style guide

### Quality Check

- [ ] Documentation is complete
- [ ] Examples are realistic
- [ ] Error cases documented
- [ ] No outdated information
- [ ] Screenshots/diagrams updated if needed

## During Code Review

### Reviewer Checks

- [ ] Documentation accurately reflects code changes
- [ ] Examples are correct and tested
- [ ] Terminology is consistent
- [ ] Links are valid
- [ ] No missing documentation

## After Merge

### Version Management

- [ ] Update `docs/release-notes.md` for significant changes
- [ ] Update `docs/versioning.md` if version changed
- [ ] Mark deprecated features
- [ ] Document breaking changes

### Verification

- [ ] Verify generated specs are correct
- [ ] Check documentation builds correctly
- [ ] Validate all links in deployed docs

## Quick Reference by Change Type

| Change        | Primary Docs                                       | Commands                     |
| ------------- | -------------------------------------------------- | ---------------------------- |
| REST endpoint | `api-endpoints.md`, `user-guide.md`                | `make generate-openapi-spec` |
| GraphQL op    | `api-endpoints.md`, `user-guide.md`                | `make generate-graphql-spec` |
| Entity        | `design-and-architecture.md`, `developer-guide.md` | -                            |
| Config        | `advanced-configuration.md`                        | -                            |
| Auth          | `security.md`, `api-endpoints.md`                  | -                            |
| Test          | `testing.md`                                       | -                            |
| Performance   | `performance.md`                                   | -                            |
| Domain        | `design-and-architecture.md`, `glossary.md`        | -                            |
