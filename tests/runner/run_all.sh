#!/bin/bash
# 全ケース実行。使い方: bash tests/runner/run_all.sh
#
# **sandbox を毎回作り直す。** 以前は作り直していなかったため、2026-08-22 の
# 凍結写しを検査し続けており、リニューアル後のコードを1行も見ていなかった。
#
# **tests/out を最初に空にする。** 途中で失敗したときに、前回の出力が残ったまま
# compare_golden.sh が通ってしまうのを防ぐ。全ケースを走り切ったときだけ、完了の印
# (tests/out/RUN_COMPLETE: 実行したケース数・HEAD のコミット・作業ツリーの汚れ)を書く。
# compare_golden.sh は、この印が無い・ケース数が合わない・HEAD が違うときに失敗する。
set -eu
cd "$(dirname "$0")/../.."
rm -rf tests/out
mkdir -p tests/out
bash tests/runner/make_sandbox.sh
n=0
for f in tests/runner/cases/*.json; do
  bash tests/runner/run_case.sh "$f"
  n=$((n + 1))
done
head="none"; dirty="unknown"
if git rev-parse --git-dir >/dev/null 2>&1; then
  head="$(git rev-parse HEAD)"
  dirty="$(git status --porcelain | wc -l | tr -d ' ')"
fi
printf 'cases=%s\nhead=%s\ndirty_files=%s\n' "$n" "$head" "$dirty" > tests/out/RUN_COMPLETE
echo "完了: ${n} ケース(HEAD=${head:0:7}, 未コミットのファイル ${dirty} 件)"
