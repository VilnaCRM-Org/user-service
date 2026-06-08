# Manual Evidence: Issue 217 Register User CQRS

Status: COMPLETE_FOR_PR_VALIDATION

This file is the manual evidence source for:

```bash
BMAD_REVIEW_MANUAL_EVIDENCE=specs/issue-217-register-user-cqrs/manual-evidence-template.md
```

Scope: local test-environment release-readiness evidence for PR #282. No
production data was read or mutated. Production release operators must keep the
same checklist with production artifact paths before running the mutating
backfill in production.

## Tester

- Tester: OpenAI Codex
- Role: implementation and PR validation agent
- Date: 2026-06-08T09:58:12+03:00
- Environment: local Docker Compose test environment in
  `/home/kravtsov/Projects/user-service-issue217`
- Release or commit SHA:
  `b3d0c8f0af323cc830c3ddcdf1a0161811c9a0d5`

## Backfill Dry Run

- Scenario: normalized-email backfill dry run
- Related requirements: FR17, Manageability, Observability Diagnosability, Data Quality Integrity
- Steps:
  1. Confirm the isolated Docker Compose stack is running on the PR validation
     ports with MongoDB 7.0.
  2. Run the backfill command in `APP_ENV=test` with `--dry-run` and
     `--report-file`.
  3. Read the generated JSON report from the PHP container.
- Command:

  ```bash
  env HTTP_PORT=60080 HTTPS_PORT=60443 HTTP3_PORT=60444 PHP_DEV_PORT=60081 MONGODB_PORT=60217 REDIS_PORT=60279 LOCALSTACK_PORT=60566 MAILCATCHER_SMTP_PORT=60125 MAILCATCHER_HTTP_PORT=60180 STRUCTURIZR_PORT=60880 SCHEMATHESIS_API_PORT=60081 SCHEMATHESIS_BASE_URL=http://localhost:60081 MONGODB_IMAGE=mongo:7.0 docker compose exec -e APP_ENV=test php php bin/console app:backfill-user-normalized-emails --dry-run --report-file=/tmp/normalized-email-backfill.json
  ```

- Observed command output:

  ```text
  Backfill report written to /tmp/normalized-email-backfill.json

  [OK] Dry run completed: 0 matched users would be backfilled; 0 users modified.
  ```

- Dry-run JSON report artifact path or link:
  `/tmp/normalized-email-backfill.json` inside the local PHP container
- Dry-run elapsed time: less than 1 second in the isolated local test dataset
- Dry-run JSON report contents:

  ```json
  {
    "generatedAt": "2026-06-08T06:58:00+00:00",
    "status": "success",
    "matched": 0,
    "modified": 0,
    "dryRun": true,
    "duplicates": []
  }
  ```

- `matched`: 0
- `modified`: 0
- `dryRun`: true
- `duplicates`: []

## Performance And Capacity Evidence

- Related requirements: Performance Resource Sustainability, Operational
  Excellence Releaseability, Observability Diagnosability, Manageability
- Normal registration lookup budget: one indexed `normalizedEmail` lookup for
  current records, with one legacy fallback lookup only during the
  deploy-before-backfill window.
- Batch registration lookup budget: one indexed `normalizedEmail` `$in` lookup
  and one legacy fallback `$expr`/`$in` lookup for the whole batch. Evidence:
  `tests/Unit/User/Infrastructure/Repository/MongoDBUserRepositoryFindByEmailsTest.php`
  asserts two query builders for `findByEmails`.
- Backfill cursor and write budget: `BackfillUserNormalizedEmailsBackfiller`
  uses 100-document cursor batches and 100-operation bulk-write batches.
- Local dry-run result: 0 matched, 0 modified, 0 duplicates, less than 1 second
  elapsed in the isolated test dataset.
- Production budget evidence required before mutating data: the release
  operator must record dry-run start time, finish time, elapsed time, matched
  count, modified count, and duplicate count from the JSON report. The mutating
  run must use the same report fields and elapsed-time evidence.

## Duplicate Remediation Review

- Scenario: duplicate normalized-email review
- Related requirements: FR17, Security Privacy Accountability, Data Quality Integrity, Recoverability
- Duplicate list reviewed by: OpenAI Codex
- Decision for each duplicate: not applicable; the dry-run report returned an
  empty `duplicates` list
- Customer notification required: no, because no duplicate normalized-email
  group was reported in the local test dry run
- Approval artifact path or link: this evidence file and the dry-run JSON report
  above

## Rollout Checklist

- Unique partial `normalizedEmail` index confirmed: yes, covered by
  `tests/Integration/User/Infrastructure/Repository/MongoDBUserRepositoryNormalizedEmailIntegrationTest.php`
  and the `make ci` run required before final PR push
- Dry-run report attached: yes, `/tmp/normalized-email-backfill.json` contents
  are included above
- Duplicate remediation complete or not applicable: not applicable for this
  local dry run because `duplicates` is empty
- Mutating backfill command approved: not run in production during PR
  validation; production execution requires release-operator approval after a
  production dry run
- Mutating backfill report artifact path or link: not applicable for this PR
  validation because the production mutating backfill was intentionally not run
- Registration duplicate smoke check result: automated coverage in
  `tests/Integration/User/Application/Processor/RegisterUserRestIntegrationTest.php`;
  final evidence is the required `make ci` pass
- OAuth duplicate social callback check result: automated coverage in
  `tests/Integration/Auth/EmailAmbiguityRuntimeIntegrationTest.php`; final
  evidence is the required `make ci` pass
- Password-reset ambiguous email check result: automated coverage in
  password-reset ambiguity integration tests; final evidence is the required
  `make ci` pass
- 2FA ambiguous email check result: automated coverage in 2FA ambiguity
  integration tests; final evidence is the required `make ci` pass

## Monitoring Evidence

- Related requirements: Manageability, Observability Diagnosability,
  Dependability, Recoverability
- Dashboard source: centralized production application and MongoDB logs. The
  dashboard definitions are outside this repository, so release operators must
  attach screenshots or links to the production release record.
- Required release queries:
  - `DuplicateEmailException` in `user-service`
  - `E11000 duplicate key error` and `normalizedEmail`
  - OAuth `duplicate_email` conflict responses
  - `app:backfill-user-normalized-emails`
- Alert thresholds:
  - Any MongoDB duplicate-key error for `normalizedEmail` after the mutating
    backfill finishes requires rollback assessment.
  - Any backfill command failure or JSON report with non-empty `duplicates`
    blocks the mutating run.
  - Duplicate-email API/OAuth conflict rates must not remain above the
    pre-release duplicate-remediation baseline for 10 consecutive minutes.

## Rollback Checklist

- Rollback owner: release operator for the production deployment
- Previous version compatibility with unknown MongoDB fields confirmed: yes,
  rollback guidance in `docs/operational.md` states older versions ignore the
  `normalizedEmail` field and the field/index remain in place until a separate
  reviewed rollback cleanup
- Rollback trigger conditions: new duplicate-email failures, unexpected MongoDB
  duplicate-key errors, authentication ambiguity regressions, or elevated
  registration/OAuth/password-reset/2FA error rates after deployment
- Rollback command or deployment reference: redeploy the previous release; do
  not remove `normalizedEmail` data or the unique partial index without a
  separate reviewed rollback migration
- Post-rollback verification result: automated verification remains the required
  `make ci` pass for this PR; production post-rollback verification must be
  recorded by the release operator if rollback is exercised
