"""Simulated native API/command evidence; no AWS or GitHub writes."""

import copy
import hashlib
import importlib.util
import io
import json
import os
import subprocess
import sys
import tarfile
import tempfile
import unittest
import zipfile
from pathlib import Path
from unittest.mock import MagicMock, patch

PATH = Path(__file__).resolve().parents[2] / "scripts/poc_image_publisher.py"
SPEC = importlib.util.spec_from_file_location("poc_image_publisher", PATH)
publisher = importlib.util.module_from_spec(SPEC)
sys.modules[SPEC.name] = publisher
SPEC.loader.exec_module(publisher)
codec = publisher.codec


def fixture():
    actor = {
        "id": publisher.BOT_ID,
        "login": f"{publisher.APP_SLUG}[bot]",
        "type": "Bot",
    }
    repository = {
        "id": 646535009,
        "full_name": codec.REPOSITORY,
        "owner": {"id": 114362548},
    }
    request = {
        "source_sha": "c" * 40,
        "platform": "linux/amd64",
        "registry_phase_receipt_id": 17,
        "registry_contract_digest": "b" * 64,
        "registry_checkpoint_version": "opaque/+version==",
    }
    env = {
        "GITHUB_REPOSITORY": codec.REPOSITORY,
        "GITHUB_REPOSITORY_ID": "646535009",
        "GITHUB_REPOSITORY_OWNER_ID": "114362548",
        "GITHUB_EVENT_NAME": "workflow_dispatch",
        "GITHUB_REF": "refs/heads/main",
        "GITHUB_RUN_ATTEMPT": "1",
        "GITHUB_WORKFLOW_REF": codec.WORKFLOW_REF,
        "GITHUB_SHA": "c" * 40,
        "GITHUB_RUN_ID": "31",
    }
    native = {
        "id": 31,
        "run_attempt": 1,
        "head_sha": "c" * 40,
        "head_branch": "main",
        "event": "workflow_dispatch",
        "path": publisher.WORKFLOW,
        "repository": repository,
        "head_repository": copy.deepcopy(repository),
        "actor": actor,
        "triggering_actor": copy.deepcopy(actor),
    }
    responses = {
        f"{publisher.API}/actions/runs/31": native,
        f"apps/{publisher.APP_SLUG}": {
            "id": publisher.APP_ID,
            "slug": publisher.APP_SLUG,
        },
        f"users/{publisher.APP_SLUG}[bot]": copy.deepcopy(actor),
        f"{publisher.API}/compare/{'c' * 40}...main": {
            "merge_base_commit": {"sha": "c" * 40},
            "status": "identical",
        },
    }
    event = {"sender": copy.deepcopy(actor), "inputs": {"request": json.dumps(request)}}
    return request, env, native, responses, event


OCI_INDEX = "application/vnd.oci.image.index.v1+json"
OCI_MANIFEST = "application/vnd.oci.image.manifest.v1+json"
OCI_CONFIG = "application/vnd.oci.image.config.v1+json"
DOCKER_LIST = "application/vnd.docker.distribution.manifest.list.v2+json"
DOCKER_MANIFEST = "application/vnd.docker.distribution.manifest.v2+json"
DOCKER_CONFIG = "application/vnd.docker.container.image.v1+json"
IN_TOTO = "application/vnd.in-toto+json"


