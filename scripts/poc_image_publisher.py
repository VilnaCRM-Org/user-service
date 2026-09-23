#!/usr/bin/env python3
"""Trusted TEST publisher; consumes a verified service App dispatch attestation.

The service authenticates registry completion before dispatch and again before
workload admission. This application does not independently read service artifacts.
No dispatcher grant or OIDC activation is installed by this source module.
"""

from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
import selectors
import time
import stat
import subprocess
import sys
import zipfile
from dataclasses import asdict
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
import poc_release_manifest as codec

API = f"repos/{codec.REPOSITORY}"
WORKFLOW = ".github/workflows/publish-poc-images.yml"
APP_ID = 4853984
APP_SLUG = "vilnacrm-user-service-evidence"
BOT_ID = 325789989
JOBS = (
    "Validate application release",
    "Build application images",
    "Publish TEST application images",
)
MAX_IMAGE = 3 * 1024**3
MAX_ARCHIVE = 7 * 1024**3
MAX_EVENT = 1024 * 1024
REQUEST_FIELDS = {
    "source_sha",
    "platform",
    *codec.RegistryReleaseBinding.__dataclass_fields__,
}


def require(value, category="publisher-binding"):
    if not value:
        raise codec.ReleaseManifestError(category)


def canonical(value):
    return (
        json.dumps(value, sort_keys=True, separators=(",", ":"), allow_nan=False) + "\n"
    ).encode()


def decode(raw):
    return codec._evidence_document(raw)


def event_document(path):
    with path.open("rb") as stream:
        raw = stream.read(MAX_EVENT + 1)
    require(0 < len(raw) <= MAX_EVENT, "dispatch-event")
    try:
        document = json.loads(
            raw, object_pairs_hook=codec._pairs, parse_constant=codec._nonfinite
        )
    except (ValueError, RecursionError):
        raise codec.ReleaseManifestError("dispatch-event") from None
    require(type(document) is dict, "dispatch-event")
    return document


def sha(path):
    with path.open("rb") as stream:
        return hashlib.file_digest(stream, "sha256").hexdigest()


def environment(kind):
    result = {
        "PATH": "/usr/local/bin:/usr/bin:/bin",
        "HOME": os.environ["HOME"],
        "LANG": "C.UTF-8",
    }
    fields = {
        "gh": ("GH_TOKEN",),
        "aws": ("AWS_ACCESS_KEY_ID", "AWS_SECRET_ACCESS_KEY", "AWS_SESSION_TOKEN"),
        "docker": (),
    }
    for key in fields[kind]:
        require(bool(os.environ.get(key)), "credential-missing")
        result[key] = os.environ[key]
    if kind == "aws":
        result.update(
            AWS_REGION="eu-central-1",
            AWS_DEFAULT_REGION="eu-central-1",
            AWS_MAX_ATTEMPTS="1",
            AWS_PAGER="",
        )
    if kind == "gh":
        result.update(GH_PROMPT_DISABLED="1", GH_PAGER="cat")
    return result


def _drain(process, timeout, output, maximum):
    result = bytearray()
    counts = [0, 0]
    deadline = time.monotonic() + timeout
    with selectors.DefaultSelector() as selector:
        selector.register(process.stdout, selectors.EVENT_READ, 0)
        selector.register(process.stderr, selectors.EVENT_READ, 1)
        while selector.get_map():
            remaining = deadline - time.monotonic()
            require(remaining > 0, "native-timeout")
            for key, _ in selector.select(remaining):
                block = os.read(key.fd, 65536)
                if not block:
                    selector.unregister(key.fileobj)
                    continue
                counts[key.data] += len(block)
                require(
                    counts[key.data] <= (maximum if key.data == 0 else 1024 * 1024),
                    "native-output",
                )
                if key.data == 0:
                    if output is None:
                        result.extend(block)
                    else:
                        output.write(block)
        require(
            process.wait(timeout=max(0.01, deadline - time.monotonic())) == 0,
            "native-command",
        )
    return bytes(result)


def run(
    kind,
    *arguments,
    payload=None,
    output=None,
    timeout=180,
    max_output=16 * 1024 * 1024,
):
    executable = {
        "gh": "/usr/bin/gh",
        "aws": "/usr/local/bin/aws",
        "docker": "/usr/bin/docker",
    }[kind]
    require(0 < max_output <= MAX_ARCHIVE and 0 < timeout <= 2400, "native-bound")
    process = subprocess.Popen(
        [executable, *arguments],
        env=environment(kind),
        stdin=subprocess.PIPE if payload is not None else subprocess.DEVNULL,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
    )
    try:
        if payload is not None:
            require(len(payload) <= 16384, "native-input")
            process.stdin.write(payload)
            process.stdin.close()
        return _drain(process, timeout, output, max_output)
    finally:
        if process.poll() is None:
            process.kill()
        process.wait()
        process.stdout.close()
        process.stderr.close()


