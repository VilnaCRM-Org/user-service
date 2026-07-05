# CI/CD Verification Coverage Audit — July 2026

A four-stage audit (inventory → gap analysis → git-history evidence mining → adversarial critique loop, converged after 2 iterations) of every automated verification check in this repository, scored against a 10-category taxonomy for **coverage** and **enforcement strength** (0–5 each; a non-blocking or never-failing check caps a category at 2).

**Outcome: 20 confirmed gaps, one GitHub issue each — [#442](https://github.com/VilnaCRM-Org/user-service/issues/442)–[#461](https://github.com/VilnaCRM-Org/user-service/issues/461)**, labeled `ci`, `quality`, `<category>`.

## OpenSSF Scorecard baseline (v5.2.1, local mode)

| Check | Score |
| --- | --- |
| Pinned-Dependencies | **0/10** — zero SHA-pinned actions across all 21 workflows |
| Token-Permissions | **0/10** — 17 workflows without a `permissions:` block |
| SAST | **0/10** — Psalm taint exists but SARIF upload dead (codeql-action@v1) |
| Fuzzing | 0/10 (Schemathesis present but not OSS-Fuzz-shaped) |
| Vulnerabilities | **7/10** — 3 live advisories on main: GHSA-cwxw-98qj-8qjx, GHSA-vm85-hxw5-5432, GHSA-wpwq-4j6v-78m3 |
| Dangerous-Workflow / Binary-Artifacts / Security-Policy / Dependency-Update-Tool | 10/10 |
| License | 9/10 |

## Category scores (before → projected after all 20 issues)

| Category | Coverage/Enforcement before | After | Honest residual |
| --- | --- | --- | --- |
| 1 Static analysis & linting | 4/3 | 5/5 | Psalm ratchet continues 4→1 over time by design |
| 2 Unit/integration/e2e tests | 4/**2** | 5/5 | — |
| 3 Test effectiveness | 4/4 | 5/4 | Integration-scope mutation + deep fuzz are nightly non-blocking (deliberate runtime tradeoff) |
| 4 Security | 3/3 | 4/5 | No dedicated DAST or race-condition harness (judged over-engineering vs. Behat security suite + Schemathesis + Semgrep + gitleaks + Trivy + SCA) |
| 5 Supply chain | **1/1** | 4/4 | No artifact signing/SLSA L3 (service publishes no packages) |
| 6 API & contract stability | 5/3 | 5/5 | GraphQL lint of generated schema deliberately excluded |
| 7 Architecture conformance | 5/4 | 5/5 | — |
| 8 Performance | 4/4 | 5/4 | Nightly profiles trend-first until budgets are trustworthy |
| 9 Docs & release hygiene | 3/**1** | 4/4 | Link checker can't catch backtick path references; no semantic docs-code sync tool exists |
| 10 CI health | **1/1** | 5/4 | Canary/flake/drift are monitors, not gates (correct shape); root-cause gates are blocking |

## Proven escapes (git-history evidence)

1. **The required "Behat" check has never run** — `E2Etests.yml` calls `make e2e-tests` (nonexistent); the Makefile catch-all `.PHONY` makes it exit 0. A real IDOR (issue #320, fixed in PR #333) shipped, and its regression test lives in the Behat suite the green check pretends to run. → #442, #443
2. **Main CI was red and flaky while gating 13 security-fix PRs** (#325–#337) — documented in PR #338. → #461
3. **Major Symfony 7.4→8.1 bumps merged minutes after PR #308 disabled full CI for Dependabot PRs** — dependency PRs are validated by nothing. → #445
4. **Committed `.env` files + a `private.pem` fixture** would trip any secrets scanner; none exists. → #454
5. **A fail-open CORS default reached main** (issue #319/PR #330) — the config-lockdown script that could gate such defaults runs only locally. → #451

## The 20 issues

| # | Title (abbreviated) | Category | Effort |
| --- | --- | --- | --- |
| #442 | Behat E2E required check is a no-op | tests | S |
| #443 | Makefile catch-all .PHONY silent-success hazard | ci-health | S |
| #444 | 100% coverage gate CI enforcement + dedup double suite run | tests | S |
| #445 | Dependabot PRs bypass ~12 required workflows | tests | M |
| #446 | CI must validate committed code strictly (cs-fixer dry-run, phpinsights without --fix, composer --strict) | static-analysis | S |
| #447 | Super-Linter continue-on-error → blocking validate job | static-analysis | S |
| #448 | Psalm errorLevel 8 → phased ratchet to 4 with grow-only baseline | static-analysis | M |
| #449 | Mutation testing blind to Integration suite + PHPUnit order randomization | test-effectiveness | M |
| #450 | Workflow hardening: permissions, dead SARIF, concurrency, actionlint+zizmor | security | M |
| #451 | Config-lockdown (validate-configuration.sh) never runs in CI | architecture | S |
| #452 | SHA-pin all actions/images/clones + github-actions/docker Dependabot | supply-chain | M |
| #453 | SCA gates: dependency-review-action + scheduled composer audit | security | S |
| #454 | Secrets scanning (gitleaks) | security | S |
| #455 | Semgrep PHP security rules | security | S–M |
| #456 | Contract gates cannot fail (openapi-diff, GraphQL `master:` baseline, spec auto-commit) | api-contract | S |
| #457 | Dead-link gate (lychee offline/weekly split) | docs-release | S |
| #458 | Release pipeline: archived create-release@v1, semver gate, SBOM+provenance | docs-release | M |
| #459 | Trivy scan of the CI-built frankenphp_prod image | security | S |
| #460 | Nightly average-load + deep fuzz + dispatchable stress/spike; cache-perf filter | performance | M |
| #461 | Nightly main canary + flake detection + required-checks drift monitor | ci-health | M |

**Sequencing:** #443 with #442 (same root cause) · #445 and #450 before #452 (else pin-bump PRs merge untested; zizmor enforces the pins) · #451 should absorb the threshold files created by #444/#449.

**Killed during the critique loop** (for the record): an OpenSSF Scorecard *action* (dashboard, not a gate — zizmor+SCA enforce its actionable checks), captainhook git hooks (client-side, adds no CI verification), a race-condition CI harness (test-authoring follow-up to #324, not a missing check), GraphQL schema linting (generated output, non-actionable), ODM index-sync and .env-parity checks (already exercised transitively by existing CI jobs).
