#!/bin/bash
# 1ケース実行: フィクスチャ再投入 → ページ/ラッパー実行 → 出力とDB状態を tests/out/ に保存
# 使い方: bash tests/runner/run_case.sh tests/runner/cases/TC-XXX.json
set -eu
cd "$(dirname "$0")/../.."

SOCK=/run/mariadb/mariadb.sock
CASE_FILE="$1"

eval "$(python3 - "$CASE_FILE" <<'PYEOF'
import json, sys, shlex
c = json.load(open(sys.argv[1]))
print(f"ID={shlex.quote(c['id'])}")
print(f"TYPE={shlex.quote(c['type'])}")
print(f"PAGE={shlex.quote(c.get('page',''))}")
print(f"PRE_IMG={shlex.quote(c.get('pre_img',''))}")
print(f"DUMP_TABLES={shlex.quote(' '.join(c.get('dump_tables',[])))}")
PYEOF
)"

mkdir -p tests/out

# フィクスチャ再投入 (毎ケース、クリーンな状態から)
mysql --socket=$SOCK beer < tests/fixtures/fixtures.sql

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
if [ "$TYPE" = "core" ]; then
  php tests/runner/core_case.php "tests/out/$ID.params.json" > "tests/out/$ID.html" 2>&1
else
  php tests/runner/exec_page.php "$PAGE" "tests/out/$ID.params.json" > "tests/out/$ID.html" 2>&1
fi
echo $? > "tests/out/$ID.exit"
set -e

# DB状態のダンプ (POST系の副作用検証用)
if [ -n "$DUMP_TABLES" ]; then
  : > "tests/out/$ID.db.txt"
  for t in $DUMP_TABLES; do
    {
      echo "== $t =="
      mysql --socket=$SOCK beer -e "SELECT * FROM $t"
    } >> "tests/out/$ID.db.txt"
  done
  # rename() の結果 (img/product の中身) も記録
  echo "== img/product ==" >> "tests/out/$ID.db.txt"
  ls tests/sandbox/img/product/ >> "tests/out/$ID.db.txt" 2>&1 || true
fi

echo "$ID done (exit=$(cat tests/out/$ID.exit))"
