# TEST PoC image publication

The protected `publish-poc-images.yml` workflow builds the current `Dockerfile`
targets `frankenphp_prod` and `app_workers`, then publishes their immutable
digests to the TEST ECR repositories. It does not create ECR, IAM, DNS, or an
application service. Those resources belong to the reviewed bootstrap and
user-service-infrastructure Pulumi stacks.

## Installation order

1. Merge this workflow to protected `main` after its checks and independent
   reviews pass. Pin the installed main commit in the service controller as
   `POC_PUBLISHER_WORKFLOW_SHA`.
2. Install the TEST registry and its completion receipt through the protected
   service-infrastructure controller. The repository names are
   `user-service-test-web` and `user-service-test-worker` in account
   `891377212104`, region `eu-central-1`.
3. Configure the application repository's `poc-test-images` environment to
   allow only `main`, require Kravalg's approval, prevent self-review, and
   disable admin bypass. Verify these settings before activating the publisher
   role. An environment name alone is not a protection gate.
4. Install the bootstrap-owned `user-service-test-ImagePublisher` IAM role and
   the two-repository ECR push grant. Its OIDC trust must match this repository,
   immutable repository and owner IDs, `main`, this workflow path,
   `workflow_dispatch`, and the protected environment. No AWS access keys or
   repository AWS secrets are needed.
5. Grant the existing `vilnacrm-user-service-evidence` GitHub App Actions write
   access to this repository. The service controller dispatches the installed
   publisher after authenticating the registry receipt and current GitHub
   protections. Ordinary manual dispatches and reruns fail closed.

The publisher uses `aws-actions/configure-aws-credentials` with GitHub OIDC,
checks the TEST account, and logs in to ECR only in the protected publish job.
Quality and image builds run without AWS credentials. The exact main source
commit must pass `make ci`; the build uses that same commit and AMD64 targets.

## Release evidence

The workflow uploads four per-run artifacts:

- `poc-image-build-{run}-1`: the web/worker Docker transfer archive and its
  native artifact digest.
- `poc-quality-evidence-{run}-1`: `quality.json`, bound to the successful
  quality job and `make ci`.
- `poc-build-provenance-{run}-1`: `provenance.json`, bound to the build artifact,
  registry receipt, exact source, fixed targets, and ECR digests.
- `poc-release-manifest-{run}-1`: `release-manifest.json` with both immutable
  image digests and evidence references.

The service-infrastructure admission verifier checks the original GitHub App
actor, attempt one, job order, artifact IDs and SHA-256 values, current registry
checkpoint, and native ECR manifests before using a release. A successful
publisher run alone does not authorize an infrastructure apply.

The fixed image tag includes the full source SHA and workflow run ID to avoid
rewriting an immutable ECR tag. Workload deployments and rollbacks consume the
content digests in the admitted manifest, not a mutable tag. If publication
fails after one image push, inspect the run and dispatch a fresh reviewed run;
attempt-two reruns are deliberately rejected.

This workflow supplies the TEST PoC publication stage only. PROD artifact
promotion, runtime pull verification, ECS startup, HTTPS/application checks,
second release, and rollback still require their separate protected acceptance.
