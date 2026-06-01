# Implementation Log

## Phase 05 — Implementation (2026-05-31)

### Files Changed

| File                                    | Change                                                                                                    | Lines     |
| --------------------------------------- | --------------------------------------------------------------------------------------------------------- | --------- |
| `infrastructure/docker/php/init-aws.sh` | `awslocal` → `aws` × 3                                                                                    | 3 changed |
| `docker-compose.override.yml`           | Image, add `FLOCI_HOSTNAME`, remove `SERVICES=sqs`, remove `localstack_data` volume mount and declaration | 6 changed |
| `docker-compose.load-tests.yml`         | Image, add `FLOCI_HOSTNAME`, remove `SERVICES=sqs`, simplify health check grep                            | 5 changed |
| `docker-compose.memory-tests.yml`       | Image, add `FLOCI_HOSTNAME`, remove `SERVICES=sqs`, simplify health check grep                            | 5 changed |
| `docker-compose.schemathesis.yml`       | Image, add `FLOCI_HOSTNAME`, remove `SERVICES=sqs`                                                        | 3 changed |

**Total: 5 files, ~22 line changes (insertions + deletions)**

### Reasons

- **`awslocal` → `aws`**: `floci/floci:latest-compat` bundles the AWS CLI pre-configured
  with the local endpoint. `awslocal` is a LocalStack-specific wrapper not present in Floci.
  The plain `aws` CLI works without any `--endpoint-url` flag in the compat image.

- **`FLOCI_HOSTNAME=localstack`**: Floci embeds the hostname in SQS `QueueUrl` response
  values. Without this setting it defaults to `localhost`, which resolves to the wrong
  container from the PHP service. Setting it to `localstack` (the compose service name)
  ensures PHP can reach the returned queue URLs.

- **Remove `SERVICES=sqs`**: LocalStack-specific env var; Floci ignores it (or translates
  it in parity mode). Removing avoids unnecessary noise.

- **Remove `localstack_data` volume**: LocalStack stored persistent data at
  `/var/lib/localstack`. Floci does not use this path. Queues are ephemeral and
  recreated by the init script on every startup.

- **Simplify health check** (load-tests, memory-tests): The pattern
  `grep -q '"sqs": "running"'` is LocalStack-specific. Floci serves `/_localstack/health`
  (parity mode) but with a different response body. A plain HTTP 200 check
  (`curl -fsS http://localhost:4566/_localstack/health`) is sufficient and portable.

### Commands Run

None — all changes are static config edits. No `make start` or application commands
run during implementation. Stack will be validated in phase 06.

### Zero Application Code Changed

`src/`, `tests/`, `config/`, `.env*`, CI YAML — all untouched.
