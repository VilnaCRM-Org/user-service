#!/bin/bash

# ==============================================================================
# GitHub PR Unresolved Comments Retrieval Script
# ==============================================================================
#
# This script retrieves all unresolved inline comments for a specific GitHub 
# Pull Request using the GitHub CLI tool (gh) with multiple authentication 
# options, outputting results to stdout in a structured format.
#
# AUTHOR:     VilnaCRM Team
# VERSION:    2.0.0
# DATE:       2025-09-06
# LICENSE:    MIT
#
# ==============================================================================
# REQUIREMENTS & INSTALLATION
# ==============================================================================
#
# Prerequisites:
# 1. GitHub CLI (gh) must be installed and accessible in PATH
# 2. Git repository with GitHub remote configured
# 3. jq command-line JSON processor (usually pre-installed)
# 4. bash shell (version 4.0 or higher recommended)
#
# GitHub CLI Installation:
# -----------------------
# macOS:          brew install gh
# Ubuntu/Debian:  sudo apt install gh  
# Fedora:         sudo dnf install gh
# Arch Linux:     sudo pacman -S github-cli
# Windows:        winget install GitHub.cli
# Manual:         https://cli.github.com/
#
# Verification:
#   gh --version
#   jq --version
#
# ==============================================================================
# AUTHENTICATION SETUP
# ==============================================================================
#
# The script supports multiple authentication methods in priority order:
#
# Method 1: Environment Variable Authentication (Recommended for Automation)
# -------------------------------------------------------------------------
# export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"
# export GH_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"  # Alternative variable name
#
# Personal Access Token Setup:
# 1. Visit: https://github.com/settings/tokens/new
# 2. Required scopes:
#    - 'repo' (for private repositories)
#    - 'public_repo' (for public repositories, minimum required)
#    - 'read:org' (if accessing organization repositories)
# 3. Copy generated token and set environment variable
#
# Method 2: GitHub CLI Authentication (Interactive)
# ------------------------------------------------
# gh auth login
#
# Method 3: GitHub CLI Token Authentication (Programmatic)  
# -------------------------------------------------------
# echo "ghp_xxxxxxxxxxxxxxxxxxxx" | gh auth login --with-token
#
# GitHub Enterprise Support:
# -------------------------
# export GITHUB_HOST="github.company.com"
# export GITHUB_API_URL="https://github.company.com/api/v3"
#
# Authentication Verification:
#   gh auth status
#   gh auth status --hostname github.company.com
#
# ==============================================================================
# USAGE EXAMPLES
# ==============================================================================
#
# Basic Usage (Auto-detect PR from current branch):
# ------------------------------------------------
# ./scripts/get-pr-comments.sh
# ./scripts/get-pr-comments.sh json
# ./scripts/get-pr-comments.sh markdown
#
# Explicit PR Specification:
# -------------------------  
# ./scripts/get-pr-comments.sh 123
# ./scripts/get-pr-comments.sh 123 json
# ./scripts/get-pr-comments.sh 456 markdown
#
# With Authentication Token:
# -------------------------
# GITHUB_TOKEN="ghp_xxx" ./scripts/get-pr-comments.sh 123 json
# GH_TOKEN="ghp_xxx" ./scripts/get-pr-comments.sh markdown
#
# GitHub Enterprise:
# -----------------
# GITHUB_HOST="github.company.com" GITHUB_TOKEN="ghp_xxx" ./scripts/get-pr-comments.sh 789
#
# Makefile Integration:
# -------------------
# make pr-comments                          # Auto-detect PR
# make pr-comments FORMAT=json            # Auto-detect PR, JSON output
# make pr-comments PR=123 FORMAT=markdown # Explicit PR, Markdown output
# GITHUB_TOKEN="ghp_xxx" make pr-comments PR=456
#
# ==============================================================================
# PARAMETERS & OPTIONS
# ==============================================================================
#
# Positional Parameters:
# ---------------------
# $1 (PR_NUMBER)  - GitHub Pull Request number (optional)
#                   If not provided, auto-detected from current git branch
#                   Must be positive integer if specified
#                   
# $2 (FORMAT)     - Output format (optional, default: text)
#                   Supported values: text, json, markdown
#
# Environment Variables:
# ---------------------
# GITHUB_TOKEN    - GitHub Personal Access Token (preferred)
# GH_TOKEN        - Alternative GitHub token variable name  
# GITHUB_HOST     - GitHub hostname (default: github.com)
# GH_HOST         - Alternative hostname variable
# GITHUB_API_URL  - Full API URL (for enterprise instances)
#
# Help Options:
# ------------
# --help, -h, help           - Show usage information
# --auth-help, auth-help     - Show authentication setup guide
# --install-help, install-help - Show installation instructions
#
# ==============================================================================
# OUTPUT FORMATS
# ==============================================================================
#
# Text Format (default):
# ---------------------
# Human-readable format with clear sections for each comment
# Includes: Comment ID, File path, Line number, Author, Timestamps, Body, URL
# Ends with: Total comment count summary
#
# JSON Format:
# -----------
# Structured JSON with metadata including:
# {
#   "pr_number": 123,
#   "total_comments": 5,
#   "comments": [...]
# }
#
# Markdown Format:
# ---------------
# GitHub-flavored markdown suitable for documentation
# Uses headers, code blocks, links, and emphasis
# Includes summary section with total count
#
# ==============================================================================
# EXIT CODES
# ==============================================================================
#
# 0  - Success (comments found and displayed)
# 0  - Success (no unresolved comments found)
# 1  - Error: GitHub CLI not installed
# 1  - Error: Authentication failed or missing
# 1  - Error: Invalid parameters or usage
# 1  - Error: PR not found or inaccessible
# 1  - Error: Repository detection failed
# 1  - Error: Network/API communication failed
# 1  - Error: Git repository not found or invalid
# 1  - Error: JSON parsing failed
#
# ==============================================================================
# TROUBLESHOOTING
# ==============================================================================
#
# Common Issues and Solutions:
#
# 1. "GitHub CLI (gh) is not installed"
#    → Install gh CLI using your package manager or from https://cli.github.com/
#
# 2. "No GitHub authentication found" 
#    → Run 'gh auth login' or set GITHUB_TOKEN environment variable
#    → Check token permissions (repo/public_repo scope required)
#
# 3. "Could not detect GitHub repository"
#    → Ensure you're in a Git repository with GitHub remote
#    → Check: git remote -v
#
# 4. "No PR found for branch 'branch-name'"
#    → Verify the branch has an associated Pull Request
#    → Use explicit PR number: ./script.sh <PR_NUMBER>
#    → Check branch name: git branch --show-current
#
# 5. "PR #123 not found in repository"
#    → Verify PR exists and is accessible
#    → Check repository permissions
#    → Ensure correct GitHub instance (GITHUB_HOST)
#
# 6. "Failed to fetch PR comments"
#    → Check network connectivity
#    → Verify authentication and permissions
#    → Ensure repository access rights
#
# 7. "Invalid GITHUB_TOKEN provided"
#    → Regenerate token with correct scopes
#    → Check token hasn't expired
#    → Verify token format (should start with 'ghp_' for classic tokens)
#
# 8. GitHub Enterprise Issues:
#    → Set GITHUB_HOST environment variable
#    → Use enterprise-specific token
#    → Verify API endpoint accessibility
#
# Debug Commands:
#   gh auth status
#   gh api user
#   gh repo view
#   git remote -v
#   git branch --show-current
#
# ==============================================================================

