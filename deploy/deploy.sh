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

# 退役したディレクトリの掃除。
# tar は上書きするだけなので、**配信物から外したファイルはサーバに残り続ける**。
# 実際、旧サイト (php/) と jQuery のデモページ (js/jPages-master/) が
# 配信対象から外れたあとも 200 を返し続けていた。
# ここに書いた分だけを消す (ワイルドカードで広く消さない)。
for retired in php css js; do
  if [ -d "$DEST/$retired" ]; then
    echo "  退役ディレクトリを削除: $retired"
    sudo rm -rf "${DEST:?}/${retired:?}"
  fi
done
# アップロード先ディレクトリ (img/tmp, img/product) を apache(UID 33) 書込可能にする
sudo mkdir -p "$DEST/img/tmp" "$DEST/img/product"
sudo chown -R 33:33 "$DEST/img/tmp" "$DEST/img/product"
echo "deployed to $DEST ($(sudo find "$DEST" -type f | wc -l) files)"
