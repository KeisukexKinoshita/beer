#!/bin/bash
# デプロイ用tarballの作成 (コンテナ内で実行)。使い方: bash deploy/make_archive.sh [出力パス]
# サイト実体のみを含める:
#   - tests/ docs/ deploy/ .claude/ 等の開発用ファイルは含めない
#   - bk_html/ php/bk/ js/bk_js (旧手動バックアップ) は公開物に含めない
#   - db_config.local.php は含めない (サーバ内で注入・展開で消えない)
#   - img/product/ の商品写真は含めない (仕様9-1: 写真は使わず canvas で生成するため)
#
# **旧サイト (php/ css/ js/) は配信しない。** 2026-08-30 に除外した。理由:
#   - php/*.php は新サイト (index.php / beer/ / brewery/ / style/) と同じ内容を出す
#     **重複コンテンツ**で、php/test.php・php/tes2.php というテストの残骸も公開されていた
#   - js/jPages-master/ には jQuery ライブラリの**デモページ23本**(他人が書いた英語の
#     ドキュメント)が入っており、**他人の Google Analytics タグ (UA-28718218-1)** ごと
#     公開されていた。5.5MB あり、新サイトからは一切参照されていない
#   - css/ と js/ を参照しているのは php/ 配下だけ (post/ は post/ 内の css と
#     common/ の js を使う)。php/ を外すと同時に不要になる
#   広告審査 (AdSense) では重複・低価値・第三者コンテンツが不合格の典型なので外した。
#   git には残してあるので、必要になったら履歴から取り出せる。
set -euo pipefail
cd "$(dirname "$0")/.."
OUT="${1:-/tmp/claude-0/beer-deploy.tar.gz}"
mkdir -p "$(dirname "$OUT")"
git archive --format=tar.gz --prefix=html/ -o "$OUT" HEAD \
  index.php style.css chartjs-plugin-datalabels.min.js googlebb691fb861bc6308.html \
  robots.txt ads.txt sitemap.php privacy.php about.php \
  common post style brewery beer assets \
  $(git ls-files img | grep -v '^img/product/' | tr '\n' ' ')
echo "archive: $OUT ($(du -h "$OUT" | cut -f1))"
