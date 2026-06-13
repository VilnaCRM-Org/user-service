# PRD — Rate limiters keyed on untrusted client IP (#316)

## Problem

The user-service runs behind a reverse proxy (Caddy/FrankenPHP) and, in
production, behind the AWS App Runner managed load balancer. Every per-IP rate
limiter (`signin_ip`, `twofa_verification_ip`, `registration`, `refresh_token`,
`email_confirmation`, `password_reset_confirm`, `oauth_social_*`,
`user_collection`, `resend_confirmation`, and the `global_api_*` buckets) builds
its key from `Symfony\Component\HttpFoundation\Request::getClientIp()` inside
`ApiRateLimitRequestResolver::buildIpKey()` and
`ApiRateLimitAuthTargetResolver::buildIpKey()`.

The production framework configuration (`config/packages/framework.yaml`) never
set `framework.trusted_proxies` or `framework.trusted_headers`. With no trusted
proxies, `getClientIp()` returns `REMOTE_ADDR`, which behind App Runner is the
load balancer's internal IP — the **same value for every external client**.
Consequences (CWE-307, Improper Restriction of Excessive Authentication
Attempts):

- All IP-keyed limiters collapse onto a single shared bucket keyed on the LB IP.
- A single noisy client (or attacker) can exhaust the shared bucket and deny
  service to the whole tenant (self-inflicted DoS).
- The per-IP anti-automation throttles provide no per-attacker isolation; an
  attacker is indistinguishable from legitimate traffic.

The naive "fix" of trusting the proxy without pinning the proxy hop is equally
dangerous: `getClientIp()` would then honour client-supplied `X-Forwarded-For`,
making the key fully attacker-controlled and trivially spoofable (a fresh bucket
per request).

## Functional Requirements

- **FR-1**: The production framework configuration MUST explicitly configure
  `framework.trusted_proxies` and `framework.trusted_headers` so that
  `getClientIp()` resolves to the real client IP rather than the load-balancer
  address.
- **FR-2**: Only the directly-connected proxy hop MUST be trusted. The default
  (`TRUSTED_PROXIES=REMOTE_ADDR`) trusts a single hop; operators MUST be able to
  override it with the exact proxy/App Runner CIDR via the `TRUSTED_PROXIES`
  environment variable.
- **FR-3**: Only the `X-Forwarded-For` header MUST be trusted
  (`TRUSTED_HEADERS=x-forwarded-for`). Other `X-Forwarded-*` headers (host,
  proto, port, prefix) MUST NOT be trusted.
- **FR-4**: An `X-Forwarded-For` header originating from an untrusted client
  (whose `REMOTE_ADDR` is not a trusted proxy) MUST be ignored; the IP key MUST
  fall back to `REMOTE_ADDR`.
- **FR-5**: When no proxy is trusted, client-supplied `X-Forwarded-For` MUST be
  ignored entirely (no blanket XFF trust).

## Non-Functional Requirements

- **NFR-Security**: The IP dimension of the anti-automation controls MUST be
  spoof-resistant. The trusted-header set MUST be minimal (`x-forwarded-for`
  only) and the trusted-proxy set MUST be a single, configurable hop — never an
  open/blanket trust. (CWE-307, OWASP API4:2023 Unrestricted Resource
  Consumption.)
- **NFR-Compatibility**: Existing rate-limiter behaviour and the integration
  test contract (`ip:127.0.0.1` keys for local requests with no XFF) MUST remain
  unchanged. No change to limiter names, thresholds, or env-driven limits.
- **NFR-Maintainability**: The fix MUST be configuration-driven (env vars with
  prod-safe defaults), introduce no new directories or classes outside existing
  conventions, and keep PHPInsights complexity 94% / quality + style 100% and
  Deptrac at 0 violations. Domain layer MUST stay framework-free.

## Out of Scope

- Re-architecting the limiter key strategy (email/user buckets, lockout logic).
- Changing rate-limit thresholds or intervals.
- The unrelated email/user-keyed bypass findings tracked separately.
- Infrastructure-as-code provisioning of the exact App Runner CIDR (operators
  set `TRUSTED_PROXIES` at deploy time).
