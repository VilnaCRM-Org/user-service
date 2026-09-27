# LLM Clean Architecture Review Checklist

Use this checklist when reviewing a PR that adds or changes LLM-backed behavior.

## Boundary Checks

- Domain code has no provider SDK, HTTP client, prompt text, model ID, token, or
  API key concept.
- Application code depends on provider-neutral interfaces, not concrete
  provider clients.
- Infrastructure adapters are the only classes that know provider payloads,
  authentication, retry details, and SDK exceptions.
- Controllers, processors, CLI commands, and subscribers delegate to Application
  use cases instead of assembling prompts or calling providers directly.

## Naming and Structure

- No catch-all classes such as `LlmService`, `AiManager`, `PromptHelper`, or
  `ProviderUtil`.
- Interfaces describe capabilities, for example
  `RegistrationRiskEvaluatorInterface`.
- DTOs or value objects define request and response shape.
- New directories use established pattern names and comply with
  `code-organization/SKILL.md`.

## Prompt and Model Policy

- Prompt construction is handled by a named factory/template/strategy.
- Prompt variables are typed and explicit.
- Shared prompt fragments are centralized.
- Model alias, timeout, token limit, temperature, and retry policy are
  configured in one place.
- Prompt/model versions are documented when output affects persisted or
  user-visible state.

## Error Handling

- Provider timeouts, rate limits, invalid responses, unsafe responses, and auth
  failures are mapped to explicit application exceptions or outcomes.
- No silent fallback hides provider failure.
- Retries have clear limits and do not wrap non-idempotent side effects without
  an explicit idempotency guard.
- Response parsing validates required fields before creating response DTOs.

## Testing

- Default tests do not call live LLM providers.
- Unit tests cover prompt factories/templates.
- Parser tests cover malformed, partial, and unexpected provider responses.
- Contract tests prove each adapter satisfies the same port behavior.
- Failure-path tests cover timeout, retry exhaustion, invalid output, and unsafe
  output.
- Any live smoke test is opt-in and excluded from default CI unless explicitly
  approved.

## Security and Observability

- Logs redact prompts, raw user content, credentials, tokens, and PII.
- Metrics capture provider, model alias, latency, status, retry count, and
  correlation ID without sensitive payloads.
- Configuration does not hardcode secrets or scatter model IDs across classes.
- Documentation states which data is sent to providers and how it is protected.

## PR Evidence

Ask for these items before approving:

- Link to the Application port and DTOs.
- Link to the Infrastructure adapter.
- Link to prompt factory/template tests.
- Link to provider response parser tests.
- Link to docs/onboarding updates when behavior changes.
- CI evidence or a clear explanation for a narrower docs-only validation path.
