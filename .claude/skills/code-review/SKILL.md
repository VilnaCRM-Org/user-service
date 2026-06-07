---
name: code-review
description: Systematically retrieve and address PR code review comments using make pr-comments. Use when handling code review feedback or addressing PR comments.
---

# Code Review Workflow Skill

## Context (Input)

- PR has unresolved code review comments
- Need systematic approach to address feedback
- Ready to implement reviewer suggestions
- Need to maintain quality standards during review implementation

## Task (Function)

Systematically retrieve, categorize, and address all PR code review comments while maintaining quality standards and PR readiness.

**Success Criteria**:

- Direct GitHub GraphQL review-thread query shows 0 unresolved review comments, and `PR_COMMENT_EVIDENCE` records `SNAPSHOT_STARTED_AT=<auto-captured ISO time>`, `SNAPSHOT_CAPTURED_BY=code-review-skill`, `PR_HEAD=<sha>`, plus every review-thread, top-level PR issue, and review body comment from that snapshot as `COMMENT_META|url|updatedAt|body_sha256` and `COMMENT|url|commit|sha`, `COMMENT|url|reply|url`, or `COMMENT|url|decline|url`; reply/decline evidence comments must be posted by the PR author or a login in `PR_COMMENT_TRUSTED_EVIDENCE_ACTORS` and include structured `EVIDENCE_SOURCE`, `EVIDENCE_ACTION`, and decline `EVIDENCE_REASON`; non-evidence comments created or edited after the snapshot block completion until the snapshot and evidence are restarted, except an otherwise qualifying approval review on pushed `HEAD` whose body is empty or exactly `FINAL_APPROVAL_NO_ACTION: true`
- `make ci` shows "✅ CI checks successfully passed!"
- Final `make ai-review-loop` runs after `make ci` and reports PASS on the same commit; if it applies fixes, repeat `make ci` and `make ai-review-loop` until both pass without new changes
- Local `HEAD` is pushed and matches `gh pr view <number> --repo "$PR_REPO" --json headRefOid`
- Commit-scoped status/check rollup for local `HEAD` is queried from the base PR repository, is non-empty, non-required contexts have only allowed terminal states (`SUCCESS`, `SKIPPED`, or `NEUTRAL`), every trusted check name/type/source in the trusted base `.github/required-pr-checks.txt` or externally validated bootstrap manifest and every live base-branch protection required status/check is present with state `SUCCESS` on that pushed head, source-less branch-protection contexts resolve through trusted manifest type/source mappings, and GitHub Actions check URLs point at the base PR repository
- Final direct GitHub GraphQL review-thread query after the pushed-head verification still shows 0 unresolved review comments
- `gh pr view <number> --repo "$PR_REPO" --json state,mergeStateStatus,mergeable,reviewDecision,isDraft,reviewRequests` shows the PR is open, not draft, not conflicting, `reviewDecision` is `APPROVED`, `reviewRequests` is empty, and a direct review query shows an `APPROVED` review on the pushed `HEAD` submitted after the latest addressed comment evidence by a non-author reviewer with `OWNER`, `MEMBER`, or `COLLABORATOR` association

## Workflow Overview

```mermaid
AI Review Loop → PR Comments → Categorize → Apply by Priority → Verify → Run CI → Final AI Review Loop → Push → GitHub Readiness → Done
```

## Quick Start

