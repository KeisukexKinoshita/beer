#!/bin/bash
# tests/out/ (最新実行) と tests/golden/ (ゴールデンマスター) を全ケース比較する
# 使い方: bash tests/runner/compare_golden.sh
#
# tests/runner/known-failures.txt に列挙したファイルは、fixtures.sql のスキーマが
# 古いために毎回 DIFF になることが分かっている(tests/cases.md §13.3)。これを
# KNOWN-DIFF として報告するが失敗にはしない。一覧に無いファイルの DIFF だけを
# 本物の回帰として失敗にする。逆に、一覧にあるのに一致してしまったファイルは
# STALE として失敗にする(直ったのに一覧に残っていると、次の人を欺くため)。
set -eu
cd "$(dirname "$0")/../.."

KNOWN_FILE="tests/runner/known-failures.txt"
KNOWN=()
if [ -f "$KNOWN_FILE" ]; then
  while IFS= read -r line; do
    line="${line%%#*}"
    line="$(echo "$line" | tr -d '[:space:]')"
    [ -n "$line" ] && KNOWN+=("$line")
  done < "$KNOWN_FILE"
fi

is_known() {
  local name="$1"
  for k in ${KNOWN[@]+"${KNOWN[@]}"}; do
    [ "$k" = "$name" ] && return 0
  done
  return 1
}

fail=0
unknown=0

for g in tests/golden/TC-*.html tests/golden/TC-*.exit tests/golden/TC-*.db.txt tests/golden/TC-*.params.json; do
  [ -e "$g" ] || continue
  base="$(basename "$g")"
  o="tests/out/$base"
  if [ ! -e "$o" ]; then
    echo "MISSING: $o"
    fail=1
    unknown=$((unknown + 1))
  elif ! diff -q "$g" "$o" >/dev/null; then
    if is_known "$base"; then
      echo "KNOWN-DIFF: $base"
    else
      echo "DIFF: $base"
      fail=1
      unknown=$((unknown + 1))
    fi
  else
    if is_known "$base"; then
      echo "STALE: $base (既知の失敗の一覧にあるが一致した。known-failures.txt から外してください)"
      fail=1
    fi
  fi
done

echo "未知の DIFF: ${unknown}件"
if [ $fail -ne 0 ]; then
  echo "---- 差分の詳細は: diff tests/golden/<file> tests/out/<file>"
  exit 1
fi
echo "ALL MATCH (known-failures 分を除く)"