def image_archive(
    label,
    *,
    layout="single",
    manifest_type=OCI_MANIFEST,
    config_type=OCI_CONFIG,
    architecture="amd64",
):
    """Synthetic `docker save` OCI layout shaped like Docker 25+ output.

    `attestation` mirrors the BuildKit default on the containerd image store
    (an index of the image plus an attestation manifest, observed locally with
    Docker 29.8.1); `--provenance=false --sbom=false` yields `single`.
    """
    blobs = {}

    def blob(raw):
        digest = hashlib.sha256(raw).hexdigest()
        blobs[digest] = raw
        return {"digest": "sha256:" + digest, "size": len(raw)}

    layer = blob(label.encode())
    config = blob(
        json.dumps(
            {
                "architecture": architecture,
                "os": "linux",
                "rootfs": {"type": "layers", "diff_ids": [layer["digest"]]},
            }
        ).encode()
    )
    image = {
        "mediaType": manifest_type,
        **blob(
            json.dumps(
                {
                    "schemaVersion": 2,
                    "mediaType": manifest_type,
                    "config": {"mediaType": config_type, **config},
                    "layers": [
                        {"mediaType": "application/vnd.oci.image.layer.v1.tar", **layer}
                    ],
                }
            ).encode()
        ),
    }
    statement = blob(b'{"_type":"https://in-toto.io/Statement/v0.1"}')
    attestation = {
        "mediaType": OCI_MANIFEST,
        **blob(
            json.dumps(
                {
                    "schemaVersion": 2,
                    "mediaType": OCI_MANIFEST,
                    "config": {"mediaType": IN_TOTO, **statement},
                    "layers": [],
                }
            ).encode()
        ),
        "annotations": {"vnd.docker.reference.type": "attestation-manifest"},
    }
    entries = [image]
    if layout == "attestation":
        entries = [
            {
                "mediaType": OCI_INDEX,
                **blob(
                    json.dumps(
                        {
                            "schemaVersion": 2,
                            "mediaType": OCI_INDEX,
                            "manifests": [image, attestation],
                        }
                    ).encode()
                ),
            }
        ]
    elif layout == "two-manifests":
        entries = [image, attestation]
    members = {
        "oci-layout": b'{"imageLayoutVersion":"1.0.0"}',
        "index.json": json.dumps(
            {"schemaVersion": 2, "mediaType": OCI_INDEX, "manifests": entries}
        ).encode(),
        "manifest.json": json.dumps(
            [
                {
                    "Config": "blobs/sha256/" + config["digest"][7:],
                    "RepoTags": [f"poc-{label}:{'c' * 40}"],
                    "Layers": ["blobs/sha256/" + layer["digest"][7:]],
                }
            ]
        ).encode(),
        **{f"blobs/sha256/{digest}": raw for digest, raw in blobs.items()},
    }
    stream = io.BytesIO()
    with tarfile.open(fileobj=stream, mode="w") as archive:
        for name, raw in members.items():
            info = tarfile.TarInfo(name)
            info.size = len(raw)
            archive.addfile(info, io.BytesIO(raw))
    return stream.getvalue()