```bash
set -euo pipefail
command -v jq >/dev/null || { echo "jq is required" >&2; exit 1; }
command -v rg >/dev/null || { echo "rg is required" >&2; exit 1; }
command -v unzip >/dev/null || { echo "unzip is required" >&2; exit 1; }
command -v sha256sum >/dev/null || { echo "sha256sum is required" >&2; exit 1; }
command -v base64 >/dev/null || { echo "base64 is required" >&2; exit 1; }

GATE_DEFINITION_CORE_FILE_PATTERN='^(AGENTS\.md|Makefile|\.claude/skills/.*|\.agents/skills/.*|deptrac\.yaml|composer\.(json|lock)|phpunit.*\.xml\.dist|psalm\.xml(\.dist)?|phpstan\.(neon|neon\.dist)|phpmd.*\.xml(\.dist)?|phpinsights.*\.php|infection\.(json|json5)(\.dist)?|phpcs\.xml(\.dist)?|\.php-cs-fixer\.dist\.php|docker-compose.*\.ya?ml|Dockerfile.*|scripts/.*|\.github/workflows/.*|\.github/actions/.*|\.github/linters/.*|\.github/required-pr-checks\.txt|\.prettierrc(\..*)?|\.prettierignore|(.*/)?prettier\.config\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?\.markdownlint(\..*)?|(.*/)?markdownlint\.config\.(js|mjs|cjs|json|ya?ml)|(.*/)?\.yamllint(\..*)?|(\.codecov|codecov)\.ya?ml|\.coderabbit\.ya?ml|\.qlty/.*|(\.qlty|qlty)\.toml|\.snyk|(\.spectral|spectral)\.ya?ml|(.*/)?spectral\.config\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?eslint\.config\.(js|mjs|cjs|ts)|(.*/)?\.eslintrc(\..*)?|(.*/)?package(-lock)?\.json|(.*/)?pnpm-lock\.yaml|(.*/)?yarn\.lock|(.*/)?\.graphqlrc(\..*)?|(.*/)?graphql\.config\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?graphql-inspector(\.config)?\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?(\.openapi-diff|openapi-diff)(\.config)?\.(js|mjs|cjs|ts|json|ya?ml))$'
GATE_DEFINITION_VALIDATION_SUPPORT_FILE_PATTERN='^(docs/onboarding\.md|tests/CLI/bats/make_ai_review_loop_tests\.bats|specs/autonomous/[0-9]{8}-[0-9]{6}-(ai-review-loop|bmad-fr-nfr-review-gate|bmad-fr-nfr-reviewer-system-design-patterns|ci-gate|gate-definition|required-pr-checks-gate|review-loop)/(architecture|epics|implementation-readiness|manual-evidence|prd|product-brief-distillate|product-brief|research|run-summary|skill-sweep)\.md)$'
GATE_DEFINITION_FILE_PATTERN="(${GATE_DEFINITION_CORE_FILE_PATTERN}|${GATE_DEFINITION_VALIDATION_SUPPORT_FILE_PATTERN})"
GATE_DEFINITION_CHANGES_PRESENT=false
GATE_DEFINITION_EXTERNAL_VALIDATION_PASSED=false
PR_COMMENT_TIMELINE=''
VALIDATED_COMMENT_EVIDENCE_URLS=''

capture_review_comment_snapshot() {
  REVIEW_COMMENT_SNAPSHOT_STARTED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  export REVIEW_COMMENT_SNAPSHOT_STARTED_AT
  PR_COMMENT_TIMELINE=''
}

assert_review_comment_snapshot_valid() {
  : "${REVIEW_COMMENT_SNAPSHOT_STARTED_AT:?Capture REVIEW_COMMENT_SNAPSHOT_STARTED_AT with capture_review_comment_snapshot before addressing PR comments}"
  printf '%s\n' "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" | rg -q '^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$'
  : "${PR_COMMENT_EVIDENCE:?Set PR_COMMENT_EVIDENCE to the review-comment evidence ledger}"
  test -r "$PR_COMMENT_EVIDENCE"
  rg -q "^SNAPSHOT_STARTED_AT=$REVIEW_COMMENT_SNAPSHOT_STARTED_AT$" "$PR_COMMENT_EVIDENCE"
  rg -q "^SNAPSHOT_CAPTURED_BY=code-review-skill$" "$PR_COMMENT_EVIDENCE"
}

iso_time_epoch() {
  date -u -d "$1" +%s
}

body_hash_from_b64() {
  printf '%s' "$1" | base64 --decode | sha256sum | awk '{ print $1 }'
}

review_thread_comment_items() {
  local owner="$1" repo="$2" comparison="$3" query thread_query page_json page_items has_next cursor thread_refs thread_id thread_cursor thread_has_next thread_json
  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviewThreads(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{id comments(first:100){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" --arg comparison "$comparison" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.reviewThreads.nodes[].comments.nodes[] | select((if $comparison == "before" then effectiveAt <= $snapshot elif $comparison == "after" then effectiveAt > $snapshot else true end) and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    printf '%s\n' "$page_items"
    thread_refs="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.nodes[] | select(.comments.pageInfo.hasNextPage == true) | "\(.id)|\(.comments.pageInfo.endCursor)"')"
    while IFS='|' read -r thread_id thread_cursor; do
      [ -n "$thread_id" ] || continue
      thread_query='query($threadId:ID!,$cursor:String){node(id:$threadId){... on PullRequestReviewThread{comments(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
      while :; do
        thread_json="$(gh api graphql -f threadId="$thread_id" -f cursor="$thread_cursor" -f query="$thread_query")"
        page_items="$(printf '%s\n' "$thread_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" --arg comparison "$comparison" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.node.comments.nodes[] | select((if $comparison == "before" then effectiveAt <= $snapshot elif $comparison == "after" then effectiveAt > $snapshot else true end) and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
        printf '%s\n' "$page_items"
        thread_has_next="$(printf '%s\n' "$thread_json" | jq -r '.data.node.comments.pageInfo.hasNextPage')"
        [ "$thread_has_next" = "true" ] || break
        thread_cursor="$(printf '%s\n' "$thread_json" | jq -r '.data.node.comments.pageInfo.endCursor')"
        test -n "$thread_cursor" && test "$thread_cursor" != "null"
      done
    done <<< "$thread_refs"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
}

load_pr_comment_timeline() {
  local owner repo query page_json page_items has_next cursor
  assert_review_comment_snapshot_valid
  [ -n "$PR_COMMENT_TIMELINE" ] && return 0
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  PR_COMMENT_TIMELINE=''

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){comments(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.nodes[] | select((.bodyText // "") | length > 0) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    PR_COMMENT_TIMELINE="${PR_COMMENT_TIMELINE}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.nodes[] | select((.bodyText // "") | length > 0) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    PR_COMMENT_TIMELINE="${PR_COMMENT_TIMELINE}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  PR_COMMENT_TIMELINE="${PR_COMMENT_TIMELINE}"$'\n'"$(review_thread_comment_items "$owner" "$repo" all)"
  PR_COMMENT_TIMELINE="$(printf '%s\n' "$PR_COMMENT_TIMELINE" | sed '/^[[:space:]]*$/d' | sort -u)"
}

comment_time_for_url() {
  load_pr_comment_timeline
  printf '%s\n' "$PR_COMMENT_TIMELINE" | awk -F'|' -v url="$1" '$1 == url { print ($3 > $2 ? $3 : $2); exit }'
}

assert_pr_commit_evidence() {
  local source_url="$1" source_time="$2" commit="$3" commit_time
  git rev-parse --verify "${commit}^{commit}" >/dev/null
  git merge-base --is-ancestor "$commit" "$LOCAL_HEAD"
  ! git merge-base --is-ancestor "$commit" "$TRUSTED_BASE_REF"
  commit_time="$(git show -s --format=%cI "$commit")"
  test "$(iso_time_epoch "$commit_time")" -gt "$(iso_time_epoch "$source_time")"
  git log -1 --format=%B "$commit" | rg -Fq "$source_url"
}

comment_json_for_url() {
  local url="$1" comment_id
  case "$url" in
    *"#issuecomment-"*)
      comment_id="${url##*#issuecomment-}"
      gh api "repos/$PR_REPO/issues/comments/$comment_id"
      ;;
    *"#discussion_r"*)
      comment_id="${url##*#discussion_r}"
      gh api "repos/$PR_REPO/pulls/comments/$comment_id"
      ;;
    *)
      return 1
      ;;
  esac
}

comment_body_for_url() {
  comment_json_for_url "$1" | jq -r .body
}

comment_author_for_url() {
  comment_json_for_url "$1" | jq -r .user.login
}

assert_evidence_comment_actor() {
  local reply_url="$1" pr_author reply_author trusted_actors
  pr_author="$(gh pr view "$PR" --repo "$PR_REPO" --json author --jq .author.login)"
  reply_author="$(comment_author_for_url "$reply_url")"
  if [ "$reply_author" = "$pr_author" ]; then
    return 0
  fi
  : "${PR_COMMENT_TRUSTED_EVIDENCE_ACTORS:?Set comma-separated trusted bot/user logins allowed to post review evidence comments}"
  trusted_actors=",$PR_COMMENT_TRUSTED_EVIDENCE_ACTORS,"
  printf '%s\n' "$trusted_actors" | rg -Fq ",$reply_author,"
}

assert_pr_reply_evidence() {
  local source_url="$1" source_time="$2" reply_url="$3" reply_time reply_body
  case "$reply_url" in
    "https://github.com/${PR_REPO}/pull/${PR}"#*) ;;
    *) return 1 ;;
  esac
  [ "$reply_url" != "$source_url" ]
  reply_time="$(comment_time_for_url "$reply_url")"
  test -n "$source_time" && test -n "$reply_time"
  test "$(iso_time_epoch "$reply_time")" -gt "$(iso_time_epoch "$source_time")"
  assert_evidence_comment_actor "$reply_url"
  reply_body="$(comment_body_for_url "$reply_url")"
  printf '%s\n' "$reply_body" | rg -Fq "EVIDENCE_SOURCE: $source_url"
  printf '%s\n' "$reply_body" | rg -iq '^EVIDENCE_ACTION:[[:space:]]*(addressed|resolved|fixed|implemented|updated|changed|applied)[[:space:]]*$'
}

assert_pr_decline_evidence() {
  local source_url="$1" source_time="$2" reply_url="$3" reply_time reply_body
  case "$reply_url" in
    "https://github.com/${PR_REPO}/pull/${PR}"#*) ;;
    *) return 1 ;;
  esac
  [ "$reply_url" != "$source_url" ]
  reply_time="$(comment_time_for_url "$reply_url")"
  test -n "$source_time" && test -n "$reply_time"
  test "$(iso_time_epoch "$reply_time")" -gt "$(iso_time_epoch "$source_time")"
  assert_evidence_comment_actor "$reply_url"
  reply_body="$(comment_body_for_url "$reply_url")"
  printf '%s\n' "$reply_body" | rg -Fq "EVIDENCE_SOURCE: $source_url"
  printf '%s\n' "$reply_body" | rg -iq '^EVIDENCE_ACTION:[[:space:]]*(declined|stale|duplicate|not applicable|not needed|won.t fix|will not fix)[[:space:]]*$'
  printf '%s\n' "$reply_body" | rg -iq '^EVIDENCE_REASON:[[:space:]].+'
}

latest_addressed_evidence_time() {
  local entry_type source_url entry_action entry_evidence source_time evidence_time source_epoch evidence_epoch latest
  latest=0
  [ -r "${PR_COMMENT_EVIDENCE:-}" ] || return 0
  while IFS='|' read -r entry_type source_url entry_action entry_evidence; do
    [ "$entry_type" = "COMMENT" ] || continue
    source_time="$(comment_time_for_url "$source_url")"
    if [ -n "$source_time" ]; then
      source_epoch="$(iso_time_epoch "$source_time")"
      if [ "$source_epoch" -gt "$latest" ]; then
        latest="$source_epoch"
      fi
    fi
    evidence_time=''
    case "$entry_action" in
      commit) evidence_time="$(git show -s --format=%cI "$entry_evidence" 2>/dev/null || true)" ;;
      reply|decline) evidence_time="$(comment_time_for_url "$entry_evidence")" ;;
    esac
    if [ -n "$evidence_time" ]; then
      evidence_epoch="$(iso_time_epoch "$evidence_time")"
      if [ "$evidence_epoch" -gt "$latest" ]; then
        latest="$evidence_epoch"
      fi
    fi
  done < "$PR_COMMENT_EVIDENCE"
  printf '%s\n' "$latest"
}

assert_comment_evidence_items() {
  local evidence_items="$1" source_url source_time source_updated_at source_effective_time source_body_b64 source_body_hash meta_line meta_type meta_url meta_updated_at meta_body_hash evidence_line entry_type entry_url entry_action entry_evidence missing_count
  assert_review_comment_snapshot_valid
  : "${PR_COMMENT_EVIDENCE:?Set PR_COMMENT_EVIDENCE to a ledger that records PR_HEAD and COMMENT|url|action|evidence lines}"
  test -r "$PR_COMMENT_EVIDENCE"
  rg -Fqx "PR_HEAD=$LOCAL_HEAD" "$PR_COMMENT_EVIDENCE"
  evidence_items="$(printf '%s\n' "$evidence_items" | sed '/^[[:space:]]*$/d' | sort -u)"
  [ -n "$evidence_items" ] || return 0
  missing_count=0
  while IFS='|' read -r source_url source_time source_updated_at source_body_b64; do
    source_effective_time="$source_time"
    if [ -n "$source_updated_at" ] && [ "$source_updated_at" \> "$source_effective_time" ]; then
      source_effective_time="$source_updated_at"
    fi
    source_body_hash="$(body_hash_from_b64 "$source_body_b64")"
    meta_line="$(awk -F'|' -v url="$source_url" '$1 == "COMMENT_META" && $2 == url { print; found = 1; exit } END { if (!found) exit 1 }' "$PR_COMMENT_EVIDENCE" || true)"
    if [ -z "$meta_line" ]; then
      printf 'Missing comment metadata evidence: %s\n' "$source_url" >&2
      missing_count=$((missing_count + 1))
      continue
    fi
    IFS='|' read -r meta_type meta_url meta_updated_at meta_body_hash <<EOF
$meta_line
EOF
    if [ "$meta_type" != "COMMENT_META" ] || [ "$meta_url" != "$source_url" ] || [ "$meta_updated_at" != "$source_updated_at" ] || [ "$meta_body_hash" != "$source_body_hash" ]; then
      printf 'Invalid comment metadata evidence: %s\n' "$source_url" >&2
      missing_count=$((missing_count + 1))
      continue
    fi
    evidence_line="$(awk -F'|' -v url="$source_url" '$1 == "COMMENT" && $2 == url { print; found = 1; exit } END { if (!found) exit 1 }' "$PR_COMMENT_EVIDENCE" || true)"
    if [ -z "$evidence_line" ]; then
      printf 'Missing comment evidence: %s\n' "$source_url" >&2
      missing_count=$((missing_count + 1))
      continue
    fi
    IFS='|' read -r entry_type entry_url entry_action entry_evidence <<EOF
$evidence_line
EOF
    if [ "$entry_type" != "COMMENT" ] || [ "$entry_url" != "$source_url" ] || [ -z "$entry_evidence" ]; then
      printf 'Invalid comment evidence: %s\n' "$source_url" >&2
      missing_count=$((missing_count + 1))
      continue
    fi
    case "$entry_action" in
      commit) assert_pr_commit_evidence "$source_url" "$source_effective_time" "$entry_evidence" || missing_count=$((missing_count + 1)) ;;
      reply)
        if assert_pr_reply_evidence "$source_url" "$source_effective_time" "$entry_evidence"; then
          VALIDATED_COMMENT_EVIDENCE_URLS="${VALIDATED_COMMENT_EVIDENCE_URLS}"$'\n'"${entry_evidence}"
        else
          missing_count=$((missing_count + 1))
        fi
        ;;
      decline)
        if assert_pr_decline_evidence "$source_url" "$source_effective_time" "$entry_evidence"; then
          VALIDATED_COMMENT_EVIDENCE_URLS="${VALIDATED_COMMENT_EVIDENCE_URLS}"$'\n'"${entry_evidence}"
        else
          missing_count=$((missing_count + 1))
        fi
        ;;
      *) missing_count=$((missing_count + 1)) ;;
    esac
  done <<< "$evidence_items"
  test "$missing_count" -eq 0
}

assert_no_unresolved_comments() {
  local owner repo query page_json page_count total_count has_next cursor
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviewThreads(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{isResolved comments(first:1){nodes{id}}}}}}}'
  total_count=0
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_count="$(printf '%s\n' "$page_json" | jq '[.data.repository.pullRequest.reviewThreads.nodes[] | select(.isResolved == false and (.comments.nodes | length > 0))] | length')"
    total_count=$((total_count + page_count))
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
  test "$total_count" -eq 0
}

non_thread_comment_items() {
  local owner="$1" repo="$2" comparison="$3" query page_json page_items has_next cursor
  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){comments(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" --arg comparison "$comparison" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.comments.nodes[] | select((if $comparison == "before" then effectiveAt <= $snapshot elif $comparison == "after" then effectiveAt > $snapshot else true end) and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    printf '%s\n' "$page_items"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" --arg comparison "$comparison" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.reviews.nodes[] | select((if $comparison == "before" then effectiveAt <= $snapshot elif $comparison == "after" then effectiveAt > $snapshot else true end) and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    printf '%s\n' "$page_items"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
}

write_comment_metadata_evidence() {
  local owner repo metadata_items existing_comment_lines source_url source_time source_updated_at source_body_b64 source_body_hash tmp_file
  assert_review_comment_snapshot_valid
  : "${LOCAL_HEAD:?Set LOCAL_HEAD to the final local head before writing PR comment evidence metadata}"
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  metadata_items="$(
    {
      non_thread_comment_items "$owner" "$repo" before
      review_thread_comment_items "$owner" "$repo" before
    } | sed '/^[[:space:]]*$/d' | sort -u
  )"
  existing_comment_lines="$(awk -F'|' '$1 == "COMMENT" { print }' "$PR_COMMENT_EVIDENCE" 2>/dev/null || true)"
  tmp_file="$(mktemp)"
  {
    printf 'SNAPSHOT_STARTED_AT=%s\n' "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT"
    printf 'SNAPSHOT_CAPTURED_BY=code-review-skill\n'
    printf 'PR_HEAD=%s\n' "$LOCAL_HEAD"
    while IFS='|' read -r source_url source_time source_updated_at source_body_b64; do
      [ -n "$source_url" ] || continue
      source_body_hash="$(body_hash_from_b64 "$source_body_b64")"
      printf 'COMMENT_META|%s|%s|%s\n' "$source_url" "$source_updated_at" "$source_body_hash"
    done <<< "$metadata_items"
    printf '%s\n' "$existing_comment_lines"
  } > "$tmp_file"
  mv "$tmp_file" "$PR_COMMENT_EVIDENCE"
}

assert_non_thread_comment_evidence() {
  local owner repo query page_json page_items evidence_items has_next cursor
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  evidence_items=''

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){comments(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.comments.nodes[] | select(effectiveAt <= $snapshot and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    evidence_items="${evidence_items}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.reviews.nodes[] | select(effectiveAt <= $snapshot and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    evidence_items="${evidence_items}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  assert_comment_evidence_items "$evidence_items"
}

assert_review_thread_comment_evidence() {
  local owner repo evidence_items
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  evidence_items="$(review_thread_comment_items "$owner" "$repo" before)"
  assert_comment_evidence_items "$evidence_items"
}

qualifying_approval_review_urls() {
  local owner repo query page_json page_urls has_next cursor min_submitted_epoch pr_author
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  min_submitted_epoch="$(latest_addressed_evidence_time)"
  pr_author="$(gh pr view "$PR" --repo "$PR_REPO" --json author --jq .author.login)"
  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url state submittedAt author{login} authorAssociation commit{oid}}}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_urls="$(printf '%s\n' "$page_json" | jq -r --arg head "$LOCAL_HEAD" --arg pr_author "$pr_author" --argjson min_submitted_epoch "$min_submitted_epoch" '.data.repository.pullRequest.reviews.nodes[] | select(.state == "APPROVED" and .commit.oid == $head and (.author.login // "") != $pr_author and (.authorAssociation | IN("OWNER", "MEMBER", "COLLABORATOR")) and ($min_submitted_epoch == 0 or (.submittedAt | fromdateiso8601) > $min_submitted_epoch)) | .url')"
    printf '%s\n' "$page_urls"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
}

non_action_approval_review_urls() {
  local owner repo query page_json page_urls has_next cursor min_submitted_epoch pr_author
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  min_submitted_epoch="$(latest_addressed_evidence_time)"
  pr_author="$(gh pr view "$PR" --repo "$PR_REPO" --json author --jq .author.login)"
  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url state submittedAt author{login} authorAssociation commit{oid} bodyText}}}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_urls="$(printf '%s\n' "$page_json" | jq -r --arg head "$LOCAL_HEAD" --arg pr_author "$pr_author" --argjson min_submitted_epoch "$min_submitted_epoch" '.data.repository.pullRequest.reviews.nodes[] | select(.state == "APPROVED" and .commit.oid == $head and (.author.login // "") != $pr_author and (.authorAssociation | IN("OWNER", "MEMBER", "COLLABORATOR")) and ($min_submitted_epoch == 0 or (.submittedAt | fromdateiso8601) > $min_submitted_epoch) and (((.bodyText // "") | length) == 0 or ((.bodyText // "") | gsub("\\r"; "") | gsub("^[[:space:]]+|[[:space:]]+$"; "") | test("^FINAL_APPROVAL_NO_ACTION:[[:space:]]*true$"; "i")))) | .url')"
    printf '%s\n' "$page_urls"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
}

assert_no_unaccounted_comments_after_snapshot() {
  local owner repo query page_json page_items after_items has_next cursor allowed_evidence_urls source_url source_time source_updated_at source_body_b64 missing_count
  assert_review_comment_snapshot_valid
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  after_items=''

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){comments(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.comments.nodes[] | select(effectiveAt > $snapshot and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    after_items="${after_items}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.reviews.nodes[] | select(effectiveAt > $snapshot and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    after_items="${after_items}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  after_items="${after_items}"$'\n'"$(review_thread_comment_items "$owner" "$repo" after)"
  allowed_evidence_urls="$(
    {
      printf '%s\n' "$VALIDATED_COMMENT_EVIDENCE_URLS"
      non_action_approval_review_urls
    } | sed '/^[[:space:]]*$/d' | sort -u
  )"
  missing_count=0
  while IFS='|' read -r source_url source_time source_updated_at source_body_b64; do
    [ -n "$source_url" ] || continue
    if ! printf '%s\n' "$allowed_evidence_urls" | rg -Fxq "$source_url"; then
      printf 'New PR comment after snapshot requires restarting comment evidence: %s\n' "$source_url" >&2
      missing_count=$((missing_count + 1))
    fi
  done <<< "$(printf '%s\n' "$after_items" | sed '/^[[:space:]]*$/d' | sort -u)"
  test "$missing_count" -eq 0
}

assert_no_forbidden_suppressions() {
  local diff_output forbidden_pattern quality_config_changes
  diff_output="$(git diff --unified=0 "$TRUSTED_BASE_REF"...HEAD)" || {
    echo "Unable to compute PR diff for suppression scan" >&2
    exit 1
  }

  forbidden_pattern="$(forbidden_suppression_pattern)"
  if printf '%s\n' "$diff_output" | rg '^\+[^+]' | rg -n "$forbidden_pattern"; then
    echo "Forbidden suppression/ignore directive found in PR diff" >&2
    exit 1
  fi

  quality_config_changes="$(git diff --name-only "$TRUSTED_BASE_REF"...HEAD | rg '(^|/)(.*baseline.*|psalm\.xml(\.dist)?|phpstan\.(neon|neon\.dist)|phpmd.*\.xml(\.dist)?|phpinsights.*\.php|infection\.(json|json5)(\.dist)?|phpcs\.xml(\.dist)?|\.php-cs-fixer\.dist\.php)$' || true)"
  if [ -n "$quality_config_changes" ] && [ "$GATE_DEFINITION_CHANGES_PRESENT" != "true" ]; then
    printf '%s\n' "$quality_config_changes"
    echo "Quality tool suppression/baseline/config changes block completion; remove suppression/ignore changes instead" >&2
    exit 1
  fi
}

forbidden_suppression_pattern() {
  printf '%s\n' '@Suppress''Warnings|@psalm-''suppress|@phpstan-''ignore|phpstan-''ignore|phpcs:(''ignore|disable)|@infection-''ignore|@codeCoverage''Ignore|@phpinsights-''ignore|@codingStandards''Ignore|codingStandards''Ignore'
}

trusted_base_required_checks_manifest() {
  git show "$TRUSTED_BASE_REF:.github/required-pr-checks.txt" 2>/dev/null
}

gate_definition_bootstrap_required() {
  [ "$GATE_DEFINITION_CHANGES_PRESENT" = "true" ] &&
    ! git cat-file -e "$TRUSTED_BASE_REF:.github/required-pr-checks.txt" 2>/dev/null &&
    test -r .github/required-pr-checks.txt
}

required_checks_manifest() {
  local manifest
  manifest="$(trusted_base_required_checks_manifest || true)"
  if [ -n "$manifest" ]; then
    printf '%s\n' "$manifest"
    return 0
  fi

  [ "$GATE_DEFINITION_CHANGES_PRESENT" = "true" ] || assert_gate_definition_change_scope
  if gate_definition_bootstrap_required; then
    assert_gate_definition_external_validation
    cat .github/required-pr-checks.txt
    return 0
  fi

  return 1
}

assert_gate_definition_change_scope() {
  local all_changes gate_definition_changes non_gate_definition_changes
  GATE_DEFINITION_CHANGES_PRESENT=false
  all_changes="$(git diff --name-only "$TRUSTED_BASE_REF"...HEAD)"
  gate_definition_changes="$(printf '%s\n' "$all_changes" | rg "$GATE_DEFINITION_FILE_PATTERN" || true)"
  if [ -n "$gate_definition_changes" ]; then
    GATE_DEFINITION_CHANGES_PRESENT=true
    non_gate_definition_changes="$(printf '%s\n' "$all_changes" | rg -v "$GATE_DEFINITION_FILE_PATTERN" || true)"
    printf '%s\n' "$gate_definition_changes"
    if [ -n "$non_gate_definition_changes" ]; then
      printf '%s\n' "$non_gate_definition_changes"
      if gate_definition_bootstrap_required; then
        echo "Initial CI/review gate bootstrap includes non-gate changes; trusted external validation is required before completion" >&2
        return 0
      fi
      echo "CI/review gate definition changes must be isolated in a dedicated gate-definition PR with no product, runtime, or unrelated test-code changes" >&2
      exit 1
    fi
  fi
}

assert_gate_definition_external_validation() {
  local trusted_base_oid validation_mode run_url run_repo run_id run_json run_head_sha workflow_path workflow_sha actual_workflow_sha artifacts_json artifact_id artifact_dir artifact_zip artifact_payload_dir artifact_evidence_file artifact_evidence_file_count trusted_validator_repos trusted_validator_heads trusted_workflows trusted_workflow_shas
  [ "$GATE_DEFINITION_CHANGES_PRESENT" = "true" ] || return 0
  [ "$GATE_DEFINITION_EXTERNAL_VALIDATION_PASSED" = "true" ] && return 0
  trusted_base_oid="${GATED_BASE_OID:-$BASE_OID}"
  : "${GATE_DEFINITION_VALIDATION_EVIDENCE:?Set GATE_DEFINITION_VALIDATION_EVIDENCE to an external trusted-base validation ledger for dedicated gate-definition PRs}"
  test -r "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  case "$(cd "$(dirname "$GATE_DEFINITION_VALIDATION_EVIDENCE")" && pwd)/$(basename "$GATE_DEFINITION_VALIDATION_EVIDENCE")" in
    "$(pwd)"/*)
      echo "Gate-definition validation evidence must be outside the PR worktree" >&2
      exit 1
      ;;
  esac
  rg -q "^PR_HEAD=$LOCAL_HEAD$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  rg -q "^TRUSTED_BASE=$trusted_base_oid$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  rg -q "^VALIDATION_MODE=(trusted-base|immutable-external)$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  rg -q "^TRUSTED_BASE_GATE=PASS$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  rg -q "^TRUSTED_MANIFEST_CHECKS=PASS$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  rg -q "^IMMUTABLE_RUN_URL=https://github\\.com/.+/actions/runs/[0-9]+$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  validation_mode="$(sed -n 's/^VALIDATION_MODE=//p' "$GATE_DEFINITION_VALIDATION_EVIDENCE" | tail -n 1)"
  run_url="$(sed -n 's/^IMMUTABLE_RUN_URL=//p' "$GATE_DEFINITION_VALIDATION_EVIDENCE" | tail -n 1)"
  run_repo="$(printf '%s\n' "$run_url" | sed -E 's#^https://github.com/([^/]+/[^/]+)/actions/runs/[0-9]+$#\1#')"
  test -n "$run_repo" && test "$run_repo" != "$run_url"
  case "$validation_mode" in
    trusted-base)
      test "$run_repo" = "$PR_REPO"
      ;;
    immutable-external)
      : "${GATE_DEFINITION_TRUSTED_VALIDATOR_REPOS:?Set comma-separated trusted validator repositories for immutable-external gate validation}"
      trusted_validator_repos=",$GATE_DEFINITION_TRUSTED_VALIDATOR_REPOS,"
      printf '%s\n' "$trusted_validator_repos" | rg -Fq ",$run_repo,"
      ;;
    *)
      return 1
      ;;
  esac
  run_id="${run_url##*/}"
  run_json="$(gh api "repos/$run_repo/actions/runs/$run_id")"
  printf '%s\n' "$run_json" | jq -e --arg run_repo "$run_repo" '.status == "completed" and .conclusion == "success" and .repository.full_name == $run_repo and ((.path // "") | test("^\\.github/workflows/"))'
  run_head_sha="$(printf '%s\n' "$run_json" | jq -r .head_sha)"
  workflow_path="$(printf '%s\n' "$run_json" | jq -r .path)"
  case "$validation_mode" in
    trusted-base)
      : "${GATE_DEFINITION_TRUSTED_BASE_WORKFLOWS:?Set comma-separated trusted-base workflow paths allowed to validate gate-definition PRs}"
      trusted_workflows=",$GATE_DEFINITION_TRUSTED_BASE_WORKFLOWS,"
      printf '%s\n' "$trusted_workflows" | rg -Fq ",$workflow_path,"
      test "$run_head_sha" = "$trusted_base_oid"
      workflow_sha="$(git rev-parse "$TRUSTED_BASE_REF:$workflow_path")"
      ;;
    immutable-external)
      : "${GATE_DEFINITION_TRUSTED_VALIDATOR_WORKFLOWS:?Set comma-separated repo:path workflow allowlist entries for immutable-external validation}"
      : "${GATE_DEFINITION_TRUSTED_VALIDATOR_HEAD_SHAS:?Set comma-separated repo|head_sha allowlist entries for immutable-external validation}"
      : "${GATE_DEFINITION_TRUSTED_VALIDATOR_WORKFLOW_SHAS:?Set pipe-delimited repo|path|sha workflow hash allowlist entries for immutable-external validation}"
      trusted_validator_heads=",$GATE_DEFINITION_TRUSTED_VALIDATOR_HEAD_SHAS,"
      printf '%s\n' "$trusted_validator_heads" | rg -Fq ",$run_repo|$run_head_sha,"
      trusted_workflows=",$GATE_DEFINITION_TRUSTED_VALIDATOR_WORKFLOWS,"
      printf '%s\n' "$trusted_workflows" | rg -Fq ",$run_repo:$workflow_path,"
      workflow_sha="$(printf '%s\n' "$GATE_DEFINITION_TRUSTED_VALIDATOR_WORKFLOW_SHAS" | awk -F'|' -v repo="$run_repo" -v path="$workflow_path" '$1 == repo && $2 == path { print $3; exit }')"
      test -n "$workflow_sha"
      actual_workflow_sha="$(gh api "repos/$run_repo/contents/$workflow_path?ref=$run_head_sha" --jq .sha)"
      test "$actual_workflow_sha" = "$workflow_sha"
      ;;
  esac
  artifacts_json="$(gh api "repos/$run_repo/actions/runs/$run_id/artifacts")"
  artifact_id="$(printf '%s\n' "$artifacts_json" | jq -r '.artifacts[] | select(.name == "trusted-base-gate-evidence" and .expired == false) | .id' | head -n 1)"
  test -n "$artifact_id" && test "$artifact_id" != "null"
  artifact_dir="$(mktemp -d)"
  artifact_zip="$artifact_dir/artifact.zip"
  artifact_payload_dir="$artifact_dir/payload"
  gh api "repos/$run_repo/actions/artifacts/$artifact_id/zip" > "$artifact_zip"
  mkdir -p "$artifact_payload_dir"
  unzip -q "$artifact_zip" -d "$artifact_payload_dir"
  artifact_evidence_file_count="$(find "$artifact_payload_dir" -type f -name trusted-base-gate-evidence.env | wc -l | tr -d '[:space:]')"
  test "$artifact_evidence_file_count" -eq 1
  artifact_evidence_file="$(find "$artifact_payload_dir" -type f -name trusted-base-gate-evidence.env -print -quit)"
  rg -Fqx "PR_HEAD=$LOCAL_HEAD" "$artifact_evidence_file"
  rg -Fqx "TRUSTED_BASE=$trusted_base_oid" "$artifact_evidence_file"
  rg -Fqx "TRUSTED_BASE_GATE=PASS" "$artifact_evidence_file"
  rg -Fqx "TRUSTED_MANIFEST_CHECKS=PASS" "$artifact_evidence_file"
  rg -Fqx "VALIDATION_MODE=$validation_mode" "$artifact_evidence_file"
  rg -Fqx "RUN_REPO=$run_repo" "$artifact_evidence_file"
  rg -Fqx "RUN_ID=$run_id" "$artifact_evidence_file"
  rg -Fqx "WORKFLOW_PATH=$workflow_path" "$artifact_evidence_file"
  rg -Fqx "WORKFLOW_SHA=$workflow_sha" "$artifact_evidence_file"
  GATE_DEFINITION_EXTERNAL_VALIDATION_PASSED=true
}

assert_head_approved() {
  test "$(qualifying_approval_review_urls | sed '/^[[:space:]]*$/d' | wc -l)" -gt 0
}

assert_required_checks() {
  local checks_json owner repo query required_checks required_check required_type required_name required_source required_count page_json page_checks has_next cursor branch_query branch_json branch_required_checks branch_required_check branch_required_kind branch_required_name branch_required_source manifest_matches manifest_required_check manifest_type manifest_name manifest_source
  : "${BASE_REF:?Set BASE_REF to the PR base branch name before asserting required checks}"
  required_checks="$(required_checks_manifest || true)"
  if [ -z "$required_checks" ]; then
    echo "Trusted required-check manifest is unavailable; gate-definition bootstrap requires externally validated .github/required-pr-checks.txt" >&2
    exit 1
  fi

  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  query='query($owner:String!,$repo:String!,$oid:GitObjectID!,$cursor:String){repository(owner:$owner,name:$repo){object(oid:$oid){... on Commit{statusCheckRollup{contexts(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{__typename ... on CheckRun{name conclusion status detailsUrl checkSuite{app{slug}}} ... on StatusContext{context state targetUrl creator{login}}}}}}}}}'
  checks_json='[]'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -f oid="$LOCAL_HEAD" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -f oid="$LOCAL_HEAD" -f query="$query")"
    fi
    page_checks="$(printf '%s\n' "$page_json" | jq '[.data.repository.object.statusCheckRollup.contexts.nodes[] | if .__typename == "CheckRun" then {type: "check_run", name: .name, state: (.conclusion // .status), source: (.checkSuite.app.slug // ""), url: (.detailsUrl // "")} else {type: "status_context", name: .context, state: .state, source: (.creator.login // ""), url: (.targetUrl // "")} end]')"
    checks_json="$(jq -n --argjson existing "$checks_json" --argjson page "$page_checks" '$existing + $page')"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.object.statusCheckRollup.contexts.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.object.statusCheckRollup.contexts.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
  printf '%s\n' "$checks_json" | jq -e 'length > 0 and all(.[]; .state | IN("SUCCESS", "SKIPPED", "NEUTRAL"))'
  printf '%s\n' "$checks_json" | jq -e --arg repo_url "https://github.com/$PR_REPO/" 'all(.[]; if .type == "check_run" and .source == "github-actions" then ((.url // "") | startswith($repo_url + "actions/runs/")) else true end)'
  required_count=0
  while IFS= read -r required_check; do
    case "$required_check" in
      ""|\#*) continue ;;
    esac
    IFS='|' read -r required_type required_name required_source <<EOF
$required_check
EOF
    test -n "$required_type" && test -n "$required_name" && test -n "$required_source"
    ([ "$required_type" = "check_run" ] || [ "$required_type" = "status_context" ])
    required_count=$((required_count + 1))
    printf '%s\n' "$checks_json" | jq -e --arg type "$required_type" --arg name "$required_name" --arg source "$required_source" '[.[] | select(.type == $type and .name == $name and .source == $source)] as $matches | ($matches | length) > 0 and all($matches[]; .state == "SUCCESS")'
  done <<< "$required_checks"
  test "$required_count" -gt 0

  branch_query='query($owner:String!,$repo:String!,$baseRefName:String!){repository(owner:$owner,name:$repo){ref(qualifiedName:$baseRefName){branchProtectionRule{requiredStatusCheckContexts requiredStatusChecks{context app{slug}}}}}}'
  branch_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -f baseRefName="$BASE_REF" -f query="$branch_query")"
  branch_required_checks="$(
    printf '%s\n' "$branch_json" |
      jq -r '
        (.data.repository.ref.branchProtectionRule // {}) as $rule |
        (($rule.requiredStatusCheckContexts // [])[] | "legacy|" + .),
        (($rule.requiredStatusChecks // [])[] | "app|" + .context + "|" + (.app.slug // ""))
      ' |
      sort -u
  )"
  while IFS= read -r branch_required_check; do
    case "$branch_required_check" in
      "") continue ;;
    esac
    IFS='|' read -r branch_required_kind branch_required_name branch_required_source <<EOF
$branch_required_check
EOF
    test -n "$branch_required_kind" && test -n "$branch_required_name"
    if [ -n "$branch_required_source" ]; then
      printf '%s\n' "$checks_json" | jq -e --arg name "$branch_required_name" --arg source "$branch_required_source" '[.[] | select(.name == $name and .source == $source)] as $matches | ($matches | length) > 0 and all($matches[]; .state == "SUCCESS")'
    else
      manifest_matches="$(printf '%s\n' "$required_checks" | awk -F'|' -v name="$branch_required_name" '$0 !~ /^[[:space:]]*(#|$)/ && NF == 3 && $2 == name { print }')"
      if [ -z "$manifest_matches" ]; then
        echo "Source-less branch required check '$branch_required_name' has no trusted manifest type/source mapping" >&2
        exit 1
      fi
      while IFS= read -r manifest_required_check; do
        IFS='|' read -r manifest_type manifest_name manifest_source <<EOF
$manifest_required_check
EOF
        test -n "$manifest_type" && test -n "$manifest_name" && test -n "$manifest_source"
        printf '%s\n' "$checks_json" | jq -e --arg type "$manifest_type" --arg name "$manifest_name" --arg source "$manifest_source" '[.[] | select(.type == $type and .name == $name and .source == $source)] as $matches | ($matches | length) > 0 and all($matches[]; .state == "SUCCESS")'
      done <<< "$manifest_matches"
    fi
  done <<< "$branch_required_checks"
}

wait_for_pr_state() {
  local attempt expected_base expected_base_ref
  expected_base="${GATED_BASE_OID:-$BASE_OID}"
  expected_base_ref="${GATED_BASE_REF:-$BASE_REF}"
  PR_STATE=''
  for attempt in $(seq 1 30); do
    PR_STATE="$(gh pr view "$PR" --repo "$PR_REPO" --json state,mergeStateStatus,mergeable,reviewDecision,isDraft,reviewRequests,headRefOid,baseRefOid,baseRefName)"
    if printf '%s\n' "$PR_STATE" | jq -e --arg head "$LOCAL_HEAD" --arg base "$expected_base" --arg base_ref "$expected_base_ref" '
      .headRefOid == $head and
      .baseRefOid == $base and
      .baseRefName == $base_ref and
      .state == "OPEN" and
      .isDraft == false and
      .mergeable == "MERGEABLE" and
      (.mergeStateStatus | IN("CLEAN", "HAS_HOOKS")) and
      .reviewDecision == "APPROVED" and
      ([.reviewRequests[]?] | length) == 0
    ' >/dev/null; then
      return 0
    fi
    sleep 10
  done
  echo "PR final readiness predicate did not pass after waiting" >&2
  return 1
}

run_final_local_readiness_gate() {
  local ci_output ci_last_line ai_review_output ai_review_last_line current_base_json
  : "${PR_COMMENT_EVIDENCE:?Set PR_COMMENT_EVIDENCE to the review-comment evidence ledger path}"
  : "${PR_COMMENT_EVIDENCE_READY:?Append COMMENT evidence lines for every COMMENT_META line, then set PR_COMMENT_EVIDENCE_READY=true}"
  assert_gate_definition_change_scope
  assert_no_forbidden_suppressions
  test -z "$(git status --short)"
  ci_output="$(make ci 2>&1)"
  printf '%s\n' "$ci_output"
  ci_last_line="$(printf '%s\n' "$ci_output" | sed '/^[[:space:]]*$/d' | tail -n 1)"
  test "$ci_last_line" = "✅ CI checks successfully passed!"
  LOCAL_HEAD="$(git rev-parse HEAD)"
  write_comment_metadata_evidence
  VALIDATED_COMMENT_EVIDENCE_URLS=''
  assert_review_thread_comment_evidence
  assert_non_thread_comment_evidence
  ai_review_output="$(AI_REVIEW_BASE="$TRUSTED_BASE_REF" make ai-review-loop 2>&1)"
  printf '%s\n' "$ai_review_output"
  ai_review_last_line="$(printf '%s\n' "$ai_review_output" | sed '/^[[:space:]]*$/d' | tail -n 1)"
  test "$ai_review_last_line" = "AI review PASS."
  test "$LOCAL_HEAD" = "$(git rev-parse HEAD)"
  test -z "$(git status --short)"
  current_base_json="$(gh pr view "$PR" --repo "$PR_REPO" --json baseRefName,baseRefOid)"
  test "$BASE_OID" = "$(printf '%s\n' "$current_base_json" | jq -r .baseRefOid)"
  test "$BASE_REF" = "$(printf '%s\n' "$current_base_json" | jq -r .baseRefName)"
  VERIFIED_HEAD="$LOCAL_HEAD"
}

# 0. Run autonomous AI review + fix loop against the PR base
: "${PR:?Set PR to the pull request number}"
case "$PR" in
  ''|*[!0-9]*) echo "PR must be numeric" >&2; exit 1 ;;
esac
: "${PR_REPO:?Set PR_REPO=owner/repo for the base PR repository}"
PR_META="$(gh pr view "$PR" --repo "$PR_REPO" --json baseRefName,baseRefOid)"
BASE_REF="$(printf '%s\n' "$PR_META" | jq -r .baseRefName)"
BASE_OID="$(printf '%s\n' "$PR_META" | jq -r .baseRefOid)"
TRUSTED_BASE_REF="refs/remotes/pr-base/$PR"
git fetch "git@github.com:${PR_REPO}.git" "$BASE_REF:$TRUSTED_BASE_REF"
test "$(git rev-parse "$TRUSTED_BASE_REF")" = "$BASE_OID"
assert_gate_definition_change_scope
AI_REVIEW_OUTPUT="$(AI_REVIEW_BASE="$TRUSTED_BASE_REF" make ai-review-loop 2>&1)"
printf '%s\n' "$AI_REVIEW_OUTPUT"
AI_REVIEW_LAST_LINE="$(printf '%s\n' "$AI_REVIEW_OUTPUT" | sed '/^[[:space:]]*$/d' | tail -n 1)"
test "$AI_REVIEW_LAST_LINE" = "AI review PASS."

# 1. Get comments
: "${PR_COMMENT_EVIDENCE:?Set PR_COMMENT_EVIDENCE to the review-comment evidence ledger path}"
capture_review_comment_snapshot
printf 'SNAPSHOT_STARTED_AT=%s\nSNAPSHOT_CAPTURED_BY=code-review-skill\n' "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" > "$PR_COMMENT_EVIDENCE"
make pr-comments PR="$PR"

# 2. Apply each suggestion/fix (one commit per comment)
git commit -m "Apply review suggestion: [description]

Ref: [comment URL]"

# 3. Verify all addressed
assert_no_unresolved_comments

# 4. Run CI
CI_OUTPUT="$(make ci 2>&1)"
printf '%s\n' "$CI_OUTPUT"
CI_LAST_LINE="$(printf '%s\n' "$CI_OUTPUT" | sed '/^[[:space:]]*$/d' | tail -n 1)"
test "$CI_LAST_LINE" = "✅ CI checks successfully passed!"
assert_gate_definition_change_scope
assert_no_forbidden_suppressions
CI_HEAD="$(git rev-parse HEAD)"
LOCAL_HEAD="$CI_HEAD"
write_comment_metadata_evidence
# Append one COMMENT|url|commit|sha, COMMENT|url|reply|url, or COMMENT|url|decline|url line for every COMMENT_META line before continuing.
: "${PR_COMMENT_EVIDENCE_READY:?Append COMMENT evidence lines for every COMMENT_META line, then set PR_COMMENT_EVIDENCE_READY=true}"
assert_review_thread_comment_evidence
assert_non_thread_comment_evidence

# 5. Run the mandatory final AI review loop before push/ready
AI_REVIEW_OUTPUT="$(AI_REVIEW_BASE="$TRUSTED_BASE_REF" make ai-review-loop 2>&1)"
printf '%s\n' "$AI_REVIEW_OUTPUT"
AI_REVIEW_LAST_LINE="$(printf '%s\n' "$AI_REVIEW_OUTPUT" | sed '/^[[:space:]]*$/d' | tail -n 1)"
test "$AI_REVIEW_LAST_LINE" = "AI review PASS."
# If the loop applies fixes or changes HEAD, commit them, then rerun make ci,
# assert_no_forbidden_suppressions, capture CI_HEAD, and run this final
# ai-review-loop until both pass on the same commit without new changes.
test "$CI_HEAD" = "$(git rev-parse HEAD)"
test -z "$(git status --short)"
assert_gate_definition_change_scope
VERIFIED_HEAD="$CI_HEAD"
rg -q "^PR_HEAD=$VERIFIED_HEAD$" "$PR_COMMENT_EVIDENCE"
CURRENT_BASE_JSON="$(gh pr view "$PR" --repo "$PR_REPO" --json baseRefName,baseRefOid)"
test "$BASE_OID" = "$(printf '%s\n' "$CURRENT_BASE_JSON" | jq -r .baseRefOid)"
test "$BASE_REF" = "$(printf '%s\n' "$CURRENT_BASE_JSON" | jq -r .baseRefName)"

# 6. Push and verify GitHub PR readiness on the pushed head
run_final_local_readiness_gate
GATED_BASE_REF="$BASE_REF"
GATED_BASE_OID="$BASE_OID"
GATED_VERIFIED_HEAD="$VERIFIED_HEAD"
test "$(git rev-parse HEAD)" = "$GATED_VERIFIED_HEAD"
LOCAL_HEAD="$GATED_VERIFIED_HEAD"
HEAD_REF="$(gh pr view "$PR" --repo "$PR_REPO" --json headRefName --jq .headRefName)"
PR_HEAD_REPO="$(gh pr view "$PR" --repo "$PR_REPO" --json headRepository --jq .headRepository.nameWithOwner)"
CURRENT_REPO="$(gh repo view --json nameWithOwner --jq .nameWithOwner)"
if [ "$PR_HEAD_REPO" = "$CURRENT_REPO" ]; then
  git push origin HEAD:"$HEAD_REF"
else
  if git remote get-url pr-head >/dev/null 2>&1; then
    git remote set-url pr-head "git@github.com:${PR_HEAD_REPO}.git"
  else
    git remote add pr-head "git@github.com:${PR_HEAD_REPO}.git"
  fi
  git push pr-head HEAD:"$HEAD_REF"
fi
PR_HEAD="$(gh pr view "$PR" --repo "$PR_REPO" --json headRefOid --jq .headRefOid)"
test "$LOCAL_HEAD" = "$PR_HEAD"
gh pr checks "$PR" --repo "$PR_REPO" --watch --interval 30
assert_gate_definition_external_validation
assert_required_checks
assert_no_unresolved_comments
VALIDATED_COMMENT_EVIDENCE_URLS=''
assert_review_thread_comment_evidence
assert_non_thread_comment_evidence
assert_no_unaccounted_comments_after_snapshot
assert_head_approved
wait_for_pr_state
printf '%s\n' "$PR_STATE" | jq -e --arg head "$LOCAL_HEAD" --arg base "$GATED_BASE_OID" --arg base_ref "$GATED_BASE_REF" '
  .headRefOid == $head and
  .baseRefOid == $base and
  .baseRefName == $base_ref and
  .state == "OPEN" and
  .isDraft == false and
  .mergeable == "MERGEABLE" and
  (.mergeStateStatus | IN("CLEAN", "HAS_HOOKS")) and
  .reviewDecision == "APPROVED" and
  ([.reviewRequests[]?] | length) == 0
'
assert_no_unresolved_comments
VALIDATED_COMMENT_EVIDENCE_URLS=''
assert_review_thread_comment_evidence
assert_non_thread_comment_evidence
assert_no_unaccounted_comments_after_snapshot
assert_required_checks
assert_head_approved
wait_for_pr_state
printf '%s\n' "$PR_STATE" | jq -e --arg head "$LOCAL_HEAD" --arg base "$GATED_BASE_OID" --arg base_ref "$GATED_BASE_REF" '
  .headRefOid == $head and
  .baseRefOid == $base and
  .baseRefName == $base_ref and
  .state == "OPEN" and
  .isDraft == false and
  .mergeable == "MERGEABLE" and
  (.mergeStateStatus | IN("CLEAN", "HAS_HOOKS")) and
  .reviewDecision == "APPROVED" and
  ([.reviewRequests[]?] | length) == 0
'
assert_no_unresolved_comments
VALIDATED_COMMENT_EVIDENCE_URLS=''
assert_review_thread_comment_evidence
assert_non_thread_comment_evidence
assert_no_unaccounted_comments_after_snapshot
assert_required_checks
assert_head_approved
```