def gh(path):
    return json.loads(run("gh", "api", path))


def request(raw):
    document = decode(raw)
    require(set(document) == REQUEST_FIELDS, "dispatch-fields")
    require(codec._hex(document["source_sha"], 40), "source-sha")
    require(document["platform"] == "linux/amd64", "publisher-platform")
    registry = codec.RegistryReleaseBinding(
        **{
            key: document[key]
            for key in codec.RegistryReleaseBinding.__dataclass_fields__
        }
    )
    registry.validate()
    return document


def actor(value):
    require(type(value) is dict, "dispatch-actor")
    require(
        value.get("id") == BOT_ID
        and type(value.get("id")) is int
        and value.get("login") == f"{APP_SLUG}[bot]"
        and value.get("type") == "Bot",
        "dispatch-actor",
    )


def admit(*, api=gh, env=None, event=None):
    env = os.environ if env is None else env
    require(
        env.get("GITHUB_REPOSITORY") == codec.REPOSITORY
        and env.get("GITHUB_REPOSITORY_ID") == "646535009"
        and env.get("GITHUB_REPOSITORY_OWNER_ID") == "114362548",
        "repository",
    )
    require(
        env.get("GITHUB_EVENT_NAME") == "workflow_dispatch"
        and env.get("GITHUB_REF") == "refs/heads/main"
        and env.get("GITHUB_RUN_ATTEMPT") == "1",
        "workflow",
    )
    require(
        env.get("GITHUB_WORKFLOW_REF") == codec.WORKFLOW_REF
        and codec._hex(env.get("GITHUB_SHA"), 40),
        "workflow",
    )
    run_id = env.get("GITHUB_RUN_ID", "")
    require(re.fullmatch(r"[1-9][0-9]*", run_id) is not None, "run-id")
    native = api(f"{API}/actions/runs/{run_id}")
    require(
        native.get("id") == int(run_id)
        and native.get("run_attempt") == 1
        and type(native.get("run_attempt")) is int,
        "run",
    )
    require(
        native.get("head_sha") == env["GITHUB_SHA"]
        and native.get("head_branch") == "main"
        and native.get("event") == "workflow_dispatch"
        and native.get("path") == WORKFLOW,
        "run",
    )
    for key in ("repository", "head_repository"):
        repository = native.get(key, {})
        require(
            repository.get("id") == 646535009
            and repository.get("full_name") == codec.REPOSITORY
            and repository.get("owner", {}).get("id") == 114362548,
            "repository",
        )
    actor(native.get("actor"))
    actor(native.get("triggering_actor"))
    app = api(f"apps/{APP_SLUG}")
    require(app.get("id") == APP_ID and app.get("slug") == APP_SLUG, "dispatch-app")
    actor(api(f"users/{APP_SLUG}[bot]"))
    event = event_document(Path(env["GITHUB_EVENT_PATH"])) if event is None else event
    require(
        type(event.get("inputs")) is dict and set(event["inputs"]) == {"request"},
        "dispatch-input",
    )
    actor(event.get("sender"))
    value = event["inputs"]["request"]
    require(type(value) is str, "dispatch-input")
    document = request(value.encode())
    require(document["source_sha"] == env["GITHUB_SHA"], "dispatch-source-sha")
    comparison = api(f"{API}/compare/{document['source_sha']}...main")
    require(
        comparison.get("merge_base_commit", {}).get("sha") == document["source_sha"]
        and comparison.get("status") in ("ahead", "identical"),
        "reviewed-main-source",
    )
    return document, int(run_id), env["GITHUB_SHA"]


def jobs(run_id, *, api=gh):
    result = api(f"{API}/actions/runs/{run_id}/attempts/1/jobs?per_page=100")
    values = result.get("jobs", [])
    require(result.get("total_count") == len(values) and len(values) == 3, "jobs")
    require({value.get("name") for value in values} == set(JOBS), "jobs")
    indexed = {value["name"]: value for value in values}
    for name in JOBS[:2]:
        job = indexed[name]
        require(
            job.get("run_id") == run_id
            and job.get("run_attempt") == 1
            and job.get("status") == "completed"
            and job.get("conclusion") == "success"
            and codec._positive(job.get("id")),
            "completed-job",
        )
    return indexed


