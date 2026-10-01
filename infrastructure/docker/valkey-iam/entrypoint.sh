#!/bin/sh
# Local test-only Valkey 7.2 with TLS and ACL users (S5.13, Redis IAM token provider).
# It mirrors the ElastiCache IAM constraints the app must meet: TLS is required
# and every app user authenticates with AUTH <user> <token>. The integration
# tests create the IAM-like user with ACL SETUSER and a locally signed token as
# its password. A throwaway CA and server certificate are generated on every
# start; nothing here is a real credential.
set -eu

tls_dir="${VALKEY_IAM_TLS_DIR:-/tls}"
mkdir -p "${tls_dir}"
umask 022
work_dir="$(mktemp -d)"
trap 'rm -rf "${work_dir}"' EXIT

openssl req -x509 -newkey rsa:2048 -nodes -days 30 \
	-subj "/CN=valkey-iam-local-test-ca" \
	-keyout "${work_dir}/ca.key" -out "${work_dir}/ca.crt" 2>/dev/null
openssl req -newkey rsa:2048 -nodes \
	-subj "/CN=valkey-iam" \
	-keyout "${work_dir}/server.key" -out "${work_dir}/server.csr" 2>/dev/null
printf 'subjectAltName=DNS:valkey-iam,DNS:localhost,IP:127.0.0.1\n' > "${work_dir}/san.ext"
openssl x509 -req -days 30 -in "${work_dir}/server.csr" \
	-CA "${work_dir}/ca.crt" -CAkey "${work_dir}/ca.key" -CAcreateserial \
	-extfile "${work_dir}/san.ext" -out "${work_dir}/server.crt" 2>/dev/null

install -m 0644 "${work_dir}/ca.crt" "${tls_dir}/ca.crt"
install -m 0644 "${work_dir}/server.crt" "${tls_dir}/server.crt"
install -m 0600 -o valkey -g valkey "${work_dir}/server.key" "${tls_dir}/server.key"

exec docker-entrypoint.sh valkey-server \
	--port 0 \
	--tls-port 6379 \
	--tls-cert-file "${tls_dir}/server.crt" \
	--tls-key-file "${tls_dir}/server.key" \
	--tls-ca-cert-file "${tls_dir}/ca.crt" \
	--tls-auth-clients no \
	--save '' \
	--appendonly no
