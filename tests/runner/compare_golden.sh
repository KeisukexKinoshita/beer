#!/bin/bash
# tests/out/ (最新実行) と tests/golden/ を、ケース定義を起点に全ケース比較する。
# 使い方: bash tests/runner/run_all.sh && bash tests/runner/compare_golden.sh
# 仕組みと失敗の種類は compare_golden.py の冒頭。例外は tests/runner/known-failures.txt
# (すべて理由つき。新サイトのページのケースは例外にできない)。
exec python3 "$(dirname "$0")/compare_golden.py"
