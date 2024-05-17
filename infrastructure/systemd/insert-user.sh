#!/bin/bash
set -e

# Add the user if it does not exist
if ! id -u workeruser > /dev/null 2>&1; then
    useradd -m workeruser
fi

# Set ownership and permissions for /app
chown -R workeruser:workeruser /app
chmod -R u+rx /app
mkdir -p /app/var/cache/dev
chown -R workeruser:workeruser /app/var/cache/dev
mkdir -p /app/var/log
chown -R workeruser:workeruser /app/var/log
