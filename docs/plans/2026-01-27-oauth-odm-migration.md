# OAuth ODM Migration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Finish the OAuth2 ODM migration with full acceptance coverage (Behat + existing Schemathesis) and a safe data strategy for existing OAuth collections.

**Architecture:** Persist League bundle models directly via Doctrine ODM XML mappings and custom ODM types. Acceptance coverage comes from Behat scenarios for OAuth grant flows and explicit error paths, while Schemathesis covers schema-level invalid inputs and examples. Add a migration (or wipe+reseed) path so existing OAuth documents align with new reference-based mappings.

**Tech Stack:** Symfony 7.2, Doctrine ODM MongoDB, league/oauth2-server-bundle, Behat, PHPUnit, Schemathesis.

---

### Task 1: Build OAuth Coverage Matrix and Identify Gaps

**Files:**
- Create: `docs/oauth-coverage-matrix.md`
- Read: `features/oauth.feature`, `tests/Behat/OAuthContext/OAuthContext.php`, `docs/api-endpoints.md`, `config/routes.yaml`

**Step 1: Draft the coverage matrix**

Create `docs/oauth-coverage-matrix.md` with a table like:

```
| Endpoint | Flow / Case | Covered By | Scenario/Tool |
| /api/oauth/token | client_credentials success | Behat | Obtaining access token with client-credentials grant |
| /api/oauth/token | invalid client | Behat | Obtaining access token with invalid credentials |
| /api/oauth/authorize | invalid scope | Behat | Failing to obtain authorization code with invalid scope |
| ... | ... | Schemathesis | OAuth OpenAPI examples |
```

**Step 2: Map existing coverage**

Populate the table from `features/oauth.feature` and note any cases already validated by Schemathesis (from current `app:seed-schemathesis-data` fixtures and schemathesis config).

**Step 3: Identify gaps and record them**

Add a “Missing Coverage” section listing each gap with a short description (e.g., “PKCE success path for public client”, “missing redirect_uri on authorize request”, “auth code reuse rejected”).

**Step 4: Commit**

```
git add docs/oauth-coverage-matrix.md
git commit -m "docs: add oauth coverage matrix"
```

---

### Task 2: Add Behat Scenarios for Missing OAuth Edge Cases

**Files:**
- Modify: `features/oauth.feature`
- Modify: `tests/Behat/OAuthContext/OAuthContext.php`
- Modify/Create (if needed): `tests/Behat/OAuthContext/Input/AuthorizationCodeGrantInput.php`

**Step 1: Add ONE missing scenario (RED)**

Pick the highest-impact gap from Task 1 and add a single scenario to `features/oauth.feature` (example: “Public client PKCE success + token exchange”).

Run only that scenario (expect FAIL):

```
docker compose exec -e APP_ENV=test php ./vendor/bin/behat --name "Public client PKCE" --format=progress
```

**Step 2: Minimal step/input changes (GREEN)**

Update `tests/Behat/OAuthContext/OAuthContext.php` and (if required) extend `AuthorizationCodeGrantInput` to include `code_verifier` so the auth-code exchange can be validated.

Example field addition:

```php
public ?string $code_verifier = null;
```

**Step 3: Re-run the same scenario**

```
docker compose exec -e APP_ENV=test php ./vendor/bin/behat --name "Public client PKCE" --format=progress
```

Expect PASS.

**Step 4: Commit**

```
git add features/oauth.feature tests/Behat/OAuthContext/OAuthContext.php tests/Behat/OAuthContext/Input/AuthorizationCodeGrantInput.php
git commit -m "test: cover oauth pkce success path"
```

**Step 5: Repeat for each gap**

Repeat Steps 1–4 for each remaining gap from Task 1. Keep each scenario in its own commit.

---

### Task 3: Decide and Implement OAuth Data Strategy (Migration vs. Wipe)

**Files (Migration path):**
- Create: `src/OAuth/Infrastructure/Command/MigrateOauthDocumentsCommand.php`
- Create: `tests/Integration/OAuth/Infrastructure/Command/MigrateOauthDocumentsCommandTest.php`
- Modify: `config/services.yaml` (service registration)

**Step 1: Confirm strategy**

