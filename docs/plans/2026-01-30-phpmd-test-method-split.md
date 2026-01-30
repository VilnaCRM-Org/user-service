# PHPMD Test Method Split Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Refactor Behat contexts and PHPUnit test classes so no test class exceeds PHPMD TooManyMethods/TooManyPublicMethods thresholds, then pass `make phpmd` and `make phpinsights`.

**Architecture:** Split large test/Behat classes into smaller, focused classes. Use shared state services for Behat contexts to preserve scenario data across split contexts. Keep all step definitions and assertions unchanged.

**Tech Stack:** PHP 8.3, Behat (SymfonyExtension), PHPUnit 10, PHPMD, Symfony test container.

---

### Task 1: Add shared state for OAuth Behat contexts

**Files:**
- Create: `tests/Behat/OAuthContext/OAuthContextState.php`
- Create: `tests/Behat/OAuthContext/OAuthStateContext.php`
- Modify: `behat.yml.dist`

**Step 1: Create state holder class**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use App\Tests\Behat\OAuthContext\Input\ObtainAccessTokenInput;
use App\Tests\Behat\OAuthContext\Input\ObtainAuthorizeCodeInput;
use Symfony\Component\HttpFoundation\Response;

final class OAuthContextState
{
    public ?ObtainAccessTokenInput $obtainAccessTokenInput = null;
    public ?ObtainAuthorizeCodeInput $obtainAuthorizeCodeInput = null;
    public ?Response $response = null;
    public string $authCode = '';
    public ?string $clientId = null;
    public ?string $clientSecret = null;
    public string $refreshToken = '';
    public ?string $username = null;
    public ?string $codeVerifier = null;

    public function reset(): void
    {
        $this->obtainAccessTokenInput = null;
        $this->obtainAuthorizeCodeInput = null;
        $this->response = null;
        $this->authCode = '';
        $this->clientId = null;
        $this->clientSecret = null;
        $this->refreshToken = '';
        $this->username = null;
        $this->codeVerifier = null;
    }
}
```

**Step 2: Create state-reset context**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

final class OAuthStateContext implements Context
{
    public function __construct(private OAuthContextState $state)
    {
    }

    /**
     * @BeforeScenario
     */
    public function resetOAuthState(BeforeScenarioScope $scope): void
    {
        $this->state->reset();
    }
}
```

**Step 3: Register new context in `behat.yml.dist`**
- Add `App\Tests\Behat\OAuthContext\OAuthStateContext` to the `default` suite contexts.

**Step 4: Run `make phpmd` (expect OAuthContext violations still present until split)**

---

### Task 2: Split OAuthContext into focused contexts (≤10 public methods each)

**Files:**
- Create: `tests/Behat/OAuthContext/OAuthClientContext.php`
- Create: `tests/Behat/OAuthContext/OAuthAuthorizationContext.php`
- Create: `tests/Behat/OAuthContext/OAuthTokenContext.php`
- Create: `tests/Behat/OAuthContext/OAuthErrorContext.php`
- Create: `tests/Behat/OAuthContext/OAuthAuthenticationContext.php`
- Delete: `tests/Behat/OAuthContext/OAuthContext.php`
- Modify: `behat.yml.dist`

**Step 1: Create OAuthClientContext (client setup + inputs)**

```php
final class OAuthClientContext implements Context
{
    public function __construct(
        private OAuthContextState $state,
        private ClientManagerInterface $clientManager,
    ) {}

    // Move public methods:
    // passingIdAndSecret
    // passingIdSecretUriAndAuthCode
    // passingIdAndRedirectURI
    // passingIdSecretEmailAndPassword
    // passingIdSecretAndEmail
    // passingIdSecretUriAndCustomAuthCode
    // passingIdUriAuthCodeAndVerifier
    // passingIdUriAuthCodeAndWrongVerifier
    // passingIdSecretAndRefreshToken
    // passingIdSecretAndCustomRefreshToken
    // clientExists
    // publicClientExists
}
```

**Step 2: Create OAuthAuthorizationContext (authorization + PKCE setup)**

```php
final class OAuthAuthorizationContext implements Context
{
    public function __construct(
        private OAuthContextState $state,
        private OAuthRequestHelper $requestHelper
    ) {}

    // Move public methods:
    // usingResponseType
    // requestingScope
    // usingCodeChallenge
    // usingCodeChallengeWithMethod
    // usingPkceWithS256
    // obtainAuthCode
    // obtainAuthCodeWithPkce
    // requestAuthorizationEndpoint
    // requestAuthorizationEndpointWithoutApproval
}
```

**Step 3: Create OAuthTokenContext (token requests + success assertions)**