## Execution Steps

### Step 0: Run Autonomous AI Review Loop

Before addressing PR comments manually, fetch the PR base and run the autonomous review loop against that base:

```bash
set -euo pipefail

: "${PR:?Set PR to the pull request number}"
case "$PR" in
  ''|*[!0-9]*) echo "PR must be numeric" >&2; exit 1 ;;
esac
: "${PR_REPO:?Set PR_REPO=owner/repo for the base PR repository}"
PR_META="$(gh pr view "$PR" --repo "$PR_REPO" --json baseRefName,baseRefOid)"
BASE_REF="$(printf '%s\n' "$PR_META" | jq -r .baseRefName)"
BASE_OID="$(printf '%s\n' "$PR_META" | jq -r .baseRefOid)"
TRUSTED_BASE_REF="refs/remotes/pr-base/$PR"
git fetch "git@github.com:${PR_REPO}.git" "$BASE_REF:$TRUSTED_BASE_REF"
test "$(git rev-parse "$TRUSTED_BASE_REF")" = "$BASE_OID"
AI_REVIEW_OUTPUT="$(AI_REVIEW_BASE="$TRUSTED_BASE_REF" make ai-review-loop 2>&1)"
printf '%s\n' "$AI_REVIEW_OUTPUT"
AI_REVIEW_LAST_LINE="$(printf '%s\n' "$AI_REVIEW_OUTPUT" | sed '/^[[:space:]]*$/d' | tail -n 1)"
test "$AI_REVIEW_LAST_LINE" = "AI review PASS."
```

