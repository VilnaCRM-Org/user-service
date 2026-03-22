#!/bin/bash

# ==============================================================================
# GitHub PR ALL Comments Retrieval Script (Enhanced)
# ==============================================================================
#
# This script retrieves ALL unresolved inline comments for a GitHub PR
# with automatic categorization and prioritization for systematic code review
#
# KEY FEATURES:
# - Fetches ALL comments in each thread (not just first)
# - Supports pagination (handles >100 threads)
# - Automatic categorization (committable, llm-prompt, question, feedback)
# - Priority sorting (HIGHEST, HIGH, MEDIUM, LOW)
# - Multiple output formats (text, json, markdown)
# - Includes outdated comments option
# - Better error handling
# - Shows fetching progress
#
# COMMENT CATEGORIES (aligned with AGENTS.md):
# - Committable Suggestions (HIGHEST) - Direct code suggestions to apply
# - LLM Prompts (HIGH) - Refactoring instructions and architectural guidance
# - Questions (MEDIUM) - Clarifications needed
# - Feedback (LOW) - General observations
#
# ==============================================================================

set -euo pipefail

# Global variables
PR_NUMBER=""
FORMAT="text"
REPO=""
GITHUB_HOST="${GITHUB_HOST:-github.com}"
INCLUDE_OUTDATED="${INCLUDE_OUTDATED:-true}"
VERBOSE="${VERBOSE:-false}"

# Help functions
show_usage() {
    echo "Usage: $0 [PR_NUMBER] [FORMAT]"
    echo ""
    echo "Parameters:"
    echo "  PR_NUMBER       - GitHub Pull Request number (optional - auto-detected from current branch)"
    echo "  FORMAT          - Output format: text, json, markdown (default: text)"
    echo ""
    echo "Environment variables:"
    echo "  GITHUB_TOKEN       - GitHub Personal Access Token"
    echo "  GH_TOKEN           - Alternative token variable"
    echo "  GITHUB_HOST        - GitHub hostname (default: github.com)"
    echo "  INCLUDE_OUTDATED   - Include outdated comments (default: false)"
    echo "  VERBOSE            - Show verbose output (default: false)"
    echo ""
    echo "Examples:"
    echo "  $0                                    # Auto-detect PR"
    echo "  $0 json                               # Auto-detect PR, JSON output"
    echo "  $0 123 markdown                       # Explicit PR #123"
    echo "  INCLUDE_OUTDATED=true $0 123 json     # Include outdated comments"
    echo "  VERBOSE=true $0 123 text              # Show verbose output"
}

# Check prerequisites
check_dependencies() {
    if ! command -v gh &> /dev/null; then
        echo "Error: GitHub CLI (gh) is not installed." >&2
        echo "Install: https://cli.github.com/" >&2
        exit 1
    fi

    if ! command -v jq &> /dev/null; then
        echo "Error: jq is not installed." >&2
        echo "Install jq via your package manager (e.g., brew install jq, apt-get install jq)." >&2
        exit 1
    fi

    # Check authentication (skip auth check - will fail on first API call if not authenticated)
    # gh auth status can hang in non-interactive environments, so we skip it
    # and let the actual API calls fail if authentication is missing

    if [[ "$VERBOSE" == "true" ]]; then
        echo "✓ Dependencies checked" >&2
    fi
}

# Auto-detect PR number
auto_detect_pr() {
    local current_branch
    if ! current_branch=$(git branch --show-current 2>/dev/null); then
        echo "Error: Could not determine current git branch."
        exit 1
    fi

    if [[ "$VERBOSE" == "true" ]]; then
        echo "→ Current branch: $current_branch"
    fi

    local pr_data
    if [[ "$GITHUB_HOST" == "github.com" ]]; then
        pr_data=$(gh pr list --head "$current_branch" --json number --limit 1 </dev/null 2>/dev/null)
    else
        pr_data=$(gh pr list --hostname "$GITHUB_HOST" --head "$current_branch" --json number --limit 1 </dev/null 2>/dev/null)
    fi

    local detected_pr
    detected_pr=$(echo "$pr_data" | jq -r '.[0].number // empty' 2>/dev/null)

    if [[ -z "$detected_pr" || "$detected_pr" == "null" ]]; then
        echo "Error: No PR found for branch '$current_branch'."
        exit 1
    fi

    if [[ "$VERBOSE" == "true" ]]; then
        echo "✓ Auto-detected PR #$detected_pr"
    fi
    PR_NUMBER="$detected_pr"
}