```php
final class OAuthTokenContext implements Context
{
    public function __construct(
        private OAuthContextState $state,
        private OAuthRequestHelper $requestHelper
    ) {}

    // Move public methods:
    // obtainingAccessToken
    // obtainingAccessTokenWithoutGrantType
    // obtainingAccessTokenWithPasswordGrantWithoutPassword
    // accessTokenShouldBeProvided
    // implicitAccessTokenShouldBeProvided
    // refreshTokenShouldBeProvided
}
```

**Step 4: Create OAuthErrorContext (error assertions)**

```php
final class OAuthErrorContext implements Context
{
    public function __construct(private OAuthContextState $state) {}

    // Move public methods:
    // invalidCredentialsError
    // invalidRequestError
    // invalidGrantErrorShouldBeReturned
    // invalidUserCredentialsErrorShouldBeReturned
    // invalidRefreshTokenErrorShouldBeReturned
    // invalidScopeErrorShouldBeReturned
    // authorizationRedirectErrorShouldBeReturned
    // unauthorizedErrorShouldBeReturned
    // unsupportedResponseTypeError
    // unsupportedGrantTypeError
}
```

**Step 5: Create OAuthAuthenticationContext (user auth)**

```php
final class OAuthAuthenticationContext implements Context
{
    public function __construct(
        private OAuthContextState $state,
        private TokenStorageInterface $tokenStorage
    ) {}

    // Move public method:
    // authenticatingUser
}
```

**Step 6: Delete old `OAuthContext.php`, update `behat.yml.dist` contexts list**

**Step 7: Run `make phpmd` (OAuthContext violations should be gone)**

---

### Task 3: Add shared state for UserOperations Behat contexts

**Files:**
- Create: `tests/Behat/UserContext/UserOperationsState.php`
- Create: `tests/Behat/UserContext/UserOperationsStateContext.php`
- Modify: `behat.yml.dist`

**Step 1: Create UserOperationsState**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\RequestInput;
use Symfony\Component\HttpFoundation\Response;

final class UserOperationsState
{
    public ?RequestInput $requestBody = null;
    public ?Response $response = null;
    public int $violationNum = 0;
    public string $language = 'en';
    public string $currentUserEmail = '';

    public function reset(): void
    {
        $this->requestBody = null;
        $this->response = null;
        $this->violationNum = 0;
        $this->language = 'en';
        $this->currentUserEmail = '';
    }
}
```

**Step 2: Create UserOperationsStateContext (BeforeScenario reset)**

```php
final class UserOperationsStateContext implements Context
{
    public function __construct(private UserOperationsState $state) {}

    /**
     * @BeforeScenario
     */
    public function resetUserOperationsState(BeforeScenarioScope $scope): void
    {
        $this->state->reset();
    }
}
```

**Step 3: Register new context in `behat.yml.dist`**

---

### Task 4: Split UserOperationsContext into focused contexts

**Files:**
- Create: `tests/Behat/UserContext/UserRequestContext.php`
- Create: `tests/Behat/UserContext/UserResponseContext.php`
- Create: `tests/Behat/UserContext/UserPasswordResetContext.php`
- Delete: `tests/Behat/UserContext/UserOperationsContext.php`
- Modify: `behat.yml.dist`

**Step 1: Create UserRequestContext (Given/When request building)**

```php
final class UserRequestContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
        private KernelInterface $kernel,
        SerializerInterface $serializer
    ) {}

    // Move public methods:
    // updatingUser
    // updatingUserWithNoOptionalFields
    // creatingUser
    // sendingUserBatch
    // addUserToBatch
    // confirmingUserWithToken
    // sendingEmptyBody
    // setLanguage
    // requestSendTo
    // requestingPasswordResetForEmail
    // confirmingPasswordResetWithValidTokenAndPassword
    // confirmingPasswordResetWithTokenAndPassword
}
```

**Step 2: Create UserResponseContext (Then assertions)**

```php
final class UserResponseContext implements Context
{
    public function __construct(private UserOperationsState $state) {}