This executes `scripts/ai-review-loop.sh`, which:

1. Runs an AI review agent against the current diff (base: `AI_REVIEW_BASE`, or `main` by default)
2. If issues are found (`STATUS: FAIL`), runs a fix agent to auto-remediate
3. Verifies fixes with `make ci`
4. Repeats up to `AI_REVIEW_MAX_ITER` times (default: 3)

The reviewer prompt in `scripts/ai-review-prompts/review.md` checks both FR/NFR
coverage and code health for supported local review agents. It explicitly
includes system design tradeoffs, appropriate design pattern use, code smells,
SOLID/DRY/KISS, DDD/CQRS, Hexagonal Architecture, and repository rules. Review
failures must stay concrete and scoped to changed code or directly affected
behavior.

Codex consumes the reviewer prompt directly. Claude keeps its built-in
`/review` invocation and receives the same repository review policy through the
loop's appended system prompt.

**Configuration** (all overridable via environment):

| Variable               | Default         | Description                         |
| ---------------------- | --------------- | ----------------------------------- |
| `AI_REVIEW_AGENTS`     | `codex`         | Agent(s) to use (`codex`, `claude`) |
| `AI_REVIEW_BASE`       | `main`          | Base branch for diff comparison     |
| `AI_REVIEW_MAX_ITER`   | `3`             | Max review/fix iterations (0=∞)     |
| `AI_REVIEW_VERIFY_CMD` | `make ci`       | Verification command after each fix |
| `AI_REVIEW_LOG_DIR`    | `var/ai-review` | Directory for review/fix logs       |

**Examples**:

```bash
# Use Claude instead of Codex
AI_REVIEW_BASE="$TRUSTED_BASE_REF" AI_REVIEW_AGENTS=claude make ai-review-loop

# Limit to 1 iteration, custom base branch
AI_REVIEW_BASE=develop AI_REVIEW_MAX_ITER=1 make ai-review-loop

# Run both agents
AI_REVIEW_BASE="$TRUSTED_BASE_REF" AI_REVIEW_AGENTS=codex,claude make ai-review-loop
```

**Prompt templates**: `scripts/ai-review-prompts/review.md` (reviewer) and `scripts/ai-review-prompts/fix.md` (fixer).

### Step 1: Get PR Comments

```bash
make pr-comments              # Auto-detect from current branch
make pr-comments PR=62       # Specify PR number
make pr-comments FORMAT=json  # JSON output
```

**Output**: All unresolved comments with file/line, author, timestamp, URL

### Step 2: Categorize Comments

| Type                   | Identifier                  | Priority | Action                               |
| ---------------------- | --------------------------- | -------- | ------------------------------------ |
| Committable Suggestion | Code block, "```suggestion" | Highest  | Apply immediately, commit separately |
| LLM Prompt             | "🤖 Prompt for AI Agents"   | High     | Execute prompt, implement changes    |
| Architecture Concern   | Class naming, file location | High     | Invoke appropriate skill             |
| Question               | Ends with "?"               | Medium   | Answer inline or via code change     |
| General Feedback       | Discussion, recommendation  | Low      | Consider and improve                 |
| Resolved/Stale         | Outdated or already fixed   | None     | Do not change code; record reason    |

### Step 3: Verify Architecture & Organization

For code changes (suggestions, prompts, new files), invoke verification skills:

| Concern Type           | Skill to Invoke                    |
| ---------------------- | ---------------------------------- |
| Class placement/naming | `code-organization`                |
| DDD patterns           | `implementing-ddd-architecture`    |
| Layer violations       | `deptrac-fixer` (if deptrac fails) |

**Quick verification**: Run `make phpcsfixer && make psalm && make deptrac && make unit-tests`

### Step 4: Apply Changes Systematically

#### For Committable Suggestions

1. Verify the suggestion still applies to current code
2. Apply the suggestion exactly when it is still valid and compatible with repository rules
3. If the suggestion is stale, implement the current equivalent fix or record why no change is needed
4. Commit with reference:

   ```bash
   git commit -m "Apply review suggestion: [brief description]

   Ref: [comment URL]"
   ```

#### For LLM Prompts

1. Copy prompt from comment
2. Verify every finding against current code before changing files
3. Execute still-valid instructions
4. Skip stale, duplicate, or contradicted findings with a brief reason
5. Verify output meets requirements
6. Commit with reference

#### For Architecture/Organization Concerns

1. Invoke appropriate skill (`code-organization` or `implementing-ddd-architecture`)
2. Implement recommended changes
3. Verify: `make phpcsfixer && make psalm && make deptrac && make unit-tests`
4. Commit with reference

#### For Questions

1. Determine if code change or reply needed
2. If code: implement + commit
3. If reply: respond on GitHub

#### For General Feedback

1. Evaluate suggestion merit
2. Implement if beneficial
3. Document reasoning if declined

### Step 5: Verify All Addressed

```bash
set -euo pipefail

: "${PR:?Set PR to the pull request number}"
case "$PR" in
  ''|*[!0-9]*) echo "PR must be numeric" >&2; exit 1 ;;
esac
: "${PR_REPO:?Set PR_REPO=owner/repo for the base PR repository}"
owner="${PR_REPO%%/*}"
repo="${PR_REPO#*/}"
query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviewThreads(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{isResolved comments(first:1){nodes{id}}}}}}}'
total_count=0
cursor=''
while :; do
  if [ -n "$cursor" ]; then
    page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
  else
    page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
  fi
  page_count="$(printf '%s\n' "$page_json" | jq '[.data.repository.pullRequest.reviewThreads.nodes[] | select(.isResolved == false and (.comments.nodes | length > 0))] | length')"
  total_count=$((total_count + page_count))
  has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.hasNextPage')"
  [ "$has_next" = "true" ] || break
  cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.endCursor')"
  test -n "$cursor" && test "$cursor" != "null"
done
test "$total_count" -eq 0
```

If unresolved comments remain, repeat categorization and implementation. If a remaining thread is stale, duplicate, or answer-only, respond or resolve it according to the review workflow before continuing. Run `capture_review_comment_snapshot` before handling comments and write `SNAPSHOT_STARTED_AT=<timestamp>` plus `SNAPSHOT_CAPTURED_BY=code-review-skill` to `PR_COMMENT_EVIDENCE`; do not supply or backdate the timestamp manually. Before final verification, record every review-thread comment, top-level PR issue comment, and review body comment URL from that snapshot in `PR_COMMENT_EVIDENCE` using `PR_HEAD=<sha>`, `COMMENT_META|url|updatedAt|body_sha256`, and `COMMENT|url|commit|sha`, `COMMENT|url|reply|url`, or `COMMENT|url|decline|url`; comment timing uses the later of `createdAt` and `updatedAt`, commit messages must reference the source URL, reply/decline comments must be posted by the PR author or a login in `PR_COMMENT_TRUSTED_EVIDENCE_ACTORS`, reply bodies must include `EVIDENCE_SOURCE: <source URL>` and positive `EVIDENCE_ACTION: <action>`, decline bodies must include `EVIDENCE_SOURCE: <source URL>`, no-change `EVIDENCE_ACTION: <action>`, and `EVIDENCE_REASON: <reason>`, and only reply/decline URLs validated against snapshotted source comments may be treated as evidence comments after the snapshot. Any later created or edited non-evidence PR/review comment requires restarting the snapshot and evidence ledger, except an otherwise qualifying approval review on pushed `HEAD` whose body is empty or exactly `FINAL_APPROVAL_NO_ACTION: true`.

### Step 6: Run Quality Checks

**MANDATORY**: Run comprehensive CI checks after implementing all changes:

```bash
set -euo pipefail
command -v rg >/dev/null || { echo "rg is required" >&2; exit 1; }
CI_OUTPUT="$(make ci 2>&1)"
printf '%s\n' "$CI_OUTPUT"
CI_LAST_LINE="$(printf '%s\n' "$CI_OUTPUT" | sed '/^[[:space:]]*$/d' | tail -n 1)"
test "$CI_LAST_LINE" = "✅ CI checks successfully passed!"
```

After `make ci`, scan the current PR diff for forbidden suppression or ignore directives. Changes to quality-tool baselines or suppression/config files block product PR completion; isolated dedicated gate-definition PRs may include those files only when external trusted-base validation passes.
Changes to CI/review gate definitions (`AGENTS.md`, `Makefile`, `.claude/skills/**`, `.agents/skills/**`, review scripts, CI workflows, architecture/quality gate configs, lint/formatter configs, external quality/check service configs, package-manager check configs, GraphQL/OpenAPI check configs, required-check manifests, AI review-loop Bats coverage, gate-specific onboarding guidance, or timestamped autonomous spec evidence whose slug exactly matches the validation-support allowlist) must be isolated in a dedicated gate-definition PR after the trusted base already has a required-check manifest. Initial gate-definition bootstrap PRs that introduce `.github/required-pr-checks.txt` may proceed only through the externally validated bootstrap path, and product PRs must not validate with gate definitions supplied only by that same PR.

```bash
command -v rg >/dev/null || { echo "rg is required for suppression scan" >&2; exit 1; }
set -o pipefail
diff_output="$(git diff --unified=0 "$TRUSTED_BASE_REF"...HEAD)" || {
  echo "Unable to compute PR diff for suppression scan" >&2
  exit 1
}

forbidden_suppression_pattern() {
  printf '%s\n' '@Suppress''Warnings|@psalm-''suppress|@phpstan-''ignore|phpstan-''ignore|phpcs:(''ignore|disable)|@infection-''ignore|@codeCoverage''Ignore|@phpinsights-''ignore|@codingStandards''Ignore|codingStandards''Ignore'
}

if printf '%s\n' "$diff_output" | rg '^\+[^+]' | rg -n "$(forbidden_suppression_pattern)"; then
  echo "Forbidden suppression/ignore directive found in PR diff" >&2
  exit 1
fi

gate_definition_core_file_pattern='^(AGENTS\.md|Makefile|\.claude/skills/.*|\.agents/skills/.*|deptrac\.yaml|composer\.(json|lock)|phpunit.*\.xml\.dist|psalm\.xml(\.dist)?|phpstan\.(neon|neon\.dist)|phpmd.*\.xml(\.dist)?|phpinsights.*\.php|infection\.(json|json5)(\.dist)?|phpcs\.xml(\.dist)?|\.php-cs-fixer\.dist\.php|docker-compose.*\.ya?ml|Dockerfile.*|scripts/.*|\.github/workflows/.*|\.github/actions/.*|\.github/linters/.*|\.github/required-pr-checks\.txt|\.prettierrc(\..*)?|\.prettierignore|(.*/)?prettier\.config\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?\.markdownlint(\..*)?|(.*/)?markdownlint\.config\.(js|mjs|cjs|json|ya?ml)|(.*/)?\.yamllint(\..*)?|(\.codecov|codecov)\.ya?ml|\.coderabbit\.ya?ml|\.qlty/.*|(\.qlty|qlty)\.toml|\.snyk|(\.spectral|spectral)\.ya?ml|(.*/)?spectral\.config\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?eslint\.config\.(js|mjs|cjs|ts)|(.*/)?\.eslintrc(\..*)?|(.*/)?package(-lock)?\.json|(.*/)?pnpm-lock\.yaml|(.*/)?yarn\.lock|(.*/)?\.graphqlrc(\..*)?|(.*/)?graphql\.config\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?graphql-inspector(\.config)?\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?(\.openapi-diff|openapi-diff)(\.config)?\.(js|mjs|cjs|ts|json|ya?ml))$'
gate_definition_validation_support_file_pattern='^(docs/onboarding\.md|tests/CLI/bats/make_ai_review_loop_tests\.bats|specs/autonomous/[0-9]{8}-[0-9]{6}-(ai-review-loop|bmad-fr-nfr-review-gate|bmad-fr-nfr-reviewer-system-design-patterns|ci-gate|gate-definition|required-pr-checks-gate|review-loop)/(architecture|epics|implementation-readiness|manual-evidence|prd|product-brief-distillate|product-brief|research|run-summary|skill-sweep)\.md)$'
gate_definition_file_pattern="(${gate_definition_core_file_pattern}|${gate_definition_validation_support_file_pattern})"
GATE_DEFINITION_CHANGES_PRESENT=false
gate_definition_bootstrap_required() {
  [ "$GATE_DEFINITION_CHANGES_PRESENT" = "true" ] &&
    ! git cat-file -e "$TRUSTED_BASE_REF:.github/required-pr-checks.txt" 2>/dev/null &&
    test -r .github/required-pr-checks.txt
}
all_changes="$(git diff --name-only "$TRUSTED_BASE_REF"...HEAD)"
gate_definition_changes="$(printf '%s\n' "$all_changes" | rg "$gate_definition_file_pattern" || true)"
if [ -n "$gate_definition_changes" ]; then
  GATE_DEFINITION_CHANGES_PRESENT=true
  non_gate_definition_changes="$(printf '%s\n' "$all_changes" | rg -v "$gate_definition_file_pattern" || true)"
  printf '%s\n' "$gate_definition_changes"
  if [ -n "$non_gate_definition_changes" ]; then
    printf '%s\n' "$non_gate_definition_changes"
    if gate_definition_bootstrap_required; then
      echo "Initial CI/review gate bootstrap includes non-gate changes; trusted external validation is required before completion" >&2
    else
      echo "CI/review gate definition changes must be isolated in a dedicated gate-definition PR with no product, runtime, or unrelated test-code changes" >&2
      exit 1
    fi
  fi
fi

quality_config_changes="$(git diff --name-only "$TRUSTED_BASE_REF"...HEAD | rg '(^|/)(.*baseline.*|psalm\.xml(\.dist)?|phpstan\.(neon|neon\.dist)|phpmd.*\.xml(\.dist)?|phpinsights.*\.php|infection\.(json|json5)(\.dist)?|phpcs\.xml(\.dist)?|\.php-cs-fixer\.dist\.php)$' || true)"
if [ -n "$quality_config_changes" ] && [ "$GATE_DEFINITION_CHANGES_PRESENT" != "true" ]; then
  printf '%s\n' "$quality_config_changes"
  echo "Quality tool suppression/baseline/config changes block completion; remove suppression/ignore changes instead" >&2
  exit 1
fi
```

