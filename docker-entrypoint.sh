#!/bin/bash
set -e

if [ ! -d /var/lib/mysql/mysql ]; then
    mysql_install_db --user=mysql --datadir=/var/lib/mysql
fi

mkdir -p /run/mysqld
chown -R mysql:mysql /run/mysqld /var/lib/mysql

mysqld_safe --skip-networking &
pid="$!"

for i in {30..0}; do
    if mysqladmin ping --silent 2>/dev/null; then
        break
    fi
    sleep 1
done

if [ "$i" = 0 ]; then
    echo >&2 'MariaDB failed to start'
    exit 1
fi

mysql -u root <<-EOSQL
    CREATE DATABASE IF NOT EXISTS imgchat
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci;
    CREATE USER IF NOT EXISTS 'imgchat'@'localhost' IDENTIFIED BY '${MYSQL_PASSWORD}';
    GRANT ALL ON imgchat.* TO 'imgchat'@'localhost';
    FLUSH PRIVILEGES;
EOSQL

mysql -u root imgchat < /var/www/html/misc/schema.sql

cat > /var/www/html/web/.env <<-EOF
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
MYSQL_PASSWORD=${MYSQL_PASSWORD}
EOF

mysqladmin -u root shutdown

exec "$@"
