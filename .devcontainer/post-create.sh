#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"
SETTINGS_FILE="${ROOT_DIR}/.devcontainer/codespaces-settings.env"

if [ -f "${SETTINGS_FILE}" ]; then
    # shellcheck disable=SC1090
    . "${SETTINGS_FILE}"
fi

# Codespaces host Docker currently exposes API 1.43; newer clients need this pin.
export DOCKER_API_VERSION="${DOCKER_API_VERSION:-1.43}"
: "${CLAUDE_NPM_PACKAGE:=@anthropic-ai/claude-code}"

ensure_apt_packages() {
    local missing_packages=()
    local package

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
    echo "Docker daemon is not available. Rebuild the Codespace and retry."
    exit 1
}

ensure_apt_packages make bats

if ! command -v claude >/dev/null 2>&1; then
    npm install -g "${CLAUDE_NPM_PACKAGE}"
fi

export GH_TOKEN_VAR="${GH_TOKEN_VAR:-GH_AUTOMATION_TOKEN}"
export CODESPACE_GITHUB_ORG="${CODESPACE_GITHUB_ORG:-VilnaCRM-Org}"

if ! bash scripts/codespaces/setup-secure-agent-env.sh; then
    echo "Error: secure agent bootstrap failed." >&2
    echo "Set Codespaces secrets and rerun: bash scripts/codespaces/setup-secure-agent-env.sh" >&2
    exit 1
fi

make start

if [ ! -f vendor/autoload.php ]; then
    make install
fi

bash scripts/codespaces/startup-smoke-tests.sh "${CODESPACE_GITHUB_ORG:-VilnaCRM-Org}"

echo "Codespace setup complete."
echo "Use 'make help' to list all available commands."