# Detect repository
detect_repo() {
    if [[ "$GITHUB_HOST" == "github.com" ]]; then
        REPO=$(gh repo view --json owner,name -q '.owner.login + "/" + .name' </dev/null 2>/dev/null)
    else
        REPO=$(gh repo view --hostname "$GITHUB_HOST" --json owner,name -q '.owner.login + "/" + .name' </dev/null 2>/dev/null)
    fi

    if [[ -z "$REPO" ]]; then
        echo "Error: Could not detect GitHub repository." >&2
        exit 1
    fi

    if [[ "$VERBOSE" == "true" ]]; then
        echo "→ Repository: $REPO" >&2
    fi
}

# Fetch ALL review threads with pagination
fetch_all_review_threads() {
    local pr_number="$1"
    local repo_owner repo_name
    repo_owner=$(echo "$REPO" | cut -d'/' -f1)
    repo_name=$(echo "$REPO" | cut -d'/' -f2)

    local temp_file
    temp_file=$(mktemp)
    echo "[]" > "$temp_file"

    local has_next_page=true
    local cursor="null"
    local page=1

    echo "→ Fetching review threads (with pagination)..." >&2

    while [[ "$has_next_page" == "true" ]]; do
        if [[ "$VERBOSE" == "true" ]]; then
            echo "  → Fetching page $page..." >&2
        fi

        local after_clause=""
        if [[ "$cursor" != "null" ]]; then
            after_clause=", after: \"$cursor\""
        fi

        # GraphQL query with pagination
        local graphql_query="{
  repository(owner: \"$repo_owner\", name: \"$repo_name\") {
    pullRequest(number: $pr_number) {
      reviewThreads(first: 100$after_clause) {
        pageInfo {
          hasNextPage
          endCursor
        }
        nodes {
          id
          isResolved
          isOutdated
          path
          line
          originalLine
          startLine
          originalStartLine
          comments(first: 100) {
            nodes {
              id
              body
              author {
                login
              }
              createdAt
              updatedAt
              url
            }
          }
        }
      }
    }
  }
}"

        # Fetch data
        local gh_args=()
        if [[ -n "$GITHUB_HOST" && "$GITHUB_HOST" != "github.com" ]]; then
            gh_args+=(--hostname "$GITHUB_HOST")
        fi

        local page_data
        if ! page_data=$(gh api graphql "${gh_args[@]}" -f query="$graphql_query" </dev/null 2>/dev/null); then
            echo "Error: Failed to fetch review threads (page $page)" >&2
            rm -f "$temp_file"
            exit 1
        fi

        # Extract threads from this page and merge with accumulated threads
        echo "$page_data" | jq '.data.repository.pullRequest.reviewThreads.nodes' | \
            jq -s --slurpfile accumulated "$temp_file" '($accumulated[0] + .[0])' > "${temp_file}.new"
        mv "${temp_file}.new" "$temp_file"

        # Check for next page
        has_next_page=$(echo "$page_data" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.hasNextPage')
        cursor=$(echo "$page_data" | jq -r '.data.repository.pullRequest.reviewThreads.pageInfo.endCursor')

        ((page++))

        if [[ "$VERBOSE" == "true" ]]; then
            echo "  → Fetched $(echo "$page_data" | jq '.data.repository.pullRequest.reviewThreads.nodes | length') threads" >&2
        fi
    done

    local all_threads
    all_threads=$(cat "$temp_file")
    rm -f "$temp_file"

    echo "✓ Fetched total $(echo "$all_threads" | jq 'length') review threads" >&2
    echo "$all_threads"
}