class PublisherAdmissionTests(unittest.TestCase):
    def test_exact_machine_dispatch(self):
        request, env, _, responses, event = fixture()
        self.assertEqual(
            publisher.admit(api=responses.__getitem__, env=env, event=event),
            (request, 31, "c" * 40),
        )

    def test_exact_dispatch_remains_valid_when_main_advances_after_run_selection(self):
        request, env, _, responses, event = fixture()
        responses[f"{publisher.API}/compare/{request['source_sha']}...main"][
            "status"
        ] = "ahead"
        self.assertEqual(
            publisher.admit(api=responses.__getitem__, env=env, event=event),
            (request, 31, request["source_sha"]),
        )

    def test_main_advancing_before_run_selection_rejects_reviewed_ancestor(self):
        request, env, _, responses, event = fixture()
        request["source_sha"] = "a" * 40
        event["inputs"]["request"] = json.dumps(request)
        responses[f"{publisher.API}/compare/{request['source_sha']}...main"] = {
            "merge_base_commit": {"sha": request["source_sha"]},
            "status": "ahead",
        }
        api = MagicMock(side_effect=responses.__getitem__)
        with self.assertRaisesRegex(codec.ReleaseManifestError, "^dispatch-source-sha$"):
            publisher.admit(api=api, env=env, event=event)
        self.assertEqual(
            [call.args[0] for call in api.call_args_list],
            [
                f"{publisher.API}/actions/runs/31",
                f"apps/{publisher.APP_SLUG}",
                f"users/{publisher.APP_SLUG}[bot]",
            ],
        )

    def test_dispatch_race_stops_every_cli_mode_before_build_or_credentials(self):
        request, env, _, responses, event = fixture()
        request["source_sha"] = "a" * 40
        event["inputs"]["request"] = json.dumps(request)
        responses[f"{publisher.API}/compare/{request['source_sha']}...main"] = {
            "merge_base_commit": {"sha": request["source_sha"]},
            "status": "ahead",
        }
        temporary = tempfile.TemporaryDirectory()
        self.addCleanup(temporary.cleanup)
        env.update(
            RUNNER_TEMP=temporary.name,
            GITHUB_OUTPUT=str(Path(temporary.name) / "output"),
        )
        admit = publisher.admit
        for mode in ("admit", "build", "prepare", "publish", "manifest", "readback"):
            with (
                self.subTest(mode=mode),
                patch.dict(os.environ, env, clear=True),
                patch.object(
                    publisher,
                    "admit",
                    side_effect=lambda: admit(
                        api=responses.__getitem__, env=env, event=event
                    ),
                ),
                patch.object(publisher, "protected_environment") as protection,
                patch.object(publisher, "build") as build,
                patch.object(publisher, "prepare") as prepare,
                patch.object(publisher, "publish") as publish,
                patch.object(publisher, "manifest") as manifest,
                patch.object(publisher, "reference") as reference,
                patch.object(publisher, "run") as native,
                patch("sys.stderr", new_callable=io.StringIO) as stderr,
            ):
                self.assertEqual(publisher.main([mode]), 1)
                self.assertEqual(stderr.getvalue(), "Image publishing failed.\n")
                for operation in (
                    protection, build, prepare, publish, manifest, reference, native
                ):
                    operation.assert_not_called()
                self.assertEqual(list(Path(temporary.name).iterdir()), [])

    def test_loads_dispatch_envelope_separately_from_request_evidence(self):
        request, env, _, responses, event = fixture()
        event["metadata"] = "x" * codec.MAX_MANIFEST_BYTES
        with tempfile.TemporaryDirectory() as directory:
            event_path = Path(directory) / "event.json"
            event_path.write_bytes(json.dumps(event).encode())
            env["GITHUB_EVENT_PATH"] = str(event_path)
            self.assertEqual(
                publisher.admit(api=responses.__getitem__, env=env),
                (request, 31, "c" * 40),
            )

    def test_rejects_oversized_dispatch_envelope(self):
        with tempfile.TemporaryDirectory() as directory:
            event_path = Path(directory) / "event.json"
            event_path.write_bytes(b"x" * (publisher.MAX_EVENT + 1))
            with self.assertRaisesRegex(codec.ReleaseManifestError, "^dispatch-event$"):
                publisher.event_document(event_path)

    def test_event_document_reads_no_more_than_its_bound(self):
        stream = MagicMock()
        stream.read.return_value = b"{}"
        path = MagicMock()
        path.open.return_value.__enter__.return_value = stream

        self.assertEqual(publisher.event_document(path), {})
        path.open.assert_called_once_with("rb")
        stream.read.assert_called_once_with(publisher.MAX_EVENT + 1)

    def test_rejects_user_fork_rerun_foreign_workflow_before_bulk_reads(self):
        for key, value in (
            ("GITHUB_REPOSITORY", "foreign/repo"),
            ("GITHUB_REPOSITORY_ID", "1"),
            ("GITHUB_REF", "refs/heads/pr"),
            ("GITHUB_EVENT_NAME", "push"),
            ("GITHUB_RUN_ATTEMPT", "2"),
            ("GITHUB_WORKFLOW_REF", "foreign"),
            ("GITHUB_RUN_ID", "../x"),
        ):
            _, env, _, responses, event = fixture()
            env[key] = value
            calls = []

            def api(path):
                calls.append(path)
                return responses[path]

            with self.subTest(key=key), self.assertRaises(codec.ReleaseManifestError):
                publisher.admit(api=api, env=env, event=event)
            self.assertEqual(calls, [])
        for key in ("actor", "triggering_actor"):
            _, env, native, responses, event = fixture()
            native[key] = {"id": 99, "login": "maintainer", "type": "User"}
            with self.subTest(key=key), self.assertRaises(codec.ReleaseManifestError):
                publisher.admit(api=responses.__getitem__, env=env, event=event)

    def test_rejects_native_identity_and_authority_substitution(self):
        for key, value in (
            ("head_sha", "d" * 40),
            ("head_branch", "feature"),
            ("event", "push"),
            ("path", "other.yml"),
            ("run_attempt", True),
            ("id", 33),
        ):
            _, env, native, responses, event = fixture()
            native[key] = value
            with self.subTest(key=key), self.assertRaises(codec.ReleaseManifestError):
                publisher.admit(api=responses.__getitem__, env=env, event=event)
        for field in ("repository", "head_repository"):
            _, env, native, responses, event = fixture()
            native[field]["id"] = 1
            with self.assertRaises(codec.ReleaseManifestError):
                publisher.admit(api=responses.__getitem__, env=env, event=event)
        _, env, _, responses, event = fixture()
        responses[f"apps/{publisher.APP_SLUG}"]["id"] = 99
        with self.assertRaises(codec.ReleaseManifestError):
            publisher.admit(api=responses.__getitem__, env=env, event=event)

    def test_closed_event_and_reviewed_source(self):
        for change in ("extra", "sender", "not-main", "missing-source"):
            _, env, _, responses, event = fixture()
            if change == "extra":
                event["inputs"]["override"] = "true"
            elif change == "sender":
                event["sender"]["id"] = 1
            else:
                comparison = responses[f"{publisher.API}/compare/{'c' * 40}...main"]
                comparison[
                    "status" if change == "not-main" else "merge_base_commit"
                ] = "diverged" if change == "not-main" else {}
            with (
                self.subTest(change=change),
                self.assertRaises(codec.ReleaseManifestError),
            ):
                publisher.admit(api=responses.__getitem__, env=env, event=event)

    def test_request_rejects_ambiguous_forged_and_wrong_fields(self):
        request, *_ = fixture()
        for key, value in (
            ("source_sha", "main"),
            ("platform", "host"),
            ("registry_phase_receipt_id", True),
            ("registry_contract_digest", "x"),
            ("registry_checkpoint_version", "x\n"),
            ("extra", "yes"),
        ):
            with self.subTest(key=key), self.assertRaises(codec.ReleaseManifestError):
                publisher.request(json.dumps(dict(request, **{key: value})).encode())
        with self.assertRaises(codec.ReleaseManifestError):
            publisher.request(b'{"source_sha":"a","source_sha":"b"}')

    def test_request_rejects_unsupported_arm64_before_native_actions(self):
        request, *_ = fixture()
        with self.assertRaisesRegex(codec.ReleaseManifestError, "publisher-platform"):
            publisher.request(
                json.dumps(dict(request, platform="linux/arm64")).encode()
            )


class PublisherArtifactTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.request, *_ = fixture()
        self.value = {
            "id": 41,
            "name": "poc-image-build-31-1",
            "expired": False,
            "size_in_bytes": 100,
            "digest": "sha256:" + "a" * 64,
            "workflow_run": {
                "id": 31,
                "repository_id": 646535009,
                "head_repository_id": 646535009,
                "head_sha": "c" * 40,
            },
        }

    def archive(self, *, extra=False, corrupt=False, layout="single"):
        source = self.root / "input"
        source.mkdir()
        images = {}
        for kind in codec.TARGETS:
            images[kind] = source / f"{kind}.tar"
            images[kind].write_bytes(
                image_archive(kind, layout=layout if kind == "worker" else "single")
            )
        metadata = publisher.metadata(self.request, 31, images)
        if corrupt:
            metadata["images"]["web"]["archive_sha256"] = "f" * 64
        path = self.root / "archive.zip"
        with zipfile.ZipFile(path, "w") as archive:
            archive.writestr("build.json", publisher.canonical(metadata))
            for kind, image in images.items():
                archive.write(image, f"{kind}.tar")
            if extra:
                archive.writestr("../escape", b"bad")
        target = self.root / "output"
        target.mkdir()
        return path, target

    def test_exact_archive_hashes_and_metadata(self):
        path, target = self.archive()
        result = publisher.extract_build(path, target, self.request, 31)
        self.assertEqual(result["source_sha"], self.request["source_sha"])
        self.assertEqual((target / "worker.tar").read_bytes(), image_archive("worker"))

    def test_prepare_refuses_bound_attestation_index_before_credentials(self):
        path, target = self.archive(layout="attestation")
        with self.assertRaisesRegex(
            codec.ReleaseManifestError, "^publisher-archive-not-single$"
        ):
            publisher.extract_build(path, target, self.request, 31)

    def test_extra_path_or_changed_image_hash_fails(self):
        for option in ("extra", "corrupt"):
            with tempfile.TemporaryDirectory() as temporary:
                self.root = Path(temporary)
                path, target = self.archive(**{option: True})
                with (
                    self.subTest(option=option),
                    self.assertRaises(codec.ReleaseManifestError),
                ):
                    publisher.extract_build(path, target, self.request, 31)

    def test_native_artifact_metadata_is_bound_and_complete(self):
        response = {"total_count": 1, "artifacts": [self.value]}
        with patch.dict(os.environ, GITHUB_SHA="c" * 40):
            self.assertEqual(
                publisher.artifact(31, self.value["name"], api=lambda path: response),
                self.value,
            )
            for key, value in (
                ("expired", True),
                ("digest", "unknown"),
                ("size_in_bytes", 0),
                ("id", False),
            ):
                changed = copy.deepcopy(response)
                changed["artifacts"][0][key] = value
                with (
                    self.subTest(key=key),
                    self.assertRaises(codec.ReleaseManifestError),
                ):
                    publisher.artifact(31, self.value["name"], api=lambda path: changed)
            for changed in (
                {"total_count": 2, "artifacts": [self.value]},
                {"total_count": 2, "artifacts": [self.value, self.value]},
            ):
                with self.assertRaises(codec.ReleaseManifestError):
                    publisher.artifact(31, self.value["name"], api=lambda path: changed)

    def test_download_matches_exact_archive_bytes(self):
        raw = b"synthetic archive bytes"
        value = dict(
            self.value,
            size_in_bytes=len(raw),
            digest="sha256:" + hashlib.sha256(raw).hexdigest(),
        )

        def execute(*args, **kwargs):
            kwargs["output"].write(raw)

        with patch.object(publisher, "run", execute):
            publisher.download(value, self.root / "download.zip")
            with self.assertRaises(codec.ReleaseManifestError):
                publisher.download(
                    dict(value, digest="sha256:" + "0" * 64), self.root / "wrong.zip"
                )

    def test_missing_failed_or_rerun_jobs_fail(self):
        jobs = [
            {
                "name": name,
                "id": index + 1,
                "run_id": 31,
                "run_attempt": 1,
                "status": "completed",
                "conclusion": "success",
            }
            for index, name in enumerate(publisher.JOBS)
        ]
        response = {"total_count": 3, "jobs": jobs}
        self.assertEqual(
            set(publisher.jobs(31, api=lambda path: response)), set(publisher.JOBS)
        )
        for key, value in (
            ("conclusion", "failure"),
            ("run_attempt", 2),
            ("status", "queued"),
            ("run_id", 44),
        ):
            changed = copy.deepcopy(response)
            changed["jobs"][0][key] = value
            with self.subTest(key=key), self.assertRaises(codec.ReleaseManifestError):
                publisher.jobs(31, api=lambda path: changed)


