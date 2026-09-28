"""Synthetic contract checks; no build, AWS, GitHub or producer authentication."""

import dataclasses
import importlib.util
import json
import sys
import unittest
from pathlib import Path

SCRIPT = Path(__file__).resolve().parents[2] / "scripts/poc_release_manifest.py"
SPEC = importlib.util.spec_from_file_location("poc_release_manifest", SCRIPT)
codec = importlib.util.module_from_spec(SPEC)
sys.modules[SPEC.name] = codec
SPEC.loader.exec_module(codec)


class ReleaseManifestTests(unittest.TestCase):
    def setUp(self):
        self.registry = codec.RegistryReleaseBinding(17, "a" * 64, "opaque/+version==")
        self.artifact = {
            "artifact_id": 41,
            "archive_sha256": "b" * 64,
            "file_sha256": "c" * 64,
        }
        self.arguments = {
            "source_sha": "d" * 40,
            "publisher_run_id": 53,
            "platform": "linux/amd64",
            "registry": self.registry,
            "provenance": self.artifact,
            "quality_evidence": dict(self.artifact, artifact_id=42),
        }
        for kind, target in codec.TARGETS.items():
            self.arguments[kind] = codec.BuildResult(
                "d" * 40,
                53,
                "linux/amd64",
                f"{codec.REGISTRY}/user-service-test-{kind}",
                target,
                "sha256:" + "e" * 64,
            )

    def encoded(self):
        return codec.build_release_manifest(**self.arguments)

    def reject(self, raw):
        with self.assertRaises(codec.ReleaseManifestError) as raised:
            codec.validate_release_manifest(raw, registry=self.registry)
        self.assertRegex(str(raised.exception), r"^[a-z-]+$")

    def test_roundtrip_is_closed_and_deterministic(self):
        encoded = self.encoded()
        manifest = codec.validate_release_manifest(encoded, registry=self.registry)
        self.assertEqual(encoded, self.encoded())
        self.assertEqual(manifest["registry_phase_receipt_id"], 17)
        self.assertEqual(manifest["web"]["target"], "frankenphp_prod")
        self.assertEqual(manifest["worker"]["target"], "app_workers")
        self.assertEqual(manifest["publisher_run_attempt"], 1)
        self.assertEqual(manifest["workflow_ref"], codec.WORKFLOW_REF)
        self.assertEqual(manifest["web"]["digest"], manifest["worker"]["digest"])
        self.assertEqual(manifest["registry_checkpoint_version"], "opaque/+version==")
        manifest["provenance"]["artifact_id"] = 99
        self.assertEqual(self.artifact["artifact_id"], 41)

    def test_both_platforms_and_opaque_utf8_version(self):
        for platform in ("linux/amd64", "linux/arm64"):
            self.arguments["platform"] = platform
            for kind in codec.TARGETS:
                self.arguments[kind] = dataclasses.replace(
                    self.arguments[kind], platform=platform
                )
            self.arguments["registry"] = dataclasses.replace(
                self.registry, registry_checkpoint_version="é" * 1024
            )
            raw = self.encoded()
            codec.validate_release_manifest(raw, registry=self.arguments["registry"])

    def test_invalid_registry_references(self):
        cases = {
            "registry_phase_receipt_id": (False, 1.0, 0, -1, "17"),
            "registry_contract_digest": (None, "A" * 64, "a" * 63),
            "registry_checkpoint_version": (
                None,
                "",
                "null",
                "x" * 1025,
                "x\n",
                "x\x7f",
                "\ud800",
            ),
        }
        for field, values in cases.items():
            for value in values:
                with self.subTest(field=field, value=repr(value)):
                    binding = dataclasses.replace(self.registry, **{field: value})
                    with self.assertRaises(codec.ReleaseManifestError):
                        codec.validate_release_manifest(
                            self.encoded(), registry=binding
                        )

    def test_fresh_registry_binding_must_match(self):
        for field, value in (
            ("registry_phase_receipt_id", 18),
            ("registry_contract_digest", "f" * 64),
            ("registry_checkpoint_version", "next"),
        ):
            with (
                self.subTest(field=field),
                self.assertRaisesRegex(codec.ReleaseManifestError, "manifest-binding"),
            ):
                codec.validate_release_manifest(
                    self.encoded(),
                    registry=dataclasses.replace(self.registry, **{field: value}),
                )

    def test_invalid_build_context(self):
        for field, values in {
            "source_sha": ("main", "D" * 40, None),
            "publisher_run_id": (True, 0, 1.0, "53"),
            "platform": ("linux/386", None),
        }.items():
            for value in values:
                with (
                    self.subTest(field=field),
                    self.assertRaises(codec.ReleaseManifestError),
                ):
                    codec.build_release_manifest(
                        **dict(self.arguments, **{field: value})
                    )

    def test_cross_build_and_foreign_destinations_rejected(self):
        changes = {
            "source_sha": "f" * 40,
            "publisher_run_id": True,
            "platform": "linux/arm64",
            "repository_uri": "foreign/repo",
            "target": "frankenphp_dev",
            "digest": "latest",
        }
        for kind in codec.TARGETS:
            for field, value in changes.items():
                with (
                    self.subTest(kind=kind, field=field),
                    self.assertRaises(codec.ReleaseManifestError),
                ):
                    changed = dataclasses.replace(
                        self.arguments[kind], **{field: value}
                    )
                    codec.build_release_manifest(
                        **dict(self.arguments, **{kind: changed})
                    )

    def test_artifact_reference_shapes(self):
        malformed = [
            [],
            {},
            dict(self.artifact, extra=1),
            dict(self.artifact, artifact_id=True),
            dict(self.artifact, archive_sha256="A" * 64),
            dict(self.artifact, file_sha256=""),
        ]
        for kind in ("provenance", "quality_evidence"):
            for value in malformed:
                with (
                    self.subTest(kind=kind),
                    self.assertRaises(codec.ReleaseManifestError),
                ):
                    codec.build_release_manifest(
                        **dict(self.arguments, **{kind: value})
                    )

    def test_manifest_fields_and_identity(self):
        original = json.loads(self.encoded())
        changes = {
            "schema_version": "other",
            "repository": "foreign/repo",
            "repository_id": True,
            "owner_id": 114362548.0,
            "publisher_run_attempt": True,
            "registry_phase_receipt_id": 17.0,
            "workflow_ref": "refs/pull/492/merge",
            "runtime_contract_version": "other",
            "platform": "linux/386",
            "source_sha": "main",
            "extra": "private-value",
            "web": [],
            "worker": dict(original["worker"], extra=True),
        }
        for key, value in changes.items():
            with self.subTest(key=key):
                self.reject(json.dumps(dict(original, **{key: value})).encode())
        for key in original:
            mutated = dict(original)
            del mutated[key]
            self.reject(json.dumps(mutated).encode())

    def test_json_failures_are_sanitized(self):
        for raw in (
            b"",
            b"x" * (codec.MAX_MANIFEST_BYTES + 1),
            "{}",
            b"[]",
            b"null",
            b"{secret",
            b"\xff",
            b'{"x":NaN}',
            b'{"x":Infinity}',
            b'{"x":1,"x":2}',
            b'{"x":{"nested":1,"nested":2}}',
            b"[" * 2000 + b"]" * 2000,
            b'{"x":' + b"9" * 5000 + b"}",
        ):
            with self.subTest(raw_type=type(raw)):
                self.reject(raw)

    def test_ordinary_json_whitespace_is_legal(self):
        self.assertEqual(
            codec.validate_release_manifest(self.encoded(), registry=self.registry),
            codec.validate_release_manifest(
                json.dumps(json.loads(self.encoded()), indent=2).encode(),
                registry=self.registry,
            ),
        )


if __name__ == "__main__":
    unittest.main()
