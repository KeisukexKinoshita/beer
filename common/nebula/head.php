<?php
/* <head> 出力。ページ側で $title / $desc を設定してから require する (§7: タイトル・meta動的化) */
$pageTitle = isset($title) && $title !== '' ? ($title . ' | Darth Beer.com') : 'Darth Beer.com';
$pageDesc  = $desc ?? 'クラフトビールを、度数・苦味・フルーティーさで比べて探せます。';
$extraCss  = $extraCss ?? [];
?><!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap">
<?php /* 見た目は露光案(try.php と同じ exposure.css)が土台。一覧・詳細・文書ページ用の
         追加分は site.css。内容が変わったら必ず読み直させる(古い CSS が残る事故の対策) */
$cssBase = '/assets/css/exposure.css';
$cssSite = '/assets/css/site.css';
$cssBaseVer = @filemtime($_SERVER['DOCUMENT_ROOT'] . $cssBase) ?: time();
$cssSiteVer = @filemtime($_SERVER['DOCUMENT_ROOT'] . $cssSite) ?: time();
?>
<link rel="stylesheet" href="<?= e($cssBase) ?>?v=<?= (int)$cssBaseVer ?>">
<link rel="stylesheet" href="<?= e($cssSite) ?>?v=<?= (int)$cssSiteVer ?>">
<?php /* スタイル色の出所は group_map() 一つ。CSS に直値を書かず、ここで流し込む(try.php と同じ) */ ?>
<style>:root{
<?php foreach (group_map() as $k => $v): ?>
  --g-<?= e($k) ?>:<?= e($v[0]) ?>; --h-<?= e($k) ?>:<?= e($v[1]) ?>;
<?php endforeach; ?>
}</style>
<?php if (!empty($useMap)): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php endif; ?>
<?php foreach ($extraCss as $c): ?><link rel="stylesheet" href="<?= e($c) ?>">
<?php endforeach; ?>
<?php require __DIR__ . '/analytics.php'; ?>
</head>
