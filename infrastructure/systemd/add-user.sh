#!/bin/bash
set -e

# Add the user if it does not exist
if ! id -u user > /dev/null 2>&1; then
    useradd -m user
fi

# Set ownership and permissions for /app
chown -R user:user /app
chmod -R u+rx /app
mkdir -p /app/var/cache/dev
chown -R user:user /app/var/cache/dev
mkdir -p /app/var/log
chown -R user:user /app/var/log
