# Specification: Evaluate migration from LocalStack to Floci for local AWS emulation

## Problem Statement

The project uses `localstack/localstack:3.4.0` solely for SQS emulation in local dev and load/memory/schemathesis test environments. LocalStack is a heavyweight, full-AWS-emulation container (~500 MB) that requires a Python runtime and emulates ~80+ services. Only SQS is used (`SERVICES=sqs`). Floci is a purpose-built, minimal Go binary SQS/SNS emulator (~20 MB) that may offer faster startup, lower resource usage, and simpler maintenance.

## Current State

| Aspect | Value |
|--------|-------|
| Image | `localstack/localstack:3.4.0` |
| Services used | SQS only |
| Port | `4566` |
| Init mechanism | `awslocal` CLI via `infrastructure/docker/php/init-aws.sh` mounted to `/etc/localstack/init/ready.d/` |
| Health check | `curl -fsS http://localhost:4566/_localstack/health \| grep -q '"sqs": "running"'` |
| Queues created | `send-email`, `failed-emails`, `insert-user` |
| SQS transports | `send-email`, `failed-send-email`, `insert-user-batch`, `domain-events`, `failed-domain-events` |
| Account ID in DSN | `000000000000` (LocalStack default) |
| Compose files affected | `docker-compose.override.yml`, `docker-compose.load-tests.yml`, `docker-compose.memory-tests.yml`, `docker-compose.schemathesis.yml` |

## Target State

Replace `localstack/localstack:3.4.0` with `floci/floci:latest-compat` (recommended; see Compatibility Matrix) in all four compose files, update the init mechanism and health check, and validate all SQS transports work end-to-end.

## Non-Goals

- Migrating production AWS infrastructure
- Changing application code (PHP `src/`)
- Changing Symfony Messenger transport configuration (DSNs stay identical)
- Adding SNS or any other AWS service emulation
- Changing CI workflow YAML files (CI uses in-memory transports)
- Updating environment variable names

## Assumptions

- Floci implements the SQS HTTP API surface required by Symfony's `amazon-sqs-messenger` (`SendMessage`, `ReceiveMessage`, `DeleteMessage`, `GetQueueUrl`, `CreateQueue`, `ListQueues`)
- Floci accepts or ignores the `sslmode=disable` query parameter in SQS DSNs
- Floci supports fake account ID `000000000000` in queue URLs
- `floci/floci:latest-compat` bundles the AWS CLI or `awslocal`-equivalent tooling for init scripts

## Open Questions

1. Does `floci/floci:latest-compat` include `awslocal` or the AWS CLI? If not, must the init script be rewritten using `aws --endpoint-url`?
2. What is Floci's readiness/health check endpoint? Is there an HTTP health probe comparable to `/_localstack/health`?
3. Does Floci support `auto_setup: true` (automatic `CreateQueue` on first dispatch)?
4. Does Floci preserve the `000000000000` account ID convention in queue URLs?
5. Should a pinned Floci version be used instead of `latest` to avoid CI drift?

## Compatibility Matrix

| Feature | LocalStack 3.4.0 | floci:latest | floci:latest-compat | Risk |
|---------|-----------------|--------------|---------------------|------|
| SQS `SendMessage` | ✓ | Expected ✓ | Expected ✓ | Low |
| SQS `ReceiveMessage` | ✓ | Expected ✓ | Expected ✓ | Low |
| SQS `DeleteMessage` | ✓ | Expected ✓ | Expected ✓ | Low |
| SQS `CreateQueue` | ✓ | Expected ✓ | Expected ✓ | Low |
| `auto_setup: true` | ✓ | Unknown | Unknown | Medium |
| `awslocal` CLI in container | ✓ | ✗ | Unknown | **High** |
| `/_localstack/health` endpoint | ✓ | ✗ | ✗ | **High** |
| Init mount path `/etc/localstack/init/ready.d/` | ✓ | ✗ | ✗ | **High** |
| Account ID `000000000000` in URLs | ✓ | Unknown | Unknown | Medium |
| `sslmode=disable` DSN param | ✓ (ignored) | Unknown | Unknown | Low |

## Risk Assessment

### High Risks
1. **`awslocal` CLI not in Floci**: `init-aws.sh` uses `awslocal sqs create-queue`. Must verify whether `floci:latest-compat` bundles this tool. If not, rewrite using `aws --endpoint-url http://floci:4566 sqs create-queue --queue-name <n>`.
2. **Health check**: `/_localstack/health` is LocalStack-specific. Must find Floci equivalent (e.g., TCP check, simple HTTP 200 probe, or `curl http://floci:4566`).
3. **Init hook path**: LocalStack's `/etc/localstack/init/ready.d/` auto-execution does not exist in Floci. Queue creation needs an alternative: `command:` entrypoint override, or a startup shell wrapper.

### Medium Risks
4. **Account ID**: All DSNs embed `000000000000`. Floci must accept this or be configured to match.
5. **`auto_setup: true`**: Symfony auto-creates queues via `CreateQueue`. Must be confirmed working against Floci before removing from init script.

### Low Risks
6. **Port 4566**: Same default — no DSN changes.
7. **`SERVICES=sqs`**: Floci-only env var, can be dropped.
8. **Image size/startup**: Expected ~20 MB vs ~500 MB improvement.

## Acceptance Criteria

- [ ] `make start` completes without errors with Floci replacing LocalStack
- [ ] All 5 Symfony Messenger SQS transports connect and operate correctly
- [ ] Queues `send-email`, `failed-emails`, `insert-user` are created on container startup
- [ ] `auto_setup: true` works for `domain-events` and `insert-user-batch` transports
- [ ] Health check / `--wait` condition passes in all four compose files
- [ ] `make behat` passes (in-memory transport in test env — SQS not exercised)
- [ ] `make smoke-load-tests` passes with end-to-end SQS message flow
- [ ] Zero application code changes (`src/`, `tests/`, `config/`)
- [ ] Zero CI workflow YAML changes

## Test Strategy

1. **Stack health**: `make start` → `docker compose ps` all services healthy
2. **Transport connect**: `docker compose exec php bin/console messenger:consume domain-events --time-limit=5`
3. **E2E**: `make behat` (in-memory in test env — validates no regressions)
4. **Load/SQS end-to-end**: `make smoke-load-tests`

## Rollback Strategy

All changes confined to 4 Docker Compose files and `infrastructure/docker/php/init-aws.sh`. Rollback = `git revert` of migration commit. No application code or CI YAML touched.

## Validation Commands

```bash
make start
docker compose ps
docker compose exec php bin/console messenger:consume domain-events --time-limit=5 2>&1
make behat
make smoke-load-tests
```
