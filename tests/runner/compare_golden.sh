#!/bin/bash
# tests/out/ (最新実行) と tests/golden/ (ゴールデンマスター) を全ケース比較する
# 使い方: bash tests/runner/compare_golden.sh
set -eu
cd "$(dirname "$0")/../.."

fail=0
for g in tests/golden/TC-*.html tests/golden/TC-*.exit tests/golden/TC-*.db.txt tests/golden/TC-*.params.json; do
  [ -e "$g" ] || continue
  o="tests/out/$(basename "$g")"
  if [ ! -e "$o" ]; then
    echo "MISSING: $o"
    fail=1
  elif ! diff -q "$g" "$o" >/dev/null; then
    echo "DIFF: $(basename "$g")"
    fail=1
  fi
done
if [ $fail -eq 0 ]; then
  echo "ALL MATCH (golden一致)"
else
  echo "---- 差分の詳細は: diff tests/golden/<file> tests/out/<file>"
  exit 1
fi
