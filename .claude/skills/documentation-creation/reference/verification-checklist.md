# Documentation Verification Checklist

Use this list after writing the docs to guarantee accuracy.

## 1. Project Discovery Completed

- [ ] `src/` contexts mapped (identify all bounded contexts)
- [ ] Entities recorded from `*/Domain/Entity`
- [ ] Commands/handlers cataloged from Application layer
- [ ] Makefile targets inventoried

## 2. Technology Stack Confirmed

```bash
# Verify PHP version and runtime
grep -i "php" Dockerfile
grep -i "fpm\|franken" Dockerfile

# Verify framework
grep -i "symfony" composer.json

# Verify API layer
grep -i "api-platform" composer.json

# Verify database
grep -i "mysql\|mariadb\|mongo\|postgres" docker-compose.yml

# Verify cache
grep -i "redis" docker-compose.yml
```

- [ ] PHP version and runtime documented correctly
- [ ] Symfony, API Platform, Doctrine versions match composer
- [ ] Database and cache types verified from docker-compose

## 3. Directory & Context Verification

```bash
# List all bounded contexts
ls -la src/

# Verify layer structure for each context
for dir in $(ls src/); do
  echo "=== $dir ==="
  ls -la src/$dir/Application 2>/dev/null || echo "  No Application layer"
  ls -la src/$dir/Domain 2>/dev/null || echo "  No Domain layer"
  ls -la src/$dir/Infrastructure 2>/dev/null || echo "  No Infrastructure layer"
done
```

- [ ] Mentioned directories actually exist
- [ ] Layer names spelled correctly
- [ ] Bounded context names match actual codebase

## 4. Command Verification

```bash
# List all make targets
grep -E "^[a-zA-Z][a-zA-Z0-9_-]*:" Makefile | sort

# Verify essential commands exist
for cmd in build start stop install unit-tests integration-tests behat ci; do
  grep -q "^$cmd:" Makefile && echo "✓ $cmd" || echo "✗ $cmd MISSING"
done
```

- [ ] All referenced make targets exist
- [ ] Command descriptions align with actual behavior
- [ ] No non-existent commands documented

## 5. Testing Layout

```bash
# Verify test directories
ls tests/Unit 2>/dev/null || echo "No tests/Unit"
ls tests/Integration 2>/dev/null || echo "No tests/Integration"
ls tests/Behat 2>/dev/null || echo "No tests/Behat"
ls tests/Load 2>/dev/null || echo "No tests/Load"
```

- [ ] Testing doc references correct folders
- [ ] Faker requirement documented if applicable
- [ ] Coverage/quality thresholds align with project standards

## 6. Documentation Tree

Ensure every required file exists:

```bash
# Core documentation files
for doc in main getting-started design-and-architecture developer-guide \
           api-endpoints testing glossary user-guide advanced-configuration \
           performance security operational onboarding community-and-support \
           legal-and-licensing release-notes versioning; do
  ls docs/$doc.md 2>/dev/null && echo "✓ $doc.md" || echo "✗ $doc.md MISSING"
done
```

- [ ] File count matches plan
- [ ] Headings follow H1/H2/H3 order
- [ ] Project-specific docs added if needed (e.g., performance-frankenphp.md)

## 7. Links & Cross-References

```bash
# Find all internal links
grep -rh '\[.*\](.*\.md)' docs/ | sort | uniq

# Verify each linked file exists
grep -roh '\](.*\.md)' docs/ | tr -d ']()' | sort | uniq | while read link; do
  ls docs/$link 2>/dev/null && echo "✓ $link" || echo "✗ $link BROKEN"
done
```

- [ ] All `[text](relative.md)` links resolve
- [ ] Table of contents added for long files (>100 lines)
- [ ] Cross-links between architecture, testing, and developer guide verified

## 8. Content Consistency

- [ ] Project name capitalization consistent throughout
- [ ] Terminology matches `docs/glossary.md`
- [ ] No leftover placeholders (e.g., `{Project Name}`, `{X.Y}`)
- [ ] Consistent date/version formatting

## 9. Command Examples Tested

- [ ] Installation steps executed or previously validated
- [ ] API examples tested with real endpoints or documented via API Platform
- [ ] Make commands produce expected output

## 10. Final Validation

```bash
# Run CI if available
make ci

# Or run individual checks
make phpcsfixer 2>/dev/null || echo "No phpcsfixer target"
make psalm 2>/dev/null || echo "No psalm target"
```

- [ ] CI passes (or note pending heavy tests with rationale)
- [ ] Skill hand-off mentions that future updates should use `documentation-sync`
- [ ] All placeholder text replaced with actual values

## Quick Verification Script

Run this script to perform automated checks:

```bash
#!/bin/bash
echo "=== Documentation Verification ==="

echo -e "\n1. Technology Stack"
grep -i "php:" Dockerfile 2>/dev/null || echo "Check Dockerfile manually"
grep -i "symfony" composer.json | head -1

echo -e "\n2. Bounded Contexts"
ls -1 src/ 2>/dev/null

echo -e "\n3. Essential Commands"
for cmd in build start stop unit-tests ci; do
  grep -q "^$cmd:" Makefile 2>/dev/null && echo "✓ $cmd" || echo "✗ $cmd"
done

echo -e "\n4. Documentation Files"
ls docs/*.md 2>/dev/null | wc -l | xargs echo "Total docs:"

echo -e "\n5. Placeholders Check"
grep -rn '{.*}' docs/*.md 2>/dev/null | head -5 || echo "No placeholders found"
```
