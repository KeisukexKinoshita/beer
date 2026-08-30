<?php
/**
 * サイトマップ。**ドメインに依存しないよう動的に組み立てる。**
 * 公開ドメインが未確定なので、静的な sitemap.xml に絶対URLを焼き込むと
 * ドメイン確定時に必ず書き直しになる。ここではリクエストのホスト名から作る。
 */
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
       || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base   = $scheme . '://' . $host;

$urls = [
    ['/index.php',           '1.0', 'daily'],
    ['/beer/products.php',   '0.9', 'daily'],
    ['/brewery/makers.php',  '0.8', 'weekly'],
    ['/style/styles.php',    '0.8', 'weekly'],
    ['/about.php',           '0.3', 'yearly'],
    ['/privacy.php',         '0.3', 'yearly'],
];
foreach (all_beers() as $b) {
    $urls[] = ['/beer/detail/product.php?ProductID=' . rawurlencode($b['ProductID']), '0.7', 'monthly'];
}
foreach (all_makers() as $m) {
    $urls[] = ['/brewery/detail/maker.php?MakerID=' . rawurlencode($m['MakerID']), '0.6', 'monthly'];
}
foreach (all_styles() as $s) {
    // 解説が無いスタイルは載せない (中身の薄いページを検索に出さない)
    if (trim((string)($s['StyleExplain'] ?? '')) === '') continue;
    $urls[] = ['/style/detail/style.php?StyleID=' . rawurlencode($s['StyleID']), '0.6', 'monthly'];
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc, $pri, $freq]) {
    echo "  <url>\n";
    echo "    <loc>" . e($base . $loc) . "</loc>\n";
    echo "    <changefreq>{$freq}</changefreq>\n";
    echo "    <priority>{$pri}</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
