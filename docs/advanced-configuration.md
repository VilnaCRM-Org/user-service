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
- `USER_INSERT_BATCH_SIZE`: The size of a batch for bulk user inserts to the database.

#### Redis

- `REDIS_URL`: The URL for connecting to the Redis server (e.g., `redis://redis:6379/0`).

#### AWS SQS / LocalStack

- `AWS_SQS_VERSION`: The AWS SQS API version.
- `AWS_SQS_REGION`: The AWS region for SQS.
- `AWS_SQS_ENDPOINT_BASE`: The SQS endpoint base (e.g., `localstack` for local development).
- `AWS_SQS_KEY`: The AWS access key for SQS.
- `AWS_SQS_SECRET`: The AWS secret key for SQS.
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
- `OAUTH_ENCRYPTION_KEY`: Required if `OAUTH_ENCRYPTION_KEY_TYPE` was set to `defuse`. Learn more [here](https://oauth2.thephpleague.com/installation/#string-password).
- `ACCESS_TOKEN_TTL`: The TTL for access tokens. Learn more [here](http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters).
- `REFRESH_TOKEN_TTL`: The TTL for refresh tokens. Learn more [here](http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters).
- `AUTH_CODE_TTL`: The TTL for authorization codes. Learn more [here](http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters).

#### JWT

- `JWT_TOKEN_TTL`: The TTL for JWT tokens in seconds.

#### Security and Tokens

- `CONFIRMATION_TOKEN_LENGTH`: The length of user confirmation tokens (default: 10).
- `PASSWORD_RESET_TOKEN_LENGTH`: The length of password reset tokens (default: 32).
- `PASSWORD_RESET_TOKEN_EXPIRATION_HOURS`: How long password reset tokens are valid in hours (default: 1).
- `PASSWORD_RESET_RATE_LIMIT_MAX_REQUESTS`: Maximum password reset requests allowed within the rate limit interval (default: 1000).
- `PASSWORD_RESET_RATE_LIMIT_INTERVAL`: The time window for password reset rate limiting (default: "1 hour").

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