    // Move public methods:
    // userShouldBeTimedOut
    // theErrorMessageShouldBe
    // theResponseStatusCodeShouldBe
    // theResponseBodyShouldContain
    // theViolationShouldBe
    // theResponseShouldContainAListOfUsers
    // userWithEmailAndInitialsShouldBeReturned
    // userWithIdShouldBeReturned
    // theResponseShouldContain
}
```

**Step 3: If any class exceeds 10 public methods, split further**
- Example: extract `UserPasswordResetContext` for password-reset related Given steps + assertions.

**Step 4: Remove inline comments during moves (per repo rule)**

**Step 5: Delete old UserOperationsContext and update `behat.yml.dist`**

**Step 6: Run `make phpmd` (UserOperationsContext violations should be gone)**

---

### Task 5: Add shared state for UserGraphQL contexts and split

**Files:**
- Create: `tests/Behat/UserGraphQLContext/UserGraphQLState.php`
- Create: `tests/Behat/UserGraphQLContext/UserGraphQLStateContext.php`
- Create: `tests/Behat/UserGraphQLContext/UserGraphQLQueryContext.php`
- Create: `tests/Behat/UserGraphQLContext/UserGraphQLMutationContext.php`
- Create: `tests/Behat/UserGraphQLContext/UserGraphQLResponseContext.php`
- Delete: `tests/Behat/UserGraphQLContext/UserGraphQLContext.php`
- Modify: `behat.yml.dist`

**Step 1: Create state + reset context**

```php
final class UserGraphQLState
{
    public string $language = 'en';
    public string $query = '';
    public string $queryName = '';
    /** @var array<string> */
    public array $responseContent = [];
    public int $errorNum = 0;
    public ?GraphQLMutationInput $graphQLInput = null;
    public ?Response $response = null;

    public function reset(): void
    {
        $this->language = 'en';
        $this->query = '';
        $this->queryName = '';
        $this->responseContent = [];
        $this->errorNum = 0;
        $this->graphQLInput = null;
        $this->response = null;
    }
}
```

**Step 2: Create UserGraphQLQueryContext (query setup)**
- Move: expectingToGetIdAndEmail, expectingToGetId, gettingUser, gettingCollectionOfUsers.

**Step 3: Create UserGraphQLMutationContext (mutation setup)**
- Move: creatingUser, updatingUser, confirmingUserWithToken, resendEmailToUser, deleteUser,
  requestPasswordResetViaGraphQL, confirmPasswordResetViaGraphQL,
  confirmPasswordResetWithValidTokenViaGraphQL, setLanguage, sendGraphQlRequest.

**Step 4: Create UserGraphQLResponseContext (assertions)**
- Move: mutationResponseShouldContainRequestedFields, queryResponseShouldContainRequestedFields,
  queryResponseShouldBeNull, graphQLPasswordResetMutationShouldSucceed,
  collectionOfUsersShouldBeReturned, graphQLErrorShouldBe.

**Step 5: Delete old UserGraphQLContext and update `behat.yml.dist`**

---

### Task 6: Split PHPUnit test classes (OAuth/Shared/User)

**Files:**
- Create new test classes, move methods, keep helpers protected.
- Delete/trim old classes so each class has ≤10 public methods.

**OAuth DoctrineType tests**
- Split `tests/Unit/OAuth/Infrastructure/DoctrineType/OAuth2GrantTypeTest.php` into:
  - `OAuth2GrantTypeConversionTest.php`
  - `OAuth2GrantTypeDatabaseTest.php`
- Split `OAuth2RedirectUriTypeTest.php` into two files similarly.
- Split `OAuth2ScopeTypeTest.php` into two files similarly.

**Shared tests**
- Split `QueryParameterValidationListenerTest.php` into two files by scenario type.
- Split `ContextBuilderTest.php` into two files by request/response contexts.
- Split `EmailSourcesTest.php` into two files by source type.
- Split `EmfNamespaceValidatorTest.php` into two files by valid/invalid cases.
- Split `UuidTest.php` into three files (construction, equality, serialization).
- Split `DomainEventMessageHandlerTest.php` into two files (sync/async cases).
- Split `ResilientAsyncEventDispatcherTest.php` into two files.
- Split `MongoDBDomainUuidTypeTest.php` into two files.
- Split `EmfDimensionValueValidatorTest.php` into two files.
- Split `EmfDimensionValueTest.php` into two files.

**User tests**
- Split `UserPatchProcessorTest.php` into 2–3 files by operation.
- Split `UserPatchResolversTest.php` into 2 files.
- Split `UserPatchPayloadValidatorTest.php` into 2 files.
- Split `ConfirmationTokenTest.php` into 2 files.
- Split `PasswordResetTokenTest.php` into 3 files.
- Split `SchemathesisCleanupListenerTest.php` into 2 files.
- Split `CachedUserRepositoryTest.php` into 2 files (cache-hit vs error paths).

**Step 1: Move tests without changing assertions**
**Step 2: Keep shared setup in abstract base classes if needed**
**Step 3: Run `make phpmd` after each folder is split**

---

### Task 7: Verification

**Step 1:** Run `make phpmd` (expect 0 violations)

**Step 2:** Run `make phpinsights` (expect all thresholds met)

**Step 3:** Run `make unit-tests` (expect 100% coverage)

---

### Task 8: Clean up

**Step 1:** Remove any unused imports after splits

**Step 2:** Ensure `behat.yml.dist` and `config/services_test.yaml` match new context classes

**Step 3:** (Optional) Run `make phpcsfixer` if style issues appear
