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
