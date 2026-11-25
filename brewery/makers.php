<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$makers = all_makers();
$total  = count($makers);
$title  = 'Brewery一覧';
$desc   = "掲載中の{$total}のクラフトブリュワリー。日本と海外の造り手を、宇宙の星団のように紹介します。";
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap pg-head">
  <h1 class="sec-h">蔵<span class="cnt">全<?= $total ?>件</span></h1>

  <?php
    $jp = array_filter($makers, fn($m) => ($m['country_code'] ?? 'JP') === 'JP');
    $ov = array_filter($makers, fn($m) => ($m['country_code'] ?? 'JP') !== 'JP');
    $render = function($list) {
      foreach ($list as $m):
        $mn = $m['MakerName'];
    ?>
      <a class="brcard" href="/brewery/detail/maker.php?MakerID=<?= e($m['MakerID']) ?>">
        <h4><?= flag($m['country_code']) ?> <?= e($mn) ?></h4>
        <p><?= e(mb_strimwidth(trim((string)$m['MakerExplain']), 0, 70, '…')) ?></p>
        <div class="loc"><?= e(country_name($m['country_code'])) ?> · 取扱 <?= (int)$m['beer_count'] ?>件</div>
      </a>
    <?php endforeach; };
  ?>

  <div class="subhead">日本</div>
  <div class="grid-br"><?php $render($jp); ?></div>

  <?php if ($ov): ?>
  <div class="subhead" style="margin-top:34px">海外</div>
  <div class="grid-br"><?php $render($ov); ?></div>
  <?php endif; ?>
</div>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