class PublisherNativeBoundaryTests(unittest.TestCase):
    def test_environment_does_not_pass_tokens_to_docker_or_extra_configuration(self):
        with patch.dict(
            os.environ,
            {
                "HOME": "/tmp/synthetic",
                "GH_TOKEN": "synthetic",
                "AWS_ACCESS_KEY_ID": "synthetic",
                "AWS_SECRET_ACCESS_KEY": "synthetic",
                "AWS_SESSION_TOKEN": "synthetic",
                "PYTHONPATH": "/hostile",
                "GITHUB_ENV": "/hostile",
                "AWS_ENDPOINT_URL": "https://hostile.invalid",
            },
            clear=True,
        ):
            docker = publisher.environment("docker")
            self.assertEqual(set(docker), {"PATH", "HOME", "LANG"})
            aws = publisher.environment("aws")
            self.assertNotIn("GH_TOKEN", aws)
            self.assertNotIn("AWS_ENDPOINT_URL", aws)
            self.assertEqual(aws["AWS_MAX_ATTEMPTS"], "1")

    def test_commands_are_absolute_and_closed(self):
        from unittest.mock import MagicMock

        process = MagicMock()
        process.poll.return_value = 0
        with (
            patch.object(publisher, "environment", return_value={}),
            patch.object(subprocess, "Popen", return_value=process) as native,
            patch.object(publisher, "_drain", return_value=b"{}"),
        ):
            self.assertEqual(publisher.run("gh", "api", "path"), b"{}")
            self.assertEqual(native.call_args.args[0][0], "/usr/bin/gh")
            self.assertNotIn("shell", native.call_args.kwargs)
            process.wait.assert_called_once()
        with self.assertRaises(codec.ReleaseManifestError):
            publisher.run("gh", "api", "path", timeout=2401)

    def test_real_local_process_bounds_timeout_failure_and_cleanup(self):
        original = subprocess.Popen
        children = []
        for program, category, timeout in (
            ("print('ok')", None, 30),
            ("print('x'*100)", "native-output", 30),
            ("import sys;sys.stderr.write('x'*1100000)", "native-output", 30),
            ("raise SystemExit(1)", "native-command", 30),
            ("import time;time.sleep(10)", "native-timeout", 0.5),
        ):

            def start(*args, **kwargs):
                child = original([sys.executable, "-I", "-c", program], **kwargs)
                children.append(child)
                return child

            with (
                self.subTest(category=category),
                patch.object(publisher, "environment", return_value={}),
                patch.object(subprocess, "Popen", side_effect=start),
            ):
                if category is None:
                    self.assertEqual(
                        publisher.run("gh", "api", "path", max_output=10), b"ok\n"
                    )
                else:
                    with self.assertRaisesRegex(
                        codec.ReleaseManifestError, "^" + category + "$"
                    ):
                        publisher.run(
                            "gh", "api", "path", max_output=10, timeout=timeout
                        )
            self.assertIsNotNone(children[-1].poll())

    def test_cli_does_not_print_exception_values(self):
        for error in (
            ValueError("private"),
            OSError("private"),
            subprocess.TimeoutExpired("private", 1),
        ):
            stderr = io.StringIO()
            with (
                patch.object(publisher, "admit", side_effect=error),
                patch("sys.stderr", stderr),
            ):
                self.assertEqual(publisher.main(["prepare"]), 1)
            self.assertEqual(stderr.getvalue(), "Image publishing failed.\n")