Decide with the team:
- **A) Migration**: Convert existing oauth2_* documents to new reference-based fields.
- **B) Wipe + Reseed**: Drop oauth2_* collections and re-seed via fixtures.

If **B**, document this in `docs/oauth-coverage-matrix.md` under “Data Strategy” and skip Steps 2–4.

**Step 2: Write a failing integration test (RED)**

Create `MigrateOauthDocumentsCommandTest.php` that seeds a legacy document (with `clientIdentifier` / `accessTokenIdentifier`) and expects the command to rewrite to `client` / `accessToken` references.

Run:
```
make integration-tests
```
Expect FAIL until command exists.

**Step 3: Implement minimal command (GREEN)**

Command should:
- Load legacy docs by collection
- Resolve client/access token references
- Write new fields (`client`, `accessToken`) and remove legacy identifier fields
- Log counts for migrated docs

**Step 4: Re-run integration test**

```
make integration-tests
```
Expect PASS.

**Step 5: Commit**

```
git add src/OAuth/Infrastructure/Command/MigrateOauthDocumentsCommand.php tests/Integration/OAuth/Infrastructure/Command/MigrateOauthDocumentsCommandTest.php config/services.yaml
git commit -m "feat: migrate legacy oauth documents to odm references"
```

---

### Task 4: Update OAuth ODM Test Results and Run Full Verification

**Files:**
- Modify: `docs/oauth-odm-test-results.md`

**Step 1: Update the document**

Replace outdated references to “Document DTOs” with the current League model mapping approach and update the “Relationships” section to reflect ODM references (`client`, `accessToken`).

**Step 2: Run full test suite**

```
make unit-tests
make integration-tests
make behat
```

If Schemathesis is part of the acceptance definition, also run:

```
make schemathesis-validate
```

**Step 3: Record results**

Paste actual run summaries into `docs/oauth-odm-test-results.md` (date/time, counts, pass/fail).

**Step 4: Commit**

```
git add docs/oauth-odm-test-results.md
git commit -m "docs: refresh oauth odm test results"
```

---

### Task 5: Final Validation

**Step 1: Architecture/quality gates**

```
make phpcsfixer
make psalm
make deptrac
```

**Step 2: CI (if required before merge)**

```
make ci
```

**Step 3: Announce completion**

Summarize:
- Coverage matrix status (100% or documented gaps)
- Data strategy chosen (migration vs. wipe)
- Test status from latest runs


---

# Context Handoff (What Was Done, Why, What’s Next)

## What was done (high level)

- Replaced custom OAuth ODM “Document” entities with direct ODM mapping of League bundle models.
  - **Why:** the bundle already uses models + DI-friendly managers; mapping the bundle models avoids DTO conversion and removes an extra abstraction layer.
  - Removed `src/OAuth/Domain/Entity/*Document.php` and old XML mappings.
  - Added new ODM XML mappings for League models in `config/doctrine/OAuth/`:
    - `AbstractClient.mongodb.xml` (mapped-superclass)
    - `Client.mongodb.xml`
    - `AccessToken.mongodb.xml`
    - `AuthorizationCode.mongodb.xml`
    - `RefreshToken.mongodb.xml`
  - Mapped references using ODM `reference-one` fields:
    - `AccessToken.client` → `Client`
    - `AuthorizationCode.client` → `Client`
    - `RefreshToken.accessToken` → `AccessToken`

- Added custom Doctrine ODM types for League value objects:
  - `oauth2_scope`, `oauth2_grant`, `oauth2_redirect_uri`
  - Implemented in `src/OAuth/Infrastructure/DoctrineType/` and registered in `config/packages/doctrine_mongodb.yaml`.
  - **Why:** League models store `Scope`, `Grant`, `RedirectUri` value objects; ODM needs stable serialization to strings.

- Updated OAuth managers to persist bundle models directly (no DTO conversion):
  - `AccessTokenManager`, `AuthorizationCodeManager`, `RefreshTokenManager`, `ClientManager`.
  - **Why:** DI hooks already allow swapping managers; ODM can persist bundle model objects.

- Updated `CredentialsRevoker` to query ODM references (client/access token relationships).

