# Passkey Authentication Manual Test Checklist

Use this checklist to capture the browser and hardware/software authenticator
evidence required for BMAD FR/NFR sign-off.

## Execution Metadata

- Tester: Codex
- Execution date/time (UTC): 2026-05-25 01:36 UTC
- Tested commit SHA: `c0e6fe896143ecbeb26e0e54796c5eb38f3746e6`
- Repro SHA: `58a46bd848e5b9cff70e11e7dc8593c3f1d734f4`
- Latest browser rerun: 2026-06-01T02:23:28.150Z against
  `http://localhost:19081`, using Chrome DevTools virtual CTAP2
  authenticators at runtime source base
  `69af2cf13c46f797da7076bff272fa7736e01ce9`.
- Reviewed PR head bridge: `19694b53eae36aa952ea96389f2e2a2e37fc9fed`; the
  strict BMAD gate re-records the final reviewed commit in its generated
  context.
- Historical environment URL: `https://localhost:65443`
- Historical browser and version: Google Chrome / HeadlessChrome 148
- Operating system/device: local Linux workspace
- Historical authenticator type: Chrome DevTools virtual CTAP2 authenticators
  with resident keys, user verification, and automatic presence simulation
  enabled
- RP ID: `localhost`
- Historical origin: `https://localhost:65443`
- Supporting artifacts: sanitized browser run id `1779672967201-kekp2o`;
  durable sanitized evidence in
  `specs/passkey-authentication/manual-browser-evidence.md`; durable sanitized
  transcript in
  `specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`;
  latest sanitized browser run id `1780280604724-451d4e`; source-impact bridged
  transcript in
  `specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`;
  raw local JSON in `/home/kravtsov/tmp/pr286-manual-webauthn-current.json`;
  expiration run challenge `01KSECHK4BX8HYP4Z2ZE66SXP2`; focused PHPUnit and
  configuration commands listed in `run-summary.md`
- Overall result: Pass

Current PR head note: the browser scenarios below were rerun at runtime source
base `69af2cf13c46f797da7076bff272fa7736e01ce9` with Chrome DevTools virtual
authenticators and are bridged to the current PR-head evidence set. The exact
final reviewed commit is recorded in the BMAD gate artifact generated when the
gate runs. The bridge accounts for later
production changes in `IssuedSessionFactory` rollback-failure logging,
`ApiRateLimitPayloadValueResolver` legacy GraphQL rate-limit fallback parsing,
and the post-`109e7538` removal of sign-up challenge release after rollback
failures. The first two changes do not alter browser WebAuthn option
generation, credential/assertion verification, successful session response
shape, pending-2FA response shape, or persisted passkey credential state. The
single-use sign-up challenge rollback/retry behavior is deterministic server
logic and is covered by current-head automated tests instead of new manual
browser evidence. Details are captured in
`specs/passkey-authentication/manual-browser-evidence.md` and
`specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`.

## Latest Browser Rerun And PR-Head Bridge Template

- Tester: Codex
- Execution date/time (UTC): `2026-06-01T02:23:28.150Z`
- Runtime source base commit: `69af2cf13c46f797da7076bff272fa7736e01ce9`
- Reviewed PR head bridge: `19694b53eae36aa952ea96389f2e2a2e37fc9fed`; the
  strict BMAD gate re-records the final reviewed commit in its generated
  context.
- Environment URL: `http://localhost:19081`
- Browser and version: Google Chrome headless through Chrome DevTools Protocol
- Operating system/device: local Linux workspace
- Authenticator type and configuration: Chrome DevTools virtual CTAP2
  authenticators with resident keys, user verification, and automatic presence
  simulation enabled
- RP ID: `localhost`
- Origin: `http://localhost:19081`
- Sanitized transcript path:
  `specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`
- Overall result: Pass

Latest browser rerun status: completed at the runtime source base commit.
Current PR head bridge status: completed through source-impact analysis and
automated coverage for the post-run production changes, including the
post-`109e7538` single-use sign-up challenge rollback/retry behavior.

## Story 1.1 BMALPH Implementation-Transition Evidence

- Tester: Codex
- Execution date/time (UTC): `2026-06-01T05:00:52Z`
- Tested commit SHA: `b5a593a7df00e51fa215d6990799945a104dd199`
- BMALPH CLI version: `2.11.0`
- Scope: Story 1.1 acceptance criteria for `bmalph doctor`,
  `bmalph implement`, Ralph implementation context generation, and
  `bmalph status` reporting Phase 4 Implementation with status
  `implementing`.

Steps and observed results:

1. Ran `bmalph -C . doctor --json` before restoring generated local assets.
   Observed expected setup failure because `_bmad/`, `_bmad/COMMANDS.md`,
   `_bmad/lite/create-prd.md`, `.ralph/ralph_loop.sh`, and `.ralph/lib/`
   were absent from the workspace.
