#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

# shellcheck source=scripts/local-coder/lib/workspace-secrets.sh
. "${ROOT_DIR}/scripts/local-coder/lib/workspace-secrets.sh"

load_workspace_settings() {
    local settings_file
    for settings_file in \
        "${ROOT_DIR}/.devcontainer/workspace-settings.env" \
        "${ROOT_DIR}/.devcontainer/codespaces-settings.env"; do
        if [ -f "${settings_file}" ]; then
            # shellcheck disable=SC1090
            . "${settings_file}"
            break
        fi
    done
}

load_workspace_settings

cs_load_host_workspace_secrets

export OPENCLAW_WORKSPACE_ROOT="${ROOT_DIR}"
export OPENCLAW_CODER_WORKSPACE_ROOT="${ROOT_DIR}"

: "${DOCKER_API_VERSION:=}"
: "${CODEX_NPM_PACKAGE:=@openai/codex}"
: "${USER_SERVICE_BOOTSTRAP_STRICT:=${CODESPACES:-false}}"

if [ -z "${DOCKER_HOST:-}" ] && [ -S /var/run/docker-host.sock ]; then
    docker_sock_target="$(readlink -f /var/run/docker.sock 2>/dev/null || true)"
    if [ ! -S /var/run/docker.sock ] || [ "${docker_sock_target}" = "/var/run/docker-host.sock" ]; then
        export DOCKER_HOST="unix:///var/run/docker-host.sock"
    fi
fi

has_passwordless_sudo() {
    command -v sudo >/dev/null 2>&1 && sudo -n true >/dev/null 2>&1
}

ensure_apt_packages() {
    local missing_packages=()
    local package

    if ! command -v apt-get >/dev/null 2>&1 || ! has_passwordless_sudo; then
        echo "Warning: package installation is unavailable; skipping apt bootstrap." >&2
        return 0
    fi

    for package in "$@"; do
        if ! dpkg -s "${package}" >/dev/null 2>&1; then
            missing_packages+=("${package}")
        fi
    done

    if [ "${#missing_packages[@]}" -gt 0 ]; then
        sudo apt-get update
        sudo apt-get install -y "${missing_packages[@]}"
    fi
}

ensure_outer_workspace_repo_alias() {
    local workspace_name owner_name outer_container outer_repo_root legacy_repo_root

    if [ "${CODER:-false}" != "true" ] || ! command -v docker >/dev/null 2>&1; then
        return 0
    fi

    workspace_name="${CODER_WORKSPACE_NAME:-}"
    owner_name="${CODER_WORKSPACE_OWNER_NAME:-}"
    if [ -z "${workspace_name}" ] || [ -z "${owner_name}" ]; then
        return 0
    fi

    outer_container="coder-${owner_name}-${workspace_name,,}"
    outer_repo_root="/workspaces/$(basename "${ROOT_DIR}")"
    legacy_repo_root="/home/coder/$(basename "${ROOT_DIR}")"

    if ! docker inspect "${outer_container}" >/dev/null 2>&1; then
        return 0
    fi

    docker exec -u 0 "${outer_container}" sh -lc "
        set -euo pipefail
        outer_repo_root='${outer_repo_root}'
        legacy_repo_root='${legacy_repo_root}'

        if [ -f \"\${outer_repo_root}/Makefile\" ] && [ -d \"\${outer_repo_root}/.git\" ]; then
            exit 0
        fi

        if [ ! -d \"\${legacy_repo_root}/.git\" ]; then
            exit 0
        fi

        mkdir -p \"\$(dirname \"\${outer_repo_root}\")\"
        if [ -e \"\${outer_repo_root}\" ] && [ ! -L \"\${outer_repo_root}\" ]; then
            rm -rf \"\${outer_repo_root}\"
        fi
        ln -sfn \"\${legacy_repo_root}\" \"\${outer_repo_root}\"
    " >/dev/null 2>&1 || {
        echo "Warning: failed to repair outer workspace repo alias for Docker path visibility." >&2
        return 0
    }
}

echo "Waiting for Docker daemon..."
for attempt in $(seq 1 90); do
    if docker info >/dev/null 2>&1; then
        break
    fi
    if [ $((attempt % 10)) -eq 0 ]; then
        echo "Still waiting for Docker daemon... (${attempt}/90)"
    fi
    sleep 2
done

docker info >/dev/null 2>&1 || {
    echo "Docker daemon is not available. Rebuild the workspace and retry."
    exit 1
}

ensure_outer_workspace_repo_alias

ensure_apt_packages make bats

if ! command -v codex >/dev/null 2>&1; then
    npm install -g "${CODEX_NPM_PACKAGE}"
elif ! command -v codex >/dev/null 2>&1; then
    echo "Warning: Codex CLI is unavailable; skipping AI smoke tests." >&2
fi

export GH_TOKEN_VAR="${GH_TOKEN_VAR:-GH_AUTOMATION_TOKEN}"
export WORKSPACE_GITHUB_ORG="${WORKSPACE_GITHUB_ORG:-${CODESPACE_GITHUB_ORG:-VilnaCRM-Org}}"
export CODESPACE_GITHUB_ORG="${CODESPACE_GITHUB_ORG:-${WORKSPACE_GITHUB_ORG}}"

agent_env_ok=true
if ! bash scripts/local-coder/setup-secure-agent-env.sh; then
    agent_env_ok=false
    echo "Warning: secure agent bootstrap failed."
    echo "Set workspace secrets and rerun: bash scripts/local-coder/setup-secure-agent-env.sh"
fi

run_workspace_make() {
    local target="$1"

    if command -v make >/dev/null 2>&1; then
        make "$target"
        return $?
    fi

    case "${target}" in
        start)
            docker compose up --detach
            docker build -t k6 -f ./tests/Load/Dockerfile .
            docker build -t user-service-spectral -f ./docker/spectral/Dockerfile .
            ;;
        install)
            docker compose exec -T php composer install --no-progress --prefer-dist --optimize-autoloader
            ;;
        *)
            echo "Error: fallback bootstrap only supports start and install targets." >&2
            return 1
            ;;
    esac
}

if [ ! -f vendor/autoload.php ]; then
    if ! run_workspace_make start; then
        echo "Warning: initial 'make start' failed before dependency install. Retrying after install."
    fi
    run_workspace_make install
fi

run_workspace_make start

if [ "${agent_env_ok}" = true ] \
    && command -v bats >/dev/null 2>&1 \
    && command -v gh >/dev/null 2>&1 \
    && command -v codex >/dev/null 2>&1 \
    && (gh auth status >/dev/null 2>&1 || codex login status >/dev/null 2>&1); then
    gh auth setup-git >/dev/null 2>&1 || true
    bash scripts/local-coder/startup-smoke-tests.sh "${WORKSPACE_GITHUB_ORG:-${CODESPACE_GITHUB_ORG:-VilnaCRM-Org}}"
else
    echo "Skipping startup smoke tests (workspace auth or test dependencies are not ready)."
fi

echo "Workspace setup complete."
echo "Use 'make help' to list all available commands."
