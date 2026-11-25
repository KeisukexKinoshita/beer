#!/bin/bash
# 特性テスト専用スキーマ(beer_characterization)を、いまの DB 定義で作り直す。
#   1. スキーマごと作り直す(前のケースの残骸・古い定義の表を持ち越さない)
#   2. tests/fixtures/fixtures.sql(2022 年構築当時の基本定義 + 合成データ)
#   3. db/migrations/ の番号順(*_rollback.sql は除く。ファイル名の昇順)を全部当てる
#   4. 引数で渡された追加 SQL(新サイトのケース用。tests/fixtures/site.sql など)
#
# **migration を足したら、ここは何も直さなくてよい。** 手で書き足した fixtures.sql を
# 持つ方式だと、migration が増えるたびにテスト用 DB だけ置いていかれた(2026-09〜11 に
# 実際にそうなって、新サイトのページが1つも検査されない状態が続いた)。
# migration のエラーは無視しない(set -e)。当たらなくなったら、その場で止まって気づける。
# 使い方: bash tests/runner/load_fixtures.sh [追加SQL...]
set -eu
cd "$(dirname "$0")/../.."

SOCK=/run/mariadb/mariadb.sock
# **ここで触るのはこのスキーマだけ。** 実データの `beer` には絶対に触らない。
DB=beer_characterization
[ "$DB" = "beer_characterization" ] || { echo "refusing: $DB" >&2; exit 2; }

{
  echo "DROP DATABASE IF EXISTS \`$DB\`;"
  echo "CREATE DATABASE \`$DB\` CHARACTER SET utf8mb4;"
  echo "USE \`$DB\`;"
  cat tests/fixtures/fixtures.sql
  echo
  for f in $(ls db/migrations/[0-9]*.sql | grep -v '_rollback\.sql$' | sort); do
    echo "-- ==== $f"
    cat "$f"
    echo
  done
  for f in "$@"; do
    echo "-- ==== $f"
    cat "$f"
    echo
  done
} | mysql --socket=$SOCK
