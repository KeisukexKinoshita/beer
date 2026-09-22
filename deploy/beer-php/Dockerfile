# beer 専用のイメージ。seisan3-php を土台に GD だけを足す。
#
# GD が要るのは、アップロードされた写真を長辺1568pxに縮めるため
# (common/reco/identify.php の identify_shrink)。縮めないと入力トークンが
# 4倍ほどになり、1枚あたりの費用がそのぶん増える。
#
# **seisan3-php 自体は変更しない。** 共用しているので、あちらを壊さないため。
FROM seisan3-php

# -dev ヘッダだけを消して .so 本体(libjpeg62-turbo 等)は残す。
# **autoremove はしない。** ヘッダ群は元々このイメージに自動インストール済みの依存として
# 入っていたため、autoremove すると「もう要らない」と判定されて .so 本体まで一緒に
# 消えてしまい、GD がロードできなくなる(実機で検証して確認したバグ)。
RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg-dev libpng-dev libwebp-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd \
 && apt-get purge -y libjpeg-dev libpng-dev libwebp-dev \
 && rm -rf /var/lib/apt/lists/*

# 写真の受け取りの上限。**門番(common/reco/gate.php)の 10MB を超える値**にしておく。
# php.ini 側が先に弾くと、門番の「写真が大きすぎます(10MBまで)」ではなく
# 「写真を受け取れませんでした」という何も分からない文言になる
# (dev の実機で実際に踏んだ。既定は upload_max_filesize=2M で、
# いまのスマホの写真はたいてい2〜5MBあるため、ほぼ全部が弾かれていた)。
#
# post_max_size は upload_max_filesize より大きくすること。
# フォームの他の項目とヘッダのぶん、送られる本体は写真より少し大きい。
RUN { \
      echo 'upload_max_filesize = 12M'; \
      echo 'post_max_size = 13M'; \
    } > /usr/local/etc/php/conf.d/beer-uploads.ini
