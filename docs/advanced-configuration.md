Welcome to the Advanced Configuration Guide for the User Service. This guide is designed to help you customize and optimize your setup beyond the basic installation steps.

## Environment Variables

### Configuration

The User Service utilizes environment variables for configuration to ensure that sensitive information is not hard-coded into the application. Here are the environment variables you can configure:

#### Core Application

- `APP_ENV`: Specifies the environment in which the application is running (e.g., `dev`, `test`, `prod`).
- `APP_SECRET`: A secret key used for cryptographic purposes, such as generating CSRF tokens or signing cookies.
- `API_BASE_URL`: The base URL of the API (e.g., `https://localhost`).
- `API_URL`: The public API URL (e.g., `https://api.vilnacrm.com`).
- `API_PREFIX`: The API route prefix (e.g., `/api`).

#### Database

- `DATABASE_URL`: The URL for connecting to the MariaDB/MySQL database, including credentials, host, port, and database name (e.g., `mysql://root:root@database:3306/db?serverVersion=11.4`).
- `MONGODB_URL`: The MongoDB or DocumentDB connection URI. DocumentDB deployments must include `tls=true`, `tlsCAFile=/usr/local/share/ca-certificates/aws-documentdb-global-bundle.pem`, and `retryWrites=false` in the URI. The application image supplies the verified CA bundle. Local MongoDB uses its own URI without TLS.
- `USER_INSERT_BATCH_SIZE`: The size of a batch for bulk user inserts to the database.

The base Compose production image listens on HTTP port 80 for a TLS-terminating
load balancer. The development override publishes HTTPS and HTTP/3 on port 443.

#### Redis

- `REDIS_URL`: The URL for connecting to the Redis server (e.g., `redis://redis:6379/0`).

#### AWS SQS / LocalStack

- `AWS_SQS_VERSION`: The AWS SQS API version.
- `AWS_SQS_REGION`: The AWS region for SQS.
- `AWS_SQS_ENDPOINT_BASE`: The LocalStack endpoint base for `dev`, `test`, `load_test`, and `schemathesis`.
- `AWS_SQS_KEY`: The LocalStack access key for `dev`, `test`, `load_test`, and `schemathesis` only.
- `AWS_SQS_SECRET`: The LocalStack secret key for `dev`, `test`, `load_test`, and `schemathesis` only.
- `LOCALSTACK_PORT`: The port on which LocalStack is running.

#### Messenger Transports

- `SEND_EMAIL_TRANSPORT_DSN`: The DSN for the messenger transport used for sending emails via Amazon SQS.
- `FAILED_EMAIL_TRANSPORT_DSN`: The DSN for the messenger transport used for handling failed email deliveries.
- `INSERT_USER_BATCH_TRANSPORT_DSN`: The DSN for the messenger transport used for batch user inserts.
- `MESSENGER_CONSUMER_NAME`: The name identifier for the messenger consumer (overwritten by Supervisor).

#### Mailer

- `MAILCATCHER_SMTP_PORT`: The port on which the MailCatcher SMTP server is running.
- `MAILCATCHER_HTTP_PORT`: The port on which the MailCatcher HTTP server is running.
- `MAILER_DSN`: The DSN for the mailer, configured to use SMTP via MailCatcher.
- `MAIL_SENDER`: The email address used as the sender for outgoing emails.

#### OAuth 2.0

- `OAUTH_PRIVATE_KEY`: The path to the private key used for OAuth 2.0 authentication.
- `OAUTH_PUBLIC_KEY`: The path to the public key used for OAuth 2.0 authentication.
- `OAUTH_PASSPHRASE`: The passphrase used to decrypt the private key.
- `OAUTH_ENCRYPTION_KEY_TYPE`: Specifies the type of encryption key used, either `plain` or `defuse`.
- `OAUTH_ENCRYPTION_KEY`: OAuth token encryption key. Keep it empty in root `.env`; set it in environment-specific files or deployment secrets.
- `ACCESS_TOKEN_TTL`: The TTL for access tokens. Learn more [here](http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters).
- `REFRESH_TOKEN_TTL`: The TTL for refresh tokens. Learn more [here](http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters).
- `AUTH_CODE_TTL`: The TTL for authorization codes. Learn more [here](http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters).

