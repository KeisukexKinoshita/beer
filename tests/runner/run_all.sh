#!/bin/bash
# 全ケース実行。使い方: bash tests/runner/run_all.sh
set -eu
cd "$(dirname "$0")/../.."
for f in tests/runner/cases/*.json; do
  bash tests/runner/run_case.sh "$f"
done
