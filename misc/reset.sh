#!/usr/bin/env bash
set -euo pipefail

docker compose down -v
rm -f .env
echo "gone. run ./setup.sh to start over."
