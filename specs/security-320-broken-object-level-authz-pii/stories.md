# Stories — Security #320

## Story 1 — Scope item read (REST Get + GraphQL Query) to the owner

**Change:** In `config/api_platform/resources/User.yaml`, add
`security: "is_granted('ROLE_USER') and object.getId() == user.getId().__toString()"`
to the `ApiPlatform\Metadata\Get` operation and to the
`ApiPlatform\Metadata\GraphQl\Query` operation. This mirrors the existing
`Patch`/`Put`/`Delete` and GraphQL `update`/`delete` expressions, where the
authenticated `user` is an `AuthorizationUserDto` (its `getId()` returns a
Stringable `UuidInterface`) and `object` is the loaded `User` entity.

**Test mapping:**

- Positive: owner requests their own id → record returned (existing
  authorization-expression behavior, exercised by item-read functional/Behat
  coverage in CI; the expression is identical to the already-tested write ops).
- Negative: a `ROLE_USER` requests another user's id → 403 (expression evaluates
  false, same denial path as write ops).
- Edge: unauthenticated request → 401 from firewall (`^/api/ -> ROLE_USER`).

**Verification commands:** see Story 3 (config validated by deptrac/cs-fixer/psalm
on changed files; expression parity with write ops is the regression guard).

## Story 2 — Scope collection read (REST GetCollection + GraphQL QueryCollection)

**Change:** Add `src/User/Application/Provider/UserCollectionProvider.php`, an
API Platform `ProviderInterface<User>` that resolves the current authenticated
`AuthorizationUserDto` via `Security`, loads only that user's `User` entity via
`GetUserQueryHandlerInterface`, and returns it as a single-element list. Wire it
as `provider:` on `ApiPlatform\Metadata\GetCollection` and
`ApiPlatform\Metadata\GraphQl\QueryCollection` in
`config/api_platform/resources/User.yaml`. Returns `[]` when there is no
`AuthorizationUserDto` caller or the caller's own record is missing.

**Test mapping** (`tests/Unit/User/Application/Provider/UserCollectionProviderTest.php`):

- Positive — `testProvideReturnsOnlyCurrentAuthenticatedUser`: authenticated
  caller → list contains exactly the caller's own record (loaded by their id).
- Negative — `testProvideReturnsEmptyWhenNotAuthorizationUser`: a non-DTO
  `UserInterface` principal → `[]`, query handler never called (no enumeration).
- Negative — `testProvideReturnsEmptyWhenNoAuthenticatedUser`: `getUser()` null
  → `[]`, query handler never called.
- Edge — `testProvideReturnsEmptyWhenOwnRecordMissing`: caller's record deleted
  (`UserNotFoundException`) → `[]` instead of a 404.

These tests FAIL without the provider (the previous default provider returned
all users) and PASS with it.

## Story 3 — Local verification (one-off containers)

Run from `/home/kravtsov/Projects/secfix-320` (image `secfix-312-php:latest`,
read-only shared vendor):

- Unit (focused): `phpunit --testsuite=Unit --filter "User"` → expect OK.
- Unit (provider only):
  `phpunit --testsuite=Unit --filter UserCollectionProvider` → 4 tests pass.
- Deptrac: `deptrac analyse --config-file=deptrac.yaml` → Violations 0.
- Psalm (changed files):
  `psalm src/User/Application/Provider/UserCollectionProvider.php tests/Unit/User/Application/Provider/UserCollectionProviderTest.php`
  → No errors.
- PHP CS Fixer (dry-run, changed files) → 0 of N files need fixing.
