# KERNEL Framework Template for Claude Skills

Use this template to refactor remaining skills following the KERNEL methodology.

## KERNEL Principles

- **K** - Keep it simple: One clear goal, no long context dumps
- **E** - Easy to verify: Clear success criteria
- **R** - Reproducible: Avoid temporal references
- **N** - Narrow scope: One goal per prompt
- **E** - Explicit constraints: Tell what NOT to do
- **L** - Logical structure: Context → Task → Constraints → Format

## Standard Skill Template

````markdown
---
name: skill-name
description: One sentence. Use when [trigger scenarios].
---

# [Skill Name] Skill

## Context (Input)

- Bullet point describing current state
- What conditions exist
- What's needed to proceed

## Task (Function)

One sentence stating the primary goal.

**Success Criteria**: Specific, measurable outcome.

## [Main Section - Commands/Workflow/Reference]

### Quick Reference Table (if applicable)

| Item | Command/Value | Description |
| ---- | ------------- | ----------- |

### Execution Steps

#### Step 1: [Action]

```bash
command
```

#### Step 2: [Check/Verify]

- ✅ **Success condition** → Next step
- ❌ **Failure condition** → Recovery action

#### Step 3: [Fix/Iterate]

[Specific remediation steps]

#### Step 4: [Verify]

```bash
verification command
```

Repeat until success criteria met.

## Constraints (Parameters)

**NEVER**:

- Specific things NOT to do
- Antipatterns to avoid
- Prohibited actions

**ALWAYS**:

- Required patterns
- Mandatory steps
- Quality gates

## Format (Output)

**Expected Output**:

```
Exact output format or success message
```

## Verification Checklist

- [ ] Specific verifiable step 1
- [ ] Specific verifiable step 2
- [ ] Specific verifiable step 3
````

## Before/After Example

### ❌ Before KERNEL (Verbose)

```markdown
This skill provides comprehensive guidance for managing database migrations...

## Overview

Doctrine ODM for MongoDB is used in this project...

## When to Use

You should activate this skill when you need to...

- Long explanation
- More context
- Historical background
```

**Problems**:

- Too much context
- Unclear goal
- No success criteria
- Vague structure

### ✅ After KERNEL (Concise)

````markdown
## Context (Input)

- Entity needs database persistence
- MongoDB schema requires updates

## Task (Function)

Create entity with XML mapping and verify schema.

**Success Criteria**: `make setup-test-db` runs without errors.

## Execution Steps

### Step 1: Create Entity

```php
// Domain/Entity/YourEntity.php
class YourEntity { /* ... */ }
```

### Step 2: Create XML Mapping

```xml
<!-- config/doctrine/YourEntity.orm.xml -->
```

### Step 3: Verify

```bash
make setup-test-db
```

## Constraints

**NEVER**:

- Modify existing migrations
- Skip XML validation
````

**Results**:

- Clear goal
- Specific steps
- Measurable success
- 70% less text

## Refactoring Checklist

When refactoring a skill to KERNEL:

- [ ] Add "Context (Input)" section with 2-3 bullets
- [ ] Add "Task (Function)" with one-sentence goal
- [ ] Add "Success Criteria" with measurable outcome
- [ ] Convert prose to tables where applicable
- [ ] Use Step 1/2/3/4 format for workflows
- [ ] Add "Constraints" section with NEVER/ALWAYS lists
- [ ] Add "Format (Output)" showing expected result
- [ ] Add "Verification Checklist" with checkboxes
- [ ] Remove historical/explanatory prose
- [ ] Remove redundant examples (keep 1-2 best ones)
- [ ] Use code blocks for all commands
- [ ] Ensure sections follow Context→Task→Constraints→Format order

## Metrics to Track

When refactoring a skill, track these metrics:

- **Before**: Original line count (e.g., 401 lines)
- **After**: New line count (e.g., 153 lines)
- **Reduction**: Percentage decrease (e.g., 62%)
- **Success criteria added**: Yes/No
- **Constraints explicit**: Yes/No
- **Verification checklist**: Yes/No

Example from testing-workflow refactoring: 401→153 lines (62% reduction)

## Remaining Skills to Refactor

Skills already refactored (✅):

1. ✅ ci-workflow (134→83 lines, 38% reduction)
2. ✅ testing-workflow (401→153 lines, 62% reduction)
3. ✅ code-review (308→130 lines, 58% reduction)

Skills needing refactoring (⏳): 4. ⏳ quality-standards (418 lines) 5. ⏳ complexity-management (370 lines) 6. ⏳ openapi-development (842 lines - split into multi-file) 7. ⏳ database-migrations (299 lines) 8. ⏳ documentation-sync (180 lines) 9. ⏳ load-testing (210 lines)

## Tips for Large Skills (>300 lines)

For skills like `openapi-development` (842 lines):

1. **Main SKILL.md**: Core workflow only (<200 lines)
2. **Supporting files**: Move detailed examples to `/reference/` or `/examples/`
3. **Quick reference**: Use tables for patterns/commands
4. **Link to details**: `See reference/pattern-details.md for examples`

Example structure:

```
skill-name/
├── SKILL.md (follows KERNEL, <200 lines)
├── reference/
│ ├── detailed-patterns.md
│ └── troubleshooting.md
└── examples/
    └── complete-example.md
```
