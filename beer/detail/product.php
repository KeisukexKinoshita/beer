<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$pid  = $_GET['ProductID'] ?? '';
$beer = beer_by_id($pid);

if (!$beer) {
    $title = 'お探しのビールが見つかりません';
    require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
    echo '<body>';
    require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php';
    echo '<div class="wrap" style="padding:80px 0"><div class="eyebrow">404</div>'
       . '<div class="sec-h">ビールが見つかりませんでした</div>'
       . '<p style="color:var(--muted);margin-top:14px">指定された銘柄は存在しないようです。'
       . '<a href="/beer/products.php" style="color:var(--teal)">Beer一覧へ戻る</a></p></div>';
    require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php';
    exit;
}

$g = style_group($beer['FamilyName'] ?? '', $beer['StyleName'] ?? '');
list($gcol, $glabel) = group_meta($g);
$allBeers = all_beers();   // グラフの座標系(平均・スケール)は全体で決める

$title = $beer['ProductName'];
$descText = trim((string)$beer['ProductExplain']);
$desc = $descText !== '' ? mb_substr($descText, 0, 110) : ($beer['ProductName'] . ' — ' . ($beer['StyleName'] ?: 'クラフトビール'));
$pageJs = ['/assets/js/radar.js', '/assets/js/galaxy.js', '/assets/js/beermap.js', '/assets/js/beerglass.js'];
$useMap = !empty($beer['latitude']) && !empty($beer['longitude']);

// レーダー用の正規化値 [IBU, アルコール, フルーティー, 色, 評価]
$rv = [
  min(1, (float)$beer['IBU_all'] / 74.6),
  min(1, (float)$beer['Alcohol'] / 11),
  min(1, (float)$beer['Fruity'] / 4),
  min(1, (float)$beer['Color'] / 10),
  min(1, (float)$beer['Favorite'] / 5),
];
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap">
  <div class="detail-hero">
    <div class="dt-img"><?= beer_glass_tag($beer, 'dt-glass') ?></div>
    <div>
      <a class="dt-back" href="/beer/products.php">← Beer一覧へ</a>
      <div class="eyebrow"><?= e($beer['StyleName'] ?: 'Craft Beer') ?> · <?= e($beer['MakerName']) ?></div>
      <h1 class="dt-title"><?= e($beer['ProductName']) ?></h1>
      <?php if ($descText !== ''): ?>
        <p class="dt-desc"><?= nl2br(e($descText)) ?></p>
      <?php endif; ?>
      <div class="spec">
        <div class="r">
          <div class="k">ブリュワリー</div>
          <div class="v"><span class="flag"><?= flag($beer['country_code']) ?></span>
            <a href="/brewery/detail/maker.php?MakerID=<?= e($beer['MakerID']) ?>" style="color:var(--teal)"><?= e($beer['MakerName']) ?></a></div>
        </div>
        <div class="r"><div class="k">スタイル</div><div class="v">
          <?php if ($beer['StyleName']): ?>
            <a href="/style/detail/style.php?StyleID=<?= e($beer['StyleID']) ?>" style="color:var(--teal)"><?= e($beer['StyleName']) ?></a>
          <?php else: ?>—<?php endif; ?>
        </div></div>
        <div class="r"><div class="k">アルコール度</div>
          <div class="v"><?= fmt_num($beer['Alcohol']) ?>%<span class="bar"><i style="width:<?= min(100, (float)$beer['Alcohol']/11*100) ?>%"></i></span></div></div>
        <div class="r"><div class="k">IBU（苦味）</div>
          <div class="v"><?= fmt_num($beer['IBU_all']) ?><span class="bar"><i style="width:<?= min(100, (float)$beer['IBU_all']/74.6*100) ?>%"></i></span></div></div>
        <div class="r"><div class="k">フルーティーさ</div>
          <div class="v"><?= fmt_num($beer['Fruity']) ?> / 4<span class="bar"><i style="width:<?= min(100, (float)$beer['Fruity']/4*100) ?>%"></i></span></div></div>
        <div class="r"><div class="k">評価</div>
          <div class="v"><?= stars_html($beer['Favorite']) ?> <?= number_format((float)$beer['Favorite'],1) ?></div></div>
      </div>
    </div>
  </div>

  <div class="panel" style="margin-top:12px">
    <h3>味覚マップ上の位置</h3>
    <div class="sub">全<?= count($allBeers) ?>銘柄の中で、この一杯がどこに位置するか（原点=平均。★が本銘柄）</div>
    <div class="galaxy-shell galaxy-wide" style="margin-top:8px">
      <canvas id="gx"></canvas>
      <div class="galaxy-tag">Flavour Galaxy · 味覚銀河</div>
      <div class="galaxy-hint">ドラッグで回転</div>
      <div class="legend" id="lg"></div>
      <div class="tt" id="tt"></div>
    </div>
  </div>

  <div class="dt-lower">
    <div class="panel">
      <h3>味プロファイル</h3>
      <div class="sub">この銘柄の5指標バランス（レーダー）</div>
      <canvas class="radar" id="radar"></canvas>
    </div>
    <div class="panel">
      <h3>製造地</h3>
      <div class="sub">ブリュワリー所在地 — <?= e(country_name($beer['country_code'])) ?></div>
      <div class="map">
        <?php if ($useMap): ?>
          <div class="lmap" data-lat="<?= e($beer['latitude']) ?>" data-lng="<?= e($beer['longitude']) ?>" data-label="<?= e($beer['MakerName']) ?>"></div>
        <?php else: ?>
          <div class="grid"></div>
          <div class="pin" style="left:60%;top:46%"></div>
          <div class="lbl" style="left:60%;top:46%"><?= e($beer['MakerName']) ?></div>
          <div class="note">※ 所在地データ未設定</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>window.BEERS = <?= json_encode(beers_for_js($allBeers), JSON_UNESCAPED_UNICODE) ?>;</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  drawRadar('#radar', <?= json_encode($rv) ?>);
  initGalaxy({canvas:'#gx', tooltip:'#tt', legend:'#lg', data:window.BEERS,
    highlightIds:[<?= json_encode($beer['ProductID']) ?>],
    onPick:function(b){location.href='/beer/detail/product.php?ProductID='+encodeURIComponent(b.id);}});
});
addEventListener('resize', function(){ drawRadar('#radar', <?= json_encode($rv) ?>); });
</script>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
