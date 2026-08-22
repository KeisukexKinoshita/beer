#!/bin/bash
# 特性テスト用ローカルMariaDBの起動 + beerスキーマ/ユーザーの用意
# コンテナ(再)起動後に一度実行する。使い方: bash tests/runner/start_db.sh
set -eu

SOCK=/run/mariadb/mariadb.sock
RDS_HOST=beer-rds.cqpmrauqnmy0.ap-northeast-1.rds.amazonaws.com

# 旧RDSホスト名をローカルに向ける (実RDSへの誤接続防止も兼ねる)
grep -q "$RDS_HOST" /etc/hosts || echo "127.0.0.1 $RDS_HOST" >> /etc/hosts

mkdir -p /run/mariadb
chown mysql:mysql /run/mariadb /var/lib/mysql 2>/dev/null || true

if ! mysqladmin --socket=$SOCK ping >/dev/null 2>&1; then
  (mariadbd --user=mysql --datadir=/var/lib/mysql --socket=$SOCK \
    --port=3306 --bind-address=127.0.0.1 >/var/log/mariadbd.log 2>&1 &)
  for i in $(seq 1 20); do
    mysqladmin --socket=$SOCK ping >/dev/null 2>&1 && break
    sleep 1
  done
fi
mysqladmin --socket=$SOCK ping

# コード直書きのプレースホルダ認証情報でローカル接続できるようにする
mysql --socket=$SOCK -e "
CREATE DATABASE IF NOT EXISTS beer CHARACTER SET utf8mb4;
CREATE USER IF NOT EXISTS 'admin'@'127.0.0.1' IDENTIFIED BY 'REDACTED_DB_PASSWORD';
CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'REDACTED_DB_PASSWORD';
GRANT ALL ON beer.* TO 'admin'@'127.0.0.1';
GRANT ALL ON beer.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;"

php -r "new PDO('mysql:dbname=beer;host=$RDS_HOST','admin','REDACTED_DB_PASSWORD'); echo 'PDO-CONNECT-OK', PHP_EOL;"
