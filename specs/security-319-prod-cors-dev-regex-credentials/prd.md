# PRD — Security #319: Production image ships dev-only CORS regex with credentials

## Problem

The production Docker image bakes in a working development CORS allow-list. The
committed `.env` defined:

```
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

`config/packages/nelmio_cors.yaml` enables `origin_regex: true` and
`allow_credentials: true` for all paths (`/api`, `/api/graphql`, `/authorize`),
reading `allow_origin` from `%env(CORS_ALLOW_ORIGIN)%` in dev, test and prod.
The Dockerfile prod stage runs `composer dump-env prod`, which resolves `.env`
and bakes `CORS_ALLOW_ORIGIN=<localhost regex>` into `.env.local.php` inside the
production artifact.

Consequence (CWE-942, Permissive Cross-domain Policy with Untrusted Domains):
unless an operator explicitly sets `CORS_ALLOW_ORIGIN` at deploy time, the
production API reflects any `http(s)://localhost` or `http://127.0.0.1` Origin
into `Access-Control-Allow-Origin` AND returns
`Access-Control-Allow-Credentials: true`. Because the JWT auth token is accepted
from both the `__Host-auth_token` cookie and the `Authorization` header, a
browser context the browser treats as `Origin: http://localhost` (local dev
server, webview, browser-extension page, DNS-rebinding to 127.0.0.1) can read
authenticated cross-origin API responses (profile, tokens, account data).

There was no fail-closed guard: an empty `CORS_ALLOW_ORIGIN` is even worse, since
nelmio compiles `allow_origin: ['']` to the regex `{}i`, which matches EVERY
origin (verified: `preg_match('{}i', 'http://evil.example.com') === 1`). So the
remediation must ship a non-empty deny-all default, not an empty value.

## In scope

- The committed `.env` default that reaches the production artifact.
- Environment-scoped overrides for dev and test.
- A regression unit test that pins the fail-closed default.

## Functional requirements

- **FR-1**: The committed `.env` (the file baked into the prod image by
  `composer dump-env prod`) MUST default `CORS_ALLOW_ORIGIN` to a deny-all regex
  (`(?!)`) that matches no origin. The dev localhost allow-list MUST NOT ship in
  this default.
- **FR-2**: The deny-all default MUST be non-empty so nelmio never compiles it to
  the fail-open `{}i` regex, and MUST NOT be the `*` wildcard.
- **FR-3**: The development environment (`.env.dev`) MUST provide the localhost
  allow-list so local development keeps working.
- **FR-4**: The test environment (`.env.test`) MUST keep the localhost allow-list
  so existing functional/CORS tests keep working.
- **FR-5**: `config/packages/nelmio_cors.yaml` MUST continue to read
  `allow_origin` from `%env(CORS_ALLOW_ORIGIN)%` in dev, test and prod (the env
  binding is the single source of truth; no per-env hardcoded origins).
- **FR-6**: `.env` MUST document that `CORS_ALLOW_ORIGIN` is a required deploy
  variable and provide an HTTPS prod example.

## Non-functional requirements

- **NFR-Security**: Fail-closed by default. With no operator-supplied value, the
  production service denies all cross-origin requests; it never reflects
  `Access-Control-Allow-Origin` + `Access-Control-Allow-Credentials: true` for
  localhost or any untrusted origin. `allow_credentials` behaviour is preserved
  for legitimately configured origins (no functional regression for valid SPAs).
- **NFR-Compatibility**: No application code, controller, route, or public API
  contract changes. nelmio CORS bundle wiring is unchanged. Dev and test flows
  retain their localhost allow-list, so no developer or CI workflow breaks.
- **NFR-Maintainability**: Change is config-only plus one focused unit test in the
  existing `tests/Unit/Config/` location (mirrors `LeagueOAuth2ServerConfigTest`).
  No new directories, no `*Service` suffixes, no Deptrac/threshold edits. The test
  parses env files with `Symfony\Component\Dotenv\Dotenv`, matching how Symfony
  resolves them, so it stays accurate if formatting changes.

## Out of scope

- Adding a runtime boot-time guard/exception when `CORS_ALLOW_ORIGIN` is unset
  (the deny-all default already fails closed; a hard boot failure would be a
  separate UX decision).
- Per-endpoint disabling of `allow_credentials` or forcing HTTPS-only origins in
  prod (deployment-time policy; the env example documents the HTTPS expectation).
- Changes to `DualAuthenticator` cookie/header acceptance.
- Adding a `.env.prod` or deployment manifest (deployment-environment concern).