**If CI fails**, invoke appropriate skill:

| Failure Type            | Skill to Use            |
| ----------------------- | ----------------------- |
| Architecture violations | `deptrac-fixer`         |
| Complexity issues       | `complexity-management` |
| Test failures           | `testing-workflow`      |
| Mutation testing issues | `testing-workflow`      |
| Code style              | Run `make phpcsfixer`   |
| Static analysis         | Run `make psalm`        |

**DO NOT** finish the task until `make ci` shows: `✅ CI checks successfully passed!`

Dedicated gate-definition PRs may complete only when all of the following are true:

1. `assert_gate_definition_change_scope` reports only gate-definition files and explicitly classified validation-support files changed against `TRUSTED_BASE_REF`, except for the initial externally validated bootstrap PR that introduces `.github/required-pr-checks.txt` when the trusted base has no manifest.
2. Step 8 reruns `make ci` and final `make ai-review-loop` on the same clean local `HEAD` before push; caller-supplied `BASE_OID` or `VERIFIED_HEAD` variables are not trusted as proof.
3. `GATE_DEFINITION_VALIDATION_EVIDENCE` points to a readable ledger outside the PR worktree with `PR_HEAD=<HEAD>`, `TRUSTED_BASE=<baseRefOid>`, `VALIDATION_MODE=trusted-base` or `VALIDATION_MODE=immutable-external`, `TRUSTED_BASE_GATE=PASS`, `TRUSTED_MANIFEST_CHECKS=PASS`, and an immutable GitHub Actions run URL whose repository is the base PR repository for `trusted-base` mode or is listed in `GATE_DEFINITION_TRUSTED_VALIDATOR_REPOS` for `immutable-external` mode; the run must complete successfully while validating pushed `HEAD` from the canonical artifact, execute from the trusted base commit for `trusted-base` mode or from a `repo|head_sha` listed in `GATE_DEFINITION_TRUSTED_VALIDATOR_HEAD_SHAS` for `immutable-external` mode, come from a workflow path allowed by `GATE_DEFINITION_TRUSTED_BASE_WORKFLOWS` or `GATE_DEFINITION_TRUSTED_VALIDATOR_WORKFLOWS`, use a trusted workflow SHA from the trusted base or `GATE_DEFINITION_TRUSTED_VALIDATOR_WORKFLOW_SHAS`, and expose exactly one non-expired `trusted-base-gate-evidence` artifact file named `trusted-base-gate-evidence.env` with exact full-line records for the same `PR_HEAD`, `TRUSTED_BASE`, `TRUSTED_BASE_GATE=PASS`, `TRUSTED_MANIFEST_CHECKS=PASS`, exact `VALIDATION_MODE`, `RUN_REPO`, `RUN_ID`, `WORKFLOW_PATH`, and `WORKFLOW_SHA`.
4. Direct review-thread query shows zero unresolved review threads.
5. `PR_COMMENT_EVIDENCE` maps every review-thread, top-level PR issue, and review body comment URL from the auto-captured `REVIEW_COMMENT_SNAPSHOT_STARTED_AT` snapshot to matching `COMMENT_META|url|updatedAt|body_sha256` metadata and to a later PR-range commit whose message references the source URL, or to a later same-PR reply/decline URL whose trusted-author body includes `EVIDENCE_SOURCE`, `EVIDENCE_ACTION`, and decline `EVIDENCE_REASON`; comment ordering uses the later of `createdAt` and `updatedAt`, only validated snapshotted evidence URLs may whitelist post-snapshot evidence comments, and later created or edited non-evidence comments force the snapshot/evidence loop to restart except for an otherwise qualifying approval review on pushed `HEAD` whose body is empty or exactly `FINAL_APPROVAL_NO_ACTION: true`.
6. Local `HEAD` is pushed and equals PR `headRefOid`.
7. Commit-scoped check rollup for pushed `HEAD` is queried from the base PR repository and passes the trusted manifest, live base-branch protection, source-less branch-protection trusted-manifest mappings, and source checks, including base-repository GitHub Actions URLs.
8. Direct review query shows an `APPROVED` review whose review commit equals pushed `HEAD`, whose `submittedAt` is later than the latest addressed comment or evidence event, and whose author is not the PR author and has `OWNER`, `MEMBER`, or `COLLABORATOR` association.
9. `gh pr view` shows `state` `OPEN`, not draft, mergeable, `reviewDecision` `APPROVED`, no review requests, and an accepted merge state after polling the full final readiness predicate.

### Step 7: Run Final AI Review Loop

After the final successful `make ci`, capture `CI_HEAD="$(git rev-parse HEAD)"` and run the autonomous review loop again before any push or ready-for-review action:

```bash
set -euo pipefail

CI_HEAD="$(git rev-parse HEAD)"
AI_REVIEW_OUTPUT="$(AI_REVIEW_BASE="$TRUSTED_BASE_REF" make ai-review-loop 2>&1)"
printf '%s\n' "$AI_REVIEW_OUTPUT"
AI_REVIEW_LAST_LINE="$(printf '%s\n' "$AI_REVIEW_OUTPUT" | sed '/^[[:space:]]*$/d' | tail -n 1)"
test "$AI_REVIEW_LAST_LINE" = "AI review PASS."
test "$CI_HEAD" = "$(git rev-parse HEAD)"
test -z "$(git status --short)"
VERIFIED_HEAD="$CI_HEAD"
CURRENT_BASE_JSON="$(gh pr view "$PR" --repo "$PR_REPO" --json baseRefName,baseRefOid)"
test "$BASE_OID" = "$(printf '%s\n' "$CURRENT_BASE_JSON" | jq -r .baseRefOid)"
test "$BASE_REF" = "$(printf '%s\n' "$CURRENT_BASE_JSON" | jq -r .baseRefName)"
```

If the AI review loop applies fixes or changes any tracked file, repeat:

1. Review `git status --short`
2. Commit intentional tracked changes with the relevant review or AI-loop reference
3. `make ci`
4. forbidden suppression scan and gate-definition change scan
5. capture `CI_HEAD="$(git rev-parse HEAD)"`
6. capture `AI_REVIEW_OUTPUT`, print it, and require the last nonblank output line from `make ai-review-loop` to equal `AI review PASS.`

Before the final loop, write `PR_HEAD=<CI_HEAD>` plus generated `COMMENT_META` lines into `PR_COMMENT_EVIDENCE`, then add one validated `COMMENT|url|commit|sha`, `COMMENT|url|reply|url`, or `COMMENT|url|decline|url` line for every `COMMENT_META` line and set `PR_COMMENT_EVIDENCE_READY=true`. After the final loop, `git rev-parse HEAD` must still equal the captured `CI_HEAD`, `git status --short` must be empty, and the current PR `baseRefName` and `baseRefOid` must still equal the `BASE_REF` and `BASE_OID` captured before CI before continuing to Step 8. Set `VERIFIED_HEAD="$CI_HEAD"` only after those checks pass. Step 8 still reruns this local readiness gate and recomputes `VERIFIED_HEAD` before pushing, so these variables are not standalone proof. Do not push, mark ready, or declare completion until this loop reports PASS on the same commit and base that passed `make ci` without leaving new uncommitted changes. If `make ai-review-loop` cannot be run, completion is blocked.

### Step 8: Push And Verify GitHub PR Readiness

Rerun the local CI and AI-review gate on a clean worktree, push the recomputed final local commit to the actual PR head repository, and verify the PR state on that exact commit:

