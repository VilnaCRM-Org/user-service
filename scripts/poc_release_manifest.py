"""Offline TEST release codec; supplied references do not establish authenticity.

The future trusted publisher must authenticate the registry deployment and original
artifact producers before acquiring AWS credentials. It must build both explicit
targets from the admitted source and observe the pushed digests. This module does
not perform those operations or authorize publishing, promotion, or rollback.
"""

from __future__ import annotations

import json
import re
from dataclasses import asdict, dataclass
from typing import Any

REPOSITORY = "VilnaCRM-Org/user-service"
WORKFLOW_REF = f"{REPOSITORY}/.github/workflows/publish-poc-images.yml@refs/heads/main"
PUBLISHER_ROLE_ARN = "arn:aws:iam::891377212104:role/user-service-test-ImagePublisher"
PUBLISHER_ENVIRONMENT = "poc-test-images"
REGISTRY = "891377212104.dkr.ecr.eu-central-1.amazonaws.com"
TARGETS = {"web": "frankenphp_prod", "worker": "app_workers"}
MAX_MANIFEST_BYTES = 16384


class ReleaseManifestError(ValueError):
    """A fixed category only; never includes supplied values."""


def _require(valid: bool, category: str) -> None:
    if not valid:
        raise ReleaseManifestError(category)


def _hex(value: object, length: int) -> bool:
    return (
        isinstance(value, str)
        and re.fullmatch(f"[0-9a-f]{{{length}}}", value) is not None
    )


def _positive(value: object) -> bool:
    return type(value) is int and value > 0


@dataclass(frozen=True)
class RegistryReleaseBinding:
    registry_phase_receipt_id: int
    registry_contract_digest: str
    registry_checkpoint_version: str

    def validate(self) -> None:
        _require(_positive(self.registry_phase_receipt_id), "registry-receipt")
        _require(_hex(self.registry_contract_digest, 64), "registry-contract")
        version = self.registry_checkpoint_version
        _require(
            isinstance(version, str)
            and 1 <= len(version) <= 1024
            and version != "null"
            and re.fullmatch(r"[^\x00-\x1f\x7f]+", version) is not None,
            "registry-version",
        )
        try:
            version.encode("utf-8")
        except UnicodeError:
            raise ReleaseManifestError("registry-version") from None


@dataclass(frozen=True)
class BuildResult:
    source_sha: str
    publisher_run_id: int
    platform: str
    repository_uri: str
    target: str
    digest: str


def _artifact(value: dict[str, Any]) -> dict[str, Any]:
    if not isinstance(value, dict):
        raise ReleaseManifestError("artifact-fields")
    _require(
        set(value) == {"artifact_id", "archive_sha256", "file_sha256"},
        "artifact-fields",
    )
    _require(_positive(value["artifact_id"]), "artifact-id")
    _require(_hex(value["archive_sha256"], 64), "artifact-archive")
    _require(_hex(value["file_sha256"], 64), "artifact-file")
    return dict(value)


def _image(
    kind: str, result: BuildResult, source: str, run: int, platform: str
) -> dict[str, str]:
    _require(result.source_sha == source, "build-source")
    _require(
        _positive(result.publisher_run_id) and result.publisher_run_id == run,
        "build-run",
    )
    _require(result.platform == platform, "build-platform")
    _require(
        result.repository_uri == f"{REGISTRY}/user-service-test-{kind}",
        "image-repository",
    )
    _require(result.target == TARGETS[kind], "image-target")
    _require(
        isinstance(result.digest, str)
        and re.fullmatch(r"sha256:[0-9a-f]{64}", result.digest) is not None,
        "image-digest",
    )
    return {
        "repository_uri": result.repository_uri,
        "target": result.target,
        "digest": result.digest,
    }


def build_release_manifest(
    *,
    source_sha: str,
    publisher_run_id: int,
    platform: str,
    registry: RegistryReleaseBinding,
    provenance: dict[str, Any],
    quality_evidence: dict[str, Any],
    web: BuildResult,
    worker: BuildResult,
) -> bytes:
    """Encode declared build observations; no source or receipt authentication."""
    registry.validate()
    _require(_hex(source_sha, 40), "source-sha")
    _require(_positive(publisher_run_id), "publisher-run")
    _require(platform in ("linux/amd64", "linux/arm64"), "platform")
    manifest = {
        "schema_version": "poc-release-v1",
        "repository": REPOSITORY,
        "repository_id": 646535009,
        "owner_id": 114362548,
        "source_sha": source_sha,
        "publisher_run_id": publisher_run_id,
        "publisher_run_attempt": 1,
        "workflow_ref": WORKFLOW_REF,
        "platform": platform,
        "runtime_contract_version": "poc-test-v1",
        **asdict(registry),
        "provenance": _artifact(provenance),
        "quality_evidence": _artifact(quality_evidence),
        "web": _image("web", web, source_sha, publisher_run_id, platform),
        "worker": _image("worker", worker, source_sha, publisher_run_id, platform),
    }
    return (
        json.dumps(manifest, sort_keys=True, separators=(",", ":"), ensure_ascii=True)
        + "\n"
    ).encode()


def _pairs(pairs: list[tuple[str, Any]]) -> dict[str, Any]:
    result: dict[str, Any] = {}
    for key, value in pairs:
        _require(key not in result, "duplicate-key")
        result[key] = value
    return result