- Introduced `OAUTH_PERSIST_ACCESS_TOKEN` env flag and injected it into `AccessTokenManager`.
  - **Why:** League bundle supports disabling access-token persistence; we preserve the flag, and skip DB operations when false.

- Behat + test stabilization:
  - Updated Behat steps to be idempotent where duplicate emails caused failures.
  - Adjusted feature fixtures to use unique emails in several scenarios.
  - Reworked health-check failure step to simulate MongoDB failure instead of Doctrine DBAL.
  - Added unit tests for new ODM types + managers + OAuth seeder behavior.

## Additional fixes applied after review

- Fixed `clearExpired()` return values in `AuthorizationCodeManager` and `RefreshTokenManager` to correctly use `getDeletedCount()` when ODM returns `DeleteResult`.
  - **Why:** casting `DeleteResult` to int always yields `1`, which is incorrect and breaks expectations in tests.

- Restored `oauth2_clients` mapping with index on `active` in `Client.mongodb.xml`.
  - **Why:** without collection/index mapping, ODM defaults to `clients` collection and breaks existing data visibility.

- Health-check context now marks reflection property accessible before injecting failing Mongo client.
  - **Why:** PHP 8.3 requires `setAccessible(true)` for private props; otherwise runtime error in Behat.

## Tests run (locally)

- `docker compose exec -e APP_ENV=test php ./vendor/bin/phpunit --testsuite=Unit --filter "AuthorizationCodeManagerTest|RefreshTokenManagerTest"`
  - **Result:** PASS (6 tests)

> NOTE: Full unit, integration, and Behat suites were not re-run after the last fixes in this context. Those are required before completion.

## Known risks / pending decisions

1. **Data migration risk (critical):**
   - Old OAuth documents used `clientIdentifier`/`accessTokenIdentifier` fields, but new mapping uses ODM references (`client`, `accessToken`).
   - Existing data will not hydrate unless migrated.
   - **Decision needed:** migrate legacy OAuth documents or wipe + reseed collections.

2. **Persist access token flag:**
   - If `OAUTH_PERSIST_ACCESS_TOKEN=0`, `CredentialsRevoker` cannot revoke refresh tokens by user/client because access tokens won’t exist in DB.
   - **Decision needed:** keep flag always on in production or adjust revoker logic.

3. **API Platform mapping path includes OAuth domain path:**
   - `config/packages/api_platform.yaml` still points to `src/OAuth/Domain/Entity` (now mostly empty).
   - **Decision needed:** keep as-is or clean up for clarity.

## What should be done next

1. **Create a coverage matrix**
   - Add `docs/oauth-coverage-matrix.md` summarizing Behat scenarios + Schemathesis coverage.
   - Identify any missing OAuth edge cases.

2. **Fill coverage gaps with Behat scenarios (TDD)**
   - Add missing scenarios one-by-one in `features/oauth.feature`.
   - Update `OAuthContext` and inputs only as needed for each gap.

3. **Choose data strategy and implement**
   - **Option A: Migration command** to translate legacy fields to new ODM references.
   - **Option B: Wipe + reseed** OAuth collections and document the decision.

4. **Refresh `docs/oauth-odm-test-results.md`**
   - The current document references the old DTO conversion pattern and is outdated.
   - Update to reflect ODM mappings + value-object types + references.

5. **Run verification (required)**
   - `make unit-tests`
   - `make integration-tests`
   - `make behat`
   - If Schemathesis is required for acceptance, run `make schemathesis-validate`.

## Files touched (key)

- ODM mappings: `config/doctrine/OAuth/*.mongodb.xml`
- ODM types: `src/OAuth/Infrastructure/DoctrineType/*`
- Managers: `src/OAuth/Infrastructure/Manager/*`
- Revoker: `src/OAuth/Infrastructure/Service/CredentialsRevoker.php`
- Config: `config/packages/doctrine_mongodb.yaml`, `config/packages/league_oauth2_server.yaml`, `config/services.yaml`, `.env`, `.env.test`
- Behat features and contexts: `features/*.feature`, `tests/Behat/*`
- Unit tests: `tests/Unit/OAuth/Infrastructure/*`

