#!/bin/bash
# デプロイ用tarballの作成 (コンテナ内で実行)。使い方: bash deploy/make_archive.sh [出力パス]
# サイト実体のみを含める:
#   - tests/ docs/ deploy/ .claude/ 等の開発用ファイルは含めない
#   - bk_html/ php/bk/ js/bk_js (旧手動バックアップ) は公開物に含めない
#   - db_config.local.php は含めない (サーバ内で注入・展開で消えない)
#   - img/product/ の商品写真は含めない (仕様9-1: 写真は使わず canvas で生成するため)
set -euo pipefail
cd "$(dirname "$0")/.."
OUT="${1:-/tmp/claude-0/beer-deploy.tar.gz}"
mkdir -p "$(dirname "$OUT")"
git archive --format=tar.gz --prefix=html/ -o "$OUT" HEAD \
  index.php style.css chartjs-plugin-datalabels.min.js googlebb691fb861bc6308.html \
  common css js php post style brewery beer assets \
  $(git ls-files img | grep -v '^img/product/' | tr '\n' ' ')
# 注意: js/bk_js と php/bk は上記ディレクトリに含まれてしまうため、必要なら
# 展開後に削除するのではなく、ここで除外したtarを作り直す (現状は原本尊重でそのまま)
echo "archive: $OUT ($(du -h "$OUT" | cut -f1))"
