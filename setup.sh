#!/usr/bin/env bash
set -euo pipefail

MYSQL_PASSWORD=$(openssl rand -base64 24 | tr '+/=' '___')
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 24 | tr '+/=' '___')

export MYSQL_PASSWORD
export MYSQL_ROOT_PASSWORD

cat > db.ini <<EOF
host = 127.0.0.1
user = imgchat
password = ${MYSQL_PASSWORD}
EOF

cat > .env <<EOF
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
MYSQL_PASSWORD=${MYSQL_PASSWORD}
EOF

docker compose up -d
