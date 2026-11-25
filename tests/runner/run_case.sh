#!/bin/bash
# 1ケース実行: フィクスチャ再投入 → ページ/ラッパー実行 → 出力とDB状態を tests/out/ に保存
# 使い方: bash tests/runner/run_case.sh tests/runner/cases/TC-XXX.json
set -eu
cd "$(dirname "$0")/../.."

SOCK=/run/mariadb/mariadb.sock
# 特性テスト専用のスキーマ。**実DB(`beer`)とは別にする。**
# 以前はここで `beer` を直接使っており、tests/unit/ が前提とする実データ
# (products 200 / maker 40 / style 102、マイグレーション適用済み)を
# 毎ケースのフィクスチャ再投入で上書きして壊していた(tests/cases.md §13.6)。
DB=beer_characterization
CASE_FILE="$1"

eval "$(python3 - "$CASE_FILE" <<'PYEOF'
import json, sys, shlex
c = json.load(open(sys.argv[1]))
print(f"ID={shlex.quote(c['id'])}")
print(f"TYPE={shlex.quote(c['type'])}")
print(f"PAGE={shlex.quote(c.get('page',''))}")
print(f"PRE_IMG={shlex.quote(c.get('pre_img',''))}")
print(f"DUMP_TABLES={shlex.quote(' '.join(c.get('dump_tables',[])))}")
print(f"SCOPE={shlex.quote(c.get('scope','legacy'))}")
print(f"EXTRA_SQL={shlex.quote(' '.join(c.get('extra_sql',[])))}")
PYEOF
)"

mkdir -p tests/out

# フィクスチャ再投入 (毎ケース、クリーンな状態から)。
# スキーマごと作り直し、fixtures.sql のあとに db/migrations/ の未適用分を全部当てる
# (tests/runner/load_fixtures.sh)。ケースが extra_sql を持てば、その後に流す
# shellcheck disable=SC2086
bash tests/runner/load_fixtures.sh $EXTRA_SQL

# 画像rename用ダミーの用意と、前ケースの残骸掃除
rm -f tests/sandbox/img/product/*.png tests/sandbox/img/tmp/*.png
if [ -n "$PRE_IMG" ]; then
  : > "tests/sandbox/img/tmp/$PRE_IMG"
fi

# 実行パラメータの抽出
python3 - "$CASE_FILE" > "tests/out/$ID.params.json" <<'PYEOF'
import json, sys
c = json.load(open(sys.argv[1]))
if c['type'] == 'core':
    json.dump(c.get('vars', {}), sys.stdout, ensure_ascii=False)
else:
    json.dump(c.get('params', {"get": {}, "post": {}}), sys.stdout, ensure_ascii=False)
PYEOF

# 実行 (1ケース=1プロセス)
set +e
rm -f "tests/out/$ID.head.txt"
if [ "$TYPE" = "core" ]; then
  php tests/runner/core_case.php "tests/out/$ID.params.json" > "tests/out/$ID.html" 2>&1
elif [ "$SCOPE" = "site" ]; then
  # 新サイトのページは、PHP 組み込みサーバに実 HTTP で叩き、ステータスと応答ヘッダも .head.txt に残す
  # (CLI の PHP は header() を保持しないため、ヘッダの比較には実サーバが要る)
  python3 tests/runner/http_page.py "$PAGE" "tests/out/$ID.params.json" "tests/out/$ID.html" "tests/out/$ID.head.txt"
else
  php tests/runner/exec_page.php "$PAGE" "tests/out/$ID.params.json" > "tests/out/$ID.html" 2>&1
fi
echo $? > "tests/out/$ID.exit"
set -e

# 実行のたびに変わる値(?v=<filemtime>、tok、Set-Cookie の識別子と期限)だけを固定の印に置き換える。
# 何を置き換えるかは tests/runner/normalize.php の冒頭にある
HEADARG=""
[ -f "tests/out/$ID.head.txt" ] && HEADARG="tests/out/$ID.head.txt"
php tests/runner/normalize.php "tests/out/$ID.html" $HEADARG > "tests/out/$ID.html.tmp"
mv "tests/out/$ID.html.tmp" "tests/out/$ID.html"
if [ -n "$HEADARG" ]; then
  php tests/runner/normalize.php "$HEADARG" > "$HEADARG.tmp"
  mv "$HEADARG.tmp" "$HEADARG"
fi

# DB状態のダンプ (POST系の副作用検証用)
if [ -n "$DUMP_TABLES" ]; then
  : > "tests/out/$ID.db.txt"
  for t in $DUMP_TABLES; do
    {
      echo "== $t =="
      mysql --socket=$SOCK $DB -e "SELECT * FROM $t"
    } >> "tests/out/$ID.db.txt"
  done
  # rename() の結果 (img/product の中身) も記録
  echo "== img/product ==" >> "tests/out/$ID.db.txt"
  ls tests/sandbox/img/product/ >> "tests/out/$ID.db.txt" 2>&1 || true
fi

# dump_queries: 実行のたびに変わる列(乱数の ID・日時)を含まないよう、ケース側で書いた SELECT の結果を残す
if python3 -c "import json,sys; sys.exit(0 if json.load(open(sys.argv[1])).get('dump_queries') else 1)" "$CASE_FILE"; then
  : > "tests/out/$ID.db.txt"
  python3 -c "
import json,sys
for q in json.load(open(sys.argv[1]))['dump_queries']: print(q)" "$CASE_FILE" | while IFS= read -r q; do
    { echo "== $q =="; mysql --socket=$SOCK $DB -e "$q"; } >> "tests/out/$ID.db.txt"
  done
fi

echo "$ID done (exit=$(cat tests/out/$ID.exit))"
