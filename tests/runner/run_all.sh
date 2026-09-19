#!/bin/bash
# 全ケース実行。使い方: bash tests/runner/run_all.sh
#
# **sandbox を毎回作り直す。** 以前は作り直していなかったため、2026-08-22 の
# 凍結写しを検査し続けており、リニューアル後のコードを1行も見ていなかった。
set -eu
cd "$(dirname "$0")/../.."
bash tests/runner/make_sandbox.sh
for f in tests/runner/cases/*.json; do
  bash tests/runner/run_case.sh "$f"
done