```bash
set -euo pipefail
command -v jq >/dev/null || { echo "jq is required" >&2; exit 1; }
command -v rg >/dev/null || { echo "rg is required" >&2; exit 1; }
command -v unzip >/dev/null || { echo "unzip is required" >&2; exit 1; }
command -v sha256sum >/dev/null || { echo "sha256sum is required" >&2; exit 1; }
command -v base64 >/dev/null || { echo "base64 is required" >&2; exit 1; }

GATE_DEFINITION_CORE_FILE_PATTERN='^(AGENTS\.md|Makefile|\.claude/skills/.*|\.agents/skills/.*|deptrac\.yaml|composer\.(json|lock)|phpunit.*\.xml\.dist|psalm\.xml(\.dist)?|phpstan\.(neon|neon\.dist)|phpmd.*\.xml(\.dist)?|phpinsights.*\.php|infection\.(json|json5)(\.dist)?|phpcs\.xml(\.dist)?|\.php-cs-fixer\.dist\.php|docker-compose.*\.ya?ml|Dockerfile.*|scripts/.*|\.github/workflows/.*|\.github/actions/.*|\.github/linters/.*|\.github/required-pr-checks\.txt|\.prettierrc(\..*)?|\.prettierignore|(.*/)?prettier\.config\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?\.markdownlint(\..*)?|(.*/)?markdownlint\.config\.(js|mjs|cjs|json|ya?ml)|(.*/)?\.yamllint(\..*)?|(\.codecov|codecov)\.ya?ml|\.coderabbit\.ya?ml|\.qlty/.*|(\.qlty|qlty)\.toml|\.snyk|(\.spectral|spectral)\.ya?ml|(.*/)?spectral\.config\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?eslint\.config\.(js|mjs|cjs|ts)|(.*/)?\.eslintrc(\..*)?|(.*/)?package(-lock)?\.json|(.*/)?pnpm-lock\.yaml|(.*/)?yarn\.lock|(.*/)?\.graphqlrc(\..*)?|(.*/)?graphql\.config\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?graphql-inspector(\.config)?\.(js|mjs|cjs|ts|json|ya?ml)|(.*/)?(\.openapi-diff|openapi-diff)(\.config)?\.(js|mjs|cjs|ts|json|ya?ml))$'
GATE_DEFINITION_VALIDATION_SUPPORT_FILE_PATTERN='^(docs/onboarding\.md|tests/CLI/bats/make_ai_review_loop_tests\.bats|specs/autonomous/[0-9]{8}-[0-9]{6}-(ai-review-loop|bmad-fr-nfr-review-gate|bmad-fr-nfr-reviewer-system-design-patterns|ci-gate|gate-definition|required-pr-checks-gate|review-loop)/(architecture|epics|implementation-readiness|manual-evidence|prd|product-brief-distillate|product-brief|research|run-summary|skill-sweep)\.md)$'
GATE_DEFINITION_FILE_PATTERN="(${GATE_DEFINITION_CORE_FILE_PATTERN}|${GATE_DEFINITION_VALIDATION_SUPPORT_FILE_PATTERN})"
GATE_DEFINITION_CHANGES_PRESENT=false
GATE_DEFINITION_EXTERNAL_VALIDATION_PASSED=false
PR_COMMENT_TIMELINE=''
VALIDATED_COMMENT_EVIDENCE_URLS=''

capture_review_comment_snapshot() {
  REVIEW_COMMENT_SNAPSHOT_STARTED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  export REVIEW_COMMENT_SNAPSHOT_STARTED_AT
  PR_COMMENT_TIMELINE=''
}

assert_review_comment_snapshot_valid() {
  : "${REVIEW_COMMENT_SNAPSHOT_STARTED_AT:?Capture REVIEW_COMMENT_SNAPSHOT_STARTED_AT with capture_review_comment_snapshot before addressing PR comments}"
  printf '%s\n' "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" | rg -q '^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$'
  : "${PR_COMMENT_EVIDENCE:?Set PR_COMMENT_EVIDENCE to the review-comment evidence ledger}"
  test -r "$PR_COMMENT_EVIDENCE"
  rg -q "^SNAPSHOT_STARTED_AT=$REVIEW_COMMENT_SNAPSHOT_STARTED_AT$" "$PR_COMMENT_EVIDENCE"
  rg -q "^SNAPSHOT_CAPTURED_BY=code-review-skill$" "$PR_COMMENT_EVIDENCE"
}

iso_time_epoch() {
  date -u -d "$1" +%s
}

body_hash_from_b64() {
  printf '%s' "$1" | base64 --decode | sha256sum | awk '{ print $1 }'
}

review_thread_comment_items() {
  local owner="$1" repo="$2" comparison="$3" query thread_query page_json page_items has_next cursor thread_refs thread_id thread_cursor thread_has_next thread_json
  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviewThreads(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{id comments(first:100){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" --arg comparison "$comparison" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.reviewThreads.nodes[].comments.nodes[] | select((if $comparison == "before" then effectiveAt <= $snapshot elif $comparison == "after" then effectiveAt > $snapshot else true end) and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    printf '%s\n' "$page_items"
    thread_refs="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.nodes[] | select(.comments.pageInfo.hasNextPage == true) | "\(.id)|\(.comments.pageInfo.endCursor)"')"
    while IFS='|' read -r thread_id thread_cursor; do
      [ -n "$thread_id" ] || continue
      thread_query='query($threadId:ID!,$cursor:String){node(id:$threadId){... on PullRequestReviewThread{comments(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
      while :; do
        thread_json="$(gh api graphql -f threadId="$thread_id" -f cursor="$thread_cursor" -f query="$thread_query")"
        page_items="$(printf '%s\n' "$thread_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" --arg comparison "$comparison" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.node.comments.nodes[] | select((if $comparison == "before" then effectiveAt <= $snapshot elif $comparison == "after" then effectiveAt > $snapshot else true end) and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
        printf '%s\n' "$page_items"
        thread_has_next="$(printf '%s\n' "$thread_json" | jq -r '.data.node.comments.pageInfo.hasNextPage')"
        [ "$thread_has_next" = "true" ] || break
        thread_cursor="$(printf '%s\n' "$thread_json" | jq -r '.data.node.comments.pageInfo.endCursor')"
        test -n "$thread_cursor" && test "$thread_cursor" != "null"
      done
    done <<< "$thread_refs"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
}

load_pr_comment_timeline() {
  local owner repo query page_json page_items has_next cursor
  assert_review_comment_snapshot_valid
  [ -n "$PR_COMMENT_TIMELINE" ] && return 0
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  PR_COMMENT_TIMELINE=''

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){comments(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.nodes[] | select((.bodyText // "") | length > 0) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    PR_COMMENT_TIMELINE="${PR_COMMENT_TIMELINE}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.nodes[] | select((.bodyText // "") | length > 0) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    PR_COMMENT_TIMELINE="${PR_COMMENT_TIMELINE}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  PR_COMMENT_TIMELINE="${PR_COMMENT_TIMELINE}"$'\n'"$(review_thread_comment_items "$owner" "$repo" all)"
  PR_COMMENT_TIMELINE="$(printf '%s\n' "$PR_COMMENT_TIMELINE" | sed '/^[[:space:]]*$/d' | sort -u)"
}

comment_time_for_url() {
  load_pr_comment_timeline
  printf '%s\n' "$PR_COMMENT_TIMELINE" | awk -F'|' -v url="$1" '$1 == url { print ($3 > $2 ? $3 : $2); exit }'
}

assert_pr_commit_evidence() {
  local source_url="$1" source_time="$2" commit="$3" commit_time
  git rev-parse --verify "${commit}^{commit}" >/dev/null
  git merge-base --is-ancestor "$commit" "$LOCAL_HEAD"
  ! git merge-base --is-ancestor "$commit" "$TRUSTED_BASE_REF"
  commit_time="$(git show -s --format=%cI "$commit")"
  test "$(iso_time_epoch "$commit_time")" -gt "$(iso_time_epoch "$source_time")"
  git log -1 --format=%B "$commit" | rg -Fq "$source_url"
}

comment_json_for_url() {
  local url="$1" comment_id
  case "$url" in
    *"#issuecomment-"*)
      comment_id="${url##*#issuecomment-}"
      gh api "repos/$PR_REPO/issues/comments/$comment_id"
      ;;
    *"#discussion_r"*)
      comment_id="${url##*#discussion_r}"
      gh api "repos/$PR_REPO/pulls/comments/$comment_id"
      ;;
    *)
      return 1
      ;;
  esac
}

comment_body_for_url() {
  comment_json_for_url "$1" | jq -r .body
}

comment_author_for_url() {
  comment_json_for_url "$1" | jq -r .user.login
}

assert_evidence_comment_actor() {
  local reply_url="$1" pr_author reply_author trusted_actors
  pr_author="$(gh pr view "$PR" --repo "$PR_REPO" --json author --jq .author.login)"
  reply_author="$(comment_author_for_url "$reply_url")"
  if [ "$reply_author" = "$pr_author" ]; then
    return 0
  fi
  : "${PR_COMMENT_TRUSTED_EVIDENCE_ACTORS:?Set comma-separated trusted bot/user logins allowed to post review evidence comments}"
  trusted_actors=",$PR_COMMENT_TRUSTED_EVIDENCE_ACTORS,"
  printf '%s\n' "$trusted_actors" | rg -Fq ",$reply_author,"
}

assert_pr_reply_evidence() {
  local source_url="$1" source_time="$2" reply_url="$3" reply_time reply_body
  case "$reply_url" in
    "https://github.com/${PR_REPO}/pull/${PR}"#*) ;;
    *) return 1 ;;
  esac
  [ "$reply_url" != "$source_url" ]
  reply_time="$(comment_time_for_url "$reply_url")"
  test -n "$source_time" && test -n "$reply_time"
  test "$(iso_time_epoch "$reply_time")" -gt "$(iso_time_epoch "$source_time")"
  assert_evidence_comment_actor "$reply_url"
  reply_body="$(comment_body_for_url "$reply_url")"
  printf '%s\n' "$reply_body" | rg -Fq "EVIDENCE_SOURCE: $source_url"
  printf '%s\n' "$reply_body" | rg -iq '^EVIDENCE_ACTION:[[:space:]]*(addressed|resolved|fixed|implemented|updated|changed|applied)[[:space:]]*$'
}

assert_pr_decline_evidence() {
  local source_url="$1" source_time="$2" reply_url="$3" reply_time reply_body
  case "$reply_url" in
    "https://github.com/${PR_REPO}/pull/${PR}"#*) ;;
    *) return 1 ;;
  esac
  [ "$reply_url" != "$source_url" ]
  reply_time="$(comment_time_for_url "$reply_url")"
  test -n "$source_time" && test -n "$reply_time"
  test "$(iso_time_epoch "$reply_time")" -gt "$(iso_time_epoch "$source_time")"
  assert_evidence_comment_actor "$reply_url"
  reply_body="$(comment_body_for_url "$reply_url")"
  printf '%s\n' "$reply_body" | rg -Fq "EVIDENCE_SOURCE: $source_url"
  printf '%s\n' "$reply_body" | rg -iq '^EVIDENCE_ACTION:[[:space:]]*(declined|stale|duplicate|not applicable|not needed|won.t fix|will not fix)[[:space:]]*$'
  printf '%s\n' "$reply_body" | rg -iq '^EVIDENCE_REASON:[[:space:]].+'
}

latest_addressed_evidence_time() {
  local entry_type source_url entry_action entry_evidence source_time evidence_time source_epoch evidence_epoch latest
  latest=0
  [ -r "${PR_COMMENT_EVIDENCE:-}" ] || return 0
  while IFS='|' read -r entry_type source_url entry_action entry_evidence; do
    [ "$entry_type" = "COMMENT" ] || continue
    source_time="$(comment_time_for_url "$source_url")"
    if [ -n "$source_time" ]; then
      source_epoch="$(iso_time_epoch "$source_time")"
      if [ "$source_epoch" -gt "$latest" ]; then
        latest="$source_epoch"
      fi
    fi
    evidence_time=''
    case "$entry_action" in
      commit) evidence_time="$(git show -s --format=%cI "$entry_evidence" 2>/dev/null || true)" ;;
      reply|decline) evidence_time="$(comment_time_for_url "$entry_evidence")" ;;
    esac
    if [ -n "$evidence_time" ]; then
      evidence_epoch="$(iso_time_epoch "$evidence_time")"
      if [ "$evidence_epoch" -gt "$latest" ]; then
        latest="$evidence_epoch"
      fi
    fi
  done < "$PR_COMMENT_EVIDENCE"
  printf '%s\n' "$latest"
}

assert_comment_evidence_items() {
  local evidence_items="$1" source_url source_time source_updated_at source_effective_time source_body_b64 source_body_hash meta_line meta_type meta_url meta_updated_at meta_body_hash evidence_line entry_type entry_url entry_action entry_evidence missing_count
  assert_review_comment_snapshot_valid
  : "${PR_COMMENT_EVIDENCE:?Set PR_COMMENT_EVIDENCE to a ledger that records PR_HEAD and COMMENT|url|action|evidence lines}"
  test -r "$PR_COMMENT_EVIDENCE"
  rg -Fqx "PR_HEAD=$LOCAL_HEAD" "$PR_COMMENT_EVIDENCE"
  evidence_items="$(printf '%s\n' "$evidence_items" | sed '/^[[:space:]]*$/d' | sort -u)"
  [ -n "$evidence_items" ] || return 0
  missing_count=0
  while IFS='|' read -r source_url source_time source_updated_at source_body_b64; do
    source_effective_time="$source_time"
    if [ -n "$source_updated_at" ] && [ "$source_updated_at" \> "$source_effective_time" ]; then
      source_effective_time="$source_updated_at"
    fi
    source_body_hash="$(body_hash_from_b64 "$source_body_b64")"
    meta_line="$(awk -F'|' -v url="$source_url" '$1 == "COMMENT_META" && $2 == url { print; found = 1; exit } END { if (!found) exit 1 }' "$PR_COMMENT_EVIDENCE" || true)"
    if [ -z "$meta_line" ]; then
      printf 'Missing comment metadata evidence: %s\n' "$source_url" >&2
      missing_count=$((missing_count + 1))
      continue
    fi
    IFS='|' read -r meta_type meta_url meta_updated_at meta_body_hash <<EOF
$meta_line
EOF
    if [ "$meta_type" != "COMMENT_META" ] || [ "$meta_url" != "$source_url" ] || [ "$meta_updated_at" != "$source_updated_at" ] || [ "$meta_body_hash" != "$source_body_hash" ]; then
      printf 'Invalid comment metadata evidence: %s\n' "$source_url" >&2
      missing_count=$((missing_count + 1))
      continue
    fi
    evidence_line="$(awk -F'|' -v url="$source_url" '$1 == "COMMENT" && $2 == url { print; found = 1; exit } END { if (!found) exit 1 }' "$PR_COMMENT_EVIDENCE" || true)"
    if [ -z "$evidence_line" ]; then
      printf 'Missing comment evidence: %s\n' "$source_url" >&2
      missing_count=$((missing_count + 1))
      continue
    fi
    IFS='|' read -r entry_type entry_url entry_action entry_evidence <<EOF
$evidence_line
EOF
    if [ "$entry_type" != "COMMENT" ] || [ "$entry_url" != "$source_url" ] || [ -z "$entry_evidence" ]; then
      printf 'Invalid comment evidence: %s\n' "$source_url" >&2
      missing_count=$((missing_count + 1))
      continue
    fi
    case "$entry_action" in
      commit) assert_pr_commit_evidence "$source_url" "$source_effective_time" "$entry_evidence" || missing_count=$((missing_count + 1)) ;;
      reply)
        if assert_pr_reply_evidence "$source_url" "$source_effective_time" "$entry_evidence"; then
          VALIDATED_COMMENT_EVIDENCE_URLS="${VALIDATED_COMMENT_EVIDENCE_URLS}"$'\n'"${entry_evidence}"
        else
          missing_count=$((missing_count + 1))
        fi
        ;;
      decline)
        if assert_pr_decline_evidence "$source_url" "$source_effective_time" "$entry_evidence"; then
          VALIDATED_COMMENT_EVIDENCE_URLS="${VALIDATED_COMMENT_EVIDENCE_URLS}"$'\n'"${entry_evidence}"
        else
          missing_count=$((missing_count + 1))
        fi
        ;;
      *) missing_count=$((missing_count + 1)) ;;
    esac
  done <<< "$evidence_items"
  test "$missing_count" -eq 0
}

assert_no_unresolved_comments() {
  local owner repo query page_json page_count total_count has_next cursor
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviewThreads(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{isResolved comments(first:1){nodes{id}}}}}}}'
  total_count=0
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_count="$(printf '%s\n' "$page_json" | jq '[.data.repository.pullRequest.reviewThreads.nodes[] | select(.isResolved == false and (.comments.nodes | length > 0))] | length')"
    total_count=$((total_count + page_count))
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
  test "$total_count" -eq 0
}

non_thread_comment_items() {
  local owner="$1" repo="$2" comparison="$3" query page_json page_items has_next cursor
  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){comments(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" --arg comparison "$comparison" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.comments.nodes[] | select((if $comparison == "before" then effectiveAt <= $snapshot elif $comparison == "after" then effectiveAt > $snapshot else true end) and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    printf '%s\n' "$page_items"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" --arg comparison "$comparison" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.reviews.nodes[] | select((if $comparison == "before" then effectiveAt <= $snapshot elif $comparison == "after" then effectiveAt > $snapshot else true end) and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    printf '%s\n' "$page_items"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
}

write_comment_metadata_evidence() {
  local owner repo metadata_items existing_comment_lines source_url source_time source_updated_at source_body_b64 source_body_hash tmp_file
  assert_review_comment_snapshot_valid
  : "${LOCAL_HEAD:?Set LOCAL_HEAD to the final local head before writing PR comment evidence metadata}"
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  metadata_items="$(
    {
      non_thread_comment_items "$owner" "$repo" before
      review_thread_comment_items "$owner" "$repo" before
    } | sed '/^[[:space:]]*$/d' | sort -u
  )"
  existing_comment_lines="$(awk -F'|' '$1 == "COMMENT" { print }' "$PR_COMMENT_EVIDENCE" 2>/dev/null || true)"
  tmp_file="$(mktemp)"
  {
    printf 'SNAPSHOT_STARTED_AT=%s\n' "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT"
    printf 'SNAPSHOT_CAPTURED_BY=code-review-skill\n'
    printf 'PR_HEAD=%s\n' "$LOCAL_HEAD"
    while IFS='|' read -r source_url source_time source_updated_at source_body_b64; do
      [ -n "$source_url" ] || continue
      source_body_hash="$(body_hash_from_b64 "$source_body_b64")"
      printf 'COMMENT_META|%s|%s|%s\n' "$source_url" "$source_updated_at" "$source_body_hash"
    done <<< "$metadata_items"
    printf '%s\n' "$existing_comment_lines"
  } > "$tmp_file"
  mv "$tmp_file" "$PR_COMMENT_EVIDENCE"
}

assert_non_thread_comment_evidence() {
  local owner repo query page_json page_items evidence_items has_next cursor
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  evidence_items=''

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){comments(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.comments.nodes[] | select(effectiveAt <= $snapshot and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    evidence_items="${evidence_items}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.reviews.nodes[] | select(effectiveAt <= $snapshot and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    evidence_items="${evidence_items}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  assert_comment_evidence_items "$evidence_items"
}

assert_review_thread_comment_evidence() {
  local owner repo evidence_items
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  evidence_items="$(review_thread_comment_items "$owner" "$repo" before)"
  assert_comment_evidence_items "$evidence_items"
}

qualifying_approval_review_urls() {
  local owner repo query page_json page_urls has_next cursor min_submitted_epoch pr_author
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  min_submitted_epoch="$(latest_addressed_evidence_time)"
  pr_author="$(gh pr view "$PR" --repo "$PR_REPO" --json author --jq .author.login)"
  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url state submittedAt author{login} authorAssociation commit{oid}}}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_urls="$(printf '%s\n' "$page_json" | jq -r --arg head "$LOCAL_HEAD" --arg pr_author "$pr_author" --argjson min_submitted_epoch "$min_submitted_epoch" '.data.repository.pullRequest.reviews.nodes[] | select(.state == "APPROVED" and .commit.oid == $head and (.author.login // "") != $pr_author and (.authorAssociation | IN("OWNER", "MEMBER", "COLLABORATOR")) and ($min_submitted_epoch == 0 or (.submittedAt | fromdateiso8601) > $min_submitted_epoch)) | .url')"
    printf '%s\n' "$page_urls"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
}

non_action_approval_review_urls() {
  local owner repo query page_json page_urls has_next cursor min_submitted_epoch pr_author
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  min_submitted_epoch="$(latest_addressed_evidence_time)"
  pr_author="$(gh pr view "$PR" --repo "$PR_REPO" --json author --jq .author.login)"
  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url state submittedAt author{login} authorAssociation commit{oid} bodyText}}}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_urls="$(printf '%s\n' "$page_json" | jq -r --arg head "$LOCAL_HEAD" --arg pr_author "$pr_author" --argjson min_submitted_epoch "$min_submitted_epoch" '.data.repository.pullRequest.reviews.nodes[] | select(.state == "APPROVED" and .commit.oid == $head and (.author.login // "") != $pr_author and (.authorAssociation | IN("OWNER", "MEMBER", "COLLABORATOR")) and ($min_submitted_epoch == 0 or (.submittedAt | fromdateiso8601) > $min_submitted_epoch) and (((.bodyText // "") | length) == 0 or ((.bodyText // "") | gsub("\\r"; "") | gsub("^[[:space:]]+|[[:space:]]+$"; "") | test("^FINAL_APPROVAL_NO_ACTION:[[:space:]]*true$"; "i")))) | .url')"
    printf '%s\n' "$page_urls"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
}

assert_no_unaccounted_comments_after_snapshot() {
  local owner repo query page_json page_items after_items has_next cursor allowed_evidence_urls source_url source_time source_updated_at source_body_b64 missing_count
  assert_review_comment_snapshot_valid
  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  after_items=''

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){comments(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.comments.nodes[] | select(effectiveAt > $snapshot and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    after_items="${after_items}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.comments.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  query='query($owner:String!,$repo:String!,$pr:Int!,$cursor:String){repository(owner:$owner,name:$repo){pullRequest(number:$pr){reviews(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{url createdAt updatedAt bodyText}}}}}'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -F pr="$PR" -f query="$query")"
    fi
    page_items="$(printf '%s\n' "$page_json" | jq -r --arg snapshot "$REVIEW_COMMENT_SNAPSHOT_STARTED_AT" 'def effectiveAt: if ((.updatedAt // .createdAt) > .createdAt) then (.updatedAt // .createdAt) else .createdAt end; .data.repository.pullRequest.reviews.nodes[] | select(effectiveAt > $snapshot and ((.bodyText // "") | length > 0)) | "\(.url)|\(.createdAt)|\(.updatedAt)|\((.bodyText // "") | @base64)"')"
    after_items="${after_items}"$'\n'"${page_items}"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.pullRequest.reviews.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done

  after_items="${after_items}"$'\n'"$(review_thread_comment_items "$owner" "$repo" after)"
  allowed_evidence_urls="$(
    {
      printf '%s\n' "$VALIDATED_COMMENT_EVIDENCE_URLS"
      non_action_approval_review_urls
    } | sed '/^[[:space:]]*$/d' | sort -u
  )"
  missing_count=0
  while IFS='|' read -r source_url source_time source_updated_at source_body_b64; do
    [ -n "$source_url" ] || continue
    if ! printf '%s\n' "$allowed_evidence_urls" | rg -Fxq "$source_url"; then
      printf 'New PR comment after snapshot requires restarting comment evidence: %s\n' "$source_url" >&2
      missing_count=$((missing_count + 1))
    fi
  done <<< "$(printf '%s\n' "$after_items" | sed '/^[[:space:]]*$/d' | sort -u)"
  test "$missing_count" -eq 0
}

assert_no_forbidden_suppressions() {
  local diff_output forbidden_pattern quality_config_changes
  diff_output="$(git diff --unified=0 "$TRUSTED_BASE_REF"...HEAD)" || {
    echo "Unable to compute PR diff for suppression scan" >&2
    exit 1
  }

  forbidden_pattern="$(forbidden_suppression_pattern)"
  if printf '%s\n' "$diff_output" | rg '^\+[^+]' | rg -n "$forbidden_pattern"; then
    echo "Forbidden suppression/ignore directive found in PR diff" >&2
    exit 1
  fi

  quality_config_changes="$(git diff --name-only "$TRUSTED_BASE_REF"...HEAD | rg '(^|/)(.*baseline.*|psalm\.xml(\.dist)?|phpstan\.(neon|neon\.dist)|phpmd.*\.xml(\.dist)?|phpinsights.*\.php|infection\.(json|json5)(\.dist)?|phpcs\.xml(\.dist)?|\.php-cs-fixer\.dist\.php)$' || true)"
  if [ -n "$quality_config_changes" ] && [ "$GATE_DEFINITION_CHANGES_PRESENT" != "true" ]; then
    printf '%s\n' "$quality_config_changes"
    echo "Quality tool suppression/baseline/config changes block completion; remove suppression/ignore changes instead" >&2
    exit 1
  fi
}

forbidden_suppression_pattern() {
  printf '%s\n' '@Suppress''Warnings|@psalm-''suppress|@phpstan-''ignore|phpstan-''ignore|phpcs:(''ignore|disable)|@infection-''ignore|@codeCoverage''Ignore|@phpinsights-''ignore|@codingStandards''Ignore|codingStandards''Ignore'
}

trusted_base_required_checks_manifest() {
  git show "$TRUSTED_BASE_REF:.github/required-pr-checks.txt" 2>/dev/null
}

gate_definition_bootstrap_required() {
  [ "$GATE_DEFINITION_CHANGES_PRESENT" = "true" ] &&
    ! git cat-file -e "$TRUSTED_BASE_REF:.github/required-pr-checks.txt" 2>/dev/null &&
    test -r .github/required-pr-checks.txt
}

required_checks_manifest() {
  local manifest
  manifest="$(trusted_base_required_checks_manifest || true)"
  if [ -n "$manifest" ]; then
    printf '%s\n' "$manifest"
    return 0
  fi

  [ "$GATE_DEFINITION_CHANGES_PRESENT" = "true" ] || assert_gate_definition_change_scope
  if gate_definition_bootstrap_required; then
    assert_gate_definition_external_validation
    cat .github/required-pr-checks.txt
    return 0
  fi

  return 1
}

assert_gate_definition_change_scope() {
  local all_changes gate_definition_changes non_gate_definition_changes
  GATE_DEFINITION_CHANGES_PRESENT=false
  all_changes="$(git diff --name-only "$TRUSTED_BASE_REF"...HEAD)"
  gate_definition_changes="$(printf '%s\n' "$all_changes" | rg "$GATE_DEFINITION_FILE_PATTERN" || true)"
  if [ -n "$gate_definition_changes" ]; then
    GATE_DEFINITION_CHANGES_PRESENT=true
    non_gate_definition_changes="$(printf '%s\n' "$all_changes" | rg -v "$GATE_DEFINITION_FILE_PATTERN" || true)"
    printf '%s\n' "$gate_definition_changes"
    if [ -n "$non_gate_definition_changes" ]; then
      printf '%s\n' "$non_gate_definition_changes"
      if gate_definition_bootstrap_required; then
        echo "Initial CI/review gate bootstrap includes non-gate changes; trusted external validation is required before completion" >&2
        return 0
      fi
      echo "CI/review gate definition changes must be isolated in a dedicated gate-definition PR with no product, runtime, or unrelated test-code changes" >&2
      exit 1
    fi
  fi
}

assert_gate_definition_external_validation() {
  local trusted_base_oid validation_mode run_url run_repo run_id run_json run_head_sha workflow_path workflow_sha actual_workflow_sha artifacts_json artifact_id artifact_dir artifact_zip artifact_payload_dir artifact_evidence_file artifact_evidence_file_count trusted_validator_repos trusted_validator_heads trusted_workflows trusted_workflow_shas
  [ "$GATE_DEFINITION_CHANGES_PRESENT" = "true" ] || return 0
  [ "$GATE_DEFINITION_EXTERNAL_VALIDATION_PASSED" = "true" ] && return 0
  trusted_base_oid="${GATED_BASE_OID:-$BASE_OID}"
  : "${GATE_DEFINITION_VALIDATION_EVIDENCE:?Set GATE_DEFINITION_VALIDATION_EVIDENCE to an external trusted-base validation ledger for dedicated gate-definition PRs}"
  test -r "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  case "$(cd "$(dirname "$GATE_DEFINITION_VALIDATION_EVIDENCE")" && pwd)/$(basename "$GATE_DEFINITION_VALIDATION_EVIDENCE")" in
    "$(pwd)"/*)
      echo "Gate-definition validation evidence must be outside the PR worktree" >&2
      exit 1
      ;;
  esac
  rg -q "^PR_HEAD=$LOCAL_HEAD$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  rg -q "^TRUSTED_BASE=$trusted_base_oid$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  rg -q "^VALIDATION_MODE=(trusted-base|immutable-external)$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  rg -q "^TRUSTED_BASE_GATE=PASS$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  rg -q "^TRUSTED_MANIFEST_CHECKS=PASS$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  rg -q "^IMMUTABLE_RUN_URL=https://github\\.com/.+/actions/runs/[0-9]+$" "$GATE_DEFINITION_VALIDATION_EVIDENCE"
  validation_mode="$(sed -n 's/^VALIDATION_MODE=//p' "$GATE_DEFINITION_VALIDATION_EVIDENCE" | tail -n 1)"
  run_url="$(sed -n 's/^IMMUTABLE_RUN_URL=//p' "$GATE_DEFINITION_VALIDATION_EVIDENCE" | tail -n 1)"
  run_repo="$(printf '%s\n' "$run_url" | sed -E 's#^https://github.com/([^/]+/[^/]+)/actions/runs/[0-9]+$#\1#')"
  test -n "$run_repo" && test "$run_repo" != "$run_url"
  case "$validation_mode" in
    trusted-base)
      test "$run_repo" = "$PR_REPO"
      ;;
    immutable-external)
      : "${GATE_DEFINITION_TRUSTED_VALIDATOR_REPOS:?Set comma-separated trusted validator repositories for immutable-external gate validation}"
      trusted_validator_repos=",$GATE_DEFINITION_TRUSTED_VALIDATOR_REPOS,"
      printf '%s\n' "$trusted_validator_repos" | rg -Fq ",$run_repo,"
      ;;
    *)
      return 1
      ;;
  esac
  run_id="${run_url##*/}"
  run_json="$(gh api "repos/$run_repo/actions/runs/$run_id")"
  printf '%s\n' "$run_json" | jq -e --arg run_repo "$run_repo" '.status == "completed" and .conclusion == "success" and .repository.full_name == $run_repo and ((.path // "") | test("^\\.github/workflows/"))'
  run_head_sha="$(printf '%s\n' "$run_json" | jq -r .head_sha)"
  workflow_path="$(printf '%s\n' "$run_json" | jq -r .path)"
  case "$validation_mode" in
    trusted-base)
      : "${GATE_DEFINITION_TRUSTED_BASE_WORKFLOWS:?Set comma-separated trusted-base workflow paths allowed to validate gate-definition PRs}"
      trusted_workflows=",$GATE_DEFINITION_TRUSTED_BASE_WORKFLOWS,"
      printf '%s\n' "$trusted_workflows" | rg -Fq ",$workflow_path,"
      test "$run_head_sha" = "$trusted_base_oid"
      workflow_sha="$(git rev-parse "$TRUSTED_BASE_REF:$workflow_path")"
      ;;
    immutable-external)
      : "${GATE_DEFINITION_TRUSTED_VALIDATOR_WORKFLOWS:?Set comma-separated repo:path workflow allowlist entries for immutable-external validation}"
      : "${GATE_DEFINITION_TRUSTED_VALIDATOR_HEAD_SHAS:?Set comma-separated repo|head_sha allowlist entries for immutable-external validation}"
      : "${GATE_DEFINITION_TRUSTED_VALIDATOR_WORKFLOW_SHAS:?Set pipe-delimited repo|path|sha workflow hash allowlist entries for immutable-external validation}"
      trusted_validator_heads=",$GATE_DEFINITION_TRUSTED_VALIDATOR_HEAD_SHAS,"
      printf '%s\n' "$trusted_validator_heads" | rg -Fq ",$run_repo|$run_head_sha,"
      trusted_workflows=",$GATE_DEFINITION_TRUSTED_VALIDATOR_WORKFLOWS,"
      printf '%s\n' "$trusted_workflows" | rg -Fq ",$run_repo:$workflow_path,"
      workflow_sha="$(printf '%s\n' "$GATE_DEFINITION_TRUSTED_VALIDATOR_WORKFLOW_SHAS" | awk -F'|' -v repo="$run_repo" -v path="$workflow_path" '$1 == repo && $2 == path { print $3; exit }')"
      test -n "$workflow_sha"
      actual_workflow_sha="$(gh api "repos/$run_repo/contents/$workflow_path?ref=$run_head_sha" --jq .sha)"
      test "$actual_workflow_sha" = "$workflow_sha"
      ;;
  esac
  artifacts_json="$(gh api "repos/$run_repo/actions/runs/$run_id/artifacts")"
  artifact_id="$(printf '%s\n' "$artifacts_json" | jq -r '.artifacts[] | select(.name == "trusted-base-gate-evidence" and .expired == false) | .id' | head -n 1)"
  test -n "$artifact_id" && test "$artifact_id" != "null"
  artifact_dir="$(mktemp -d)"
  artifact_zip="$artifact_dir/artifact.zip"
  artifact_payload_dir="$artifact_dir/payload"
  gh api "repos/$run_repo/actions/artifacts/$artifact_id/zip" > "$artifact_zip"
  mkdir -p "$artifact_payload_dir"
  unzip -q "$artifact_zip" -d "$artifact_payload_dir"
  artifact_evidence_file_count="$(find "$artifact_payload_dir" -type f -name trusted-base-gate-evidence.env | wc -l | tr -d '[:space:]')"
  test "$artifact_evidence_file_count" -eq 1
  artifact_evidence_file="$(find "$artifact_payload_dir" -type f -name trusted-base-gate-evidence.env -print -quit)"
  rg -Fqx "PR_HEAD=$LOCAL_HEAD" "$artifact_evidence_file"
  rg -Fqx "TRUSTED_BASE=$trusted_base_oid" "$artifact_evidence_file"
  rg -Fqx "TRUSTED_BASE_GATE=PASS" "$artifact_evidence_file"
  rg -Fqx "TRUSTED_MANIFEST_CHECKS=PASS" "$artifact_evidence_file"
  rg -Fqx "VALIDATION_MODE=$validation_mode" "$artifact_evidence_file"
  rg -Fqx "RUN_REPO=$run_repo" "$artifact_evidence_file"
  rg -Fqx "RUN_ID=$run_id" "$artifact_evidence_file"
  rg -Fqx "WORKFLOW_PATH=$workflow_path" "$artifact_evidence_file"
  rg -Fqx "WORKFLOW_SHA=$workflow_sha" "$artifact_evidence_file"
  GATE_DEFINITION_EXTERNAL_VALIDATION_PASSED=true
}

assert_head_approved() {
  test "$(qualifying_approval_review_urls | sed '/^[[:space:]]*$/d' | wc -l)" -gt 0
}

assert_required_checks() {
  local checks_json owner repo query required_checks required_check required_type required_name required_source required_count page_json page_checks has_next cursor branch_query branch_json branch_required_checks branch_required_check branch_required_kind branch_required_name branch_required_source manifest_matches manifest_required_check manifest_type manifest_name manifest_source
  : "${BASE_REF:?Set BASE_REF to the PR base branch name before asserting required checks}"
  required_checks="$(required_checks_manifest || true)"
  if [ -z "$required_checks" ]; then
    echo "Trusted required-check manifest is unavailable; gate-definition bootstrap requires externally validated .github/required-pr-checks.txt" >&2
    exit 1
  fi

  owner="${PR_REPO%%/*}"
  repo="${PR_REPO#*/}"
  query='query($owner:String!,$repo:String!,$oid:GitObjectID!,$cursor:String){repository(owner:$owner,name:$repo){object(oid:$oid){... on Commit{statusCheckRollup{contexts(first:100,after:$cursor){pageInfo{hasNextPage endCursor} nodes{__typename ... on CheckRun{name conclusion status detailsUrl checkSuite{app{slug}}} ... on StatusContext{context state targetUrl creator{login}}}}}}}}}'
  checks_json='[]'
  cursor=''
  while :; do
    if [ -n "$cursor" ]; then
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -f oid="$LOCAL_HEAD" -f cursor="$cursor" -f query="$query")"
    else
      page_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -f oid="$LOCAL_HEAD" -f query="$query")"
    fi
    page_checks="$(printf '%s\n' "$page_json" | jq '[.data.repository.object.statusCheckRollup.contexts.nodes[] | if .__typename == "CheckRun" then {type: "check_run", name: .name, state: (.conclusion // .status), source: (.checkSuite.app.slug // ""), url: (.detailsUrl // "")} else {type: "status_context", name: .context, state: .state, source: (.creator.login // ""), url: (.targetUrl // "")} end]')"
    checks_json="$(jq -n --argjson existing "$checks_json" --argjson page "$page_checks" '$existing + $page')"
    has_next="$(printf '%s\n' "$page_json" | jq -r '.data.repository.object.statusCheckRollup.contexts.pageInfo.hasNextPage')"
    [ "$has_next" = "true" ] || break
    cursor="$(printf '%s\n' "$page_json" | jq -r '.data.repository.object.statusCheckRollup.contexts.pageInfo.endCursor')"
    test -n "$cursor" && test "$cursor" != "null"
  done
  printf '%s\n' "$checks_json" | jq -e 'length > 0 and all(.[]; .state | IN("SUCCESS", "SKIPPED", "NEUTRAL"))'
  printf '%s\n' "$checks_json" | jq -e --arg repo_url "https://github.com/$PR_REPO/" 'all(.[]; if .type == "check_run" and .source == "github-actions" then ((.url // "") | startswith($repo_url + "actions/runs/")) else true end)'
  required_count=0
  while IFS= read -r required_check; do
    case "$required_check" in
      ""|\#*) continue ;;
    esac
    IFS='|' read -r required_type required_name required_source <<EOF
$required_check
EOF
    test -n "$required_type" && test -n "$required_name" && test -n "$required_source"
    ([ "$required_type" = "check_run" ] || [ "$required_type" = "status_context" ])
    required_count=$((required_count + 1))
    printf '%s\n' "$checks_json" | jq -e --arg type "$required_type" --arg name "$required_name" --arg source "$required_source" '[.[] | select(.type == $type and .name == $name and .source == $source)] as $matches | ($matches | length) > 0 and all($matches[]; .state == "SUCCESS")'
  done <<< "$required_checks"
  test "$required_count" -gt 0

  branch_query='query($owner:String!,$repo:String!,$baseRefName:String!){repository(owner:$owner,name:$repo){ref(qualifiedName:$baseRefName){branchProtectionRule{requiredStatusCheckContexts requiredStatusChecks{context app{slug}}}}}}'
  branch_json="$(gh api graphql -f owner="$owner" -f repo="$repo" -f baseRefName="$BASE_REF" -f query="$branch_query")"
  branch_required_checks="$(
    printf '%s\n' "$branch_json" |
      jq -r '
        (.data.repository.ref.branchProtectionRule // {}) as $rule |
        (($rule.requiredStatusCheckContexts // [])[] | "legacy|" + .),
        (($rule.requiredStatusChecks // [])[] | "app|" + .context + "|" + (.app.slug // ""))
      ' |
      sort -u
  )"
  while IFS= read -r branch_required_check; do
    case "$branch_required_check" in
      "") continue ;;
    esac
    IFS='|' read -r branch_required_kind branch_required_name branch_required_source <<EOF
$branch_required_check
EOF
    test -n "$branch_required_kind" && test -n "$branch_required_name"
    if [ -n "$branch_required_source" ]; then
      printf '%s\n' "$checks_json" | jq -e --arg name "$branch_required_name" --arg source "$branch_required_source" '[.[] | select(.name == $name and .source == $source)] as $matches | ($matches | length) > 0 and all($matches[]; .state == "SUCCESS")'
    else
      manifest_matches="$(printf '%s\n' "$required_checks" | awk -F'|' -v name="$branch_required_name" '$0 !~ /^[[:space:]]*(#|$)/ && NF == 3 && $2 == name { print }')"
      if [ -z "$manifest_matches" ]; then
        echo "Source-less branch required check '$branch_required_name' has no trusted manifest type/source mapping" >&2
        exit 1
      fi
      while IFS= read -r manifest_required_check; do
        IFS='|' read -r manifest_type manifest_name manifest_source <<EOF
$manifest_required_check
EOF
        test -n "$manifest_type" && test -n "$manifest_name" && test -n "$manifest_source"
        printf '%s\n' "$checks_json" | jq -e --arg type "$manifest_type" --arg name "$manifest_name" --arg source "$manifest_source" '[.[] | select(.type == $type and .name == $name and .source == $source)] as $matches | ($matches | length) > 0 and all($matches[]; .state == "SUCCESS")'
      done <<< "$manifest_matches"
    fi
  done <<< "$branch_required_checks"
}

wait_for_pr_state() {
  local attempt expected_base expected_base_ref
  expected_base="${GATED_BASE_OID:-$BASE_OID}"
  expected_base_ref="${GATED_BASE_REF:-$BASE_REF}"
  PR_STATE=''
  for attempt in $(seq 1 30); do
    PR_STATE="$(gh pr view "$PR" --repo "$PR_REPO" --json state,mergeStateStatus,mergeable,reviewDecision,isDraft,reviewRequests,headRefOid,baseRefOid,baseRefName)"
    if printf '%s\n' "$PR_STATE" | jq -e --arg head "$LOCAL_HEAD" --arg base "$expected_base" --arg base_ref "$expected_base_ref" '
      .headRefOid == $head and
      .baseRefOid == $base and
      .baseRefName == $base_ref and
      .state == "OPEN" and
      .isDraft == false and
      .mergeable == "MERGEABLE" and
      (.mergeStateStatus | IN("CLEAN", "HAS_HOOKS")) and
      .reviewDecision == "APPROVED" and
      ([.reviewRequests[]?] | length) == 0
    ' >/dev/null; then
      return 0
    fi
    sleep 10
  done
  echo "PR final readiness predicate did not pass after waiting" >&2
  return 1
}

run_final_local_readiness_gate() {
  local ci_output ci_last_line ai_review_output ai_review_last_line current_base_json
  : "${PR_COMMENT_EVIDENCE:?Set PR_COMMENT_EVIDENCE to the review-comment evidence ledger path}"
  : "${PR_COMMENT_EVIDENCE_READY:?Append COMMENT evidence lines for every COMMENT_META line, then set PR_COMMENT_EVIDENCE_READY=true}"
  assert_gate_definition_change_scope
  assert_no_forbidden_suppressions
  test -z "$(git status --short)"
  ci_output="$(make ci 2>&1)"
  printf '%s\n' "$ci_output"
  ci_last_line="$(printf '%s\n' "$ci_output" | sed '/^[[:space:]]*$/d' | tail -n 1)"
  test "$ci_last_line" = "✅ CI checks successfully passed!"
  LOCAL_HEAD="$(git rev-parse HEAD)"
  write_comment_metadata_evidence
  VALIDATED_COMMENT_EVIDENCE_URLS=''
  assert_review_thread_comment_evidence
  assert_non_thread_comment_evidence
  ai_review_output="$(AI_REVIEW_BASE="$TRUSTED_BASE_REF" make ai-review-loop 2>&1)"
  printf '%s\n' "$ai_review_output"
  ai_review_last_line="$(printf '%s\n' "$ai_review_output" | sed '/^[[:space:]]*$/d' | tail -n 1)"
  test "$ai_review_last_line" = "AI review PASS."
  test "$LOCAL_HEAD" = "$(git rev-parse HEAD)"
  test -z "$(git status --short)"
  current_base_json="$(gh pr view "$PR" --repo "$PR_REPO" --json baseRefName,baseRefOid)"
  test "$BASE_OID" = "$(printf '%s\n' "$current_base_json" | jq -r .baseRefOid)"
  test "$BASE_REF" = "$(printf '%s\n' "$current_base_json" | jq -r .baseRefName)"
  VERIFIED_HEAD="$LOCAL_HEAD"
}

: "${PR:?Set PR to the pull request number}"
case "$PR" in
  ''|*[!0-9]*) echo "PR must be numeric" >&2; exit 1 ;;
esac
: "${PR_REPO:?Set PR_REPO=owner/repo for the base PR repository}"
PR_META="$(gh pr view "$PR" --repo "$PR_REPO" --json baseRefName,baseRefOid)"
BASE_REF="$(printf '%s\n' "$PR_META" | jq -r .baseRefName)"
BASE_OID="$(printf '%s\n' "$PR_META" | jq -r .baseRefOid)"
TRUSTED_BASE_REF="refs/remotes/pr-base/$PR"
git fetch "git@github.com:${PR_REPO}.git" "$BASE_REF:$TRUSTED_BASE_REF"
test "$(git rev-parse "$TRUSTED_BASE_REF")" = "$BASE_OID"
run_final_local_readiness_gate
GATED_BASE_REF="$BASE_REF"
GATED_BASE_OID="$BASE_OID"
GATED_VERIFIED_HEAD="$VERIFIED_HEAD"
test "$(git rev-parse HEAD)" = "$GATED_VERIFIED_HEAD"
LOCAL_HEAD="$GATED_VERIFIED_HEAD"
HEAD_REF="$(gh pr view "$PR" --repo "$PR_REPO" --json headRefName --jq .headRefName)"
PR_HEAD_REPO="$(gh pr view "$PR" --repo "$PR_REPO" --json headRepository --jq .headRepository.nameWithOwner)"
CURRENT_REPO="$(gh repo view --json nameWithOwner --jq .nameWithOwner)"
if [ "$PR_HEAD_REPO" = "$CURRENT_REPO" ]; then
  git push origin HEAD:"$HEAD_REF"
else
  if git remote get-url pr-head >/dev/null 2>&1; then
    git remote set-url pr-head "git@github.com:${PR_HEAD_REPO}.git"
  else
    git remote add pr-head "git@github.com:${PR_HEAD_REPO}.git"
  fi
  git push pr-head HEAD:"$HEAD_REF"
fi
PR_HEAD="$(gh pr view "$PR" --repo "$PR_REPO" --json headRefOid --jq .headRefOid)"
test "$LOCAL_HEAD" = "$PR_HEAD"
gh pr checks "$PR" --repo "$PR_REPO" --watch --interval 30
assert_gate_definition_external_validation
assert_required_checks
assert_no_unresolved_comments
VALIDATED_COMMENT_EVIDENCE_URLS=''
assert_review_thread_comment_evidence
assert_non_thread_comment_evidence
assert_no_unaccounted_comments_after_snapshot
assert_head_approved
wait_for_pr_state
printf '%s\n' "$PR_STATE" | jq -e --arg head "$LOCAL_HEAD" --arg base "$GATED_BASE_OID" --arg base_ref "$GATED_BASE_REF" '
  .headRefOid == $head and
  .baseRefOid == $base and
  .baseRefName == $base_ref and
  .state == "OPEN" and
  .isDraft == false and
  .mergeable == "MERGEABLE" and
  (.mergeStateStatus | IN("CLEAN", "HAS_HOOKS")) and
  .reviewDecision == "APPROVED" and
  ([.reviewRequests[]?] | length) == 0
'
assert_no_unresolved_comments
VALIDATED_COMMENT_EVIDENCE_URLS=''
assert_review_thread_comment_evidence
assert_non_thread_comment_evidence
assert_no_unaccounted_comments_after_snapshot
assert_required_checks
assert_head_approved
wait_for_pr_state
printf '%s\n' "$PR_STATE" | jq -e --arg head "$LOCAL_HEAD" --arg base "$GATED_BASE_OID" --arg base_ref "$GATED_BASE_REF" '
  .headRefOid == $head and
  .baseRefOid == $base and
  .baseRefName == $base_ref and
  .state == "OPEN" and
  .isDraft == false and
  .mergeable == "MERGEABLE" and
  (.mergeStateStatus | IN("CLEAN", "HAS_HOOKS")) and
  .reviewDecision == "APPROVED" and
  ([.reviewRequests[]?] | length) == 0
'
assert_no_unresolved_comments
VALIDATED_COMMENT_EVIDENCE_URLS=''
assert_review_thread_comment_evidence
assert_non_thread_comment_evidence
assert_no_unaccounted_comments_after_snapshot
assert_required_checks
assert_head_approved
```

