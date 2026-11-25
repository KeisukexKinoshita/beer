#!/bin/bash
# tests/out/ の現在の出力を golden にする。**取り直す前に出力を読むこと**
# (Fatal / Warning / 空 / 壊れた HTML を正しい出力として保存しないため)。
# 使い方: bash tests/runner/run_all.sh && bash tests/runner/update_golden.sh [ケースID...]  (省略で全ケース)
set -eu
cd "$(dirname "$0")/../.."
ids=("$@")
if [ ${#ids[@]} -eq 0 ]; then
  for f in tests/runner/cases/*.json; do ids+=("$(basename "$f" .json)"); done
fi
mkdir -p tests/golden
for id in "${ids[@]}"; do
  rm -f tests/golden/$id.*
  cp tests/out/$id.* tests/golden/
  echo "golden updated: $id"
done
echo "次: bash tests/runner/compare_golden.sh (Fatal/Warning が golden に入っていないか NOISE 検査が見る)"
