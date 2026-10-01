# JWT signing with AWS KMS

User Service signs every JWT with an AWS KMS asymmetric key (story S5.11, FR-06).
This covers the first-party access tokens issued by sign-in and refresh (lexik) and
the OAuth 2.0 access tokens issued by `/api/oauth/token` (league). The private key
never leaves KMS. No PEM key, key file or passphrase exists in the application, the
image or its configuration.

## How it works

- **Signing.** `kms:Sign` with `SigningAlgorithm=RSASSA_PKCS1_V1_5_SHA_256` and
  `MessageType=DIGEST` over the SHA-256 digest of the JWS signing input. The result
  is a standard RS256 JWS. Only the current key (`JWT_KMS_KEY_ID`) signs.
- **Key id.** The JWS header carries `kid`, the RFC 7638 SHA-256 JWK thumbprint of
  the signing key's public key. The kid derives from the key material, so pointing
  an alias at another key changes the kid.
- **Verification.** Tokens are verified locally with the public key from
  `kms:GetPublicKey`. The verifier picks the key by `kid`: the current key, or the
  additional verify-only key (`JWT_KMS_PREVIOUS_KEY_ID`) during a key-change window.
  The slot holds the old key after the signing switch, and the new key while it is
  pre-published. Each GetPublicKey result is cached in process memory for
  `JWT_KMS_PUBLIC_KEY_CACHE_TTL` seconds (1 to 3600; any other value fails at
  startup). Signing uses the key ARN from the same
  cached GetPublicKey result, so the `kid` always matches the signing key.
- **JWK set.** `GET /api/.well-known/jwks.json` publishes the verification keys as
  an RFC 7517 JWK set (`kty`, `use=sig`, `alg=RS256`, `kid`, `n`, `e`): the current
  key, then the previous key when one is configured. The response is public and needs
  no authentication. Like every response it carries `Cache-Control: no-store`
  (NFR-66).
- **Credentials.** The AWS SDK default credential chain supplies the ECS task role
  credentials. No static AWS key is used for KMS.

| Component                                                             | Role                                                     |
| --------------------------------------------------------------------- | -------------------------------------------------------- |
| `Shared\Infrastructure\Provider\KmsJwtKeyProvider`                    | GetPublicKey, bounded TTL cache, current/previous by kid |
| `Shared\Infrastructure\Factory\KmsJwtFactory`                         | builds the JWS and signs it with `kms:Sign`              |
| `Shared\Infrastructure\Validator\JwtSignatureVerifier`                | RS256 only, `kid` lookup, local signature check          |
| `Shared\Infrastructure\Adapter\KmsJwsProvider`                        | lexik JWS provider (`app.jwt.kms_encoder`)               |
| `OAuth\Infrastructure\Repository\KmsAccessTokenRepository`            | league tokens are KMS-signed `KmsSignedAccessToken`      |
| `OAuth\Infrastructure\Security\KmsBearerTokenValidator`               | league resource server validation by `kid`               |
| `Shared\Infrastructure\DependencyInjection\KmsJwtSigningCompilerPass` | removes league's PEM `CryptKey`                          |
| `OAuth\Application\Controller\JsonWebKeySetController`                | `GET /api/.well-known/jwks.json`                         |
| `Shared\Application\EventListener\JwtKmsConfigurationListener`        | production guard                                         |
| `Shared\Infrastructure\Provider\AwsKmsEndpointProvider`               | the KMS client endpoint the guard inspects               |

## Environment variables

| Variable                       | Production                                         | Local (dev, test, load_test, schemathesis) |
| ------------------------------ | -------------------------------------------------- | ------------------------------------------ |
| `JWT_KMS_KEY_ID`               | KMS key ARN or alias ARN of the current JWT key    | LocalStack `alias/user-service-jwt` ARN    |
| `JWT_KMS_PREVIOUS_KEY_ID`      | empty, or the additional verify-only key ARN       | empty                                      |
| `JWT_KMS_PUBLIC_KEY_CACHE_TTL` | GetPublicKey cache seconds, 1 to 3600 (300)        | 300                                        |
| `AWS_REGION`                   | the task region (set by the infrastructure)        | not used                                   |
| `AWS_KMS_LOCAL_ENDPOINT`       | not used                                           | `http://localstack:4566`                   |
| `AWS_KMS_LOCAL_REGION`         | not used                                           | `us-east-1`                                |
| `AWS_KMS_LOCAL_KEY`            | not used                                           | `fake`                                     |
| `AWS_KMS_LOCAL_SECRET`         | not used                                           | `fake`                                     |

The infrastructure passes the key ARNs as plain environment values (USI story S1.8;
architecture AD-15). The JWT key is `RSA_4096` `SIGN_VERIFY`. The ECS task role holds
`kms:Sign` (with the condition `kms:SigningAlgorithm=RSASSA_PKCS1_V1_5_SHA_256`) and
`kms:GetPublicKey` on the current key, and `kms:GetPublicKey` on the previous key
for the duration of a key-change window (AD-15a; D-17, USI plan).

The retired variables `OAUTH_PRIVATE_KEY`, `OAUTH_PUBLIC_KEY` and `OAUTH_PASSPHRASE`
no longer exist. `OAUTH_ENCRYPTION_KEY` is unrelated to JWT signing and stays.

## Local, test and CI

