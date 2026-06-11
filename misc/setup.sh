#!/usr/bin/env bash
set -euo pipefail

if ! php -m | grep -qi pdo_mysql; then
  echo "installing php-mysql ..."
  sudo apt-get update && sudo apt-get install -y php-mysql || true
fi

if ! command -v weasyprint &>/dev/null; then
  echo "installing weasyprint ..."
  sudo apt-get update && sudo apt-get install -y weasyprint || true
fi

if ! command -v qrencode &>/dev/null; then
  echo "installing qrencode ..."
  sudo apt-get update && sudo apt-get install -y qrencode || true
fi

if ! command -v docker &>/dev/null; then
  echo "installing docker ..."
  curl -fsSL https://get.docker.com | sh
  sudo usermod -aG docker "$USER"
  echo "you'll need to log out and back in for the docker group to take effect, or run 'newgrp docker'"
fi

if ! docker compose version &>/dev/null && ! command -v docker-compose &>/dev/null; then
  echo "installing docker compose plugin ..."
  sudo apt-get update && sudo apt-get install -y docker-compose-plugin || true
fi

if [[ -f .env ]]; then
  echo "using existing .env"
  set -a; source .env; set +a
else
  MYSQL_PASSWORD=$(openssl rand -base64 24 | tr '+/=' '___')
  MYSQL_ROOT_PASSWORD=$(openssl rand -base64 24 | tr '+/=' '___')
  export MYSQL_PASSWORD MYSQL_ROOT_PASSWORD

  cat > .env <<EOF
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
MYSQL_PASSWORD=${MYSQL_PASSWORD}
EOF
fi

docker compose up -d