set -euo pipefail

# Global variables
PR_NUMBER=""
FORMAT="text"
REPO=""
GITHUB_HOST="${GITHUB_HOST:-github.com}"

# Help functions
show_usage() {
    echo "Usage: $0 [PR_NUMBER] [FORMAT]"
    echo ""
    echo "Parameters:"
    echo "  PR_NUMBER       - GitHub Pull Request number (optional - auto-detected from current branch if not provided)"
    echo "  FORMAT          - Output format: text, json, markdown (default: text)"
    echo ""
    echo "Environment variables:"
    echo "  GITHUB_TOKEN    - GitHub Personal Access Token (preferred)"
    echo "  GH_TOKEN        - Alternative token variable"
    echo "  GITHUB_HOST     - GitHub hostname (default: github.com)"
    echo ""
    echo "Examples:"
    echo "  # Auto-detect PR from current branch"
    echo "  $0"
    echo "  $0 json"
    echo "  GITHUB_TOKEN='ghp_xxx' $0 markdown"
    echo ""
    echo "  # Explicitly specify PR number"
    echo "  GITHUB_TOKEN='ghp_xxx' $0 123 json"
    echo "  $0 123 text"
    echo "  $0 456 markdown"
    echo ""
    echo "For authentication help: $0 --auth-help"
}

show_token_help() {
    echo "Personal Access Token (PAT) Setup:"
    echo "=================================="
    echo "1. Generate token: https://$GITHUB_HOST/settings/tokens/new"
    echo "2. Required scopes:"
    echo "   - 'repo' (for private repositories)"
    echo "   - 'public_repo' (for public repositories, minimum)"
    echo "3. Set environment variable:"
    echo "   export GITHUB_TOKEN='your_token_here'"
    echo "4. Or use inline:"
    echo "   GITHUB_TOKEN='your_token' make pr-comments PR=123"
    echo ""
    echo "Alternative methods:"
    echo "5. GitHub CLI login:"
    echo "   gh auth login"
    echo "6. Token pipe:"
    echo "   echo 'your_token' | gh auth login --with-token"
}

