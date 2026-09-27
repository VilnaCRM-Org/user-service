#!/usr/bin/env python3
"""Build the closed, non-secret TEST image publication evidence documents."""

from __future__ import annotations

import argparse
import json
import os
import re
import sys
from pathlib import Path

REPOSITORY = "VilnaCRM-Org/user-service"
REPOSITORY_ID = "646535009"
OWNER_ID = "114362548"
ACTOR = "vilnacrm-user-service-evidence[bot]"
ACTOR_ID = "325789989"
WORKFLOW_REF = REPOSITORY + "/.github/workflows/publish-poc-images.yml@refs/heads/main"
REGISTRY = "891377212104.dkr.ecr.eu-central-1.amazonaws.com"
REQUEST_KEYS = {
    "source_sha",
    "platform",
    "registry_phase_receipt_id",
    "registry_contract_digest",
    "registry_checkpoint_version",
}


def require(condition: bool, category: str) -> None:
    if not condition:
        raise ValueError(category)


def _pairs(pairs: list[tuple[str, object]]) -> dict[str, object]:
    value: dict[str, object] = {}
    for key, item in pairs:
        require(key not in value, "duplicate-json-key")
        value[key] = item
    return value


def _invalid_constant(_value: str) -> None:
    raise ValueError("non-finite-json")


def _json(raw: str) -> dict[str, object]:
    require(len(raw.encode("utf-8")) <= 4096, "json-size")
    value = json.loads(raw, object_pairs_hook=_pairs, parse_constant=_invalid_constant)
    require(type(value) is dict, "json-object")
    return value


def _hex(raw: str) -> str:
    require(type(raw) is str, "sha256-type")
    value = raw.removeprefix("sha256:")
    require(re.fullmatch(r"[0-9a-f]{64}", value) is not None, "sha256")
    return value


def _positive(raw: str | int) -> int:
    require(type(raw) in (str, int), "integer-type")
    value = str(raw)
    require(re.fullmatch(r"[1-9][0-9]*", value) is not None, "integer")
    return int(value)


def context(environment: dict[str, str]) -> dict[str, object]:
    required = {
        "GITHUB_REPOSITORY": REPOSITORY,
        "GITHUB_REPOSITORY_ID": REPOSITORY_ID,
        "GITHUB_REPOSITORY_OWNER_ID": OWNER_ID,
        "GITHUB_EVENT_NAME": "workflow_dispatch",
        "GITHUB_REF": "refs/heads/main",
        "GITHUB_REF_PROTECTED": "true",
        "GITHUB_WORKFLOW_REF": WORKFLOW_REF,
        "GITHUB_ACTOR": ACTOR,
        "GITHUB_ACTOR_ID": ACTOR_ID,
        "GITHUB_TRIGGERING_ACTOR": ACTOR,
        "GITHUB_RUN_ATTEMPT": "1",
    }
    for key, expected in required.items():
        require(environment.get(key) == expected, "publisher-run-identity")
    sha = environment.get("GITHUB_SHA", "")
    require(re.fullmatch(r"[0-9a-f]{40}", sha) is not None, "source-sha")
    require(environment.get("GITHUB_WORKFLOW_SHA") == sha, "workflow-sha")
    return {
        "source_sha": sha,
        "run_id": _positive(environment.get("GITHUB_RUN_ID", "")),
    }


def request(raw: str, source_sha: str) -> dict[str, object]:
    value = _json(raw)
    require(set(value) == REQUEST_KEYS, "request-fields")
    require(value["source_sha"] == source_sha, "request-source")
    require(value["platform"] == "linux/amd64", "request-platform")
    require(
        type(value["registry_phase_receipt_id"]) is int,
        "request-receipt-type",
    )
    _positive(value["registry_phase_receipt_id"])
    _hex(value["registry_contract_digest"])
    version = value["registry_checkpoint_version"]
    require(
        type(version) is str
        and 1 <= len(version) <= 1024
        and all(32 <= ord(char) != 127 for char in version),
        "request-checkpoint",
    )
    return value


def _read_request(path: Path, sha: str) -> dict[str, object]:
    require(path.is_file() and path.stat().st_size <= 4096, "request-file")
    return request(path.read_text(encoding="utf-8"), sha)