class PublisherBuildTests(unittest.TestCase):
    """D-18: one linux/amd64 image per target, never an index or attestation."""

    HELP = (
        b"Usage:  docker buildx build [OPTIONS] PATH | URL | -\n"
        b"      --platform stringArray      Set target platform for build\n"
        b"      --provenance string         Shorthand for \"--attest=type=provenance\"\n"
        b"      --sbom string               Shorthand for \"--attest=type=sbom\"\n"
    )

    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        (self.root / ".source").mkdir()
        self.output = self.root / "poc-images"
        self.output.mkdir()
        self.request, *_ = fixture()
        self.calls = []
        self.help = self.HELP
        self.layout = "single"

    def native(self, kind, *args, **kwargs):
        self.calls.append((kind, args))
        if args[:2] == ("build", "--help"):
            return self.help
        if args[0] == "save":
            path = Path(args[args.index("--output") + 1])
            label = path.stem
            path.write_bytes(image_archive(label, layout=self.layout))
        return b""

    def git(self, arguments, **kwargs):
        stdout = (
            f"{self.request['source_sha']}\n".encode()
            if "rev-parse" in arguments
            else b""
        )
        return subprocess.CompletedProcess(arguments, 0, stdout, b"")

    def build(self):
        with (
            patch.dict(os.environ, GITHUB_WORKSPACE=str(self.root)),
            patch.object(publisher.subprocess, "run", side_effect=self.git),
            patch.object(publisher, "run", side_effect=self.native),
        ):
            publisher.build(self.request, 31, self.output)

    def builds(self):
        return [
            args
            for kind, args in self.calls
            if kind == "docker" and args[0] == "build" and "--help" not in args
        ]

    def test_build_disables_attestations_and_pins_single_platform(self):
        self.build()
        builds = self.builds()
        self.assertEqual(len(builds), 2)
        for args, target in zip(builds, codec.TARGETS.values()):
            self.assertIn("--provenance=false", args)
            self.assertIn("--sbom=false", args)
            self.assertLess(args.index("--sbom=false"), args.index("--platform"))
            self.assertEqual(args[args.index("--platform") + 1], "linux/amd64")
            self.assertEqual(args.count("--platform"), 1)
            self.assertEqual(args[args.index("--target") + 1], target)
            self.assertFalse(
                any(
                    value.startswith(("--attest", "--output", "--push", "--load"))
                    for value in args
                )
            )
        self.assertEqual(
            [args[0] for kind, args in self.calls],
            ["build", "build", "save", "build", "save"],
        )
        self.assertTrue((self.output / "build.json").exists())

    def test_builder_without_attestation_flags_fails_closed_before_build(self):
        for missing in (b"--provenance", b"--sbom"):
            self.calls.clear()
            self.help = self.HELP.replace(missing, b"--unrelated")
            with (
                self.subTest(missing=missing),
                self.assertRaisesRegex(
                    codec.ReleaseManifestError, "^publisher-attestation-flags$"
                ),
            ):
                self.build()
            self.assertEqual(self.builds(), [])
            self.assertFalse((self.output / "build.json").exists())

    def test_saved_attestation_index_fails_before_transfer_metadata(self):
        for layout in ("attestation", "two-manifests"):
            self.calls.clear()
            self.layout = layout
            with (
                self.subTest(layout=layout),
                self.assertRaisesRegex(
                    codec.ReleaseManifestError, "^publisher-archive-not-single$"
                ),
            ):
                self.build()
            self.assertEqual(len(self.builds()), 1)
            self.assertFalse((self.output / "build.json").exists())
            for path in self.output.iterdir():
                path.unlink()

    def test_single_image_archive_contract(self):
        accepted = (
            {},
            {"manifest_type": DOCKER_MANIFEST, "config_type": DOCKER_CONFIG},
        )
        for options in accepted:
            path = self.root / "accepted.tar"
            path.write_bytes(image_archive("web", **options))
            with self.subTest(accepted=options):
                publisher.single_image(path)
        rejected = (
            ({"layout": "attestation"}, "publisher-archive-not-single"),
            ({"layout": "two-manifests"}, "publisher-archive-not-single"),
            ({"manifest_type": OCI_INDEX}, "publisher-archive-not-single"),
            ({"manifest_type": DOCKER_LIST}, "publisher-archive-not-single"),
            ({"config_type": IN_TOTO}, "publisher-archive-not-single"),
            ({"architecture": "arm64"}, "publisher-platform"),
        )
        for options, category in rejected:
            path = self.root / "rejected.tar"
            path.write_bytes(image_archive("web", **options))
            with (
                self.subTest(rejected=options),
                self.assertRaisesRegex(codec.ReleaseManifestError, f"^{category}$"),
            ):
                publisher.single_image(path)
        raw = image_archive("web")
        for name, value in (
            ("not-a-tar", b"web"),
            ("index-missing", raw.replace(b"index.json", b"index.jsom")),
            ("blob-changed", raw.replace(b'"os": "linux"', b'"os": "linuy"')),
        ):
            path = self.root / f"{name}.tar"
            path.write_bytes(value)
            with (
                self.subTest(corrupt=name),
                self.assertRaisesRegex(
                    codec.ReleaseManifestError, "^publisher-archive-not-single$"
                ),
            ):
                publisher.single_image(path)


class PublisherRoundtripTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.request, *_ = fixture()
        self.jobs = {
            name: {"id": index + 51} for index, name in enumerate(publisher.JOBS)
        }
        paths = {}
        for kind in codec.TARGETS:
            paths[kind] = self.root / f"{kind}.tar"
            paths[kind].write_bytes(image_archive(kind))
        (self.root / "build.json").write_bytes(
            publisher.canonical(publisher.metadata(self.request, 31, paths))
        )
        self.prepared = {
            "request": self.request,
            "quality_job_id": 51,
            "build_job_id": 52,
            "build_artifact": {
                "artifact_id": 42,
                "archive_sha256": "d" * 64,
                "file_sha256": publisher.sha(self.root / "build.json"),
            },
        }
        (self.root / "prepared.json").write_bytes(publisher.canonical(self.prepared))
        self.calls = []
        self.wrong_identity = False
        self.missing_image = False
        self.readback = {}

    def native(self, kind, *args, **kwargs):
        self.calls.append((kind, args))
        if args[:2] == ("sts", "get-caller-identity"):
            return publisher.canonical(
                {
                    "Account": "foreign" if self.wrong_identity else "891377212104",
                    "Arn": "arn:aws:sts::891377212104:assumed-role/user-service-test-ImagePublisher/poc-images-31-1",
                }
            )
        if args[:2] == ("ecr", "get-login-password"):
            return b"synthetic-login-password"
        if args[:2] == ("ecr", "describe-images"):
            repo = args[args.index("--repository-name") + 1]
            tag = args[args.index("--image-ids") + 1].removeprefix("imageTag=")
            return publisher.canonical(
                {
                    "imageDetails": []
                    if self.missing_image
                    else [
                        {
                            "registryId": "891377212104",
                            "repositoryName": repo,
                            "imageTags": [tag],
                            "imageDigest": "sha256:"
                            + ("e" if repo.endswith("web") else "f") * 64,
                            "imageManifestMediaType": DOCKER_MANIFEST,
                            "artifactMediaType": DOCKER_CONFIG,
                            **self.readback,
                        }
                    ]
                }
            )
        return b""

    def test_simulated_publish_observations_reach_exact_codec(self):
        with (
            patch.object(publisher, "jobs", return_value=self.jobs),
            patch.object(publisher, "run", side_effect=self.native),
        ):
            publisher.publish(self.request, 31, "c" * 40, self.root)
        pushes = [
            args for kind, args in self.calls if kind == "docker" and args[0] == "push"
        ]
        self.assertEqual(len(pushes), 2)
        self.assertTrue(
            all(
                args[1].startswith(codec.REGISTRY + "/user-service-test-")
                for args in pushes
            )
        )
        references = {
            "provenance.json": {
                "artifact_id": 61,
                "archive_sha256": "1" * 64,
                "file_sha256": publisher.sha(self.root / "provenance.json"),
            },
            "quality.json": {
                "artifact_id": 62,
                "archive_sha256": "2" * 64,
                "file_sha256": publisher.sha(self.root / "quality.json"),
            },
        }
        with patch.object(
            publisher,
            "reference",
            side_effect=lambda run, name, member, path: references[member],
        ):
            publisher.manifest(self.request, 31, "c" * 40, self.root)
        binding = codec.RegistryReleaseBinding(
            **{
                key: self.request[key]
                for key in codec.RegistryReleaseBinding.__dataclass_fields__
            }
        )
        result = codec.validate_release_manifest(
            (self.root / "release-manifest.json").read_bytes(), registry=binding
        )
        self.assertNotEqual(result["web"]["digest"], result["worker"]["digest"])
        self.assertEqual(result["provenance"], references["provenance.json"])
        self.assertEqual(
            json.loads((self.root / "quality.json").read_bytes())["quality_job_id"], 51
        )

    def test_wrong_role_or_changed_input_fails_before_login_push(self):
        self.wrong_identity = True
        with (
            patch.object(publisher, "jobs", return_value=self.jobs),
            patch.object(publisher, "run", side_effect=self.native),
        ):
            with self.assertRaises(codec.ReleaseManifestError):
                publisher.publish(self.request, 31, "c" * 40, self.root)
        self.assertEqual(
            self.calls, [("aws", ("sts", "get-caller-identity", "--output", "json"))]
        )
        self.calls.clear()
        (self.root / "web.tar").write_bytes(b"changed")
        with (
            patch.object(publisher, "jobs", return_value=self.jobs),
            patch.object(publisher, "run", side_effect=self.native),
        ):
            with self.assertRaises(codec.ReleaseManifestError):
                publisher.publish(self.request, 31, "c" * 40, self.root)
        self.assertEqual(self.calls, [])

    def test_oci_single_image_readback_is_accepted(self):
        self.readback = {
            "imageManifestMediaType": OCI_MANIFEST,
            "artifactMediaType": OCI_CONFIG,
        }
        with (
            patch.object(publisher, "jobs", return_value=self.jobs),
            patch.object(publisher, "run", side_effect=self.native),
        ):
            publisher.publish(self.request, 31, "c" * 40, self.root)
        self.assertTrue((self.root / "published.json").exists())

    def test_pushed_index_or_attestation_never_generates_release_evidence(self):
        for name, readback in (
            ("oci-index", {"imageManifestMediaType": OCI_INDEX}),
            ("docker-manifest-list", {"imageManifestMediaType": DOCKER_LIST}),
            ("manifest-type-missing", {"imageManifestMediaType": None}),
            (
                "attestation-manifest",
                {"imageManifestMediaType": OCI_MANIFEST, "artifactMediaType": IN_TOTO},
            ),
            ("artifact-type-missing", {"artifactMediaType": None}),
            ("referrer", {"subjectManifestDigest": "sha256:" + "e" * 64}),
        ):
            self.calls.clear()
            self.readback = {
                key: value for key, value in readback.items() if value is not None
            }
            removed = [key for key, value in readback.items() if value is None]
            native = self.native

            def observe(kind, *args, **kwargs):
                raw = native(kind, *args, **kwargs)
                if args[:2] != ("ecr", "describe-images"):
                    return raw
                document = json.loads(raw)
                for key in removed:
                    document["imageDetails"][0].pop(key)
                return publisher.canonical(document)

            with (
                self.subTest(readback=name),
                patch.object(publisher, "jobs", return_value=self.jobs),
                patch.object(publisher, "run", side_effect=observe),
            ):
                with self.assertRaisesRegex(
                    codec.ReleaseManifestError, "^publisher-manifest-not-single$"
                ):
                    publisher.publish(self.request, 31, "c" * 40, self.root)
            pushes = [
                args
                for kind, args in self.calls
                if kind == "docker" and args[0] == "push"
            ]
            self.assertEqual(len(pushes), 1)
            self.assertFalse((self.root / "provenance.json").exists())
            self.assertFalse((self.root / "published.json").exists())

    def test_bound_attestation_archive_fails_before_credentials_or_push(self):
        (self.root / "worker.tar").write_bytes(
            image_archive("worker", layout="attestation")
        )
        paths = {kind: self.root / f"{kind}.tar" for kind in codec.TARGETS}
        (self.root / "build.json").write_bytes(
            publisher.canonical(publisher.metadata(self.request, 31, paths))
        )
        self.prepared["build_artifact"]["file_sha256"] = publisher.sha(
            self.root / "build.json"
        )
        (self.root / "prepared.json").write_bytes(publisher.canonical(self.prepared))
        with (
            patch.object(publisher, "jobs", return_value=self.jobs),
            patch.object(publisher, "run", side_effect=self.native),
        ):
            with self.assertRaisesRegex(
                codec.ReleaseManifestError, "^publisher-archive-not-single$"
            ):
                publisher.publish(self.request, 31, "c" * 40, self.root)
        self.assertEqual(self.calls, [])

    def test_missing_native_image_never_generates_release_evidence(self):
        self.missing_image = True
        with (
            patch.object(publisher, "jobs", return_value=self.jobs),
            patch.object(publisher, "run", side_effect=self.native),
        ):
            with self.assertRaises(codec.ReleaseManifestError):
                publisher.publish(self.request, 31, "c" * 40, self.root)
        self.assertFalse((self.root / "provenance.json").exists())
        self.assertFalse((self.root / "published.json").exists())


