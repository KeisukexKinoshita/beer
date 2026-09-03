<?php
/* <head> 出力。ページ側で $title / $desc を設定してから require する (§7: タイトル・meta動的化) */
$pageTitle = isset($title) && $title !== '' ? ($title . ' | Darth Beer.com') : 'Darth Beer.com';
$pageDesc  = $desc ?? '世界中のクラフトビールを、味わいの座標で並べた宇宙。あなたの好みの一杯を見つけよう。';
$extraCss  = $extraCss ?? [];
?><!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<meta name="keywords" content="craft beer,クラフトビール,ビール,IPA,ブリュワリー">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:type" content="website">
<?php
/* 正規URL。同じ内容に複数のURLで到達できるため、どれが本命かを明示する:
     - `/` と `/index.php` はどちらも同じトップページを返す
     - www.drtbeer.com は 301 で apex に寄せているが、念のため canonical でも示す
   クエリは ProductID / MakerID / StyleID だけを残す。並び替えや絞り込みの
   パラメータが付いても、同じページとして1本に寄せるため。 */
$canonHost  = $_SERVER['HTTP_HOST'] ?? '';
$canonPath  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
if ($canonPath === '/index.php') { $canonPath = '/'; }
$canonKeep  = array_intersect_key($_GET, array_flip(['ProductID', 'MakerID', 'StyleID']));
$canonQuery = $canonKeep ? '?' . http_build_query($canonKeep) : '';
if ($canonHost !== '') : ?>
<link rel="canonical" href="https://<?= e($canonHost . $canonPath . $canonQuery) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=M+PLUS+1:wght@400;500;700;800&family=Noto+Sans+JP:wght@300;400;500;700&display=swap">
<link rel="stylesheet" href="/assets/css/nebula.css">
<?php
/* Google AdSense。**本番ホストでのみ読み込む。**
   dev (dev.drtbeer.com) や内部名 (beer.local) で読み込むと、開発中の閲覧が
   表示回数として計上され、無効なトラフィックとみなされる恐れがある。
   プライバシーポリシー(/privacy.php)の「3. 広告について」と外部送信の表は、
   この設置と同時に現在形へ改めてある。 */
if (($_SERVER['HTTP_HOST'] ?? '') === 'drtbeer.com'): ?>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3292112504579579" crossorigin="anonymous"></script>
<?php endif; ?>
<?php if (!empty($useMap)): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php endif; ?>
<?php foreach ($extraCss as $c): ?><link rel="stylesheet" href="<?= e($c) ?>">
<?php endforeach; ?>
</head>