def _write(path: Path, value: dict[str, object]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    raw = json.dumps(value, sort_keys=True, separators=(",", ":"), allow_nan=False)
    descriptor = os.open(path, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
    with os.fdopen(descriptor, "w", encoding="utf-8") as output:
        output.write(raw + "\n")


def _common(run: dict[str, object]) -> dict[str, object]:
    return {
        "repository": REPOSITORY,
        "source_sha": run["source_sha"],
        "publisher_run_id": run["run_id"],
        "publisher_run_attempt": 1,
        "workflow_sha": run["source_sha"],
    }


def _artifact(identifier: str, archive: str, file_digest: str) -> dict[str, object]:
    return {
        "artifact_id": _positive(identifier),
        "archive_sha256": _hex(archive),
        "file_sha256": _hex(file_digest),
    }


def _images(web_digest: str, worker_digest: str) -> dict[str, dict[str, str]]:
    return {
        "web": {
            "repository_uri": REGISTRY + "/user-service-test-web",
            "target": "frankenphp_prod",
            "digest": "sha256:" + _hex(web_digest),
        },
        "worker": {
            "repository_uri": REGISTRY + "/user-service-test-worker",
            "target": "app_workers",
            "digest": "sha256:" + _hex(worker_digest),
        },
    }


def quality(run: dict[str, object], job_id: str) -> dict[str, object]:
    return {
        **_common(run),
        "schema_version": "poc-quality-v1",
        "quality_job_id": _positive(job_id),
        "command": "make ci",
        "conclusion": "success",
    }


def provenance(
    run: dict[str, object],
    source: dict[str, object],
    *,
    job_id: str,
    build_artifact: dict[str, object],
    web_digest: str,
    worker_digest: str,
) -> dict[str, object]:
    return {
        **_common(run),
        "schema_version": "poc-build-provenance-v1",
        "platform": "linux/amd64",
        "build_job_id": _positive(job_id),
        "build_artifact": build_artifact,
        "registry": {
            "registry_phase_receipt_id": source["registry_phase_receipt_id"],
            "registry_contract_digest": source["registry_contract_digest"],
            "registry_checkpoint_version": source["registry_checkpoint_version"],
        },
        **_images(web_digest, worker_digest),
    }


def release(
    run: dict[str, object],
    source: dict[str, object],
    *,
    quality_artifact: dict[str, object],
    provenance_artifact: dict[str, object],
    web_digest: str,
    worker_digest: str,
) -> dict[str, object]:
    return {
        "schema_version": "poc-release-v1",
        "repository": REPOSITORY,
        "repository_id": int(REPOSITORY_ID),
        "owner_id": int(OWNER_ID),
        "source_sha": run["source_sha"],
        "publisher_run_id": run["run_id"],
        "publisher_run_attempt": 1,
        "workflow_ref": WORKFLOW_REF,
        "platform": "linux/amd64",
        "runtime_contract_version": "poc-test-v1",
        "provenance": provenance_artifact,
        "quality_evidence": quality_artifact,
        **_images(web_digest, worker_digest),
        "registry_phase_receipt_id": source["registry_phase_receipt_id"],
        "registry_contract_digest": source["registry_contract_digest"],
        "registry_checkpoint_version": source["registry_checkpoint_version"],
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "mode", choices=("validate", "quality", "provenance", "release")
    )
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--request-file", type=Path)
    parser.add_argument("--job-id")
    parser.add_argument("--build-artifact-id")
    parser.add_argument("--build-archive-sha256")
    parser.add_argument("--build-file-sha256")
    parser.add_argument("--quality-artifact-id")
    parser.add_argument("--quality-archive-sha256")
    parser.add_argument("--quality-file-sha256")
    parser.add_argument("--provenance-artifact-id")
    parser.add_argument("--provenance-archive-sha256")
    parser.add_argument("--provenance-file-sha256")
    parser.add_argument("--web-digest")
    parser.add_argument("--worker-digest")
    args = parser.parse_args()
    try:
        run = context(dict(os.environ))
        if args.mode == "validate":
            value = request(os.environ.get("POC_REQUEST", ""), str(run["source_sha"]))
        else:
            require(args.request_file is not None, "request-file-required")
            source = _read_request(args.request_file, str(run["source_sha"]))
            if args.mode == "quality":
                value = quality(run, args.job_id)
            elif args.mode == "provenance":
                value = provenance(
                    run,
                    source,
                    job_id=args.job_id,
                    build_artifact=_artifact(
                        args.build_artifact_id,
                        args.build_archive_sha256,
                        args.build_file_sha256,
                    ),
                    web_digest=args.web_digest,
                    worker_digest=args.worker_digest,
                )
            else:
                value = release(
                    run,
                    source,
                    quality_artifact=_artifact(
                        args.quality_artifact_id,
                        args.quality_archive_sha256,
                        args.quality_file_sha256,
                    ),
                    provenance_artifact=_artifact(
                        args.provenance_artifact_id,
                        args.provenance_archive_sha256,
                        args.provenance_file_sha256,
                    ),
                    web_digest=args.web_digest,
                    worker_digest=args.worker_digest,
                )
        _write(args.output, value)
        return 0
    except (
        ValueError,
        TypeError,
        KeyError,
        OSError,
        UnicodeError,
        json.JSONDecodeError,
    ):
        print("INVALID: TEST publisher evidence inputs", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