2. Ran `bmalph -C . upgrade --force`. Observed BMALPH restore `_bmad/` and
   Ralph assets including `.ralph/ralph_loop.sh`, `.ralph/ralph_import.sh`,
   `.ralph/ralph_monitor.sh`, `.ralph/lib/`, `.ralph/PROMPT.md`,
   `.ralph/@AGENT.md`, `.ralph/REVIEW_PROMPT.md`, `.ralph/.ralphrc`, and
   `.ralph/RALPH-REFERENCE.md`.
3. Ran `bmalph -C . doctor --json` again. Observed `passed=19`, `failed=0`,
   `total=19`; checks for `_bmad/`, `_bmad/COMMANDS.md`,
   `_bmad/lite/create-prd.md`, `.ralph/ralph_loop.sh`, and `.ralph/lib/`
   all passed.
4. Ran `bmalph -C . status --json` before transition. Observed Phase 3
   `Solutioning`, status `planning`, artifact directory `docs/planning`, found
   `architecture.md`, `epics.md`, `implementation-readiness.md`, and `prd.md`,
   no missing artifacts, and next action `Run: bmalph implement`.
5. Ran `bmalph -C . implement`. Observed BMALPH locate artifacts, parse
   stories, generate a fix plan for 5 stories, prepare the specs tree, copy
   specs to `.ralph/specs/`, generate `.ralph/SPECS_INDEX.md`,
   `.ralph/PROJECT_CONTEXT.md`, and `.ralph/PROMPT.md`, then report
   `Transition complete: phase 4 (implementing)`.
6. Inspected `.ralph/PROJECT_CONTEXT.md` and `.ralph/SPECS_INDEX.md`.
   Observed Ralph implementation context for the passkey feature with goals,
   scope boundaries, and NFRs, plus an index of 5 transition spec files:
   `README.md`, `architecture.md`, `epics.md`,
   `implementation-readiness.md`, and `prd.md`.
7. Ran `bmalph -C . status --json` after transition. Observed `phase=4`,
   `phaseName=Implementation`, `status=implementing`, Ralph status
   `not_started`, and next action `Start Ralph loop with: bmalph run`.

Result: Pass. Story 1.1 implementation-transition evidence now supports the
doctor, implement, Ralph context, and Phase 4 status acceptance criteria without
fabricating manual evidence.

## Preconditions

- The service is running with the passkey environment values documented in
  `docs/passkey-authentication.md`.
- A registered baseline account exists for existing-email and authenticated
  enrollment checks.
- A registered account with TOTP enabled exists for the 2FA parity check.
- The browser supports WebAuthn JSON helpers or the client uses the documented
  base64url-to-ArrayBuffer fallback.
- Network/API response artifacts are captured without storing secrets,
  credential private material, or bearer tokens.

## Scenario 1: Existing-Email Sign-Up Options Rejection

Steps:

1. Choose an email address that already belongs to a registered account.
2. Submit `POST /api/passkeys/signup/options` with that email, valid initials,
   and an optional display name.
3. Confirm the API returns the documented conflict response.
4. Confirm no browser WebAuthn creation ceremony starts.
5. Confirm no passkey challenge is persisted for the rejected request.

Expected result: the endpoint returns `409`, no `challenge_id` is returned, and no
passkey challenge is created.

Observed result: browser run id `1779672967201-kekp2o` created baseline account
`manual-existing-1779672967201-kekp2o@example.test`, then signup options for the
same email returned `409` and did not return a `challenge_id`. Automated
coverage in `PasskeyRegistrationCommandHandlerTest::testStartSignupRejectsExistingEmail`
asserts no challenge id is generated and no challenge is saved for this path.
June 1 run id `1780280604724-451d4e`, bridged by current source-impact
analysis, also returned HTTP `409` and no challenge id for existing-email signup
rejection.

Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`,
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`,
`specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`.

Result: Pass

## Scenario 2: New-Email Sign-Up Ceremony

Steps:

1. Choose an email address that is not registered.
2. Submit `POST /api/passkeys/signup/options` with that email, valid initials,
   and an optional display name.
3. Start `navigator.credentials.create()` with the returned `public_key`
   options.
4. Submit `POST /api/passkeys/signup/complete` with the returned `challengeId`
   and browser credential JSON.
5. Confirm a user session is issued.
6. Confirm the stored credential can be used for a later passkey sign-in.

Expected result: the browser creates a credential, the API completes sign-up,
the user and credential are persisted, and the response issues the expected
session payload/cookie.