def protected_environment(*, api=gh):
    endpoint = f"{API}/environments/{codec.PUBLISHER_ENVIRONMENT}"
    value = api(endpoint)
    require(value.get("can_admins_bypass") is False, "environment-bypass")
    require(
        value.get("deployment_branch_policy")
        == {"protected_branches": False, "custom_branch_policies": True},
        "environment-branches",
    )
    policies = api(f"{endpoint}/deployment-branch-policies?per_page=100")
    branches = policies.get("branch_policies", [])
    require(
        policies.get("total_count") == 1
        and len(branches) == 1
        and branches[0].get("name") == "main"
        and branches[0].get("type") == "branch",
        "environment-branches",
    )
    user = api("users/Kravalg")
    require(
        user.get("login") == "Kravalg"
        and user.get("type") == "User"
        and codec._positive(user.get("id"))
        and user["id"] != BOT_ID,
        "environment-reviewer",
    )
    reviews = [
        rule
        for rule in value.get("protection_rules", [])
        if rule.get("type") == "required_reviewers"
    ]
    require(
        len(reviews) == 1 and reviews[0].get("prevent_self_review") is True,
        "environment-reviewer",
    )
    reviewers = reviews[0].get("reviewers", [])
    require(
        len(reviewers) == 1
        and reviewers[0].get("type") == "User"
        and reviewers[0].get("reviewer", {}).get("id") == user["id"],
        "environment-reviewer",
    )


def artifact(run_id, name, *, api=gh, maximum=codec.MAX_MANIFEST_BYTES + 4096):
    response = api(f"{API}/actions/runs/{run_id}/artifacts?per_page=100")
    values = response.get("artifacts", [])
    require(
        response.get("total_count") == len(values) and len(values) <= 20, "artifacts"
    )
    matches = [value for value in values if value.get("name") == name]
    require(len(matches) == 1, "artifact-name")
    value = matches[0]
    require(
        codec._positive(value.get("id"))
        and value.get("expired") is False
        and type(value.get("size_in_bytes")) is int
        and 0 < value["size_in_bytes"] <= maximum,
        "artifact",
    )
    require(
        re.fullmatch(r"sha256:[0-9a-f]{64}", value.get("digest", "")) is not None,
        "artifact-digest",
    )
    origin = value.get("workflow_run", {})
    require(
        origin.get("id") == run_id
        and origin.get("repository_id") == 646535009
        and origin.get("head_repository_id") == 646535009
        and origin.get("head_sha") == os.environ["GITHUB_SHA"],
        "artifact-run",
    )
    return value


def download(value, path):
    with path.open("xb") as stream:
        run(
            "gh",
            "api",
            f"{API}/actions/artifacts/{value['id']}/zip",
            output=stream,
            timeout=900,
            max_output=value["size_in_bytes"],
        )
    require(
        path.stat().st_size == value["size_in_bytes"]
        and sha(path) == value["digest"].removeprefix("sha256:"),
        "artifact-bytes",
    )


def metadata(document, run_id, image_paths):
    images = {}
    for kind, target in codec.TARGETS.items():
        path = image_paths[kind]
        require(0 < path.stat().st_size <= MAX_IMAGE, "image-size")
        images[kind] = {
            "target": target,
            "archive_sha256": sha(path),
            "archive_bytes": path.stat().st_size,
        }
    return {
        "schema_version": "poc-image-build-v1",
        "source_sha": document["source_sha"],
        "publisher_run_id": run_id,
        "publisher_run_attempt": 1,
        "platform": document["platform"],
        "images": images,
    }


def extract_build(archive_path, directory, document, run_id):
    with zipfile.ZipFile(archive_path) as archive:
        entries = archive.infolist()
        require(
            len(entries) == 3
            and {member.filename for member in entries}
            == {"build.json", "web.tar", "worker.tar"},
            "build-members",
        )
        for member in entries:
            maximum = (
                codec.MAX_MANIFEST_BYTES
                if member.filename == "build.json"
                else MAX_IMAGE
            )
            require(
                member.filename == member.orig_filename
                and not member.is_dir()
                and not member.flag_bits & 1
                and stat.S_IFMT(member.external_attr >> 16) in (0, stat.S_IFREG)
                and 0 < member.file_size <= maximum,
                "build-member",
            )
            with (
                archive.open(member) as source,
                (directory / member.filename).open("xb") as target,
            ):
                count = 0
                while block := source.read(1024 * 1024):
                    count += len(block)
                    require(count <= maximum, "build-size")
                    target.write(block)
            require(count == member.file_size, "build-size")
    actual = decode((directory / "build.json").read_bytes())
    expected = metadata(
        document, run_id, {kind: directory / f"{kind}.tar" for kind in codec.TARGETS}
    )
    require(canonical(actual) == canonical(expected), "build-binding")
    return actual


