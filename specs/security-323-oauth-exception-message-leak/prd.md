# PRD — Security #323: OAuth provider exception message leak (CWE-209)

## Problem

`OAuthExceptionListener` (`src/OAuth/Application/EventListener/OAuthExceptionListener.php`)
builds the client-facing `application/problem+json` error response with
`'detail' => $exception->getMessage()`. For `OAuthProviderException`, that message is
`sprintf('OAuth provider %s error: %s', $provider, $message)` where `$message` is the
verbatim `getMessage()` of any `\Throwable` caught during the provider token exchange or
profile fetch (`GitHubOAuthProvider::exchangeCode()` and `::fetchProfile()` wrap
`$e->getMessage()` from `league/oauth2-client` / Guzzle).

Guzzle and league exception messages routinely embed the outbound request URL (which may
carry `client_id` and other query parameters), the provider's raw error body, HTTP status
lines, and internal hostnames. The listener is not debug-gated, so the leak is emitted in
**all** environments including production. The social-callback path (`/api/auth/social`)
is `PUBLIC_ACCESS` in `security.yaml`, so the response is reachable by an unauthenticated
attacker who can force the provider exchange to fail with a crafted/invalid `code`.

This is an information-exposure-through-error-message weakness (CWE-209), rated MEDIUM,
usable for reconnaissance of the OAuth integration (client_id, provider endpoints,
internal hostnames, upstream error semantics).

## Functional Requirements

- **FR-1**: For every OAuth exception handled by `OAuthExceptionListener`, the client
  response `detail` field MUST be a static, provider-agnostic, developer-controlled string.
  It MUST NOT contain `$exception->getMessage()` or any upstream/third-party error text.
- **FR-2**: The HTTP status, `error_code`, `type`, and `title` fields of the response
  contract MUST remain unchanged (no breaking change to the existing client contract).
- **FR-3**: When a handled exception occurs, the full `$exception->getMessage()` and the
  exception object (for trace) MUST be logged server-side at `error` level, including the
  `error_code`, so operators retain full diagnostic information.
- **FR-4**: Exceptions not present in the listener's map MUST continue to be ignored
  (no response set, no log written by this listener).

## Non-Functional Requirements

- **NFR-Security**: No untrusted, upstream, or third-party text reaches an unauthenticated
  client through the OAuth error response. Sensitive material (client_id, secrets, provider
  URLs, internal hostnames, HTTP status lines, raw provider error bodies) is confined to
  server-side logs. Closes CWE-209 for the OAuth social-callback path.
- **NFR-Compatibility**: The response shape (keys `type`, `title`, `detail`, `status`,
  `error_code`), Content-Type (`application/problem+json`), and HTTP status codes are
  preserved. Only the human-readable `detail` string value changes from a dynamic raw
  message to a stable static message.
- **NFR-Maintainability**: The static `detail` strings are colocated with each entry in the
  existing `ERROR_CODE_MAP`, so adding a new handled exception requires defining its
  `detail` alongside `error_code` and `status` — a single, obvious place. No new directory,
  class type, or `*Service` suffix is introduced. Hexagonal/DDD boundaries are preserved
  (the listener stays in the Application layer; the injected `Psr\Log\LoggerInterface` is a
  framework-agnostic PSR-3 port, autowired via existing `_defaults` config).

## Out of Scope

- Changing the domain exception classes' own messages (they are developer-controlled and
  remain useful for server-side logging).
- Removing `$e->getMessage()` propagation inside `GitHubOAuthProvider` — the raw upstream
  text is intentionally preserved as the exception message for server-side logging; the
  remediation is enforced at the single client-facing boundary (the listener), which also
  covers any future provider adapter.
- Localization/translation of the `detail` strings (the strings are English and static;
  i18n can be layered later without affecting this fix).
- Any change to authentication/authorization behavior or the OAuth flow itself.