Observed result: browser run id `1779672967201-kekp2o` requested signup options
for `manual-signup-1779672967201-kekp2o@example.test`, created a credential with
`navigator.credentials.create()`, submitted `credential.toJSON()` to
`/api/passkeys/signup/complete`, and received access and refresh tokens with
`2fa_enabled=false`.
June 1 run id `1780280604724-451d4e`, bridged by current source-impact
analysis, also returned a challenge id, RP ID `localhost`,
`residentKey=required`, `2fa=false`, and access/refresh token fields after
browser credential creation and signup completion.

Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`,
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`,
`specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`.

Result: Pass

## Scenario 3: Authenticated Passkey Enrollment

Steps:

1. Sign in with an existing account.
2. Submit `POST /api/passkeys/register/options`.
3. Start `navigator.credentials.create()` with the returned `public_key`
   options.
4. Submit `POST /api/passkeys/register/complete` with the returned
   `challengeId` and browser credential JSON.
5. Confirm the account can sign in with the newly enrolled passkey.

Expected result: authenticated enrollment stores one credential for the current
user and excludes existing credentials when supported.

Observed result: browser run id `1779672967201-kekp2o` used the issued bearer
token, requested authenticated registration options, created a second credential
on a second virtual authenticator, and `/api/passkeys/register/complete` returned
a credential id.
June 1 run id `1780280604724-451d4e`, bridged by current source-impact
analysis, also returned a registration challenge id, an `excludeCredentials`
array, `residentKey=required`, and a credential id after browser credential
creation and registration completion.

Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`,
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`,
`specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`.

Result: Pass

## Scenario 4: Passkey Sign-In With 2FA Parity

Steps:

1. Use an account that has both a passkey and TOTP enabled.
2. Submit `POST /api/passkeys/signin/options` for that account.
3. Start `navigator.credentials.get()` with the returned `public_key` options.
4. Submit `POST /api/passkeys/signin/complete` with the returned `challengeId`
   and browser assertion JSON.
5. Confirm the response follows the existing pending 2FA session behavior
   instead of issuing final tokens immediately.

Expected result: passkey assertion succeeds, the API creates a pending 2FA
session, and final tokens/cookies are withheld until the existing 2FA completion
flow finalizes authentication.

Observed result: browser run id `1779672967201-kekp2o` enabled TOTP with
`/api/2fa/setup` and `/api/2fa/confirm`, receiving 8 recovery codes. A later
passkey sign-in returned `2fa_enabled=true` and a `pending_session_id`, and did
not return access or refresh tokens.
June 1 run id `1780280604724-451d4e`, bridged by current source-impact
analysis, also returned empty
`allowCredentials`, `userVerification=required`, `2fa=false`, and tokens
before 2FA; after TOTP setup/confirm it returned `2fa=true`, a pending session
id, and no access or refresh token fields.

Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`,
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`,
`specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`.

Result: Pass

## Scenario 5: Challenge Reuse And Expiration

Steps:

1. Complete one passkey sign-up, registration, or sign-in challenge
   successfully.
2. Resubmit the same `challengeId` and credential JSON.
3. Start a new ceremony, wait until the configured challenge TTL expires, and
   submit the expired challenge.

Expected result: reused and expired challenges are rejected through the generic
invalid-credential path, and no additional credential/session side effect is
created.

Observed result: browser run id `1779672967201-kekp2o` resubmitted the completed
signup challenge and received `401` without access or refresh tokens. A separate
expiration run used `PASSKEY_CHALLENGE_TTL_SECONDS=1`, waited past expiry for
challenge `01KSECHK4BX8HYP4Z2ZE66SXP2`, and completion returned `401` with
detail `Invalid or expired passkey challenge.` and no access token. Automated
repository coverage verifies atomic challenge claim returns no challenge when no
record is updated, and current-head rollback coverage verifies failed signup
completion does not keep additional credential or user state and does not
release the consumed sign-up challenge for retry.
June 1 run id `1780280604724-451d4e`, bridged by current source-impact
analysis, also returned HTTP `401` and no access token for
successful-completion replay rejection and expiration rejection.
Post-`109e7538` retry-after-rollback rejection is covered by
`PasskeySignUpAuthenticationRollbackTest::testCompleteSignupRejectsRetryAfterAuthenticationIssueFails`.

Artifacts: `specs/passkey-authentication/manual-browser-evidence.md`,
`specs/passkey-authentication/manual-browser-run-1779672967201-kekp2o.sanitized.md`,
`specs/passkey-authentication/manual-browser-run-1780280604724-451d4e.sanitized.md`.

Result: Pass

## Evidence Update Instructions

After executing the scenarios, update `specs/passkey-authentication/run-summary.md`
with the commit SHA, tester, date/time, browser/authenticator details, scenario
results, and artifact references.
