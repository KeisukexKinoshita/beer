#!/bin/bash
# 作業ツリーの指紋。run_all.sh が実行時点のものを RUN_COMPLETE に書き、compare_golden.py / update_golden.sh が
# いまのものと突き合わせる(run_all のあとにソースを編集して compare だけ走らせ、古い出力で通るのを防ぐ)。
# 対象: `git diff HEAD`(追跡ファイルの変更)+ 未追跡で git が無視していないファイルの一覧と中身のハッシュ。
# 対象外: tests/golden(実行結果に影響しない。update_golden の前後で変わってよい)・tests/out・tests/sandbox。
# git が無い場所(git archive の展開先など)は "none"。
cd "$(dirname "$0")/../.."
git rev-parse --git-dir >/dev/null 2>&1 || { echo none; exit 0; }
EX=(':(exclude)tests/golden' ':(exclude)tests/out' ':(exclude)tests/sandbox')
{
  git diff HEAD --binary -- . "${EX[@]}"
  echo "-- untracked"
  git ls-files --others --exclude-standard -z -- . "${EX[@]}" | sort -z | xargs -0 -r sha256sum
} | sha256sum | cut -d' ' -f1