def _nonfinite(value: str) -> None:
    raise ReleaseManifestError("nonfinite-json")


def validate_release_manifest(
    raw: bytes, *, registry: RegistryReleaseBinding
) -> dict[str, Any]:
    """Compare registry references supplied by the future authenticated caller.

    Historical source SHAs remain legal for a separately admitted rollback. The
    caller must authenticate original quality/provenance artifacts, their archive
    and file hashes, producer identity, freshness/retention and registry compatibility.
    """
    registry.validate()
    _require(type(raw) is bytes and 0 < len(raw) <= MAX_MANIFEST_BYTES, "manifest-size")
    try:
        manifest = json.loads(raw, object_pairs_hook=_pairs, parse_constant=_nonfinite)
        _require(isinstance(manifest, dict), "manifest-fields")
        _require(type(manifest["repository_id"]) is int, "repository-id")
        _require(type(manifest["owner_id"]) is int, "owner-id")
        _require(type(manifest["publisher_run_attempt"]) is int, "publisher-attempt")
        _require(type(manifest["registry_phase_receipt_id"]) is int, "registry-receipt")
        source, run, platform = (
            manifest[key] for key in ("source_sha", "publisher_run_id", "platform")
        )
        builds = {}
        for kind in TARGETS:
            image = manifest[kind]
            _require(
                isinstance(image, dict)
                and set(image) == {"repository_uri", "target", "digest"},
                "image-fields",
            )
            builds[kind] = BuildResult(source, run, platform, **image)
        expected = build_release_manifest(
            source_sha=source,
            publisher_run_id=run,
            platform=platform,
            registry=registry,
            provenance=manifest["provenance"],
            quality_evidence=manifest["quality_evidence"],
            **builds,
        )
        _require(manifest == json.loads(expected), "manifest-binding")
        return manifest
    except ReleaseManifestError:
        raise
    except (KeyError, TypeError, ValueError, RecursionError):
        raise ReleaseManifestError("manifest-json") from None


def build_release_evidence(
    *,
    source_sha,
    publisher_run_id,
    platform,
    registry,
    web,
    worker,
    workflow_sha,
    quality_job_id,
    build_job_id,
    build_artifact,
):
    """Encode native observations supplied by the trusted publisher."""
    registry.validate()
    _require(_hex(source_sha, 40) and _hex(workflow_sha, 40), "evidence-sha")
    _require(
        all(_positive(v) for v in (publisher_run_id, quality_job_id, build_job_id)),
        "evidence-id",
    )
    _require(platform in ("linux/amd64", "linux/arm64"), "platform")
    common = {
        "repository": REPOSITORY,
        "source_sha": source_sha,
        "publisher_run_id": publisher_run_id,
        "publisher_run_attempt": 1,
        "workflow_sha": workflow_sha,
    }
    quality = {
        **common,
        "schema_version": "poc-quality-v1",
        "quality_job_id": quality_job_id,
        "command": "make ci",
        "conclusion": "success",
    }
    provenance = {
        **common,
        "schema_version": "poc-build-provenance-v1",
        "platform": platform,
        "build_job_id": build_job_id,
        "build_artifact": _artifact(build_artifact),
        "registry": asdict(registry),
        "web": _image("web", web, source_sha, publisher_run_id, platform),
        "worker": _image("worker", worker, source_sha, publisher_run_id, platform),
    }
    return tuple(
        (json.dumps(value, sort_keys=True, separators=(",", ":")) + "\n").encode()
        for value in (provenance, quality)
    )


def _evidence_document(raw):
    _require(type(raw) is bytes and 0 < len(raw) <= MAX_MANIFEST_BYTES, "evidence-size")
    try:
        document = json.loads(raw, object_pairs_hook=_pairs, parse_constant=_nonfinite)
        _require(type(document) is dict, "evidence-fields")
        return document
    except ReleaseManifestError:
        raise
    except (ValueError, TypeError, RecursionError):
        raise ReleaseManifestError("evidence-json") from None


def validate_release_evidence(
    provenance_bytes,
    quality_bytes,
    *,
    manifest,
    workflow_sha,
    quality_job_id,
    build_job_id,
    build_artifact,
):
    """Compare evidence with separately authenticated manifest and native job IDs."""
    provenance, quality = map(_evidence_document, (provenance_bytes, quality_bytes))
    try:
        registry = RegistryReleaseBinding(
            **{
                key: manifest[key]
                for key in RegistryReleaseBinding.__dataclass_fields__
            }
        )
        source, run, platform = (
            manifest[k] for k in ("source_sha", "publisher_run_id", "platform")
        )
        expected = build_release_evidence(
            source_sha=source,
            publisher_run_id=run,
            platform=platform,
            registry=registry,
            web=BuildResult(source, run, platform, **manifest["web"]),
            worker=BuildResult(source, run, platform, **manifest["worker"]),
            workflow_sha=workflow_sha,
            quality_job_id=quality_job_id,
            build_job_id=build_job_id,
            build_artifact=build_artifact,
        )
        _require(
            all(
                json.dumps(actual, sort_keys=True)
                == json.dumps(json.loads(want), sort_keys=True)
                for actual, want in zip((provenance, quality), expected, strict=True)
            ),
            "evidence-binding",
        )
    except (KeyError, TypeError, ValueError) as error:
        if isinstance(error, ReleaseManifestError):
            raise
        raise ReleaseManifestError("evidence-fields") from None
