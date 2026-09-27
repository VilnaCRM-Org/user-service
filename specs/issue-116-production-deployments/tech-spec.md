# Issue #116 — TEST PoC image publication stage

## Functional requirements

- FR1: Build the real production web (`frankenphp_prod`) and worker
  (`app_workers`) targets from one immutable, reviewed application `main` commit.
- FR2: Require the normal `make ci` quality gate before AWS publication.
- FR3: Publish to the two fixed TEST ECR repositories with the dedicated
  bootstrap-owned GitHub OIDC role; do not use static AWS keys, the
  infrastructure apply role, or a caller-selected repository.
- FR4: Produce the service controller's `poc-release-v1` manifest and its
  quality, build, and provenance artifacts with native artifact IDs, file and
  archive SHA-256 values, exact registry receipt binding, and ECR digests.
- FR5: Allow only the installed GitHub App to dispatch attempt one on protected
  `main`; require the `poc-test-images` human-reviewed environment before OIDC.

## Nonfunctional requirements

- Source and workflow SHAs are identical and bound to the exact run. The job
  rejects duplicate JSON fields, unknown request fields, mutable image tags,
  foreign repository/actor identities, manual reruns, and wrong account pins.
- The quality/build jobs have no cloud credentials. The publish job has only
  TEST ECR push/read rights and no AWS access-key secret. The IAM role, registry,
  environment, and App installation are prerequisites, not auto-created by the
  publisher.
- ECR tags are unique per source/run; consumers use digest references. Failed
  or partial publication cannot be treated as an admitted release.
- Evidence files contain identifiers and digests only; never credentials,
  environment secret values, Docker config blobs, or image layers.

## Verification and boundaries

Offline unit and Bats tests cover the closed run/request/evidence generator;
`actionlint` validates the workflow. The repository's `make ci`, hosted PR
checks, independently reviewed current head, protected environment readback,
actual OIDC assumption, two real ECR digest observations, and service admission
must all pass before the TEST publisher is accepted. These source checks do not
claim an AWS deployment or complete issue #116.

The full issue still requires a protected PROD distribution path, verified
runtime pull access, application startup, second release, and rollback. This
stage enables the TEST milestone in user-service-infrastructure issue #18.
