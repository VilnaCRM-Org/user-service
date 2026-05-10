# Documentation Quality Standards

## Consistency Requirements

### Structure

- Follow existing documentation file structure
- Use consistent heading levels
- Maintain uniform formatting style
- Group related content logically

### Terminology

- Use terms defined in `docs/glossary.md`
- Define new terms before using them
- Be consistent across all documentation files
- Avoid jargon without explanation

### Code Examples

- Include syntax highlighting (\```language)
- Test all examples before committing
- Show both basic and advanced usage
- Include error handling examples

### Cross-References

- Link to related documentation sections
- Use relative links for internal docs
- Verify links remain valid after changes
- Provide context for external links

## Completeness Requirements

### API Documentation

- Document all public endpoints
- Include request/response schemas
- Show authentication requirements
- List all error codes and meanings
- Provide curl examples

### Feature Documentation

- Explain feature purpose and benefits
- Provide step-by-step usage instructions
- Include configuration requirements
- Show common use cases
- Document limitations

### Error Handling

- Document all error scenarios
- Explain error messages
- Provide troubleshooting steps
- Show example error responses

## Maintenance Requirements

### Deprecation

- Mark deprecated features clearly
- Provide migration path to new features
- Include removal timeline
- Update references in all docs

### Versioning

- Update `docs/versioning.md` for releases
- Maintain backward compatibility notes
- Document breaking changes
- Track feature availability by version

### Link Validation

- Check all internal links work
- Verify external links are accessible
- Update moved documentation references
- Remove dead links

### Diagram Updates

- Update architecture diagrams for structural changes
- Keep sequence diagrams in sync with flows
- Maintain entity relationship diagrams
- Use consistent diagram notation

## Style Guidelines

### Writing Style

- Use active voice
- Write clear, concise sentences
- Avoid unnecessary complexity
- Use lists for multiple items
- Include examples for clarity

### Formatting

- Use **bold** for emphasis
- Use `code` for inline code/commands
- Use > blockquotes for important notes
- Use tables for structured data
- Use numbered lists for ordered steps

### Examples

- Make examples realistic
- Use consistent example data
- Show expected output
- Include error examples
- Test before committing
