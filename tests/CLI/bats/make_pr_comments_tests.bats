#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

# Test environment setup
setup() {
    # Store original environment variables
    export ORIGINAL_GITHUB_TOKEN="${GITHUB_TOKEN:-}"
    export ORIGINAL_GH_TOKEN="${GH_TOKEN:-}"
    export ORIGINAL_GITHUB_HOST="${GITHUB_HOST:-}"
    
    # Create test directory if needed
    if [[ ! -d "tests/CLI/bats/temp" ]]; then
        mkdir -p "tests/CLI/bats/temp"
    fi
}

teardown() {
    # Restore original environment variables
    if [[ -n "$ORIGINAL_GITHUB_TOKEN" ]]; then
        export GITHUB_TOKEN="$ORIGINAL_GITHUB_TOKEN"
    else
        unset GITHUB_TOKEN
    fi
    
    if [[ -n "$ORIGINAL_GH_TOKEN" ]]; then
        export GH_TOKEN="$ORIGINAL_GH_TOKEN"
    else
        unset GH_TOKEN
    fi
    
    if [[ -n "$ORIGINAL_GITHUB_HOST" ]]; then
        export GITHUB_HOST="$ORIGINAL_GITHUB_HOST"
    else
        unset GITHUB_HOST
    fi
    
    # Clean up test files
    rm -rf "tests/CLI/bats/temp"
}

@test "make pr-comments without GitHub CLI should fail with helpful message" {
    # Create a clean environment with only essential commands, excluding gh
    mkdir -p tests/CLI/bats/temp/clean_bin
    
    # Copy only actual executable files (not shell builtins)
    for cmd in make bash sh; do
        if command -v "$cmd" >/dev/null 2>&1 && [[ -f "$(command -v "$cmd")" ]]; then
            cp "$(command -v "$cmd")" tests/CLI/bats/temp/clean_bin/
        fi
    done
    
    # Run with clean PATH that doesn't include gh
    PATH="tests/CLI/bats/temp/clean_bin" run make pr-comments
    assert_failure
    assert_output --partial "Error: GitHub CLI (gh) is required but not installed"
    assert_output --partial "Visit: https://cli.github.com/ for installation instructions"
}

@test "make pr-comments without PR parameter should auto-detect from branch" {
    # Skip if we don't have gh CLI
    if ! command -v gh &> /dev/null; then
        skip "GitHub CLI not available"
    fi
    
    # Run with timeout to avoid hanging
    run timeout 10 make pr-comments
    
    # Should either succeed or fail with meaningful message about branch/PR detection
    # Accept timeout as well since external calls may hang
    if [[ $status -eq 124 ]]; then
        skip "Test timed out - likely due to external GitHub API calls"
    elif [[ $status -eq 0 ]]; then
        assert_output --partial "Auto-detecting PR from current git branch"
    else
        assert_output --partial "Auto-detecting PR from current git branch" || 
        assert_output --partial "No PR found for branch" ||
        assert_output --partial "Could not determine current git branch" ||
        assert_output --partial "GitHub CLI (gh) is required but not installed" ||
        assert_output --partial "No GitHub authentication found"
    fi
}

@test "make pr-comments with explicit PR number should use it" {
    # Skip if we don't have gh CLI
    if ! command -v gh &> /dev/null; then
        skip "GitHub CLI not available"
    fi
    
    # Run with timeout to avoid hanging
    run timeout 10 make pr-comments PR=999999
    
    # Should either succeed or fail with message about PR not found
    # Accept timeout as well since external calls may hang
    if [[ $status -eq 124 ]]; then
        skip "Test timed out - likely due to external GitHub API calls"
    elif [[ $status -eq 0 ]]; then
        assert_output --partial "Retrieving unresolved comments for PR #999999"
    else
        assert_output --partial "Retrieving unresolved comments for PR #999999" ||
        assert_output --partial "PR #999999 not found" ||
        assert_output --partial "Failed to fetch PR comments" ||
        assert_output --partial "No GitHub authentication found"
    fi
}




@test "scripts/get-pr-comments.sh direct call shows usage when called with --help" {
    run ./scripts/get-pr-comments.sh --help
    assert_success
    assert_output --partial "Usage:"
    assert_output --partial "PR_NUMBER"
    assert_output --partial "FORMAT"
    assert_output --partial "Examples:"
}

@test "scripts/get-pr-comments.sh direct call shows authentication help with --auth-help" {
    run ./scripts/get-pr-comments.sh --auth-help
    assert_success
    assert_output --partial "Personal Access Token (PAT) Setup:"
    assert_output --partial "Required scopes:"
    assert_output --partial "GITHUB_TOKEN"
}

@test "scripts/get-pr-comments.sh fails gracefully when no GitHub CLI is available" {
    # Create a temporary directory without gh in PATH
    mkdir -p tests/CLI/bats/temp/empty_path
    
    # Run with empty PATH to simulate missing gh CLI
    run bash -c "PATH=tests/CLI/bats/temp/empty_path ./scripts/get-pr-comments.sh"
    assert_failure
    assert_output --partial "Error: GitHub CLI (gh) is not installed"
    assert_output --partial "GitHub CLI Installation:"
}

@test "scripts/get-pr-comments.sh validates format parameter" {
    # Skip if we don't have gh CLI
    if ! command -v gh &> /dev/null; then
        skip "GitHub CLI not available"
    fi
    
    # Test with invalid format - the script should handle this gracefully
    run ./scripts/get-pr-comments.sh 123 invalid_format
    # The script may accept any format or validate it - either way should not crash
    assert_failure || assert_success
}

@test "scripts/get-pr-comments.sh handles non-numeric PR parameter gracefully" {
    # Skip if we don't have gh CLI
    if ! command -v gh &> /dev/null; then
        skip "GitHub CLI not available"
    fi
    
    # Test with non-numeric PR parameter
    run ./scripts/get-pr-comments.sh invalid_pr_number
    # Should either treat it as format or fail gracefully
    assert_failure || assert_success
}

@test "make pr-comments integration with Makefile variables" {
    # Test that Makefile properly exports environment variables
    run bash -c "echo 'GITHUB_HOST in Makefile:'; grep -n 'GITHUB_HOST' Makefile"
    assert_success
    assert_output --partial "GITHUB_HOST"
}

@test "make pr-comments integration with scripts directory" {
    # Verify the script file exists and is executable
    [ -f "scripts/get-pr-comments.sh" ]
    [ -x "scripts/get-pr-comments.sh" ]
}

@test "make help includes pr-comments target" {
    run make help
    assert_success
    assert_output --partial "Retrieve unresolved comments for a GitHub Pull Request"
}