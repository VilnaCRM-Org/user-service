# Impact Analysis: LocalStack → Floci Migration

## Open Questions Resolved

All 5 open questions from `spec.md` are now answered via Floci documentation research.

| #   | Question                                            | Answer                                                                                                                                                                        | Source                                         |
| --- | --------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------- |
| 1   | Does `latest-compat` include `awslocal` or AWS CLI? | **AWS CLI included and pre-configured** with local endpoint — no `--endpoint-url` needed. `awslocal` (LocalStack-specific wrapper) is NOT included; replace with plain `aws`. | floci.io docs, Docker Hub                      |
| 2   | What is Floci's health check endpoint?              | **`/_localstack/health` is served by Floci** in LocalStack parity mode (on by default). Existing health check works unchanged.                                                | floci.io parity docs                           |
| 3   | Does Floci support `auto_setup: true`?              | **Yes.** `auto_setup` triggers standard `CreateQueue` calls via Symfony Messenger, which Floci fully supports.                                                                | Symfony Messenger docs + Floci SQS API support |
| 4   | Does Floci preserve `000000000000` account ID?      | **Yes.** `FLOCI_DEFAULT_ACCOUNT_ID` defaults to `000000000000`. Existing DSNs unchanged.                                                                                      | floci.io configuration docs                    |
| 5   | Should a pinned version be used?                    | **Yes.** `floci/floci:1.5.11-compat` (pinned stable compat tag) is available. Prevents CI drift.                                                                              | Docker Hub                                     |

## Risk Re-Assessment

All 3 High risks from the spec have been downgraded:

| Risk                                            | Spec Rating | Actual Rating | Resolution                                                                             |
| ----------------------------------------------- | ----------- | ------------- | -------------------------------------------------------------------------------------- |
| `awslocal` CLI not in Floci                     | **High**    | **Low**       | Replace `awslocal` → `aws` in `init-aws.sh` (AWS CLI pre-configured in compat image)   |
| Health check (`/_localstack/health`)            | **High**    | **None**      | Floci serves `/_localstack/health` — zero change needed                                |
| Init mount path `/etc/localstack/init/ready.d/` | **High**    | **None**      | Floci reads both `/etc/localstack/init/` and `/etc/floci/init/` — existing mount works |
| Account ID `000000000000`                       | **Medium**  | **None**      | Floci defaults to `000000000000`                                                       |
| `auto_setup: true`                              | **Medium**  | **None**      | Standard SQS `CreateQueue` API — fully supported                                       |

**New risk discovered:**

| Risk                                                                              | Rating     | Resolution                                                                      |
| --------------------------------------------------------------------------------- | ---------- | ------------------------------------------------------------------------------- |
| `FLOCI_HOSTNAME` not set — SQS queue URLs use `localhost` instead of service name | **Medium** | Add `FLOCI_HOSTNAME: localstack` (or Floci service name) to all 4 compose files |

## Files Affected

### Application Code

**Zero files changed.** `src/`, `tests/`, `config/`, `.env*`, CI YAML — all untouched.

### Docker Compose Files (4 files, minimal changes each)

| File                              | Changes                                                |
| --------------------------------- | ------------------------------------------------------ |
| `docker-compose.override.yml`     | Image tag, add `FLOCI_HOSTNAME`, remove `SERVICES=sqs` |
| `docker-compose.load-tests.yml`   | Same                                                   |
| `docker-compose.memory-tests.yml` | Same                                                   |
| `docker-compose.schemathesis.yml` | Same                                                   |

Health check (`/_localstack/health` grep) and init volume mount (`/etc/localstack/init/ready.d/`) require **no change**.

### Infrastructure Scripts (1 file, 3-line change)

| File                                    | Change                                                  |
| --------------------------------------- | ------------------------------------------------------- |
| `infrastructure/docker/php/init-aws.sh` | `awslocal` → `aws` on 3 lines (queue creation commands) |

## Diff Summary

**Total changes: 5 files, ~15 lines.**

```
docker-compose.override.yml        image tag + FLOCI_HOSTNAME env var + remove SERVICES
docker-compose.load-tests.yml      same
docker-compose.memory-tests.yml    same
docker-compose.schemathesis.yml    same
infrastructure/docker/php/init-aws.sh   s/awslocal/aws/ × 3
```

## LocalStack Sunset Context

LocalStack Community Edition sunset in **March 2026** — it now requires an auth token and has frozen security updates. This migration is no longer purely an optimization; it is a maintenance necessity.

## Compatibility Summary

| Concern                             | Verdict                                           |
| ----------------------------------- | ------------------------------------------------- |
| Health check unchanged              | ✓                                                 |
| Init script path unchanged          | ✓                                                 |
| Queue DSNs unchanged                | ✓                                                 |
| Account ID `000000000000` unchanged | ✓                                                 |
| `auto_setup: true` works            | ✓                                                 |
| `sslmode=disable` DSN param         | ✓ (ignored by Floci)                              |
| Image size reduction                | ~500 MB → ~20 MB standard; compat slightly larger |
| Startup speed improvement           | ~500 ms+ → ~24 ms (Quarkus native binary)         |
| `FLOCI_HOSTNAME` required           | **New — must add**                                |
| `awslocal` → `aws` in init script   | **New — 3-line change**                           |
