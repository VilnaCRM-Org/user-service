"""Static workflow boundary checks; native metadata is tested separately."""

import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]


class PublisherWorkflowTests(unittest.TestCase):
    def test_fixed_three_job_workflow_and_no_manual_secret_contract(self):
        text = (ROOT / ".github/workflows/publish-poc-images.yml").read_text()
        for name in (
            "Validate application release",
            "Build application images",
            "Publish TEST application images",
        ):
            self.assertEqual(text.count("name: " + name + "\n"), 1)
        self.assertEqual(text.count("    runs-on: ubuntu-latest"), 3)
        self.assertIn("  workflow_dispatch:", text)
        self.assertNotIn("${{ inputs.", text)
        self.assertNotIn("secrets.", text)
        self.assertNotIn("pull_request_target:", text)
        self.assertEqual(text.count("persist-credentials: false"), 5)

    def test_only_fresh_publisher_has_oidc_after_verified_bytes(self):
        text = (ROOT / ".github/workflows/publish-poc-images.yml").read_text()
        build, publish = text.split("  publish:", 1)
        self.assertNotIn("id-token: write", build)
        self.assertNotIn("configure-aws-credentials", build)
        self.assertIn("environment: poc-test-images", publish)
        self.assertIn("github.ref == 'refs/heads/main'", publish)
        self.assertLess(
            publish.index("poc_image_publisher.py prepare"),
            publish.index("configure-aws-credentials@"),
        )
        self.assertLess(
            publish.index("configure-aws-credentials@"),
            publish.index("poc_image_publisher.py publish"),
        )
        self.assertNotIn("make ", publish)
        self.assertNotIn("path: .source", publish)
        self.assertIn(
            "role-to-assume: arn:aws:iam::891377212104:role/user-service-test-ImagePublisher",
            publish,
        )
        self.assertIn("allowed-account-ids: '891377212104'", publish)

    def test_evidence_is_uploaded_by_publisher_only_with_native_readback(self):
        text = (ROOT / ".github/workflows/publish-poc-images.yml").read_text()
        earlier, publish = text.split("  publish:", 1)
        for name, member in (
            ("poc-build-provenance", "provenance.json"),
            ("poc-quality-evidence", "quality.json"),
            ("poc-release-manifest", "release-manifest.json"),
        ):
            self.assertNotIn("name: " + name, earlier)
            self.assertIn("name: " + name + "-${{ github.run_id }}-1", publish)
            self.assertIn("/poc-images/" + member, publish)
        self.assertLess(
            publish.index("poc_image_publisher.py manifest"),
            publish.index("name: poc-release-manifest-"),
        )
        self.assertGreater(
            publish.index("poc_image_publisher.py readback"),
            publish.index("name: poc-release-manifest-"),
        )