def build(document, run_id, directory):
    source = Path(os.environ["GITHUB_WORKSPACE"]) / ".source"
    result = subprocess.run(
        ["/usr/bin/git", "-C", str(source), "rev-parse", "HEAD"],
        capture_output=True,
        check=True,
        timeout=30,
    )
    require(result.stdout.decode().strip() == document["source_sha"], "build-checkout")
    clean = subprocess.run(
        [
            "/usr/bin/git",
            "-C",
            str(source),
            "status",
            "--porcelain",
            "--untracked-files=all",
        ],
        capture_output=True,
        check=True,
        timeout=30,
    )
    require(clean.stdout == b"", "dirty-build-source")
    images = {}
    for kind, target in codec.TARGETS.items():
        tag = f"poc-{kind}:{document['source_sha']}"
        run(
            "docker",
            "build",
            "--quiet",
            "--pull",
            "--platform",
            document["platform"],
            "--target",
            target,
            "--tag",
            tag,
            "--file",
            str(source / "Dockerfile"),
            str(source),
            timeout=2400,
        )
        path = directory / f"{kind}.tar"
        run("docker", "save", "--output", str(path), tag, timeout=300)
        images[kind] = path
    (directory / "build.json").write_bytes(
        canonical(metadata(document, run_id, images))
    )


def prepare(document, run_id, directory):
    completed = jobs(run_id)
    value = artifact(run_id, f"poc-image-build-{run_id}-1", maximum=MAX_ARCHIVE)
    archive = directory / "build.zip"
    download(value, archive)
    extract_build(archive, directory, document, run_id)
    binding = {
        "artifact_id": value["id"],
        "archive_sha256": sha(archive),
        "file_sha256": sha(directory / "build.json"),
    }
    evidence = {
        "request": document,
        "quality_job_id": completed[JOBS[0]]["id"],
        "build_job_id": completed[JOBS[1]]["id"],
        "build_artifact": binding,
    }
    (directory / "prepared.json").write_bytes(canonical(evidence))


def publish(document, run_id, workflow_sha, directory):
    prepared = decode((directory / "prepared.json").read_bytes())
    require(prepared["request"] == document, "prepared-request")
    completed = jobs(run_id)
    require(
        prepared["quality_job_id"] == completed[JOBS[0]]["id"]
        and prepared["build_job_id"] == completed[JOBS[1]]["id"],
        "prepared-jobs",
    )
    require(
        sha(directory / "build.json") == prepared["build_artifact"]["file_sha256"],
        "prepared-build",
    )
    require(
        canonical(decode((directory / "build.json").read_bytes()))
        == canonical(
            metadata(
                document,
                run_id,
                {kind: directory / f"{kind}.tar" for kind in codec.TARGETS},
            )
        ),
        "prepared-images",
    )
    identity = json.loads(run("aws", "sts", "get-caller-identity", "--output", "json"))
    require(
        identity.get("Account") == "891377212104"
        and identity.get("Arn")
        == f"arn:aws:sts::891377212104:assumed-role/user-service-test-ImagePublisher/poc-images-{run_id}-1",
        "publisher-identity",
    )
    password = run("aws", "ecr", "get-login-password", "--region", "eu-central-1")
    run(
        "docker",
        "login",
        "--username",
        "AWS",
        "--password-stdin",
        codec.REGISTRY,
        payload=password,
    )
    results = {}
    for kind, target in codec.TARGETS.items():
        source = f"poc-{kind}:{document['source_sha']}"
        repository = f"{codec.REGISTRY}/user-service-test-{kind}"
        tag = f"sha-{document['source_sha']}-{run_id}-1"
        run("docker", "load", "--input", str(directory / f"{kind}.tar"), timeout=300)
        run("docker", "tag", source, f"{repository}:{tag}")
        run("docker", "push", f"{repository}:{tag}", timeout=1200)
        observed = json.loads(
            run(
                "aws",
                "ecr",
                "describe-images",
                "--repository-name",
                f"user-service-test-{kind}",
                "--image-ids",
                f"imageTag={tag}",
                "--output",
                "json",
            )
        )
        details = observed.get("imageDetails", [])
        require(
            len(details) == 1
            and tag in details[0].get("imageTags", [])
            and details[0].get("registryId") == "891377212104"
            and details[0].get("repositoryName") == f"user-service-test-{kind}",
            "image-readback",
        )
        results[kind] = codec.BuildResult(
            document["source_sha"],
            run_id,
            document["platform"],
            repository,
            target,
            details[0]["imageDigest"],
        )
    registry = codec.RegistryReleaseBinding(
        **{
            key: document[key]
            for key in codec.RegistryReleaseBinding.__dataclass_fields__
        }
    )
    common = dict(
        source_sha=document["source_sha"],
        publisher_run_id=run_id,
        platform=document["platform"],
        registry=registry,
        **results,
    )
    provenance, quality = codec.build_release_evidence(
        **common,
        workflow_sha=workflow_sha,
        quality_job_id=prepared["quality_job_id"],
        build_job_id=prepared["build_job_id"],
        build_artifact=prepared["build_artifact"],
    )
    (directory / "provenance.json").write_bytes(provenance)
    (directory / "quality.json").write_bytes(quality)
    (directory / "published.json").write_bytes(
        canonical({kind: asdict(value) for kind, value in results.items()})
    )


