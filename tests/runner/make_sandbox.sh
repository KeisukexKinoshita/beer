#!/bin/bash
# 特性テスト用sandboxを原本から再構築する (原本は一切変更しない)
# 使い方: bash tests/runner/make_sandbox.sh   (リポジトリルートで実行)
set -eu
cd "$(dirname "$0")/../.."

rm -rf tests/sandbox
mkdir -p tests/sandbox
for d in common css js php post style brewery beer bk_html assets; do
  cp -a "$d" tests/sandbox/
done
cp -a index.php about.php privacy.php terms.php agecheck.php try.php history.php sitemap.php robots.txt style.css chartjs-plugin-datalabels.min.js tests/sandbox/

# DB接続設定。**実データの入った db_config.local.php をそのままコピーしない。**
# 以前はそのままコピーしていたため、特性テストが実DB(beer)に直接フィクスチャを
# 流し込み、tests/unit/ が前提とする実データ(products 200 / maker 40 / style 102)を
# 壊していた(tests/cases.md §13.6)。host/user/passはそのままに、DB名だけ
# 特性テスト専用スキーマ(beer_characterization。tests/runner/start_db.sh が作る)に
# 差し替えたものを生成する。
if [ -f db_config.local.php ]; then
  php -r '
    require $argv[1];
    $db_name = "beer_characterization";
    $db_dsn  = "mysql:dbname={$db_name};host={$db_host};charset=utf8mb4";
    $out  = "<?php\n";
    $out .= "// tests/runner/make_sandbox.sh が生成(手で編集しない)。\n";
    $out .= "// 実データの db_config.local.php とは別スキーマを指す(tests/cases.md §13.6)。\n";
    $out .= "\$db_host = " . var_export($db_host, true) . ";\n";
    $out .= "\$db_name = " . var_export($db_name, true) . ";\n";
    $out .= "\$db_user = " . var_export($db_user, true) . ";\n";
    $out .= "\$db_pass = " . var_export($db_pass, true) . ";\n";
    $out .= "\$db_dsn  = " . var_export($db_dsn, true) . ";\n";
    file_put_contents($argv[2], $out);
  ' db_config.local.php tests/sandbox/db_config.local.php
fi

# img/ は118MBあるためコピーしない。sql_POST の rename() に必要な骨格だけ作る
mkdir -p tests/sandbox/img/tmp tests/sandbox/img/product

# 合成フィクスチャのメーカー (mk90xx) 用ダミーexplainファイル
# (index.php が brewery/detail/explain/<MakerID>.html を require するため)
for id in mk9000 mk9001 mk9002 mk9003; do
  printf '<p>fixture explain %s</p>\n' "$id" > "tests/sandbox/brewery/detail/explain/$id.html"
done

echo "sandbox rebuilt: $(du -sh tests/sandbox | cut -f1)"
