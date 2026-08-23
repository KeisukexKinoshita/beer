<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$beers   = all_beers();               // 新着順(ProductID降順)
$makers  = all_makers();
$title   = '';                        // トップは "Darth Beer.com" のみ
$desc    = '世界中のクラフトビールを、味わいの座標で並べた宇宙。度数・IBU・フルーティーさの銀河から、あなたの好みの一杯を見つけよう。';
$pageJs  = ['/assets/js/galaxy.js'];

$beersJP = array_values(array_filter($beers, fn($b) => ($b['country_code'] ?? 'JP') === 'JP'));
$beersOV = array_values(array_filter($beers, fn($b) => ($b['country_code'] ?? 'JP') !== 'JP'));
usort($makers, fn($a, $b) => (int)$b['beer_count'] <=> (int)$a['beer_count']);
$makJP = array_values(array_filter($makers, fn($m) => ($m['country_code'] ?? 'JP') === 'JP'));
$makOV = array_values(array_filter($makers, fn($m) => ($m['country_code'] ?? 'JP') !== 'JP'));

function beer_card($b) {
  $g = style_group($b['FamilyName'] ?? '', $b['StyleName'] ?? '');
  list($gcol, $glabel) = group_meta($g);
  ob_start(); ?>
  <a class="bcard" href="/beer/detail/product.php?ProductID=<?= e($b['ProductID']) ?>">
    <div class="thumb">
      <div class="halo" style="position:absolute;width:60%;aspect-ratio:1;border-radius:50%;background:radial-gradient(circle,<?= $gcol ?>55,transparent 66%);filter:blur(8px)"></div>
      <img src="/img/product/<?= e($b['ProductID']) ?>.png" alt="<?= e($b['ProductName']) ?> のボトル画像" loading="lazy" onerror="this.style.opacity=0">
    </div>
    <div class="body">
      <div class="stylechip"><?= e($glabel) ?></div>
      <h4><?= e($b['ProductName']) ?></h4>
      <div class="mk"><?= flag($b['country_code']) ?> <?= e($b['MakerName']) ?></div>
      <div class="meta"><span><?= fmt_num($b['Alcohol']) ?>% · IBU<?= fmt_num($b['IBU_all']) ?></span><?= stars_html($b['Favorite']) ?></div>
    </div>
  </a>
  <?php return ob_get_clean();
}
function brewery_card($m) {
  $init = mb_substr(preg_replace('/\s.*$/', '', $m['MakerName']), 0, 1);
  ob_start(); ?>
  <a class="brcard" href="/brewery/detail/maker.php?MakerID=<?= e($m['MakerID']) ?>">
    <div class="logo"><?= e($init) ?></div>
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

<div class="wrap hero">
  <div class="hero-grid">
    <div>
      <div class="eyebrow">Darth Beer.com</div>
      <h1>宇宙は、<span class="grad">味わいでできている</span>。</h1>
      <p class="lead">度数もIBUも、フルーティーさも。すべての感覚を色と光にして、<?= count($beers) ?>の銘柄が星雲のように渦を巻く。惹かれた星をタップすれば、そこに新しい一杯が待っている。</p>
      <div class="cta">
        <a class="btn pri" href="/beer/products.php">銀河を旅する →</a>
        <a class="btn ghost" href="#latest">最新のBeer</a>
      </div>
      <div class="stat-row">
        <div class="stat"><b class="grad"><?= count($beers) ?></b><span>登録ビール</span></div>
        <div class="stat"><b class="grad"><?= count($makers) ?></b><span>ブリュワリー</span></div>
      </div>
    </div>
    <div class="galaxy-shell">
      <canvas id="gx"></canvas>
      <div class="galaxy-tag">Flavour Galaxy · 味覚銀河</div>
      <div class="galaxy-hint">ドラッグで回転 · クリックで詳細</div>
      <div class="legend" id="lg"></div>
      <div class="tt" id="tt"></div>
    </div>
  </div>
</div>

<section class="blk"><div class="wrap">
  <div class="sec-top">
    <div><div class="eyebrow">Latest Beer</div><div class="sec-h" id="latest">最新のBeer</div></div>
    <a class="more" href="/beer/products.php">すべてのBeerを見る →</a>
  </div>
  <div class="subhead"><span class="fl">🇯🇵</span>日本</div>
  <div class="grid-b"><?php foreach (array_slice($beersJP, 0, 4) as $b) echo beer_card($b); ?></div>
  <?php if ($beersOV): ?>
  <div class="subhead"><span class="fl">🌍</span>海外</div>
  <div class="grid-b"><?php foreach (array_slice($beersOV, 0, 4) as $b) echo beer_card($b); ?></div>
  <?php endif; ?>
</div></section>

<section class="blk"><div class="wrap">
  <div class="sec-top">
    <div><div class="eyebrow">Popular Brewery</div><div class="sec-h">人気のBrewery</div></div>
    <a class="more" href="/brewery/makers.php">すべてのBreweryを見る →</a>
  </div>
  <div class="subhead"><span class="fl">🇯🇵</span>日本</div>
  <div class="grid-br"><?php foreach (array_slice($makJP, 0, 4) as $m) echo brewery_card($m); ?></div>
  <?php if ($makOV): ?>
  <div class="subhead"><span class="fl">🌍</span>海外</div>
  <div class="grid-br"><?php foreach (array_slice($makOV, 0, 4) as $m) echo brewery_card($m); ?></div>
  <?php endif; ?>
</div></section>

<script>window.BEERS = <?= json_encode(beers_for_js($beers), JSON_UNESCAPED_UNICODE) ?>;</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  initGalaxy({canvas:'#gx', tooltip:'#tt', legend:'#lg', data:window.BEERS,
    onPick:function(b){location.href='/beer/detail/product.php?ProductID='+encodeURIComponent(b.id);}});
});
</script>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