def reference(run_id, name, member, path):
    value = artifact(run_id, name)
    downloaded = path.parent / f"{member}.zip"
    download(value, downloaded)
    with zipfile.ZipFile(downloaded) as archive:
        entries = archive.infolist()
        require(
            len(entries) == 1
            and entries[0].filename == member
            and 0 < entries[0].file_size <= codec.MAX_MANIFEST_BYTES,
            "evidence-member",
        )
        require(archive.read(member) == path.read_bytes(), "evidence-readback")
    return {
        "artifact_id": value["id"],
        "archive_sha256": sha(downloaded),
        "file_sha256": sha(path),
    }


def manifest(document, run_id, workflow_sha, directory):
    registry = codec.RegistryReleaseBinding(
        **{
            key: document[key]
            for key in codec.RegistryReleaseBinding.__dataclass_fields__
        }
    )
    results = decode((directory / "published.json").read_bytes())
    raw = codec.build_release_manifest(
        source_sha=document["source_sha"],
        publisher_run_id=run_id,
        platform=document["platform"],
        registry=registry,
        provenance=reference(
            run_id,
            f"poc-build-provenance-{run_id}-1",
            "provenance.json",
            directory / "provenance.json",
        ),
        quality_evidence=reference(
            run_id,
            f"poc-quality-evidence-{run_id}-1",
            "quality.json",
            directory / "quality.json",
        ),
        **{key: codec.BuildResult(**value) for key, value in results.items()},
    )
    prepared = decode((directory / "prepared.json").read_bytes())
    codec.validate_release_evidence(
        (directory / "provenance.json").read_bytes(),
        (directory / "quality.json").read_bytes(),
        manifest=json.loads(raw),
        workflow_sha=workflow_sha,
        quality_job_id=prepared["quality_job_id"],
        build_job_id=prepared["build_job_id"],
    )
    (directory / "release-manifest.json").write_bytes(raw)


def main(argv=None):
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "mode", choices=("admit", "build", "prepare", "publish", "manifest", "readback")
    )
    args = parser.parse_args(argv)
    try:
        os.umask(0o077)
        document, run_id, workflow_sha = admit()
        if args.mode in ("prepare", "publish", "manifest", "readback"):
            protected_environment()
        directory = Path(os.environ["RUNNER_TEMP"]) / "poc-images"
        if args.mode == "admit":
            with open(os.environ["GITHUB_OUTPUT"], "a", encoding="utf-8") as stream:
                stream.write(
                    f"source_sha={document['source_sha']}\nplatform={document['platform']}\n"
                )
        elif args.mode in ("build", "prepare"):
            directory.mkdir(mode=0o700)
            {"build": build, "prepare": prepare}[args.mode](document, run_id, directory)
        elif args.mode in ("publish", "manifest"):
            {"publish": publish, "manifest": manifest}[args.mode](
                document, run_id, workflow_sha, directory
            )
        else:
            reference(
                run_id,
                f"poc-release-manifest-{run_id}-1",
                "release-manifest.json",
                directory / "release-manifest.json",
            )
        print(f"PASS: image publisher {args.mode}")
        return 0
    except (
        OSError,
        ValueError,
        KeyError,
        TypeError,
        subprocess.SubprocessError,
        zipfile.BadZipFile,
        EOFError,
    ):
        print("Image publishing failed.", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
