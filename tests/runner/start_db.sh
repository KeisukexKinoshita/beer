#!/bin/bash
# 特性テスト用ローカルMariaDBの起動 + beerスキーマ/ユーザーの用意
# コンテナ(再)起動後に一度実行する。使い方: bash tests/runner/start_db.sh
set -eu

SOCK=/run/mariadb/mariadb.sock
RDS_HOST=beer-rds.cqpmrauqnmy0.ap-northeast-1.rds.amazonaws.com

# 旧RDSホスト名をローカルに向ける (実RDSへの誤接続防止も兼ねる)
# (このコンテナの中だけの話。deploy/ 側の _remote_query が実 RDS を引く経路とは無関係)
# 追記は「末尾の改行を保証してから」行う。以前は `echo >>` だけで、/etc/hosts の最後が改行で
# 終わっていないと前の行に連結して(例: `ff02::2 ip6-allrouters127.0.0.1 beer-rds…`)
# どちらの行も壊れ、RDS ホスト名が名前解決できなくなっていた。
# /etc/hosts はコンテナでは bind mount なので、sed -i(rename)ではなく中身を書き戻す
HOSTS_FILE="${HOSTS_FILE:-/etc/hosts}"   # 試験のときだけ写しのファイルを指す
fix_hosts() {
  local tmp; tmp="$(mktemp)"
  # すでに連結してしまっている行を、改行で分け直す
  sed -E "s/([^[:space:]])(127\\.0\\.0\\.1[[:space:]]+$RDS_HOST)/\\1\\n\\2/" "$HOSTS_FILE" > "$tmp"
  # RDS のホスト名が 127.0.0.1 以外の IP で書かれていれば、そのホスト名だけを行から外す
  # (後ろに 127.0.0.1 の行を足しても、先に書かれた行が勝って実 RDS を向いたままになるため)。
  # ホスト名を外して IP だけが残った行は捨てる
  awk -v h="$RDS_HOST" '
    /^[[:space:]]*#/ || NF == 0 { print; next }
    { ip = $1; keep = ""; hit = 0
      for (i = 2; i <= NF; i++) { if ($i == h && ip != "127.0.0.1") { hit = 1 } else { keep = keep " " $i } }
      if (!hit) print; else if (keep != "") print ip keep }' "$tmp" > "$tmp.2" && mv "$tmp.2" "$tmp"
  if ! grep -Eq "^127\\.0\\.0\\.1[[:space:]]+([^#]*[[:space:]])?$RDS_HOST([[:space:]]|$)" "$tmp"; then
    [ -n "$(tail -c1 "$tmp")" ] && echo >> "$tmp"
    echo "127.0.0.1 $RDS_HOST" >> "$tmp"
  fi
  if ! cmp -s "$tmp" "$HOSTS_FILE"; then cat "$tmp" > "$HOSTS_FILE"; echo "$HOSTS_FILE を修復/追記した"; fi
  rm -f "$tmp"
}
fix_hosts

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
# `beer` は tests/unit/ が前提とする実データ用(このスクリプトは作るだけで中身には触らない)。
# `beer_characterization` は特性テスト(tests/runner/)専用のスキーマ。
# 特性テストはケースごとに tests/fixtures/fixtures.sql で丸ごと作り直すため、
# 実データの入った `beer` と分けないと、実行するたびに実データを壊してしまう
# (tests/cases.md §13.6)。
mysql --socket=$SOCK -e "
CREATE DATABASE IF NOT EXISTS beer CHARACTER SET utf8mb4;
CREATE DATABASE IF NOT EXISTS beer_characterization CHARACTER SET utf8mb4;
CREATE USER IF NOT EXISTS 'admin'@'127.0.0.1' IDENTIFIED BY 'REDACTED_DB_PASSWORD';
CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'REDACTED_DB_PASSWORD';
GRANT ALL ON beer.* TO 'admin'@'127.0.0.1';
GRANT ALL ON beer.* TO 'admin'@'localhost';
GRANT ALL ON beer_characterization.* TO 'admin'@'127.0.0.1';
GRANT ALL ON beer_characterization.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;"

php -r "new PDO('mysql:dbname=beer;host=$RDS_HOST','admin','REDACTED_DB_PASSWORD'); echo 'PDO-CONNECT-OK', PHP_EOL;"
