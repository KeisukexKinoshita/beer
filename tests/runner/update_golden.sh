#!/bin/bash
# tests/out/ の現在の出力を golden にする。**取り直す前に出力を読むこと**
# (Fatal / Warning / 空 / 壊れた HTML を正しい出力として保存しないため)。
# 使い方: bash tests/runner/run_all.sh && bash tests/runner/update_golden.sh [ケースID...]  (省略で全ケース)
#
# 安全策: 出力が無い・古い(RUN_COMPLETE が無い、作業ツリーの指紋が合わない)ときは、
# **golden を1つも消さずに**失敗する。先に出力を別の場所へ写し、全部写せてから golden を入れ替える。
set -eu
cd "$(dirname "$0")/../.."
[ -f tests/out/RUN_COMPLETE ] || { echo "update_golden: tests/out/RUN_COMPLETE が無い。先に run_all.sh を実行すること" >&2; exit 1; }
want="$(sed -n 's/^fingerprint=//p' tests/out/RUN_COMPLETE)"
now="$(bash tests/runner/fingerprint.sh)"
[ "$want" = "$now" ] || { echo "update_golden: run_all.sh のあとに作業ツリーが変わっている(古い出力)。run_all.sh をやり直すこと" >&2; exit 1; }
ids=("$@")
if [ ${#ids[@]} -eq 0 ]; then
  for f in tests/runner/cases/*.json; do ids+=("$(basename "$f" .json)"); done
fi
stage="$(mktemp -d)"; trap 'rm -rf "$stage"' EXIT
for id in "${ids[@]}"; do
  [ -f "tests/out/$id.html" ] && [ -f "tests/out/$id.exit" ] || { echo "update_golden: tests/out/$id.* が無い(何も変更していない)" >&2; exit 1; }
  cp tests/out/$id.* "$stage"/
done
mkdir -p tests/golden
for id in "${ids[@]}"; do
  rm -f tests/golden/$id.*
  cp "$stage"/$id.* tests/golden/
  echo "golden updated: $id"
done
echo "次: bash tests/runner/compare_golden.sh (Fatal/Warning が golden に入っていないか NOISE 検査が見る)"