In production, the application throws an explicit runtime configuration error if `OAUTH_ENCRYPTION_KEY` is empty.
Password grant is intentionally disabled (`enable_password_grant: false`); use authorization code + PKCE or client credentials grants.

#### Social sign-in

`SOCIAL_OAUTH_ENABLED` defaults to `true` in every environment, including production.
Enabled deployments support GitHub, Google, Facebook, and Twitter and require their
configured provider credentials. Set `SOCIAL_OAUTH_ENABLED=false` explicitly for a
deployment without social sign-in, such as the AWS PoC. The disabled factories do not
resolve provider credentials and return empty provider and provider-name collections;
social sign-in endpoints return `unsupported_provider`. Set the flag back to `true`
with valid provider credentials to re-enable social sign-in without a source change.
Local registration, password sign-in, and token flows remain unchanged.

#### JWT

- `JWT_TOKEN_TTL`: The TTL for JWT tokens in seconds.

#### Security and Tokens

- `JWT_ISSUER`: Expected JWT issuer for first-party access tokens (default: `vilnacrm-user-service`).
- `JWT_AUDIENCE`: Expected JWT audience for first-party access tokens (default: `vilnacrm-api`).
- `AUTH_ACCESS_TOKEN_TTL_SECONDS`: Access token lifetime used by the sign-in flow (default: `900`).
- `AUTH_STANDARD_SESSION_TTL_SECONDS`: Standard session TTL in seconds (default: `900`).
- `AUTH_REMEMBER_ME_SESSION_TTL_SECONDS`: Remember-me session TTL in seconds (default: `2592000`).
- `AUTH_STANDARD_COOKIE_MAX_AGE`: Standard auth cookie max-age in seconds (default: `900`).
- `AUTH_REMEMBER_ME_COOKIE_MAX_AGE`: Remember-me auth cookie max-age in seconds (default: `2592000`).
- `REQUEST_BODY_MAX_SIZE_BYTES`: Maximum accepted request body size in bytes (default: `65536`).
- `CONFIRMATION_TOKEN_LENGTH`: The length of user confirmation tokens (default: 32).
- `PASSWORD_RESET_TOKEN_LENGTH`: The length of password reset tokens (default: 32).
- `PASSWORD_RESET_TOKEN_EXPIRATION_HOURS`: How long password reset tokens are valid in hours (default: 1).
- `PASSWORD_RESET_RATE_LIMIT_MAX_REQUESTS`: Maximum password reset requests allowed within the rate limit interval (default: 1000).
- `PASSWORD_RESET_RATE_LIMIT_INTERVAL`: The time window for password reset rate limiting (default: "1 hour").
- `PASSWORD_RESET_CONFIRM_RATE_LIMIT_MAX_REQUESTS`: Maximum password reset confirmation requests allowed within the rate limit interval (default: 10).
- `PASSWORD_RESET_CONFIRM_RATE_LIMIT_INTERVAL`: The time window for password reset confirmation rate limiting (default: "1 minute").
- `GLOBAL_API_ANONYMOUS_RATE_LIMIT_MAX_REQUESTS`: Maximum anonymous API requests allowed per interval (default: 100).
- `GLOBAL_API_ANONYMOUS_RATE_LIMIT_INTERVAL`: Time window for anonymous API rate limiting (default: "1 minute").
- `GLOBAL_API_AUTHENTICATED_RATE_LIMIT_MAX_REQUESTS`: Maximum authenticated API requests allowed per interval (default: 300).
- `GLOBAL_API_AUTHENTICATED_RATE_LIMIT_INTERVAL`: Time window for authenticated API rate limiting (default: "1 minute").
- `REGISTRATION_RATE_LIMIT_MAX_REQUESTS`: Maximum registration requests allowed per interval (default: 5).
- `REGISTRATION_RATE_LIMIT_INTERVAL`: Time window for registration rate limiting (default: "1 minute").
- `OAUTH_TOKEN_RATE_LIMIT_MAX_REQUESTS`: Maximum token exchange requests allowed per interval (default: 10).
- `OAUTH_TOKEN_RATE_LIMIT_INTERVAL`: Time window for token exchange rate limiting (default: "1 minute").
- `SIGNIN_IP_RATE_LIMIT_MAX_REQUESTS`: Maximum sign-in attempts per IP per interval (default: 10).
- `SIGNIN_IP_RATE_LIMIT_INTERVAL`: Time window for sign-in IP rate limiting (default: "1 minute").
- `SIGNIN_EMAIL_RATE_LIMIT_MAX_REQUESTS`: Maximum sign-in attempts per email per interval (default: 5).
- `SIGNIN_EMAIL_RATE_LIMIT_INTERVAL`: Time window for sign-in email rate limiting (default: "1 minute").
- `TWOFA_VERIFICATION_USER_RATE_LIMIT_MAX_REQUESTS`: Maximum 2FA verification attempts per user per interval (default: 5).
- `TWOFA_VERIFICATION_USER_RATE_LIMIT_INTERVAL`: Time window for 2FA verification user rate limiting (default: "1 minute").
- `TWOFA_VERIFICATION_IP_RATE_LIMIT_MAX_REQUESTS`: Maximum 2FA verification attempts per IP per interval (default: 5).
- `TWOFA_VERIFICATION_IP_RATE_LIMIT_INTERVAL`: Time window for 2FA verification IP rate limiting (default: "1 minute").
- `TWOFA_SETUP_RATE_LIMIT_MAX_REQUESTS`: Maximum 2FA setup requests per user per interval (default: 5).
- `TWOFA_SETUP_RATE_LIMIT_INTERVAL`: Time window for 2FA setup rate limiting (default: "1 minute").
- `TWOFA_CONFIRM_RATE_LIMIT_MAX_REQUESTS`: Maximum 2FA confirmation requests per user per interval (default: 5).
- `TWOFA_CONFIRM_RATE_LIMIT_INTERVAL`: Time window for 2FA confirmation rate limiting (default: "1 minute").
- `TWOFA_DISABLE_RATE_LIMIT_MAX_REQUESTS`: Maximum 2FA disable requests per user per interval (default: 3).
- `TWOFA_DISABLE_RATE_LIMIT_INTERVAL`: Time window for 2FA disable rate limiting (default: "1 minute").
- `EMAIL_CONFIRMATION_RATE_LIMIT_MAX_REQUESTS`: Maximum email confirmation requests per interval (default: 10).
- `EMAIL_CONFIRMATION_RATE_LIMIT_INTERVAL`: Time window for email confirmation rate limiting (default: "1 minute").
- `USER_COLLECTION_RATE_LIMIT_MAX_REQUESTS`: Maximum user collection requests per interval (default: 30).
- `USER_COLLECTION_RATE_LIMIT_INTERVAL`: Time window for user collection rate limiting (default: "1 minute").
- `USER_UPDATE_RATE_LIMIT_MAX_REQUESTS`: Maximum user update requests per user per interval (default: 10).
- `USER_UPDATE_RATE_LIMIT_INTERVAL`: Time window for user update rate limiting (default: "1 minute").
- `USER_DELETE_RATE_LIMIT_MAX_REQUESTS`: Maximum user delete requests per user per interval (default: 3).
- `USER_DELETE_RATE_LIMIT_INTERVAL`: Time window for user delete rate limiting (default: "1 minute").
- `RESEND_CONFIRMATION_RATE_LIMIT_MAX_REQUESTS`: Maximum resend confirmation requests per interval (default: 3).
- `RESEND_CONFIRMATION_RATE_LIMIT_INTERVAL`: Time window for resend confirmation rate limiting (default: "1 minute").
- `RESEND_CONFIRMATION_TARGET_RATE_LIMIT_MAX_REQUESTS`: Maximum resend confirmation requests per target user per interval (default: 3).
- `RESEND_CONFIRMATION_TARGET_RATE_LIMIT_INTERVAL`: Time window for resend confirmation target-user rate limiting (default: "1 minute").
- `RECOVERY_CODES_RATE_LIMIT_MAX_REQUESTS`: Maximum recovery code regeneration requests per user per interval (default: 3).
- `RECOVERY_CODES_RATE_LIMIT_INTERVAL`: Time window for recovery code regeneration rate limiting (default: "1 minute").
- `SIGNOUT_RATE_LIMIT_MAX_REQUESTS`: Maximum sign-out requests per user per interval (default: 10).
- `SIGNOUT_RATE_LIMIT_INTERVAL`: Time window for sign-out rate limiting (default: "1 minute").
- `SIGNOUT_ALL_RATE_LIMIT_MAX_REQUESTS`: Maximum sign-out-all requests per user per interval (default: 5).
- `SIGNOUT_ALL_RATE_LIMIT_INTERVAL`: Time window for sign-out-all rate limiting (default: "1 minute").

