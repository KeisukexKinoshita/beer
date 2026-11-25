<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$beers   = all_beers();               // 新着順(ProductID降順)
$makers  = all_makers();
$title   = '';                        // トップは "Darth Beer.com" のみ
$desc    = '世界中のクラフトビールを、味わいの座標で並べた宇宙。度数・IBU・フルーティーさの銀河から、あなたの好みの一杯を見つけよう。';
$pageJs  = ['/assets/js/galaxy.js', '/assets/js/beerglass.js'];

$beersJP = array_values(array_filter($beers, fn($b) => ($b['country_code'] ?? 'JP') === 'JP'));
$beersOV = array_values(array_filter($beers, fn($b) => ($b['country_code'] ?? 'JP') !== 'JP'));
usort($makers, fn($a, $b) => (int)$b['beer_count'] <=> (int)$a['beer_count']);
$makJP = array_values(array_filter($makers, fn($m) => ($m['country_code'] ?? 'JP') === 'JP'));
$makOV = array_values(array_filter($makers, fn($m) => ($m['country_code'] ?? 'JP') !== 'JP'));
$types = styles_used();

function beer_card($b) {
  $g = style_group($b['FamilyName'] ?? '', $b['StyleName'] ?? '');
  list($gcol, $glabel) = group_meta($g);
  ob_start(); ?>
  <a class="bcard" href="/beer/detail/product.php?ProductID=<?= e($b['ProductID']) ?>">
    <div class="thumb"><?= beer_glass_tag($b) ?></div>
    <div class="body">
      <div class="stylechip"><i class="ex-dot" style="background:<?= $gcol ?>"></i><?= e($glabel) ?></div>
      <h4><?= e($b['ProductName']) ?></h4>
      <div class="mk"><?= flag($b['country_code']) ?> <?= e($b['MakerName']) ?></div>
      <div class="meta"><span><?= fmt_unit($b['Alcohol'],'%') ?> · IBU<?= fmt_unit($b['IBU_all']) ?><?= est_mark($b,'IBU_all') ?></span><?= stars_html($b['Favorite']) ?></div>
    </div>
  </a>
  <?php return ob_get_clean();
}
function brewery_card($m) {
  ob_start(); ?>
  <a class="brcard" href="/brewery/detail/maker.php?MakerID=<?= e($m['MakerID']) ?>">
    <h4><?= flag($m['country_code']) ?> <?= e($m['MakerName']) ?></h4>
    <p><?= e(mb_strimwidth(trim((string)$m['MakerExplain']), 0, 66, '…')) ?></p>
    <div class="loc"><?= e(country_name($m['country_code'])) ?> · 取扱 <?= (int)$m['beer_count'] ?>件</div>
  </a>
  <?php return ob_get_clean();
}
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap">
  <h1 class="sr">Darth Beer.com</h1>
  <div class="galaxy-shell galaxy-wide" style="margin-top:14px">
    <canvas id="gx"></canvas>
    <div class="galaxy-tag">味覚銀河</div>
    <div class="galaxy-hint">ドラッグで回転 · クリックで詳細</div>
    <div class="legend" id="lg"></div>
    <div class="tt" id="tt"></div>
  </div>
  <div class="statline"><b><?= count($beers) ?></b> 銘柄 · <b><?= count($makers) ?></b> 蔵</div>

  <section class="blk">
    <div class="sec-top" id="latest">
      <h2 class="sec-h">新着</h2>
      <a class="more" href="/beer/products.php">すべて →</a>
    </div>
    <div class="subhead">日本</div>
    <div class="grid-b"><?php foreach (array_slice($beersJP, 0, 4) as $b) echo beer_card($b); ?></div>
    <?php if ($beersOV): ?>
    <div class="subhead">海外</div>
    <div class="grid-b"><?php foreach (array_slice($beersOV, 0, 4) as $b) echo beer_card($b); ?></div>
    <?php endif; ?>
  </section>

  <section class="blk">
    <div class="sec-top">
      <h2 class="sec-h">人気の蔵</h2>
      <a class="more" href="/brewery/makers.php">すべて →</a>
    </div>
    <div class="subhead">日本</div>
    <div class="grid-br"><?php foreach (array_slice($makJP, 0, 4) as $m) echo brewery_card($m); ?></div>
    <?php if ($makOV): ?>
    <div class="subhead">海外</div>
    <div class="grid-br"><?php foreach (array_slice($makOV, 0, 4) as $m) echo brewery_card($m); ?></div>
    <?php endif; ?>
  </section>

  <section class="blk">
    <div class="sec-top">
      <h2 class="sec-h">人気のスタイル</h2>
      <a class="more" href="/style/styles.php">すべて →</a>
    </div>
    <div class="types">
      <?php foreach (array_slice($types, 0, 6) as $t):
        $g = style_group($t['FamilyName'] ?? '', $t['StyleName'] ?? '');
        list($gcol, $glabel) = group_meta($g);
      ?>
      <a class="trow" href="/style/detail/style.php?StyleID=<?= e($t['StyleID']) ?>">
        <i class="ex-dot" style="background:<?= $gcol ?>"></i>
        <div class="tn"><?= e($t['StyleName']) ?></div>
        <div class="cap"><?= e($t['catchcopy'] ?: ($t['FamilyName'] ?: '')) ?></div>
        <div class="ct"><?= e($glabel) ?> · <?= (int)$t['beer_count'] ?>件</div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<script>window.BEERS = <?= json_encode(beers_for_js($beers), JSON_UNESCAPED_UNICODE) ?>;</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  initGalaxy({canvas:'#gx', tooltip:'#tt', legend:'#lg', data:window.BEERS,
    onPick:function(b){location.href='/beer/detail/product.php?ProductID='+encodeURIComponent(b.id);}});
});
</script>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
