"""Offline contract checks for the GitHub App-only TEST image publisher."""

from __future__ import annotations

import importlib.util
import json
import os
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

SCRIPT = Path(__file__).resolve().parents[3] / "scripts/poc_image_publisher.py"
SPEC = importlib.util.spec_from_file_location("poc_image_publisher", SCRIPT)
assert SPEC and SPEC.loader
publisher = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(publisher)
SHA = "a" * 40
DIGEST = "b" * 64


def environment() -> dict[str, str]:
    return {
        "GITHUB_REPOSITORY": publisher.REPOSITORY,
        "GITHUB_REPOSITORY_ID": publisher.REPOSITORY_ID,
        "GITHUB_REPOSITORY_OWNER_ID": publisher.OWNER_ID,
        "GITHUB_EVENT_NAME": "workflow_dispatch",
        "GITHUB_REF": "refs/heads/main",
        "GITHUB_REF_PROTECTED": "true",
        "GITHUB_WORKFLOW_REF": publisher.WORKFLOW_REF,
        "GITHUB_WORKFLOW_SHA": SHA,
        "GITHUB_ACTOR": publisher.ACTOR,
        "GITHUB_ACTOR_ID": publisher.ACTOR_ID,
        "GITHUB_TRIGGERING_ACTOR": publisher.ACTOR,
        "GITHUB_RUN_ATTEMPT": "1",
        "GITHUB_RUN_ID": "42",
        "GITHUB_SHA": SHA,
    }


def request() -> dict[str, object]:
    return {
        "source_sha": SHA,
        "platform": "linux/amd64",
        "registry_phase_receipt_id": 17,
        "registry_contract_digest": DIGEST,
        "registry_checkpoint_version": "version/+==",
    }


class PublisherTest(unittest.TestCase):
    def test_closed_identity_rejects_manual_dispatch_reruns_and_other_source(
        self,
    ) -> None:
        for key, wrong in (
            ("GITHUB_ACTOR", "Kravalg"),
            ("GITHUB_ACTOR_ID", "9444106"),
            ("GITHUB_TRIGGERING_ACTOR", "Kravalg"),
            ("GITHUB_RUN_ATTEMPT", "2"),
            ("GITHUB_REF", "refs/heads/test"),
            ("GITHUB_REF_PROTECTED", "false"),
            ("GITHUB_WORKFLOW_SHA", "c" * 40),
            ("GITHUB_REPOSITORY_ID", "1"),
        ):
            with self.subTest(key=key):
                values = environment()
                values[key] = wrong
                with self.assertRaises(ValueError):
                    publisher.context(values)

    def test_request_rejects_untrusted_and_ambiguous_fields(self) -> None:
        good = request()
        self.assertEqual(publisher.request(json.dumps(good), SHA), good)
        bad = [
            '{"source_sha":"' + SHA + '","source_sha":"' + SHA + '"}',
            json.dumps({**good, "source_sha": "c" * 40}),
            json.dumps({**good, "platform": "linux/arm64"}),
            json.dumps({**good, "registry_phase_receipt_id": True}),
            json.dumps({**good, "registry_contract_digest": "latest"}),
            json.dumps({**good, "registry_checkpoint_version": "line\nvalue"}),
            json.dumps({**good, "extra": "unreviewed"}),
            "[]",
            '{"value":NaN}',
        ]
        for value in bad:
            with self.subTest(value=value[:40]):
                with self.assertRaises(ValueError):
                    publisher.request(value, SHA)

    def test_quality_provenance_and_release_match_closed_protocol(self) -> None:
        run = publisher.context(environment())
        source = request()
        artifact = publisher._artifact("3", "sha256:" + DIGEST, "c" * 64)
        quality = publisher.quality(run, "11")
        self.assertEqual(quality["command"], "make ci")
        self.assertEqual(quality["quality_job_id"], 11)
        provenance = publisher.provenance(
            run,
            source,
            job_id="12",
            build_artifact=artifact,
            web_digest="d" * 64,
            worker_digest="e" * 64,
        )
        self.assertEqual(provenance["build_artifact"], artifact)
        self.assertEqual(provenance["build_job_id"], 12)
        self.assertEqual(provenance["web"]["target"], "frankenphp_prod")
        self.assertEqual(provenance["worker"]["target"], "app_workers")
        release = publisher.release(
            run,
            source,
            quality_artifact=artifact,
            provenance_artifact=artifact,
            web_digest="d" * 64,
            worker_digest="e" * 64,
        )
        self.assertEqual(release["workflow_ref"], publisher.WORKFLOW_REF)
        self.assertEqual(release["publisher_run_id"], 42)
        self.assertEqual(release["registry_phase_receipt_id"], 17)
        self.assertEqual(release["web"], provenance["web"])

    def test_artifact_and_image_digests_reject_mutable_tags(self) -> None:
        for value in ("latest", "SHA256:" + DIGEST, "f" * 63, "0" * 65):
            with self.subTest(value=value):
                with self.assertRaises(ValueError):
                    publisher._artifact("3", value, "c" * 64)
                with self.assertRaises(ValueError):
                    publisher._images(value, "e" * 64)

    def test_cli_writes_bounded_manifest_and_redacts_bad_request(self) -> None:
        with tempfile.TemporaryDirectory() as directory:
            target = Path(directory) / "request.json"
            values = {
                **os.environ,
                **environment(),
                "POC_REQUEST": json.dumps(request()),
            }
            good = subprocess.run(
                [sys.executable, str(SCRIPT), "validate", "--output", str(target)],
                env=values,
                capture_output=True,
                text=True,
                check=False,
            )
            self.assertEqual(good.returncode, 0, good.stderr)
            self.assertEqual(json.loads(target.read_text()), request())
            values["POC_REQUEST"] = '{"marker":"DO_NOT_ECHO_TEST_MARKER"}'
            failed = subprocess.run(
                [
                    sys.executable,
                    str(SCRIPT),
                    "validate",
                    "--output",
                    str(Path(directory) / "bad.json"),
                ],
                env=values,
                capture_output=True,
                text=True,
                check=False,
            )
            self.assertEqual(failed.returncode, 1)
            self.assertNotIn("DO_NOT_ECHO_TEST_MARKER", failed.stdout + failed.stderr)


if __name__ == "__main__":
    unittest.main()