Required state:

- PR `headRefOid` equals local `HEAD` before and after waiting for checks
- PR `baseRefName` and `baseRefOid` still equal the `BASE_REF` and `BASE_OID` used by Step 8 for CI, suppression scan, final AI review, trusted required-check manifest, and branch-protection query
- Commit-scoped status/check rollup for local `HEAD` is queried from the base PR repository and is non-empty; non-required contexts may be only `SUCCESS`, `SKIPPED`, or `NEUTRAL`
- Every trusted check name/type/source in trusted base `.github/required-pr-checks.txt` or externally validated bootstrap manifest and every live base-branch protection required status/check is present with state `SUCCESS`; source-less branch-protection contexts must have trusted manifest type/source mappings, GitHub Actions check URLs must point at the base PR repository, and an empty or incomplete check list is a blocker
- CI/review gate definition changes are isolated to a dedicated gate-definition PR after the manifest exists in trusted base, use the initial externally validated manifest-bootstrap path, or are absent
- Final direct GitHub GraphQL review-thread query after pushed-head verification shows 0 unresolved review comments
- `PR_COMMENT_EVIDENCE` records `SNAPSHOT_STARTED_AT=<timestamp>`, `SNAPSHOT_CAPTURED_BY=code-review-skill`, `PR_HEAD=<sha>`, validated `COMMENT_META|url|updatedAt|body_sha256` lines, and parseable, validated `COMMENT|url|action|evidence` lines for every review-thread, top-level PR issue, and review body comment from the auto-captured `REVIEW_COMMENT_SNAPSHOT_STARTED_AT` for pushed `HEAD`
- No non-evidence PR/review comments were created or edited after `REVIEW_COMMENT_SNAPSHOT_STARTED_AT`; if one appears, restart comment snapshot capture and evidence collection, except for an otherwise qualifying approval review on pushed `HEAD` whose body is empty or exactly `FINAL_APPROVAL_NO_ACTION: true`
- `state` is `OPEN`
- `isDraft` is `false`
- `mergeable` is `MERGEABLE`
- `mergeStateStatus` is `CLEAN` or `HAS_HOOKS`
- `reviewDecision` is `APPROVED`
- Direct review query shows at least one `APPROVED` review whose review commit equals local `HEAD`, whose `submittedAt` is later than the latest addressed comment or evidence event, and whose author is not the PR author and has `OWNER`, `MEMBER`, or `COLLABORATOR` association
- `reviewRequests` is empty