show_installation_help() {
    echo "GitHub CLI Installation:"
    echo "======================="
    echo "macOS:        brew install gh"
    echo "Ubuntu/Debian: sudo apt install gh"
    echo "Fedora:       sudo dnf install gh"
    echo "Arch Linux:   sudo pacman -S github-cli"
    echo "Windows:      winget install GitHub.cli"
    echo ""
    echo "Or visit: https://cli.github.com/"
}

# Check prerequisites with enhanced authentication
check_dependencies() {
    if ! command -v gh &> /dev/null; then
        echo "Error: GitHub CLI (gh) is not installed."
        echo ""
        show_installation_help
        exit 1
    fi

    if ! command -v jq &> /dev/null; then
        echo "Error: jq is not installed."
        echo ""
        echo "Install jq via your package manager (e.g., brew install jq, apt-get install jq)."
        exit 1
    fi
    
    # Try multiple authentication methods
    authenticate_github
}

# Enhanced authentication function
authenticate_github() {
    local temp_auth=false
    
    # Method 1: Environment token
    if [[ -n "${GITHUB_TOKEN:-}" ]]; then
        echo "→ Using GITHUB_TOKEN from environment"
        temp_auth=true
    elif [[ -n "${GH_TOKEN:-}" ]]; then
        echo "→ Using GH_TOKEN from environment"
        temp_auth=true
    fi
    
    # Method 2: Check existing CLI authentication
    if ! $temp_auth && gh auth status --hostname "$GITHUB_HOST" &>/dev/null; then
        echo "→ Using existing GitHub CLI authentication"
    elif ! $temp_auth; then
        # Method 3: Interactive authentication
        echo "No GitHub authentication found."
        echo ""
        echo "Choose authentication method:"
        echo "1. Enter Personal Access Token (recommended for automation)"
        echo "2. Interactive browser login"
        echo "3. Show token setup help"
        echo "4. Exit"
        read -p "Choice (1-4): " choice
        
        case $choice in
            1)
                read -s -p "Enter GitHub Personal Access Token: " token
                echo ""
                if echo "$token" | gh auth login --with-token --hostname "$GITHUB_HOST" 2>/dev/null; then
                    echo "✓ Token authentication successful"
                else
                    echo "✗ Token authentication failed"
                    show_token_help
                    exit 1
                fi
                ;;
            2)
                if gh auth login --hostname "$GITHUB_HOST"; then
                    echo "✓ Interactive authentication successful"
                else
                    echo "✗ Interactive authentication failed"
                    exit 1
                fi
                ;;
            3)
                show_token_help
                exit 0
                ;;
            4)
                echo "Authentication required. Exiting."
                exit 1
                ;;
            *)
                echo "Invalid choice"
                exit 1
                ;;
        esac
    fi
    
    # Final verification
    if ! gh auth status --hostname "$GITHUB_HOST" &>/dev/null; then
        echo "Error: GitHub authentication verification failed"
        exit 1
    fi
    
    # Show authenticated user
    local user
    if user=$(gh api user --hostname "$GITHUB_HOST" -q '.login' 2>/dev/null); then
        echo "✓ Authenticated as: $user"
    fi
}

