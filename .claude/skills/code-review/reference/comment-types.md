# Per-Comment-Type Execution Reference

Detailed handling per category from the Step 2 categorization table. Apply alongside the **Skill Routing** table in `SKILL.md`.

## Committable Suggestions

Apply the change verbatim, then commit using the Conventional Commits template from `SKILL.md`:

```bash
git commit -m "<type>(#<issue>): <imperative description>

Ref: [comment URL]"
```

Use `fix` for bug-correcting suggestions, `refactor` for non-behavioral cleanups, `docs` for doc-only changes.

## LLM Prompts

Copy the prompt from the comment, execute it, verify output matches the reviewer's intent, commit with reference.

## Architecture / Organization Concerns

1. Invoke the matching skill from **Skill Routing**
2. Verify: `make ci`
3. Commit with reference

## Questions

If the answer requires a code change, implement + commit. Otherwise reply on GitHub and mark the thread resolved.

## General Feedback

Apply if it improves the change; otherwise reply with reasoning and resolve the thread.
