# Stories — issue #312

## Story 1: Make issuer/audience validation mandatory for every token
- **As** the API security boundary, **I** reject any access token whose `iss`
  is not `vilnacrm-user-service` or whose `aud` does not contain
  `vilnacrm-api`, **so that** tokens minted for other audiences (League OAuth2
  client tokens) cannot authenticate as first-party principals.
- Implementation: `AccessTokenValidator::validateClaims()` calls
  `validateIssuerAndAudience()` unconditionally; the conditional
  `validateFirstPartyClaims()` is replaced by `validateSessionBinding()`.
- Tests (positive/negative/edge):
  - positive: first-party token with valid iss/aud/sid/roles validates.
  - negative: OAuth2-shaped token (no iss, `aud=client_id`) rejected.
  - negative: token with `aud=vilnacrm-api` but no iss rejected.
  - edge: token with valid iss/aud but no roles → `ROLE_USER`.

## Story 2: Never infer ROLE_SERVICE from missing claims
- **As** the role extractor, **I** default a token without an explicit `roles`
  claim to `ROLE_USER`, **so that** absence of claims cannot grant service
  privileges (CWE-1188 insecure default).
- Implementation: `AccessTokenValidator::extractRoles()` returns `['ROLE_USER']`
  when `roles` is absent.
- Test: `testValidateDefaultsToRoleUserWhenRolesClaimAbsent`,
  `testValidateAcceptsExplicitServiceTokenWithFirstPartyClaims`.

## Story 3: Defense-in-depth on the bulk-import endpoint
- **As** the bulk user-import operation, **I** also enforce
  `is_granted('ROLE_SERVICE')` at the API Platform layer, **so that** the
  service-only restriction survives a firewall misconfiguration.
- Implementation: `security` expression on `create_batch_http` in
  `config/api_platform/resources/User.yaml`.
- Coverage: existing `features/auth_gate.feature` batch scenarios
  (403 without ROLE_SERVICE, success with ROLE_SERVICE) continue to pass.

## Verification
- Unit: `tests/Unit/Shared/Infrastructure/Validator/*`,
  `tests/Unit/Shared/Infrastructure/Adapter/DualAuthenticatorTest.php`.
- E2E: `features/auth_gate.feature`, `features/user_operations.feature`,
  `features/user_localization.feature`, `features/error_format.feature`.
- Static: psalm, deptrac (no new boundary crossings — edits stay in
  Infrastructure + config), phpcsfixer, phpinsights.