The repository's own tests and CI have no AWS account. The dev, test, load_test and
schemathesis environments point the same `Aws\Kms\KmsClient` service at LocalStack
KMS through `AWS_KMS_LOCAL_*`. `infrastructure/docker/php/init-aws.sh` creates the
`alias/user-service-jwt` `SIGN_VERIFY` key when LocalStack starts (`RSA_2048`, because
LocalStack signs about five times slower with `RSA_4096`, which distorts the Behat
timing checks; the code accepts any RSA signing key); the local
environments reference it by its alias ARN
(`arn:aws:kms:us-east-1:000000000000:alias/user-service-jwt`). The
code path is the same as in production; only the endpoint and the fake credentials
differ, and both exist only in the local environment configuration.

The load-test service token comes from `app:load-test:issue-service-token`, which
signs through the same KMS path. The command refuses to run in production.

## Production guard

On every main request and console command in `APP_ENV=prod`,
`JwtKmsConfigurationListener` fails closed (the request or command errors) when:

- `JWT_KMS_KEY_ID` is not a KMS key ARN or alias ARN (a LocalStack alias name such
  as `alias/user-service-jwt` is refused);
- `JWT_KMS_PREVIOUS_KEY_ID` is set and is not a KMS key ARN or alias ARN;
- any AWS credential source other than the ECS task role is configured: a set
  `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_SESSION_TOKEN`, `AWS_PROFILE`,
  `AWS_SHARED_CREDENTIALS_FILE` or `AWS_CONTAINER_CREDENTIALS_FULL_URI`, or both
  `AWS_WEB_IDENTITY_TOKEN_FILE` and `AWS_ROLE_ARN` (the task role itself comes
  through `AWS_CONTAINER_CREDENTIALS_RELATIVE_URI`, which stays allowed);
- the KMS client endpoint is not the regional AWS endpoint
  (`https://kms.<region>.amazonaws.com` or its FIPS variant), which refuses a
  LocalStack or any other local signer.

## Key change with a dual-key window

The plan's acceptance requires a manual asymmetric key change with a dual-key window
(FR-06). The old key stays in KMS during the window; verification reads the public
halves of both keys through GetPublicKey (D-17, USI plan). Change keys by changing
the key ARNs in the environment, not by repointing an alias: an alias repoint
changes the signing key without any window, and tasks disagree on the kid until
their GetPublicKey cache expires.

1. **Create** the new KMS key (`RSA_4096`, `SIGN_VERIFY`) in the infrastructure.
2. **Grant** the ECS task role `kms:Sign` and `kms:GetPublicKey` on the new key, and
   keep `kms:GetPublicKey` on the old key.
3. **Pre-publish** the new key: deploy with `JWT_KMS_KEY_ID` = the old key and the
   additional verify-only key `JWT_KMS_PREVIOUS_KEY_ID` = the new key. The new key
   only verifies and appears in the JWK set. Wait until the rollout has replaced
   every task.
4. **Switch** signing: deploy with `JWT_KMS_KEY_ID` = the new key and
   `JWT_KMS_PREVIOUS_KEY_ID` = the old key. Because every task already trusts both
   keys, tokens from tasks of either deployment verify during the rolling update.
5. **Wait**, starting once the step-4 rollout has replaced every task, at least the
   longest token lifetime: `AUTH_ACCESS_TOKEN_TTL_SECONDS` (the `exp` of the
   first-party access tokens issued by sign-in and refresh), `JWT_TOKEN_TTL` (the
   lexik default when a payload has no `exp`) and `ACCESS_TOKEN_TTL` (league OAuth
   access tokens), plus `JWT_KMS_PUBLIC_KEY_CACHE_TTL`.
6. **Clear** `JWT_KMS_PREVIOUS_KEY_ID` and redeploy. Tokens of the old key are now
   rejected (unknown `kid`).
7. **Revoke** the task role grants on the old key and schedule its deletion.

Step 3 is a safety step on top of the plan's six steps (create, grant, deploy with
the window, wait, clear, revoke): without it, tasks still on the previous
deployment reject tokens that the new tasks sign until the rollout finishes.

Refresh tokens are opaque values, not JWTs, so a key change does not affect them.

**First KMS deployment.** Access tokens signed by the retired PEM key carry no
`kid` and are rejected after the first deployment of this change. Clients recover
with their refresh token or by signing in again.

## Failure modes

All failures are fail-closed. No path falls back to a local private key.

| Failure                                                                 | Effect                                                    |
| ----------------------------------------------------------------------- | --------------------------------------------------------- |
| KMS `Sign` error (throttling, AccessDenied, key disabled)               | token issuance fails (sign-in, refresh, OAuth token: 5xx) |
| KMS `GetPublicKey` error for the current key                            | verification fails: the request is unauthenticated (401)  |
| KMS `GetPublicKey` error for the previous key                           | previous-key tokens rejected (401); JWK set returns 500   |
| `GetPublicKey` error on the league resource-server path                 | 500 (no firewall uses that path today)                    |
| GetPublicKey returns a non-`SIGN_VERIFY` key or no RS256                | signing and verification fail                             |
| Token `alg` is not `RS256` (for example `none`, `HS256`)                | rejected (401)                                            |
| Token `kid` missing or not the current/previous key, or a `crit` header | rejected (401)                                            |
| Tampered header, payload or signature                                   | rejected (401)                                            |
| Previous key removed from configuration                                 | its tokens are rejected (401)                             |
| JWK set request while KMS is unavailable                                | 500                                                       |
| Misconfigured production (see the guard above)                          | every request and console command fails                   |

The live checks (a TEST login, JWT verification against the published key and a
CloudTrail `kms:Sign` event) run in the TEST campaign (USI S4.6 step 7), not in this
repository's tests.
