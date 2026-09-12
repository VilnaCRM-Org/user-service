# Offline helper tests use an already available Python container, never AWS.
PUBLISHER_TEST_IMAGE ?= poc-service-reconciled-pulumi:latest
.PHONY: test check
test:
	docker run --rm --network none --read-only --cap-drop ALL --security-opt no-new-privileges --user 65534:65534 --mount type=bind,src=$(CURDIR),dst=/source,readonly --workdir /source --entrypoint python3 $(PUBLISHER_TEST_IMAGE) -I -B tests/CLI/test_poc_release_manifest.py
check:
	docker run --rm --network none --read-only --tmpfs /tmp:rw,noexec,nosuid,size=32m --cap-drop ALL --security-opt no-new-privileges --user 65534:65534 --mount type=bind,src=$(CURDIR),dst=/source,readonly --workdir /source --entrypoint sh $(PUBLISHER_TEST_IMAGE) -c 'export COVERAGE_FILE=/tmp/poc-release.coverage; /home/dev/.venvs/bootstrap-infrastructure/bin/python -I -B -m coverage run --branch --source=scripts tests/CLI/test_poc_release_manifest.py && /home/dev/.venvs/bootstrap-infrastructure/bin/python -I -B -m coverage report --show-missing --fail-under=100'
