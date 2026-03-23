# Naming Refactor Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Normalize changed class and file names to established patterns and remove ambiguous or ad hoc naming from the current PR.

**Architecture:** Keep existing DDD boundaries intact and limit this pass to renames, directory/type alignment, and dependency wiring updates. Do not mix behavior changes into the rename work unless a rename exposes a broken dependency registration or test fixture.

**Tech Stack:** PHP 8.3, Symfony 7.3, API Platform 4.1, Doctrine ODM, PHPUnit, Deptrac

### Task 1: Finish Matcher Renames

**Files:**

- Modify: `src/Shared/Infrastructure/Adapter/DualAuthenticator.php`
- Modify: `src/User/Infrastructure/EventListener/SchemathesisCleanupListener.php`
- Modify: `src/User/Infrastructure/Resolver/SchemathesisEmailResolver.php`
- Rename: `src/Shared/Infrastructure/Validator/PublicAccessValidator.php`
- Rename: `src/User/Infrastructure/Validator/SchemathesisCleanupValidator.php`
- Rename tests under `tests/Unit/Shared/Infrastructure/Validator/`
- Rename tests under `tests/Unit/User/Infrastructure/`

**Step 1: Rename `PublicAccessValidator` to `PublicAccessMatcher`**

Use the existing matcher-oriented semantics because the class only matches route rules.

**Step 2: Rename `SchemathesisCleanupValidator` to `SchemathesisCleanupMatcher`**

Use the existing matcher-oriented semantics because the class only matches request/response conditions.

**Step 3: Update constructor arguments and local variable names**

Use `$publicAccessMatcher` and `$schemathesisCleanupMatcher` consistently.

**Step 4: Rename matching tests**

Mirror production names exactly in test class names and filenames.

**Step 5: Run verification**

Run: `make unit-tests`

**Step 6: Commit**

```bash
git add src/Shared/Infrastructure src/User/Infrastructure tests/Unit/Shared/Infrastructure tests/Unit/User/Infrastructure
git commit -m "refactor: rename matcher-style classes consistently"
```

### Task 2: Fix OpenAPI Endpoint Naming

**Files:**

- Rename: `src/Shared/Application/OpenApi/Factory/Endpoint/OAuthAuthEndpointFactory.php`
- Rename: `src/Shared/Application/OpenApi/Factory/Endpoint/ParamUserEndpointFactory.php`
- Rename: `src/Shared/Application/Provider/OpenApi/ParamUserResponseProvider.php`
- Rename matching tests under `tests/Unit/Shared/Application/OpenApi/Factory/Endpoint/`

**Step 1: Rename `OAuthAuthEndpointFactory` to `OAuthAuthorizeEndpointFactory`**

Align the class name with the `/oauth/authorize` endpoint and avoid the doubled `Auth` wording.

**Step 2: Rename `ParamUserEndpointFactory`**

Use a resource-oriented name such as `UserByIdEndpointFactory` or `UserItemEndpointFactory`.

**Step 3: Rename `ParamUserResponseProvider`**

Use the same resource-oriented name chosen in Step 2, for example `UserByIdResponseProvider`.

**Step 4: Rename tests to match production names**

Remove the current `ParamUser` vs `ParametrizedUser` mismatch.

**Step 5: Run verification**

Run: `make unit-tests`

**Step 6: Commit**

```bash
git add src/Shared/Application/OpenApi src/Shared/Application/Provider/OpenApi tests/Unit/Shared/Application/OpenApi
git commit -m "refactor: normalize openapi endpoint naming"
```

### Task 3: Normalize Symfony Constraint Validator Naming

**Files:**

- Modify: `src/Shared/Application/Validator/Constraint/CreateUserBatch.php`
- Modify: `src/Shared/Application/Validator/Constraint/UniqueEmail.php`
- Rename: `src/Shared/Application/Validator/CreateUserBatchValidator.php`
- Rename: `src/Shared/Application/Validator/CreateUserBatchConstraintValidator.php`
- Rename: `src/Shared/Application/Validator/UniqueEmailValidator.php`
- Rename matching tests under `tests/Unit/Shared/Application/Validator/`

**Step 1: Reserve `*ConstraintValidator` for Symfony `ConstraintValidator` classes**

The class returned by a Symfony constraint should use the explicit `ConstraintValidator` suffix.

**Step 2: Rename `CreateUserBatchValidator` to `CreateUserBatchConstraintValidator`**

This is the actual Symfony validator.

**Step 3: Rename the current helper `CreateUserBatchConstraintValidator`**

Use a helper-oriented name that reflects raw payload validation, for example `CreateUserBatchPayloadValidator`.

**Step 4: Rename `UniqueEmailValidator` to `UniqueEmailConstraintValidator`**

This is the actual Symfony validator.

**Step 5: Keep or rename the helper class deliberately**

If the helper remains in `Validator/`, keep a name that does not collide with Symfony naming, such as `EmailUniquenessValidator`.

**Step 6: Update all constraint `validatedBy()` mappings and tests**

Make test names follow the new production names exactly.

**Step 7: Run verification**

Run: `make unit-tests`

**Step 8: Commit**

```bash
git add src/Shared/Application/Validator tests/Unit/Shared/Application/Validator
git commit -m "refactor: clarify constraint validator naming"
```

### Task 4: Resolve Directory-Type Mismatch for Account Lockout

**Files:**

- Renamed: `src/User/Infrastructure/Provider/RedisAccountLockoutProvider.php` (was `Validator/RedisAccountLockout.php`)
- Renamed: `src/User/Application/Provider/AccountLockoutProviderInterface.php` (was `Validator/AccountLockoutValidatorInterface.php`)
- Renamed tests: `tests/Unit/User/Infrastructure/Provider/RedisAccountLockoutProviderTest.php`

**Resolution**: Renamed to Provider — class manages lockout state (read + write + clear), not validation.

**Step 3: Update tests and service references**

Mirror the chosen production name everywhere.

**Step 4: Run verification**

Run: `make unit-tests`

**Step 5: Commit**

```bash
git add src/User/Application/Validator src/User/Infrastructure/Validator tests/Unit/User/Infrastructure/Validator
git commit -m "refactor: align account lockout class with validator naming"
```

### Task 5: Review Exceptions and Close the Naming Pass

**Files:**

- Review only: `src/OAuth/Infrastructure/Adapter/*.php`
- Review only: `src/User/Application/Validator/UserCredentialValidator.php`
- Review only: `src/User/Infrastructure/Factory/TOTPFactory.php`

**Step 1: Keep vendor-aligned `*Manager` names unless wrapping away from League interfaces**

Do not rename the OAuth manager classes in this pass if the only reason is dislike of the `Manager` suffix.

**Step 2: Decide whether `UserCredentialValidator` is acceptable**

If the team wants pure semantics, plan a separate pass to rename it to an authenticator-style concept and move it accordingly.

**Step 3: Decide whether `TOTPFactory` is too generic**

If renamed later, prefer a more explicit factory name based on what it returns, not what it instantiates internally.

**Step 4: Run final verification**

Run: `make deptrac`

Run: `make unit-tests`

**Step 5: Final commit**

```bash
git add .
git commit -m "refactor: complete naming normalization pass"
```
