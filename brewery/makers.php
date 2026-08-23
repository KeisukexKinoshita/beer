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

<div class="wrap" style="padding-top:40px;padding-bottom:70px">
  <div class="eyebrow">All Breweries</div>
  <div class="sec-h">Brewery一覧 <span style="color:var(--muted);font-weight:400;font-size:.6em">全<?= $total ?>件</span></div>

  <?php
    $jp = array_filter($makers, fn($m) => ($m['country_code'] ?? 'JP') === 'JP');
    $ov = array_filter($makers, fn($m) => ($m['country_code'] ?? 'JP') !== 'JP');
    $render = function($list) {
      foreach ($list as $m):
        $mn = $m['MakerName']; $init = mb_substr(preg_replace('/\s.*$/', '', $mn), 0, 1);
    ?>
      <a class="brcard" href="/brewery/detail/maker.php?MakerID=<?= e($m['MakerID']) ?>">
        <div class="logo"><?= e($init) ?></div>
        <h4><?= flag($m['country_code']) ?> <?= e($mn) ?></h4>
        <p><?= e(mb_strimwidth(trim((string)$m['MakerExplain']), 0, 70, '…')) ?></p>
        <div class="loc"><?= e(country_name($m['country_code'])) ?> · 取扱 <?= (int)$m['beer_count'] ?>件</div>
      </a>
    <?php endforeach; };
  ?>

  <div class="subhead"><span class="fl">🇯🇵</span>日本</div>
  <div class="grid-br"><?php $render($jp); ?></div>

  <?php if ($ov): ?>
  <div class="subhead" style="margin-top:40px"><span class="fl">🌍</span>海外</div>
  <div class="grid-br"><?php $render($ov); ?></div>
  <?php endif; ?>
</div>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