# Auto-detect PR number from current git branch
auto_detect_pr() {
    local current_branch
    if ! current_branch=$(git branch --show-current 2>/dev/null); then
        echo "Error: Could not determine current git branch."
        echo "Make sure you're in a Git repository."
        exit 1
    fi
    
    if [[ -z "$current_branch" ]]; then
        echo "Error: No current branch detected (you might be in detached HEAD state)."
        echo "Please specify PR number explicitly or checkout a branch."
        exit 1
    fi
    
    echo "→ Current branch: $current_branch"
    echo "→ Searching for PR associated with this branch..."
    
    # Use GitHub CLI to find PR for the current branch
    local pr_data
    if [[ "$GITHUB_HOST" == "github.com" ]]; then
        # For github.com, don't use --hostname flag
        if ! pr_data=$(gh pr list --head "$current_branch" --json number --limit 1 2>/dev/null); then
            echo "Error: Failed to search for PR associated with branch '$current_branch'."
            echo "Make sure you're authenticated and the branch has an associated PR."
            exit 1
        fi
    else
        # For GitHub Enterprise, use --hostname
        if ! pr_data=$(gh pr list --hostname "$GITHUB_HOST" --head "$current_branch" --json number --limit 1 2>/dev/null); then
            echo "Error: Failed to search for PR associated with branch '$current_branch'."
            echo "Make sure you're authenticated and the branch has an associated PR."
            exit 1
        fi
    fi
    
    # Extract PR number from the response
    local detected_pr
    if ! detected_pr=$(echo "$pr_data" | jq -r '.[0].number // empty' 2>/dev/null); then
        echo "Error: Failed to parse PR data from GitHub API response."
        exit 1
    fi
    
    if [[ -z "$detected_pr" || "$detected_pr" == "null" ]]; then
        echo "Error: No PR found for branch '$current_branch'."
        echo "Please ensure:"
        echo "  1. The branch has an associated Pull Request"
        echo "  2. You have proper permissions to access the repository"
        echo "  3. You're authenticated with GitHub CLI"
        echo ""
        echo "Alternatively, specify the PR number explicitly:"
        echo "  $0 <PR_NUMBER> [FORMAT]"
        exit 1
    fi
    
    echo "✓ Auto-detected PR #$detected_pr for branch '$current_branch'"
    PR_NUMBER="$detected_pr"
}

# Enhanced repository detection with hostname support
detect_repo() {
    if [[ "$GITHUB_HOST" == "github.com" ]]; then
        # For github.com, don't use --hostname flag as it's not supported in all versions
        if ! REPO=$(gh repo view --json owner,name -q '.owner.login + "/" + .name' 2>/dev/null); then
            echo "Error: Could not detect GitHub repository."
            echo "Make sure you're in a Git repository with $GITHUB_HOST remote."
            echo "Or the repository exists on the specified GitHub instance."
            exit 1
        fi
    else
        # For GitHub Enterprise, use --hostname if supported
        if ! REPO=$(gh repo view --hostname "$GITHUB_HOST" --json owner,name -q '.owner.login + "/" + .name' 2>/dev/null); then
            echo "Error: Could not detect GitHub repository."
            echo "Make sure you're in a Git repository with $GITHUB_HOST remote."
            echo "Or the repository exists on the specified GitHub instance."
            exit 1
        fi
    fi
    echo "→ Repository: $REPO"
}

# Validate PR number
validate_pr() {
    if ! [[ "$PR_NUMBER" =~ ^[0-9]+$ ]]; then
        echo "Error: PR number must be a positive integer, got: $PR_NUMBER"
        exit 1
    fi
    
    # Check if PR exists
    if [[ "$GITHUB_HOST" == "github.com" ]]; then
        # For github.com, don't use --hostname flag as it's not supported in all versions
        if ! gh pr view "$PR_NUMBER" --json number &>/dev/null; then
            echo "Error: PR #$PR_NUMBER not found in repository $REPO"
            exit 1
        fi
    else
        # For GitHub Enterprise, use --hostname if supported
        if ! gh pr view "$PR_NUMBER" --hostname "$GITHUB_HOST" --json number &>/dev/null; then
            echo "Error: PR #$PR_NUMBER not found in repository $REPO"
            exit 1
        fi
    fi
}

