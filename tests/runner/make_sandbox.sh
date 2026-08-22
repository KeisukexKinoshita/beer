#!/bin/bash
# 特性テスト用sandboxを原本から再構築する (原本は一切変更しない)
# 使い方: bash tests/runner/make_sandbox.sh   (リポジトリルートで実行)
set -eu
cd "$(dirname "$0")/../.."

rm -rf tests/sandbox
mkdir -p tests/sandbox
for d in common css js php post style brewery bk_html; do
  cp -a "$d" tests/sandbox/
done
cp -a index.php style.css chartjs-plugin-datalabels.min.js tests/sandbox/

# img/ は118MBあるためコピーしない。sql_POST の rename() に必要な骨格だけ作る
mkdir -p tests/sandbox/img/tmp tests/sandbox/img/product

# 合成フィクスチャのメーカー (mk90xx) 用ダミーexplainファイル
# (index.php が brewery/detail/explain/<MakerID>.html を require するため)
for id in mk9000 mk9001 mk9002 mk9003; do
  printf '<p>fixture explain %s</p>\n' "$id" > "tests/sandbox/brewery/detail/explain/$id.html"
done

echo "sandbox rebuilt: $(du -sh tests/sandbox | cut -f1)"