# Process and filter threads
process_threads() {
    local threads="$1"
    local pr_number="$2"

    echo "→ Processing and filtering comments..." >&2

    # Build filter based on INCLUDE_OUTDATED
    local outdated_filter
    if [[ "$INCLUDE_OUTDATED" == "true" ]]; then
        outdated_filter="true"
    else
        outdated_filter="select(.isOutdated == false)"
    fi

    # Extract ALL comments from ALL unresolved threads (using stdin to avoid argument size limits)
    local all_comments
    all_comments=$(echo "$threads" | jq --arg pr_number "$pr_number" "
        map(select(
            (.isResolved == false) and
            (.comments | type == \"object\") and
            (.comments.nodes != null) and
            (.comments.nodes | type == \"array\") and
            (.comments.nodes | length > 0) and
            ($outdated_filter)
        ))
        | map(
            .comments.nodes[] as \$comment |
            (\$comment.body | ascii_downcase) as \$body_lower |
            (if (\$comment.body | test(\"suggestion\")) then \"committable\"
             elif (\$body_lower | test(\"refactor|implement|should|must|need to|extract|create new|add new|update|change|modify|fix\")) then \"llm-prompt\"
             elif (\$body_lower | test(\"why|how|what|\\\\?\")) then \"question\"
             else \"feedback\"
             end) as \$category |
            (if \$category == \"committable\" then 1
             elif \$category == \"llm-prompt\" then 2
             elif \$category == \"question\" then 3
             else 4
             end) as \$priority |
            {
                id: \$comment.id,
                path: .path,
                line: .line,
                original_line: .originalLine,
                start_line: .startLine,
                original_start_line: .originalStartLine,
                body: \$comment.body,
                user: \$comment.author,
                created_at: \$comment.createdAt,
                updated_at: \$comment.updatedAt,
                html_url: \$comment.url,
                thread_id: .id,
                is_outdated: .isOutdated,
                in_reply_to_id: null,
                category: \$category,
                priority: \$priority,
                priority_label: (if \$priority == 1 then \"HIGHEST\"
                                 elif \$priority == 2 then \"HIGH\"
                                 elif \$priority == 3 then \"MEDIUM\"
                                 else \"LOW\"
                                 end)
            }
        )
        | flatten
        | sort_by(.priority, .created_at)
    ")

    local comment_count
    comment_count=$(echo "$all_comments" | jq 'length')

    echo "✓ Found $comment_count unresolved comments" >&2

    if [[ "$comment_count" -eq 0 ]]; then
        echo "" >&2
        echo "No unresolved comments found for PR #$pr_number" >&2
        exit 0
    fi

    echo "$all_comments"
}

# Get ALL comments for a PR
get_pr_comments() {
    local pr_number="$1"
    local format="$2"

    # Fetch all threads with pagination
    local all_threads
    all_threads=$(fetch_all_review_threads "$pr_number")

    # Process and filter
    local unresolved_comments
    unresolved_comments=$(process_threads "$all_threads" "$pr_number")

    local comment_count
    comment_count=$(echo "$unresolved_comments" | jq 'length')

    # Output in requested format
    case "$format" in
        "json")
            output_json "$unresolved_comments" "$pr_number" "$comment_count"
            ;;
        "markdown")
            output_markdown "$unresolved_comments" "$pr_number" "$comment_count"
            ;;
        "text"|*)
            output_text "$unresolved_comments" "$pr_number" "$comment_count"
            ;;
    esac
}

# Calculate category counts (reusable across output formats)
calculate_category_counts() {
    local comments="$1"

    echo "$(echo "$comments" | jq '[.[] | select(.category == "committable")] | length')|$(echo "$comments" | jq '[.[] | select(.category == "llm-prompt")] | length')|$(echo "$comments" | jq '[.[] | select(.category == "question")] | length')|$(echo "$comments" | jq '[.[] | select(.category == "feedback")] | length')"
}

# Output functions
output_text() {
    local comments="$1"
    local pr_number="$2"
    local comment_count="$3"

    echo ""
    echo "======================================="
    echo "Unresolved Comments for PR #$pr_number"
    echo "======================================="
    echo ""

    echo "$comments" | jq -r '.[] |
        "Comment ID: " + (.id | tostring) + "\n" +
        "Priority: " + .priority_label + " (" + .category + ")\n" +
        "File: " + .path + " (Line " + (.line // .original_line | tostring) + ")\n" +
        "Author: @" + .user.login + "\n" +
        "Created: " + .created_at + "\n" +
        "Updated: " + .updated_at + "\n" +
        (if .is_outdated then "Status: OUTDATED\n" else "" end) +
        "Body:\n" + .body + "\n" +
        "URL: " + .html_url + "\n" +
        "---\n"'

    echo "==============================================="
    echo "Total unresolved comments found: $comment_count"
    echo "==============================================="
    echo ""

    IFS='|' read -r committable_count llm_prompt_count question_count feedback_count <<< "$(calculate_category_counts "$comments")"

    echo "By Category:"
    echo "  - Committable Suggestions (HIGHEST): $committable_count"
    echo "  - LLM Prompts (HIGH): $llm_prompt_count"
    echo "  - Questions (MEDIUM): $question_count"
    echo "  - Feedback (LOW): $feedback_count"
    echo ""
    echo "Recommended Workflow:"
    echo "  1. Address Committable Suggestions first - apply them directly"
    echo "  2. Process LLM Prompts - use as detailed refactoring instructions"
    echo "  3. Answer Questions - provide explanations and improve code clarity"
    echo "  4. Consider Feedback - for future improvements"
}

output_json() {
    local comments="$1"
    local pr_number="$2"
    local comment_count="$3"

    echo "$comments" | jq --arg pr_number "$pr_number" --arg count "$comment_count" '{
        "pr_number": ($pr_number | tonumber),
        "total_comments": ($count | tonumber),
        "include_outdated": (env.INCLUDE_OUTDATED == "true"),
        "fetched_at": (now | todate),
        "comments": .
    }'
}

output_markdown() {
    local comments="$1"
    local pr_number="$2"
    local comment_count="$3"

    echo "# Unresolved Comments for PR #$pr_number"
    echo ""

    if [[ "$INCLUDE_OUTDATED" == "true" ]]; then
        echo "> **Note**: Including outdated comments"
        echo ""
    fi

    echo "$comments" | jq -r '.[] |
        "## " + (if .priority == 1 then ":fire: " elif .priority == 2 then ":warning: " elif .priority == 3 then ":question: " else ":bulb: " end) +
        .priority_label + " - " + .category + "\n" +
        "\n" +
        "**Comment by** @" + .user.login + " in `" + .path + "`\n" +
        "\n" +
        "**Line:** " + (.line // .original_line | tostring) + "  \n" +
        "**Created:** " + .created_at + "  \n" +
        "**Updated:** " + .updated_at + "\n" +
        (if .is_outdated then "_:warning: Outdated comment_\n" else "" end) +
        "\n" +
        .body + "\n" +
        "\n" +
        "[View on GitHub](" + .html_url + ")\n" +
        "\n" +
        "---\n"'

    echo ""
    echo "## Summary"
    echo ""
    echo "**Total unresolved comments found:** $comment_count"
    echo ""

    IFS='|' read -r committable_count llm_prompt_count question_count feedback_count <<< "$(calculate_category_counts "$comments")"

    echo "### By Category"
    echo ""
    echo "- :fire: **Committable Suggestions (HIGHEST):** $committable_count"
    echo "- :warning: **LLM Prompts (HIGH):** $llm_prompt_count"
    echo "- :question: **Questions (MEDIUM):** $question_count"
    echo "- :bulb: **Feedback (LOW):** $feedback_count"
    echo ""
    echo "### Recommended Workflow"
    echo ""
    echo "1. Address **Committable Suggestions** first - apply them directly"
    echo "2. Process **LLM Prompts** - use as detailed refactoring instructions"
    echo "3. Answer **Questions** - provide explanations and improve code clarity"
    echo "4. Consider **Feedback** - for future improvements"
}

# Main function
main() {
    # Handle help
    case "${1:-}" in
        "-h"|"--help"|"help")
            show_usage
            exit 0
            ;;
    esac

    # Handle parameters
    if [[ $# -eq 0 ]]; then
        FORMAT="text"
    elif [[ $# -eq 1 ]]; then
        if [[ "$1" =~ ^[0-9]+$ ]]; then
            PR_NUMBER="$1"
            FORMAT="text"
        else
            FORMAT="$1"
        fi
    elif [[ $# -eq 2 ]]; then
        PR_NUMBER="$1"
        FORMAT="$2"
    else
        echo "Error: Too many arguments"
        show_usage
        exit 1
    fi

    # Validate format
    case "$FORMAT" in
        "text"|"json"|"markdown") ;;
        *)
            echo "Error: Invalid format '$FORMAT'"
            exit 1
            ;;
    esac

    echo "GitHub PR Comments Retrieval (Improved)" >&2
    echo "========================================" >&2
    echo "" >&2

    check_dependencies
    detect_repo

    if [[ -z "$PR_NUMBER" ]]; then
        auto_detect_pr
    fi

    get_pr_comments "$PR_NUMBER" "$FORMAT"
}

main "$@"