## Constraints (Parameters)

**NEVER**:

- Skip the autonomous AI review loop (`make ai-review-loop`)
- Skip committable suggestions
- Batch unrelated changes in one commit
- Ignore LLM prompts from reviewers
- Apply stale or invalid review suggestions blindly
- Commit without running verification
- Leave questions unanswered
- Accept organizational violations (invoke `code-organization` skill)
- Accept architecture violations (invoke `implementing-ddd-architecture` skill)
- Add suppression/ignore annotations to "fix" review comments or CI failures
- Finish task before `make ci` shows success message
- Finish when `make ai-review-loop` was not run after final `make ci`
- Finish while `git status --short` reports uncommitted changes after the final AI review loop
- Finish while local `HEAD` differs from the PR `headRefOid`
- Finish while GitHub reports failing checks, conflicts, draft status, requested changes, required review, or a blocking merge state

**ALWAYS**:

- Run `make ai-review-loop` before manually addressing PR comments and again after final `make ci` before push/ready
- Verify review findings against current code before applying them
- Commit each suggestion separately with URL reference
- Invoke `code-organization` skill for structural issues
- Invoke `implementing-ddd-architecture` skill for DDD violations
- Run `make ci` after implementing all changes
- Scan the PR diff for forbidden suppression/ignore directives
- Push and verify PR `headRefOid` equals local `HEAD`
- Check `gh pr checks` and `gh pr view` after pushing
- Address ALL CI failures before finishing
- Mark conversations resolved after addressing

## Format (Output)

**Commit Message Template**:

```
Apply review suggestion: [concise description]

[Optional: explanation if non-obvious]

Ref: https://github.com/owner/repo/pull/XX#discussion_rYYYYYYY
```

**Final Verification**:

```bash
✅ direct GitHub GraphQL review-thread query shows 0 unresolved
✅ make ci shows "CI checks successfully passed!"
✅ final make ai-review-loop reports PASS after make ci
✅ git status --short is empty after final ai-review-loop
✅ PR baseRefName/baseRefOid still match the Step 8 base used for CI, final ai-review-loop, trusted manifest, and branch-protection query
✅ local HEAD matches the PR headRefOid
✅ base-repository commit-scoped status/check rollup shows a non-empty complete check list, no disallowed non-required states, all trusted manifest type/name/source checks passing, all source-less branch-protection contexts mapped through the trusted manifest, and all live base-branch protection required status/checks passing on that pushed head
✅ final direct GitHub GraphQL review-thread query after pushed-head verification shows 0 unresolved
✅ PR_COMMENT_EVIDENCE has auto-captured snapshot metadata, pushed HEAD, comment updatedAt/body hashes, and validated trusted-author action/evidence for every snapshotted review-thread, top-level PR issue, and review body comment URL
✅ no non-evidence PR/review comments were created or edited after the captured snapshot except an otherwise qualifying approval review body that is empty or exactly FINAL_APPROVAL_NO_ACTION: true
✅ gh pr view shows state OPEN, no conflicts, no draft status, mergeStateStatus CLEAN/HAS_HOOKS, reviewDecision APPROVED, and no reviewRequests
✅ direct review query shows an APPROVED review on the pushed HEAD submitted after the latest addressed comment evidence by a non-author OWNER/MEMBER/COLLABORATOR reviewer
```

## Verification Checklist

- [ ] Initial autonomous AI review loop run via `make ai-review-loop`
- [ ] All PR comments retrieved via `make pr-comments`
- [ ] Comments categorized by type (suggestion/prompt/architecture/question/feedback)
- [ ] Stale or duplicate comments recorded with concrete reasons
- [ ] Architecture verified using appropriate skills
- [ ] `make deptrac` passes (0 violations)
- [ ] Committable suggestions applied and committed separately
- [ ] LLM prompts executed and implemented
- [ ] Questions answered (code or reply)
- [ ] General feedback evaluated and addressed
- [ ] `make ci` shows "✅ CI checks successfully passed!"
- [ ] PR diff scanned for forbidden suppression/ignore directives
- [ ] Final `make ai-review-loop` reports PASS after `make ci`
- [ ] `git status --short` is empty after final `make ai-review-loop`
- [ ] PR `baseRefName`/`baseRefOid` still match the Step 8 base used for `make ci`, final `make ai-review-loop`, trusted manifest, and branch-protection query
- [ ] Direct GitHub GraphQL review-thread query shows zero unresolved
- [ ] `PR_COMMENT_EVIDENCE` has auto-captured snapshot metadata, pushed `HEAD`, comment updatedAt/body hashes, and validated trusted-author action/evidence for every snapshotted review-thread, top-level PR issue, and review body comment URL
- [ ] No non-evidence PR/review comments were created or edited after the captured snapshot except an otherwise qualifying approval review body that is empty or exactly `FINAL_APPROVAL_NO_ACTION: true`
- [ ] Local `HEAD` pushed and equal to PR `headRefOid`
- [ ] Base-repository commit-scoped status/check rollup shows a non-empty complete check list, no disallowed non-required states, all trusted manifest type/name/source checks passing, all source-less branch-protection contexts mapped through the trusted manifest, and all live base-branch protection required status/checks passing on the latest pushed head
- [ ] Final direct GitHub GraphQL review-thread query after pushed-head verification shows zero unresolved
- [ ] Final non-thread comment evidence check passes for pushed `HEAD`
- [ ] `gh pr view` shows `state` `OPEN`, no conflicts, no draft status, mergeStateStatus CLEAN/HAS_HOOKS, reviewDecision APPROVED, and no reviewRequests
- [ ] Direct review query shows an `APPROVED` review on the pushed `HEAD` submitted after the latest addressed comment evidence by a non-author OWNER/MEMBER/COLLABORATOR reviewer
- [ ] All conversations marked resolved on GitHub

## Quick Reference: When to Use Related Skills

During code review, you may need to invoke other skills:

| Issue                    | Skill to Use                    |
| ------------------------ | ------------------------------- |
| Class in wrong directory | `code-organization`             |
| Vague naming             | `code-organization`             |
| DDD pattern violations   | `implementing-ddd-architecture` |
| Deptrac failures         | `deptrac-fixer`                 |
| Complexity too high      | `complexity-management`         |
| Test failures            | `testing-workflow`              |
| Quality standards        | `quality-standards`             |

## Related Skills

- **code-organization**: Enforces "Directory X contains ONLY class type X" and naming conventions
- **implementing-ddd-architecture**: DDD patterns, layer structure, and boundaries
- **deptrac-fixer**: Fixes architectural boundary violations
- **complexity-management**: Reduces cyclomatic complexity
- **testing-workflow**: Test coverage and mutation testing
- **quality-standards**: Overall quality metrics and thresholds
- **ci-workflow**: Comprehensive CI checks

## Related Documentation

- Reference: `reference/quality-standards.md` - Quality standards integration details
- Organizational fix patterns: `../code-organization/SKILL.md`
