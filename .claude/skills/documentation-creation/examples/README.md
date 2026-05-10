# Documentation Creation Examples

This directory contains real-world examples of documentation creation using this skill.

## Available Examples

### [user-service-example.md](user-service-example.md)

Complete walkthrough of creating and verifying documentation for `user-service`.

**Highlights**:

- Technology stack analysis (FrankenPHP, Symfony, MySQL)
- Bounded context discovery (User, OAuth, Shared, Internal/HealthCheck)
- Entity identification (User, ConfirmationToken)
- Directory structure verification against `src/`
- Command and link verification

## How to Use Examples

1. Read through the example to understand the workflow
2. Follow the same pattern for your documentation project:
   - Analyze project structure first
   - Document the technology stack
   - Identify bounded contexts and entities
   - Create documentation files using templates
   - Verify all references
3. Use the verification checklist from `../reference/verification-checklist.md`
4. Adapt templates from `../reference/doc-templates.md`

## Adding New Examples

When adding new documentation examples:

1. Document the project being analyzed
2. Include technology stack discovery commands and results
3. Show bounded context and entity analysis
4. List verification steps performed
5. Note lessons learned