#### CORS

- `CORS_ALLOW_ORIGIN`: The regular expression defining the allowed origins for Cross-Origin Resource Sharing (CORS).

#### Development

- `STRUCTURIZR_PORT`: The port on which Structurizr architecture diagrams are served.
- `XDEBUG_MODE`: Xdebug mode configuration (e.g., `off`, `debug`, `coverage`).

Learn more about [Symfony Environment Variables](https://symfony.com/doc/current/configuration.html#configuring-environment-variables-in-env-files)

### Managing different environments

You can use `.env.test` and `.env.prod` to override variables for other environments.

- **`.env.test`**: Contains environment variables for the testing environment. Use this file to set configurations that should only apply when running tests, such as database connections, API endpoints, and service credentials that are different from your production settings.

- **`.env.prod`**: Holds environment variables for the production environment. This file should include configurations for your live application, such as database URLs, third-party API keys, and any other variables that your application needs to run in production.

#### Best Practices

1. Never commit your `.env.prod` file to version control. This file will likely contain sensitive information that should not be exposed publicly.

2. While your `.env.prod` file should not be committed to version control, your `.env.test` file can be if it does not contain sensitive information. This helps maintain consistency across testing environments.

## Configuring Load Tests

The User Service includes a comprehensive suite for load testing its endpoints. The configuration for these tests is defined in a JSON file (`tests/Load/config.json.dist`). Below is a guide on how to configure general settings and specific endpoint settings for load testing.

### General Settings

First of all, there are settings common for each testing script. Here is their breakdown:

- `apiHost`: Specifies the hostname for the API to make requests to.
- `apiPort`: Specifies the post for the API to be added to a host.
- `mailCatcherPort`: Specifies the port number for MailCatcher, to retrieve confirmation tokens.
- `batchSize`: Specifies the batch size, used for inserted users before script execution.
- `delayBetweenScenarios`: Specifies the delay (in seconds) between scenarios execution.
- `usersFileName`: Specifies the name of a `.json`, which contains the data of inserted users.
- `usersFileLocation`: Specifies the location of a `.json` file with users, relative to a `/tests/Load` folder.

### Endpoint Settings

Each endpoint testing config has some common settings. Here is their breakdown:

- `setupTimeoutInMinutes`: Specifies the time (in minutes) for setting up the load testing environment for each script before it will be executed.
- `teardownTimeoutInMinutes`: Specifies the time (in minutes) finishing the load test script after execution.

- `smoke`: Configuration for smoke testing.

  - `threshold`: Specifies the threshold for response time (in milliseconds).
  - `rps`: Specifies the requests per second (RPS) for the smoke test.
  - `vus`: Specifies the virtual users (VUs) for the smoke test.
  - `duration`: Specifies the duration of the smoke test (in seconds).

- `average`: Configuration for average load testing.

  - `threshold`: Specifies the threshold for response time (in milliseconds).
  - `rps`: Specifies the requests per second (RPS) for average load testing.
  - `vus`: Specifies the virtual users (VUs) for average load testing.
  - `duration`: Specifies the duration of each phase of the load test:
    - `rise`: The duration of the ramp-up phase (in seconds).
    - `plateau`: The duration of the plateau phase (in seconds).
    - `fall`: The duration of the ramp-down phase (in seconds).

- `stress`: Configuration for stress testing.

  - `threshold`: Specifies the threshold for response time (in milliseconds).
  - `rps`: Specifies the requests per second (RPS) for stress testing.
  - `vus`: Specifies the virtual users (VUs) for stress testing.
  - `duration`: Specifies the duration of each phase of the load test:
    - `rise`: The duration of the ramp-up phase (in seconds).
    - `plateau`: The duration of the plateau phase (in seconds).
    - `fall`: The duration of the ramp-down phase (in seconds).

- `spike`: Configuration for spike testing.
  - `threshold`: Specifies the threshold for response time (in milliseconds).
  - `rps`: Specifies the requests per second (RPS) for spike testing.
  - `vus`: Specifies the virtual users (VUs) for spike testing.
  - `duration`: Specifies the duration of each phase of the spike test:
    - `rise`: The duration of the spike ramp-up phase (in seconds).
    - `fall`: The duration of the spike ramp-down phase (in seconds).

Learn more about [Load testing with K6](https://grafana.com/docs/k6/latest/javascript-api/k6/)

### OAuth Endpoint settings

OAuth testing authentication endpoint requires additional settings, such as OAuth Client credentials. Here is their breakdown:

- `clientName`: Specifies the name of the OAuth client.
- `clientSecret`: Specifies the secret key used for authentication by the OAuth client.
- `clientID`: Specifies the client identifier assigned by the authorization server.
- `clientRedirectUri`: Specifies the URI to which the authorization server will redirect the user after authorization.

### Collection testing settings

Testing of endpoints that return collection requires additional settings, such as a number of users to get with each request:

- `usersToGetInOneRequest`: Amount of users to retrieve with each request.

### Create user batch settings

User batch endpoint testing requires additional settings, such as the size of the user's batch to be sent with each request:

- `batchSize`: Specifies the batch size, used for each request.

Learn more about [OAuth Server Bundle](https://oauth2.thephpleague.com/).

Learn more about [Community and Support](community-and-support.md).

### SQS credentials in AWS

Production SQS health checks use the AWS SDK default credential provider chain
and the regional AWS endpoint. ECS deployments must attach a task role with
`sqs:GetQueueUrl` on the preprovisioned `health-check-queue`. Health checks only
look up this queue; they never create it. LocalStack setup must create it before
running application health checks. The ECS
execution role does not provide application credentials. Do not inject static
AWS access keys or mount shared AWS credential files into the application.

Deploy this configuration in the application image before removing the legacy
health-check IAM user and access key from an existing infrastructure stack.
Development and test environments retain explicit LocalStack endpoint and dummy
credentials. A non-production AWS deployment should run with `APP_ENV=prod`.

See the [AWS SDK credential provider documentation](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/defaultprovider-provider.html).

### Production web and worker containers

Build the web image from the `frankenphp_prod` target. Its production Caddy
configuration serves HTTP on the unprivileged port 8080 and HTTPS on the
unprivileged port 8443 (see [In-container TLS on 8443](#in-container-tls-on-8443))
behind the ALB HTTPS listener, and forces `APP_ENV=prod` and `APP_DEBUG=0`. It
exposes no test listener.
The development Caddy configuration and its arbitrary listener/configuration
overrides do not apply to this production target.

Build the worker image from the `app_workers` target. Supervisor runs ten
production consumers for `send-email`, `insert-user-batch`, and `domain-events`.
Failed-message transports are not consumed automatically. Worker output goes
to standard output and standard error for collection by the container platform.
The container health check requires all ten expected consumers to be running;
a missing, stopped, or unexpected process makes the check fail. This process
check does not prove message delivery or downstream service availability.

#### In-container TLS on 8443

The web image serves the application over HTTPS on port 8443 for the PROD load
balancer's HTTPS target group. Port 8080 keeps serving plain HTTP, unchanged. TEST
keeps its HTTP target group on 8080 under a recorded risk acceptance, and the
local development image (`frankenphp_dev`) uses its own Caddy configuration, so
neither is affected. The 8443 listener is always on in the production web image;
no flag enables it.

- **Certificate.** Caddy's internal CA (`local_certs`) issues the certificate for
  `user-service.internal`. Caddy presents it to every client, including one that
  connects by IP address without SNI or with another server name
  (`default_sni` and `fallback_sni`). The ALB does not validate target
  certificates, so a certificate from a private CA is enough. Caddy contacts no
  public CA and does not install its root into any trust store.
- **Generated at runtime.** The image contains no private key or certificate.
  On start, Caddy creates the root CA, the intermediate CA and the certificate in
  `/srv/app/var/caddy`, inside the `/srv/app/var` volume that must already be
  writable, so the ECS task needs no extra volume. The directories have mode
  `0700` and the files `0600`, owned by `10001:10001`. Each task has its own CA.
  The CA and the certificate last as long as the task's volume, and a new task
  creates new ones.
- **Renewal.** The certificate lifetime is 12 hours and Caddy checks every
  10 minutes whether any certificate needs renewal (Caddy's defaults). It renews
  in the last third of the lifetime, about four hours before expiry, and serves
  the new certificate without a restart. Caddy also renews the intermediate CA
  (7 days) before it expires; the root CA lasts 10 years, far beyond a task's life,
  and the certificate never outlives its issuer. `CADDY_INTERNAL_CERT_LIFETIME` and
  `CADDY_RENEW_INTERVAL` exist only so the image check can prove renewal in
  seconds. Leave them unset in deployments.
- **No plain HTTP on 8443.** The listener has no HTTP-to-HTTPS redirect and no
  HTTP fallback. A plain HTTP request on 8443 gets
  `400 Client sent an HTTP request to an HTTPS server.` and no application
  response. Only HTTP/1.1 and HTTP/2 are enabled on 8443, so there is no UDP
  (HTTP/3) listener.
- **Proxies.** The 8443 server trusts the same `TRUSTED_PROXY_CIDRS` as 8080.
- **Non-root.** Port 8443 is unprivileged, so the listener needs no capability
  and runs under the same contract as 8080.

#### Non-root runtime contract

Both production images run as a fixed non-root account. The container platform
configuration should match these values:

- `USER`: `10001:10001` (`app`) in both images.
- Web container ports: `8080/tcp` (HTTP) and `8443/tcp` (HTTPS with a
  certificate from Caddy's internal CA). Both are always on in the web image. The
  Caddy admin endpoint stays on `localhost:2019`. The worker exposes no port.
- Web health check: the image `HEALTHCHECK` runs
  `curl -fsS -o /dev/null http://127.0.0.1:8080/api/health`, which returns `204`.
  TEST points its HTTP target group health check at the same port and path. PROD
  uses an HTTPS target group and an HTTPS health check on port 8443 with the same
  `/api/health` path.
- Worker health check: `/usr/local/bin/worker-healthcheck`.
- Writable volumes (`VOLUME`): `/srv/app/var`, `/data` and `/config` for the web
  image; `/srv/app/var` for the worker image. Caddy keeps its internal CA and
  certificates in `/srv/app/var/caddy`.
- Supervisor files: socket `/srv/app/var/run/supervisor.sock` (mode `0700`), pid
  file `/srv/app/var/run/supervisord.pid` and log
  `/srv/app/var/log/supervisord.log`.
- Linux capabilities: neither container needs any, so both can drop `ALL`.

Notes on the contract:

- The application code, configuration and dependencies stay root-owned and
  read-only for the application user; the build removes the world-writable bits
  that some dependency archives carry. Only the declared volumes, plus
  `/srv/app/public/bundles` and `/srv/app/config/jwt` that the image's own
  entrypoint writes when it runs the default `frankenphp` command, belong to
  `10001:10001`. The images pre-create `/srv/app/var/{cache,log,run,tmp}` with that
  owner.
- The `VOLUME` declarations matter on ECS: an ECS task volume copies the image's
  data and ownership only when the image declares a `VOLUME` at the same path;
  otherwise the volume is owned by `root` with mode `0755` and the application
  user cannot write it. Mount the task's ephemeral volumes at exactly these paths
  when the root filesystem is read-only. The worker no longer writes `/run`.
- The FrankenPHP binary carries no `cap_net_bind_service` file capability, and the
  PHP preload switch (`opcache.preload_user = app`) applies only when PHP starts
  as `root`. Neither container needs `NET_BIND_SERVICE`, `SETUID` or `SETGID`, and
  binding a port below 1024 fails.
- The local development image (`frankenphp_dev`) keeps `root` and its port 80,
  443 and 8081 listeners. The load-test and Schemathesis harnesses bind-mount the
  host checkout and install dependencies, assets and keys into it, so they set
  `user: '0:0'` and keep their development listeners.

`make image-runtime-tests` builds both images and verifies this contract with
Docker: the numeric `USER` and process UID, a refused bind on port 80 (with the
default capabilities and with `--cap-drop ALL`), successful binds on 8080 and 8443,
application ownership of every writable path, no world-writable application
files, no JWT key files, `config/reference.php` or `tests/` in the images, no capabilities
on PID 1, no setuid, setgid or file-capability binaries, and passing health
checks with the image defaults and with the ECS task shape (read-only root
filesystem, every capability dropped, the bootstrap command override; like
Fargate, without `no-new-privileges`). In both shapes it also checks the 8443
listener: `/api/health` over HTTPS returns `204` with a certificate that chains to
the root CA the container generated, and by address without SNI; plain HTTP on 8443
gets `400` with no redirect; and the CA keys and certificate in `/srv/app/var/caddy`
belong to the application user with no group or world access. The web image must
declare 8080 and 8443 (the worker image neither) and ship no TLS key, certificate or
Caddy certificate storage. A third web container in the ECS shape runs with a 90-second certificate
lifetime and a 5-second renewal check interval, and must serve a renewed
certificate before the first one expires, with the renewed certificate written to
its storage. Seeded negative fixtures (a world-writable file, a baked-in private
key, setuid, setgid and file-capability binaries, a stopped or missing container,
a plain-HTTP listener) prove the checks fail closed and name the offending paths.

The checks run on an internal Docker network (no egress, no default route) with
MongoDB, Redis and LocalStack. Each run generates throwaway production secrets
and uses synthetic KMS key ARNs in the fake account `123456789012`. The
containers get no static AWS credential variables, which the production guards
refuse; like an ECS task, they read credentials from
`AWS_CONTAINER_CREDENTIALS_RELATIVE_URI` at `169.254.170.2`, served by a stub on
the internal network. The stub runs the LocalStack 3.4.0 image pinned by digest
(the only digest-pinned reference in the check; the MongoDB, Redis and LocalStack
dependencies use tags) and returns fake credentials. Only `AWS_ENDPOINT_URL_SQS`
points the queue client at LocalStack. The run also restores the kernel default
for privileged ports (`net.ipv4.ip_unprivileged_port_start=1024`), which Docker
otherwise lowers to 0.

Every run uses the fixed task-role subnet `169.254.170.0/24`, and default runs
share the `user-service-web:non-root-check` and `user-service-worker:non-root-check`
tags. Runs on one Docker host are therefore serialized with `flock` (util-linux;
the run fails if it is missing) on `/tmp/user-service-image-check.lock` (override
with `IMAGE_CHECK_LOCK_FILE`). The lock is taken before the images are built. A
later run waits up to `IMAGE_CHECK_LOCK_TIMEOUT_SECONDS` (default 3600) and then
fails. The run records the built image IDs, checks them through tags unique to
the run, and before reporting success asserts that every runtime container and
fixture used exactly those IDs. If any Docker network still uses an overlapping
subnet, the run records a failure that names that network and skips the runtime
stack.
The build context excludes `tests/`, so the images ship no tests, fixtures or
check harness; every container that runs tests bind-mounts the checkout.

### SES delivery with task credentials

The application includes the Symfony Amazon Mailer SDK transport. Use
`ses+api://default?region=eu-central-1` as the mailer DSN for the TEST PoC, with
the approved sending identity and recipient restrictions on the ECS task role.
Do not put AWS access keys in the DSN. SES sandbox identity and recipient
verification still apply; installing the transport does not configure or
verify those identities.