class PublisherEnvironmentTests(unittest.TestCase):
    def test_main_only_independent_review_is_checked_natively(self):
        endpoint = f"{publisher.API}/environments/poc-test-images"
        environment = {
            "can_admins_bypass": False,
            "deployment_branch_policy": {
                "protected_branches": False,
                "custom_branch_policies": True,
            },
            "protection_rules": [
                {
                    "type": "required_reviewers",
                    "prevent_self_review": True,
                    "reviewers": [{"type": "User", "reviewer": {"id": 51}}],
                }
            ],
        }
        responses = {
            endpoint: environment,
            endpoint + "/deployment-branch-policies?per_page=100": {
                "total_count": 1,
                "branch_policies": [{"name": "main", "type": "branch"}],
            },
            "users/Kravalg": {"login": "Kravalg", "type": "User", "id": 51},
        }
        publisher.protected_environment(api=responses.__getitem__)
        for mutation in ("bypass", "branch", "tag", "review", "self-review"):
            changed = copy.deepcopy(responses)
            if mutation == "bypass":
                changed[endpoint]["can_admins_bypass"] = True
            elif mutation in ("branch", "tag"):
                changed[endpoint + "/deployment-branch-policies?per_page=100"][
                    "branch_policies"
                ][0]["name" if mutation == "branch" else "type"] = (
                    "*" if mutation == "branch" else "tag"
                )
            elif mutation == "review":
                changed[endpoint]["protection_rules"][0]["reviewers"][0]["reviewer"][
                    "id"
                ] = publisher.BOT_ID
            else:
                changed[endpoint]["protection_rules"][0]["prevent_self_review"] = False
            with (
                self.subTest(mutation=mutation),
                self.assertRaises(codec.ReleaseManifestError),
            ):
                publisher.protected_environment(api=changed.__getitem__)


if __name__ == "__main__":
    unittest.main()
