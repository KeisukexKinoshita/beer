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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=M+PLUS+1:wght@400;500;700;800&family=Noto+Sans+JP:wght@300;400;500;700&display=swap">
<link rel="stylesheet" href="/assets/css/nebula.css">
<?php if (!empty($useMap)): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJ2QWc3iyU8bHnJ0lHl6mCNv5Il2A=" crossorigin=""></script>
<?php endif; ?>
<?php foreach ($extraCss as $c): ?><link rel="stylesheet" href="<?= e($c) ?>">
<?php endforeach; ?>
</head>
