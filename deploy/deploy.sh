#!/bin/bash
# サーバ側デプロイスクリプト: /srv/beer/deploy/deploy.sh <prod|dev> <tarball>
# make_archive.sh が作る tar (html/ ルート) を対象環境に展開する。
# db_config.local.php はtarに含まれないため展開で消えない。
set -euo pipefail
TARGET="${1:?usage: deploy.sh <prod|dev> <tarball>}"
TAR="${2:?usage: deploy.sh <prod|dev> <tarball>}"
[[ "$TARGET" == "prod" || "$TARGET" == "dev" ]] || { echo "target must be prod|dev"; exit 1; }
DEST="/srv/beer/$TARGET/html"
sudo mkdir -p "$DEST"
sudo tar xf "$TAR" --strip-components=1 -C "$DEST"
# アップロード先ディレクトリ (img/tmp, img/product) を apache(UID 33) 書込可能にする
sudo mkdir -p "$DEST/img/tmp" "$DEST/img/product"
sudo chown -R 33:33 "$DEST/img/tmp" "$DEST/img/product"
echo "deployed to $DEST ($(sudo find "$DEST" -type f | wc -l) files)"
