# Issue #302: Replace LocalStack with Floci for local SQS emulation

## Problem

The project uses `localstack/localstack:3.4.0` solely for SQS emulation in
local dev, load-test, memory-test, and schemathesis environments. LocalStack
is a heavyweight full-AWS-emulation container (~500 MB, Python runtime, 80+
services) when only SQS is needed.

Additionally, LocalStack Community Edition was sunset in March 2026: it now
requires an auth token and has frozen security updates. Continuing to use it
introduces a maintenance and supply-chain risk.

Floci is a purpose-built Quarkus-native SQS/SNS emulator that starts in ~24 ms,
idles at ~13 MiB, and is a drop-in replacement requiring fewer than 15 line
changes across 5 files.

## Goals

- Replace `localstack/localstack:3.4.0` with `floci/floci:latest-compat` in
  all four compose files.
- Replace the `awslocal` CLI with the plain `aws` CLI (bundled in the compat
  image) in `infrastructure/docker/php/init-aws.sh`, passing the Floci endpoint
  explicitly via `--endpoint-url http://localhost:4566 --no-sign-request`.
- Add `FLOCI_HOSTNAME=localstack` so SQS `QueueUrl` responses use the
  compose service name instead of `localhost`.
- Remove the now-unnecessary `SERVICES=sqs` env var and the
  `localstack_data` volume mount.
- Simplify health-check test commands to a plain HTTP 200 probe where
  the LocalStack-specific grep pattern would otherwise break.

## Non-Goals

- Do not change any application code (`src/`, `tests/`, `config/`).
- Do not change Symfony Messenger transport DSNs or env var names.
- Do not modify CI workflow YAML files (CI uses in-memory transports).
- Do not add SNS or any other AWS service emulation.
- Do not migrate production AWS infrastructure.

## Proposed Design

**`infrastructure/docker/php/init-aws.sh`** — replace `awslocal` with `aws`.
The `awslocal` wrapper injected the LocalStack endpoint and dummy credentials
automatically; the plain `aws` CLI does not, so the script targets Floci
explicitly with `--endpoint-url http://localhost:4566` and skips request signing
with `--no-sign-request`. The script also sets `set -eu` so a failed
`create-queue` aborts container init instead of silently continuing.

**All four compose files** — identical pattern per file:

| Change                                                 | Reason                                                                                                                                                        |
| ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `image: floci/floci:latest-compat`                     | Drop-in replacement; compat bundles AWS CLI                                                                                                                   |
| Add `FLOCI_HOSTNAME=localstack`                        | Queue URLs must embed service name, not `localhost`                                                                                                           |
| Remove `SERVICES=sqs`                                  | Floci runs all services; env var is LocalStack-specific noise                                                                                                 |
| Remove `localstack_data:/var/lib/localstack` volume    | Floci does not use this path; queues are ephemeral and recreated by init script                                                                               |
| Simplify health-check grep (load/memory compose files) | `"sqs": "running"` pattern is LocalStack-specific; Floci serves `/_localstack/health` but with a different response body — plain HTTP 200 probe is sufficient |

Floci compatibility guarantees that make zero further changes necessary:

- `/_localstack/health` is served (LocalStack parity mode, on by default)
- `/etc/localstack/init/ready.d/` is read at startup
- `FLOCI_DEFAULT_ACCOUNT_ID` defaults to `000000000000`
- `CreateQueue` / `auto_setup: true` are fully supported

## Acceptance Mapping

- `make start` completes with all services healthy and Floci replacing LocalStack.
- Queues `send-email`, `failed-emails`, `insert-user` are created by the init
  script on container startup.
- All 5 Symfony Messenger SQS transports connect and operate correctly.
- `auto_setup: true` works for `domain-events` and `insert-user-batch`.
- Health check passes in all four compose files.
- `make behat` passes (in-memory transport in test env — SQS not exercised).
- `make smoke-load-tests` passes with end-to-end SQS message flow.
- Zero application code changes (`src/`, `tests/`, `config/`).
- Zero CI workflow YAML changes.

## Validation Plan

- `make start` → `docker compose ps` → all services healthy
- `docker compose exec localstack aws sqs list-queues` → 3 queues present
- `docker compose exec php bin/console messenger:consume domain-events --time-limit=5`
- `make behat`
- `make smoke-load-tests`

## Performance Impact

Image size drops from ~500 MB (LocalStack, Python runtime) to ~100 MB
(Floci compat with AWS CLI). Startup time drops from ~500 ms to ~24 ms.
No runtime impact on the PHP application.
