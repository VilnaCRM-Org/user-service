#!/bin/bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_REF="${1:-origin/main}"
OPENAPI_DIR="$REPO_ROOT/.github/openapi-spec"
HEAD_SPEC="$OPENAPI_DIR/spec.yaml"
TMP_DIR="$REPO_ROOT/var/tmp"
BASE_SPEC="$TMP_DIR/openapi-base-spec.yaml"

if [ ! -f "$HEAD_SPEC" ]; then
  echo "Error: $HEAD_SPEC not found. Generate the OpenAPI spec before running the diff."
  exit 1
fi

mkdir -p "$TMP_DIR"

if ! git show "$BASE_REF:.github/openapi-spec/spec.yaml" > "$BASE_SPEC" 2>/dev/null; then
  echo "Error: Unable to read OpenAPI spec from $BASE_REF. Ensure the reference exists and includes .github/openapi-spec/spec.yaml."
  exit 1
fi

docker run --rm \
  -v "$HEAD_SPEC:/workspace/head.yaml" \
  -v "$BASE_SPEC:/workspace/base.yaml" \
  openapitools/openapi-diff:latest \
  /workspace/head.yaml \
  /workspace/base.yaml
