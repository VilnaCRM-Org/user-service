"""Pure evidence binding tests; native producer authority is a separate check."""

import copy
import json
import unittest

import test_poc_release_manifest as fixtures

codec = fixtures.codec


class ReleaseEvidenceTests(unittest.TestCase):
    def setUp(self):
        fixture = fixtures.ReleaseManifestTests()
        fixture.setUp()
        self.registry = fixture.registry
        self.manifest = json.loads(fixture.encoded())
        self.arguments = {
            key: value
            for key, value in fixture.arguments.items()
            if key not in ("provenance", "quality_evidence")
        }
        self.arguments.update(
            workflow_sha="f" * 40,
            quality_job_id=61,
            build_job_id=62,
            build_artifact=fixture.artifact,
        )
        self.verification = {
            key: self.arguments[key]
            for key in ("workflow_sha", "quality_job_id", "build_job_id")
        }
        self.verification["manifest"] = self.manifest

    def test_native_reference_comparison_roundtrip(self):
        documents = codec.build_release_evidence(**self.arguments)
        codec.validate_release_evidence(*documents, **self.verification)
        self.assertEqual(json.loads(documents[1])["command"], "make ci")
        self.assertEqual(
            json.loads(documents[0])["registry"]["registry_phase_receipt_id"], 17
        )

    def test_wrong_job_workflow_source_or_registry_is_rejected(self):
        documents = codec.build_release_evidence(**self.arguments)
        for key, value in (
            ("quality_job_id", 99),
            ("build_job_id", 99),
            ("workflow_sha", "a" * 40),
        ):
            with self.subTest(key=key), self.assertRaises(codec.ReleaseManifestError):
                codec.validate_release_evidence(
                    *documents, **dict(self.verification, **{key: value})
                )
        for key, value in (("source_sha", "a" * 40), ("registry_phase_receipt_id", 99)):
            changed = dict(self.manifest, **{key: value})
            with self.subTest(key=key), self.assertRaises(codec.ReleaseManifestError):
                codec.validate_release_evidence(
                    *documents, **dict(self.verification, manifest=changed)
                )

    def test_closed_documents_and_strict_integer_types(self):
        documents = codec.build_release_evidence(**self.arguments)
        for index in (0, 1):
            document = json.loads(documents[index])
            for key, value in (
                ("extra", 1),
                ("publisher_run_attempt", True),
                ("repository", "foreign/repo"),
            ):
                changed = list(documents)
                changed[index] = json.dumps(dict(document, **{key: value})).encode()
                with (
                    self.subTest(index=index, key=key),
                    self.assertRaises(codec.ReleaseManifestError),
                ):
                    codec.validate_release_evidence(*changed, **self.verification)

    def test_ambiguous_or_unbounded_json_fails(self):
        documents = codec.build_release_evidence(**self.arguments)
        for raw in (
            b"",
            b"x" * 16385,
            b"[]",
            b"{",
            b'{"x":1,"x":2}',
            b'{"x":NaN}',
            b"\xff",
        ):
            with (
                self.subTest(raw_length=len(raw)),
                self.assertRaises(codec.ReleaseManifestError),
            ):
                codec.validate_release_evidence(raw, documents[1], **self.verification)

    def test_invalid_encoder_bindings(self):
        for key, value in (
            ("quality_job_id", True),
            ("build_job_id", 0),
            ("publisher_run_id", 1.0),
            ("workflow_sha", "bad"),
        ):
            with self.subTest(key=key), self.assertRaises(codec.ReleaseManifestError):
                codec.build_release_evidence(**dict(self.arguments, **{key: value}))
        missing = copy.deepcopy(self.manifest)
        del missing["web"]
        with self.assertRaises(codec.ReleaseManifestError):
            codec.validate_release_evidence(
                *codec.build_release_evidence(**self.arguments),
                **dict(self.verification, manifest=missing),
            )


if __name__ == "__main__":
    unittest.main()
