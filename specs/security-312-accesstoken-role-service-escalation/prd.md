# PRD — Fix: OAuth2/JWT access tokens silently escalate to ROLE_SERVICE (issue #312)

## Problem (FR)

The custom `AccessTokenValidator` and `AccessTokenUserResolver` treat any
validly-signed RS256 JWT that lacks both an `iss` and a `roles` claim as a
`ROLE_SERVICE` principal, and skip issuer/audience validation for that class of
token. Because the Lexik first-party JWT and the League OAuth2 authorization
server are wired to the **same** RSA keypair (`config/jwt/private.pem` /
`public.pem`), a League OAuth2 access token (shape: `aud=client_id`, `sub`,
`scopes`, `exp/nbf`, **no** `iss`, **no** `roles`) passes signature
verification and is auto-elevated to `ROLE_SERVICE`.

`ROLE_SERVICE` is the sole gate for `POST /api/users/batch` (bulk user import)
and also satisfies the `^/api/` `ROLE_USER` catch-all, so any token minted by
`/api/oauth/token` — including a minimal-scope `client_credentials` token or a
`password`-grant end-user token — gains service-level privileges.

CWE-269 / CWE-287 / CWE-345. Severity: **critical**.

## Functional requirements

- **FR-1**: Issuer and audience MUST be validated for **every** accepted access
  token, unconditionally. A token whose `iss != vilnacrm-user-service` or whose
  `aud` does not contain `vilnacrm-api` MUST be rejected with
  `Invalid access token claims.`
- **FR-2**: `ROLE_SERVICE` MUST NOT be inferred from the absence of claims. A
  token without an explicit `roles` claim defaults to the least-privilege
  `ROLE_USER`.
- **FR-3**: A token that carries a `roles` claim MUST also carry a `sid`
  (session binding) — unchanged from prior behaviour.
- **FR-4**: Legitimate first-party tokens (user and service) that carry
  `iss=vilnacrm-user-service`, `aud=vilnacrm-api`, `sid`, and explicit `roles`
  continue to validate and resolve exactly as before.
- **FR-5 (defense-in-depth)**: `create_batch_http` (`POST /api/users/batch`)
  carries an explicit API Platform `security: is_granted('ROLE_SERVICE')`
  expression so the restriction survives firewall misconfiguration.

## Non-functional requirements

- **NFR-Security**: League OAuth2 tokens can no longer cross-verify as
  first-party principals; privilege derives only from positively-asserted,
  signed first-party claims (no insecure defaults — CWE-1188).
- **NFR-Compatibility**: No change to the wire format of issued first-party
  tokens; existing Behat/integration ROLE_SERVICE and ROLE_USER flows pass
  unchanged.
- **NFR-Maintainability**: Validation logic simplified — one mandatory
  iss/aud gate instead of conditional branches.

## Out of scope (tracked separately)

- Separating the OAuth2 resource-server keypair from the first-party JWT key
  (defense-in-depth hardening) — follow-up; the iss/aud gate already closes the
  escalation with the shared key.
- Session-revocation checks for genuine machine ROLE_SERVICE tokens (finding 11
  residual): service tokens are M2M and stateless by design; the escalation
  vector is closed because ROLE_SERVICE now requires a trusted signed claim.
