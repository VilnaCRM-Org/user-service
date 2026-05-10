#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

ERROR_COUNT=0
WARNING_COUNT=0
ERRORS=""
WARNINGS=""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

LOCKED_CONFIG_FILES=(
    "phpinsights.php"
    "phpinsights-tests.php"
    "psalm.xml"
    "deptrac.yaml"
    "infection.json5"
    "phpmd-strict.xml"
    "phpmd.tests.xml"
    ".php-cs-fixer.dist.php"
)

REQUIRED_DIRS=(
    "src"
    "src/Shared"
    "src/Shared/Application"
    "src/Shared/Domain"
    "src/Shared/Infrastructure"
    "config"
    "config/packages"
    "config/doctrine"
    "tests"
    "tests/Unit"
    "tests/Integration"
    "tests/Behat"
)

print_error() {
    echo -e "${RED}$1${NC}" >&2
}

print_success() {
    echo -e "${GREEN}$1${NC}"
}

print_warning() {
    echo -e "${YELLOW}$1${NC}"
}

print_info() {
    echo -e "${BLUE}$1${NC}"
}

add_error() {
    ERROR_COUNT=$((ERROR_COUNT + 1))
    ERRORS="${ERRORS}\n  - $1"
}

add_warning() {
    WARNING_COUNT=$((WARNING_COUNT + 1))
    WARNINGS="${WARNINGS}\n  - $1"
}

validate_directory_structure() {
    local validation_passed=true

    print_info "-> Validating required directory structure..."

    for dir in "${REQUIRED_DIRS[@]}"; do
        local dir_path="$PROJECT_ROOT/$dir"

        if [ ! -e "$dir_path" ]; then
            add_error "Missing required directory: $dir"
            validation_passed=false
        elif [ ! -d "$dir_path" ]; then
            add_error "Expected directory but found file: $dir"
            validation_passed=false
        fi
    done

    if [ "$validation_passed" = true ]; then
        print_success "  [OK] Directory structure validation passed"
    else
        print_error "  [FAIL] Directory structure validation failed"
    fi

    return $([ "$validation_passed" = true ] && echo 0 || echo 1)
}

get_all_config_files() {
    local files=()

    for file in "${LOCKED_CONFIG_FILES[@]}"; do
        files+=("$PROJECT_ROOT/$file")
    done

    printf '%s\n' "${files[@]}"
}

check_git_changes_to_config() {
    print_info "-> Checking for modifications to locked configuration files..."

    if ! git -C "$PROJECT_ROOT" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
        add_warning "Not a git repository - skipping git modification checks"
        return 0
    fi

    cd "$PROJECT_ROOT"

    local modified_files=()
    local comparison_successful=false

    if git rev-parse --is-shallow-repository 2>/dev/null | grep -q "true"; then
        print_info "  -> Shallow clone detected; attempting to fetch base ref..."
        for ref in main master; do
            if git fetch --no-tags --depth=1 origin "$ref" >/dev/null 2>&1; then
                break
            fi
        done
    fi

    for ref in origin/main origin/master main master; do
        if git rev-parse --verify "$ref" >/dev/null 2>&1; then
            local merge_base=""
            merge_base="$(git merge-base "$ref" HEAD 2>/dev/null || true)"

            if [ -n "$merge_base" ]; then
                while IFS= read -r line; do
                    [ -n "$line" ] && modified_files+=("$PROJECT_ROOT/$line")
                done < <(git diff --name-only "$merge_base" HEAD 2>/dev/null || true)
            else
                add_warning "Could not compute merge-base with $ref; falling back to $ref..HEAD diff"
                while IFS= read -r line; do
                    [ -n "$line" ] && modified_files+=("$PROJECT_ROOT/$line")
                done < <(git diff --name-only "$ref" HEAD 2>/dev/null || true)
            fi

            comparison_successful=true
            print_info "  -> Comparing against reference branch: $ref"
            break
        fi
    done

    while IFS= read -r line; do
        [ -n "$line" ] && modified_files+=("$PROJECT_ROOT/$line")
    done < <(git diff --name-only HEAD 2>/dev/null || true)

    while IFS= read -r line; do
        [ -n "$line" ] && modified_files+=("$PROJECT_ROOT/$line")
    done < <(git diff --cached --name-only 2>/dev/null || true)

    if [ "$comparison_successful" = false ]; then
        if [ "${CI:-}" = "true" ] || [ "${CI:-}" = "1" ]; then
            add_error "Could not find comparison reference branch; CI cannot validate locked config drift without base ref"
            return 1
        fi
        add_warning "No reference branch found; checked only local uncommitted/staged changes"
    fi

    local config_files=()
    while IFS= read -r line; do
        config_files+=("$line")
    done < <(get_all_config_files)

    local modified_config=()
    for modified_file in "${modified_files[@]}"; do
        for config_file in "${config_files[@]}"; do
            if [ "$modified_file" = "$config_file" ]; then
                modified_config+=("$modified_file")
                break
            fi
        done
    done

    if [ ${#modified_config[@]} -gt 0 ]; then
        modified_config=($(printf '%s\n' "${modified_config[@]}" | sort -u))
    fi

    if [ ${#modified_config[@]} -gt 0 ]; then
        for file in "${modified_config[@]}"; do
            local rel_path="${file#$PROJECT_ROOT/}"
            add_error "Modification of locked configuration file is not allowed: $rel_path
      Please discuss any configuration changes with the team first."
        done
        print_error "  [FAIL] Found ${#modified_config[@]} modified configuration file(s)"
        return 1
    fi

    print_success "  [OK] No modifications to locked configuration files detected"
    return 0
}

report_results() {
    echo ""
    echo "========================================"
    echo "Validation Results"
    echo "========================================"

    if [ $ERROR_COUNT -eq 0 ] && [ $WARNING_COUNT -eq 0 ]; then
        print_success "[OK] Configuration validation passed"
        return 0
    fi

    if [ $ERROR_COUNT -gt 0 ]; then
        echo ""
        print_error "[FAIL] Configuration validation failed"
        echo ""
        echo "Errors:"
        echo -e "$ERRORS"
    fi

    if [ $WARNING_COUNT -gt 0 ]; then
        echo ""
        print_warning "Warnings:"
        echo -e "$WARNINGS"
    fi

    echo ""
    echo "========================================"
    if [ $ERROR_COUNT -gt 0 ]; then
        print_error "Found $ERROR_COUNT error(s)"
        if [ $WARNING_COUNT -gt 0 ]; then
            print_warning "Found $WARNING_COUNT warning(s)"
        fi
        echo ""
        print_error "Please keep required directories present and locked config files unchanged."
        return 1
    fi

    return 0
}

main() {
    print_info "========================================"
    print_info "Configuration Validation"
    print_info "========================================"
    print_info "Project root: $PROJECT_ROOT"
    echo ""

    local validation_passed=true

    if ! validate_directory_structure; then
        validation_passed=false
    fi

    if ! check_git_changes_to_config; then
        validation_passed=false
    fi

    echo ""

    if ! report_results; then
        exit 1
    fi

    if [ "$validation_passed" = false ]; then
        exit 1
    fi

    exit 0
}

main "$@"
