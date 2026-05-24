# Implementation Plan: LocalStack → Floci Migration

## Summary

5 files, ~15 line changes. No application code, no CI YAML, no DSNs, no env var names touched.

## Prerequisites

- Floci image pulled: `docker pull floci/floci:latest-compat`
- No other dependencies

## Step 1 — Update `infrastructure/docker/php/init-aws.sh`

Replace `awslocal` with `aws` on all 3 queue creation lines. In `latest-compat`, the AWS CLI is pre-configured with the local endpoint — no `--endpoint-url` flag required.

```diff
 #!/bin/sh
-awslocal sqs create-queue --queue-name send-email
-awslocal sqs create-queue --queue-name failed-emails
-awslocal sqs create-queue --queue-name insert-user
+aws sqs create-queue --queue-name send-email
+aws sqs create-queue --queue-name failed-emails
+aws sqs create-queue --queue-name insert-user
```

## Step 2 — Update `docker-compose.override.yml`

```diff
   localstack:
-    image: localstack/localstack:3.4.0
+    image: floci/floci:latest-compat
     ports:
       - '${LOCALSTACK_PORT}:4566'
     environment:
-      - SERVICES=sqs
       - DEBUG=1
+      - FLOCI_HOSTNAME=localstack
     volumes:
-      - localstack_data:/var/lib/localstack
       - ./infrastructure/docker/php/init-aws.sh:/etc/localstack/init/ready.d/init-aws.sh
     networks:
       - user-service
```

**Notes:**
- `SERVICES=sqs` dropped — Floci translates it but it's unnecessary noise
- `FLOCI_HOSTNAME=localstack` is required so SQS `QueueUrl` values in responses use `localstack` (the compose service name) instead of `localhost`
- `localstack_data` volume mount removed — Floci does not use `/var/lib/localstack`; queues are ephemeral and recreated by init script
- Volume declaration `localstack_data:` in `volumes:` section must also be removed

## Step 3 — Update `docker-compose.load-tests.yml`

Same image and env changes. Health check `grep` pattern must be updated because Floci's `/_localstack/health` response does not include `"sqs": "running"` — replace with a plain HTTP 200 check.

```diff
   localstack:
-    image: localstack/localstack:3.4.0
+    image: floci/floci:latest-compat
     environment:
-      - SERVICES=sqs
       - DEBUG=1
+      - FLOCI_HOSTNAME=localstack
     volumes:
       - ./infrastructure/docker/php/init-aws.sh:/etc/localstack/init/ready.d/init-aws.sh
     healthcheck:
       test:
         [
           'CMD-SHELL',
-          'curl -fsS http://localhost:4566/_localstack/health | grep -q "\"sqs\": \"running\""',
+          'curl -fsS http://localhost:4566/_localstack/health',
         ]
       interval: 5s
       timeout: 3s
       retries: 20
       start_period: 10s
```

## Step 4 — Update `docker-compose.memory-tests.yml`

Same changes as Step 3.

```diff
   localstack:
-    image: localstack/localstack:3.4.0
+    image: floci/floci:latest-compat
     environment:
-      - SERVICES=sqs
       - DEBUG=1
+      - FLOCI_HOSTNAME=localstack
     volumes:
       - ./infrastructure/docker/php/init-aws.sh:/etc/localstack/init/ready.d/init-aws.sh
     healthcheck:
       test:
         [
           'CMD-SHELL',
-          'curl -fsS http://localhost:4566/_localstack/health | grep -q "\"sqs\": \"running\""',
+          'curl -fsS http://localhost:4566/_localstack/health',
         ]
```

## Step 5 — Update `docker-compose.schemathesis.yml`

Image and env changes only (no health check in this file).

```diff
   localstack:
-    image: localstack/localstack:3.4.0
+    image: floci/floci:latest-compat
     environment:
-      - SERVICES=sqs
       - DEBUG=1
+      - FLOCI_HOSTNAME=localstack
     volumes:
       - ./infrastructure/docker/php/init-aws.sh:/etc/localstack/init/ready.d/init-aws.sh
```

## Validation Sequence

After all changes applied:

```bash
# 1. Bring up dev stack
make start
docker compose ps   # all services healthy

# 2. Verify queues were created by init script
docker compose exec localstack aws sqs list-queues

# 3. Verify SQS transport connectivity from PHP
docker compose exec php bin/console messenger:consume domain-events --time-limit=5 2>&1

# 4. Regression: Behat (in-memory transport in test env — SQS not exercised)
make behat

# 5. E2E SQS: load test
make smoke-load-tests
```

## Rollback

```bash
git revert HEAD   # or git checkout main -- <files>
make start
```

All changes are in 5 files. Rollback takes < 60 seconds.

## Version Pinning Note

`floci/floci:latest-compat` is safe for dev environments where image freshness is acceptable. For reproducibility across CI-adjacent environments (load tests, memory tests), consider pinning to `floci/floci:1.5.11-compat` once the current stable version is confirmed on Docker Hub.