# Get unresolved comments for a PR
get_pr_comments() {
    local pr_number="$1"
    local format="$2"
    
    echo "→ Fetching unresolved comments for PR #$pr_number..."
    
    # Get repository owner and name for GraphQL query
    local repo_owner repo_name
    repo_owner=$(echo "$REPO" | cut -d'/' -f1)
    repo_name=$(echo "$REPO" | cut -d'/' -f2)
    
    # Use GraphQL to get unresolved review threads
    local graphql_query='{
  repository(owner: "'$repo_owner'", name: "'$repo_name'") {
    pullRequest(number: '$pr_number') {
      reviewThreads(first: 100) {
        nodes {
          id
          isResolved
          isOutdated
          path
          line
          originalLine
          startLine
          originalStartLine
          comments(first: 10) {
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
}'
    
    local threads_data
    # Use GraphQL API (only add hostname if it's not empty/default)
    local gh_args=()
    if [[ -n "$GITHUB_HOST" && "$GITHUB_HOST" != "github.com" ]]; then
        gh_args+=(--hostname "$GITHUB_HOST")
    fi
    
    if ! threads_data=$(gh api graphql "${gh_args[@]}" -f query="$graphql_query" 2>/dev/null); then
        echo "Error: Failed to fetch PR comments via GraphQL. Check your permissions and network connection."
        echo "Debug: GraphQL query failed. Trying to get error details..."
        gh api graphql "${gh_args[@]}" -f query="$graphql_query"
        exit 1
    fi
    
    # Filter for unresolved and non-outdated threads and transform to expected format
    local unresolved_comments
    unresolved_comments=$(echo "$threads_data" | jq --argjson pr_number "$pr_number" '
        .data.repository.pullRequest.reviewThreads.nodes
        | map(select(.isResolved == false))
        | map(select(.isOutdated == false))
        | map(select(.comments.nodes | length > 0))
        | map(.comments.nodes[0] as $comment | {
            id: $comment.id,
            path: .path,
            line: .line,
            original_line: .originalLine,
            start_line: .startLine,
            original_start_line: .originalStartLine,
            body: $comment.body,
            user: $comment.author,
            created_at: $comment.createdAt,
            updated_at: $comment.updatedAt,
            html_url: $comment.url,
            thread_id: .id,
            in_reply_to_id: null
        })
    ')
    
    # Check if any unresolved comments exist
    local comment_count
    comment_count=$(echo "$unresolved_comments" | jq 'length')
    
    if [[ "$comment_count" -eq 0 ]]; then
        echo "No unresolved comments found for PR #$pr_number"
        exit 0
    fi
    
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

# Output comments in text format
output_text() {
    local comments="$1"
    local pr_number="$2"
    local comment_count="$3"
    
    echo "Unresolved Comments for PR #$pr_number"
    echo "======================================="
    echo ""
    
    echo "$comments" | jq -r '.[] | 
        "Comment ID: " + (.id | tostring) + "\n" +
        "File: " + .path + " (Line " + (.line // .original_line | tostring) + ")\n" +
        "Author: " + .user.login + "\n" +
        "Created: " + .created_at + "\n" +
        "Updated: " + .updated_at + "\n" +
        "Body:\n" + .body + "\n" +
        "URL: " + .html_url + "\n" +
        "---"'
    
    echo ""
    echo "==============================================="
    echo "Total unresolved comments found: $comment_count"
    echo "==============================================="
}

# Output comments in JSON format
output_json() {
    local comments="$1"
    local pr_number="$2"
    local comment_count="$3"
    
    echo "$comments" | jq --argjson pr_number "$pr_number" --argjson count "$comment_count" '{
        "pr_number": $pr_number,
        "total_comments": $count,
        "comments": .
    }'
}

# Output comments in markdown format
output_markdown() {
    local comments="$1"
    local pr_number="$2"
    local comment_count="$3"
    
    echo "# Unresolved Comments for PR #$pr_number"
    echo ""
    
    echo "$comments" | jq -r '.[] | 
        "## Comment by @" + .user.login + " in `" + .path + "`\n" +
        "\n" +
        "**Line:** " + (.line // .original_line | tostring) + "  \n" +
        "**Created:** " + .created_at + "  \n" +
        "**Updated:** " + .updated_at + "\n" +
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
}

# Main function
main() {
    # Handle help flags
    case "${1:-}" in
        "-h"|"--help"|"help")
            show_usage
            exit 0
            ;;
        "--auth-help"|"auth-help")
            show_token_help
            exit 0
            ;;
        "--install-help"|"install-help")
            show_installation_help
            exit 0
            ;;
    esac
    
    # Handle parameters - PR number is now optional
    if [[ $# -eq 0 ]]; then
        # No arguments provided - auto-detect PR and use default format
        FORMAT="text"
    elif [[ $# -eq 1 ]]; then
        # One argument - could be PR number or format
        if [[ "$1" =~ ^[0-9]+$ ]]; then
            # First argument is numeric - treat as PR number
            PR_NUMBER="$1"
            FORMAT="text"
        else
            # First argument is not numeric - treat as format, auto-detect PR
            FORMAT="$1"
        fi
    elif [[ $# -eq 2 ]]; then
        # Two arguments - PR number and format
        PR_NUMBER="$1"
        FORMAT="$2"
    else
        echo "Error: Too many arguments provided"
        echo ""
        show_usage
        exit 1
    fi
    
    # Validate format
    case "$FORMAT" in
        "text"|"json"|"markdown")
            ;;
        *)
            echo "Error: Invalid format '$FORMAT'. Supported formats: text, json, markdown"
            exit 1
            ;;
    esac
    
    # Execute main workflow
    echo "GitHub PR Unresolved Comments Retrieval"
    echo "========================================"
    echo ""
    
    check_dependencies
    detect_repo
    
    # Auto-detect PR number if not provided
    if [[ -z "$PR_NUMBER" ]]; then
        auto_detect_pr
    fi
    
    validate_pr
    get_pr_comments "$PR_NUMBER" "$FORMAT"
}

# Execute main function with all arguments
main "$@"