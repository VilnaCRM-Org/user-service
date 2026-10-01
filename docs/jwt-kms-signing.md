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
  previous key (`JWT_KMS_PREVIOUS_KEY_ID`) during a key-change window. Each
  GetPublicKey result is cached in process memory for
  `JWT_KMS_PUBLIC_KEY_CACHE_TTL` seconds. Signing uses the key ARN from the same
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

## Environment variables

| Variable                       | Production                                         | Local (dev, test, load_test, schemathesis) |
| ------------------------------ | -------------------------------------------------- | ------------------------------------------ |
| `JWT_KMS_KEY_ID`               | KMS key ARN or alias ARN of the current JWT key    | LocalStack `alias/user-service-jwt` ARN    |
| `JWT_KMS_PREVIOUS_KEY_ID`      | empty, or the previous key ARN during a key change | empty                                      |
| `JWT_KMS_PUBLIC_KEY_CACHE_TTL` | seconds a GetPublicKey result is cached (300)      | 300                                        |
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
`alias/user-service-jwt` `RSA_4096` `SIGN_VERIFY` key when LocalStack starts; the local
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
- any static `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` or `AWS_SESSION_TOKEN` is
  set;
- the KMS client endpoint is not the regional AWS endpoint
  (`https://kms.<region>.amazonaws.com` or its FIPS variant), which refuses a
  LocalStack or any other local signer.

## Key change with a dual-key window

The plan's acceptance requires a manual asymmetric key change with a dual-key window
(FR-06). The old key stays in KMS during the window; verification reads the public
halves of both keys through GetPublicKey (D-17, USI plan). The steps:

1. **Create** the new KMS key (`RSA_4096`, `SIGN_VERIFY`) in the infrastructure.
2. **Grant** the ECS task role `kms:Sign` and `kms:GetPublicKey` on the new key, and
   keep `kms:GetPublicKey` on the old key.
3. **Deploy** with `JWT_KMS_KEY_ID` = the new key and `JWT_KMS_PREVIOUS_KEY_ID` = the
   old key. New tokens carry the new `kid`; tokens signed by the old key still
   verify, and the JWK set lists both keys.
4. **Wait** at least the longest token lifetime: `JWT_TOKEN_TTL` (lexik access
   tokens) and `ACCESS_TOKEN_TTL` (league access tokens), plus
   `JWT_KMS_PUBLIC_KEY_CACHE_TTL`.
5. **Clear** `JWT_KMS_PREVIOUS_KEY_ID` and redeploy. Tokens of the old key are now
   rejected (unknown `kid`).
6. **Revoke** the task role grants on the old key and schedule its deletion.

Refresh tokens are opaque values, not JWTs, so a key change does not affect them.

## Failure modes

All failures are fail-closed. No path falls back to a local private key.

| Failure                                                   | Effect                                                    |
| --------------------------------------------------------- | --------------------------------------------------------- |
| KMS `Sign` error (throttling, AccessDenied, key disabled) | token issuance fails (sign-in, refresh, OAuth token: 5xx) |
| KMS `GetPublicKey` error                                  | verification fails: the request is unauthenticated (401)  |
| GetPublicKey returns a non-`SIGN_VERIFY` key or no RS256  | signing and verification fail                             |
| Token `alg` is not `RS256` (for example `none`, `HS256`)  | rejected (401)                                            |
| Token `kid` missing or not the current/previous key       | rejected (401)                                            |
| Tampered header, payload or signature                     | rejected (401)                                            |
| Previous key removed from configuration                   | its tokens are rejected (401)                             |
| JWK set request while KMS is unavailable                  | 500                                                       |
| Misconfigured production (see the guard above)            | every request and console command fails                   |

The live checks (a TEST login, JWT verification against the published key and a
CloudTrail `kms:Sign` event) run in the TEST campaign (USI S4.6 step 7), not in this
repository's tests.
