Welcome to the Advanced Configuration Guide for the User Service. This guide is designed to help you customize and optimize your setup beyond the basic installation steps.

## Environment Variables

### Configuration

The User Service utilizes environment variables for configuration to ensure that sensitive information is not hard-coded into the application. Here are the environment variables you can configure:
- `APP_ENV`: Specifies the environment in which the application is running.
- `APP_SECRET`: A secret key used for cryptographic purposes, such as generating CSRF tokens or signing cookies.
- `API_DOMAIN`: The domain name of the API.
- `API_BASE_URL`: The base URL of the API, constructed using the `API_DOMAIN`.
- `DB_USER`: The username for the MySQL database.
- `DB_PASSWORD`: The password for the MySQL database.
- `DB_NAME`: The name of the MySQL database.
- `DB_HOST`: Specifies the hostname or IP address where the MySQL database server is running.
- `DB_PORT`: The port on which the MySQL database is running.
- `DATABASE_URL`: The URL for connecting to the MySQL database, including the username, password, host, port, and database name.
- `BATCH_SIZE`: The size of a batch for bulk inserts to a database.
- `REDIS_PORT`: The port on which the Redis server is running.
- `REDIS_URL`: The URL for connecting to the Redis server.
- `STRUCTURIZR_PORT`: The port on which Structurizr is running.
- `EMAIL_QUEUE_NAME`: The name of the queue for sending emails.
- `FAILED_EMAIL_QUEUE_NAME`: The name of the queue for failed email deliveries.
- `LOCALSTACK_PORT`: The port on which Localstack is running.
- `MESSENGER_TRANSPORT_DSN`: The DSN (Data Source Name) for the messenger transport, configured to use Amazon SQS via Localstack for sending emails.
- `FAILED_EMAIL_TRANSPORT_DSN`: The DSN for the messenger transport used for handling failed email deliveries.
- `MAILCATCHER_SMTP_PORT`: The port on which the Mailcatcher SMTP server is running.
- `MAILCATCHER_HTTP_PORT`: The port on which the Mailcatcher HTTP server is running.
- `MAILER_DSN`: The DSN for the mailer, configured to use SMTP via Mailcatcher.
- `MAIL_SENDER`: The email address used as the sender for outgoing emails.
- `OAUTH_PRIVATE_KEY`: The path to the private key used for OAuth 2.0 authentication.
- `OAUTH_PUBLIC_KEY`: The path to the public key used for OAuth 2.0 authentication.
- `OAUTH_PASSPHRASE`: The passphrase used to decrypt the private key.
- `OAUTH_ENCRYPTION_KEY_TYPE`: Specifies the type of encryption key used, either "plain" or "defuse".
- `OAUTH_ENCRYPTION_KEY`: Required if `OAUTH_ENCRYPTION_KEY_TYPE` was set to "defuse". Learn more [here](https://oauth2.thephpleague.com/installation/#string-password).
- `ACCESS_TOKEN_TTL`: The TTL for access tokens. Learn more [here](http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters).
- `REFRESH_TOKEN_TTL`: The TTL for refresh tokens. Learn more [here](http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters).
- `AUTH_CODE_TTL`: The TTL for authorization codes. Learn more [here](http://php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters7).
- `JWT_TOKEN_TTL`: The TTL for JWT tokens.
- `PASSWORD_HASHING_COST`: The cost factor for the bcrypt password hashing algorithm.
- `CORS_ALLOW_ORIGIN`: The regular expression defining the allowed origins for Cross-Origin Resource Sharing (CORS).

Learn more about [Symfony Environment Valiables](https://symfony.com/doc/current/configuration.html#configuring-environment-variables-in-env-files)

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