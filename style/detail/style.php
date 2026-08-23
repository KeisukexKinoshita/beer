<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$sid   = $_GET['StyleID'] ?? '';
$style = style_by_id($sid);

if (!$style) {
    $title = 'お探しのスタイルが見つかりません';
    require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
    echo '<body>';
    require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php';
    echo '<div class="wrap" style="padding:80px 0"><div class="eyebrow">404</div>'
       . '<div class="sec-h">スタイルが見つかりませんでした</div>'
       . '<p style="color:var(--muted);margin-top:14px"><a href="/beer/products.php" style="color:var(--teal)">Beer一覧へ</a></p></div>';
    require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php';
    exit;
}

$beers    = beers_by_style($sid);
$allBeers = all_beers();
$hlIds    = array_column($beers, 'ProductID');
$g        = style_group($style['FamilyName'] ?? '', $style['StyleName'] ?? '');
list($gcol, $glabel) = group_meta($g);
$sn       = $style['StyleName'];
$title    = $sn;
$desc     = trim((string)$style['StyleExplain']) ?: ($sn . ' — ' . $glabel . 'のクラフトビール' . count($beers) . '件');
$pageJs   = ['/assets/js/galaxy.js'];
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap" style="padding-top:36px">
  <a class="dt-back" href="/beer/products.php" style="margin-bottom:16px">← Beer一覧へ</a>
  <div class="eyebrow">Type · <?= e($style['FamilyName'] ?: $glabel) ?></div>
  <h1 class="dt-title" style="margin:8px 0"><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:<?= $gcol ?>;box-shadow:0 0 12px <?= $gcol ?>;vertical-align:middle;margin-right:10px"></span><?= e($sn) ?></h1>
  <?php if (!empty($style['catchcopy'])): ?>
    <p class="grad" style="font-family:var(--f-disp);font-weight:700;font-size:18px;margin-top:6px"><?= e($style['catchcopy']) ?></p>
  <?php endif; ?>
  <?php if (trim((string)$style['StyleExplain']) !== ''): ?>
    <p style="color:var(--muted);font-size:15px;line-height:1.9;max-width:60ch;margin-top:12px"><?= nl2br(e(trim((string)$style['StyleExplain']))) ?></p>
  <?php else: ?>
    <p style="color:var(--faint);font-size:13.5px;margin-top:12px">※ このスタイルの説明文は今後追加予定です（style.catchcopy / StyleExplain）。</p>
  <?php endif; ?>

  <div class="panel" style="margin-top:24px">
    <h3>味覚マップ上の位置</h3>
    <div class="sub">全<?= count($allBeers) ?>銘柄の中で、<?= e($sn) ?>（<?= count($beers) ?>件）がどの味域に集まるか（原点=平均）</div>
    <div class="galaxy-shell galaxy-wide" style="margin-top:8px">
      <canvas id="gx"></canvas>
      <div class="galaxy-tag">Flavour Galaxy · 味覚銀河</div>
      <div class="galaxy-hint">ドラッグで回転 · 星クリックで詳細</div>
      <div class="legend" id="lg"></div>
      <div class="tt" id="tt"></div>
    </div>
  </div>

  <section class="blk" style="border-top:1px solid var(--line);margin-top:30px">
    <div class="eyebrow">Beers</div>
    <div class="sec-h" style="margin-bottom:6px"><?= e($sn) ?> のビール <span style="color:var(--muted);font-weight:400;font-size:.6em">全<?= count($beers) ?>件</span></div>
    <div class="grid-b" style="margin-top:24px;margin-bottom:30px">
      <?php foreach ($beers as $b): ?>
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
      <?php endforeach; ?>
    </div>
  </section>
</div>

<script>window.BEERS = <?= json_encode(beers_for_js($allBeers), JSON_UNESCAPED_UNICODE) ?>;</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  initGalaxy({canvas:'#gx', tooltip:'#tt', legend:'#lg', data:window.BEERS,
    highlightIds:<?= json_encode($hlIds) ?>,
    onPick:function(b){location.href='/beer/detail/product.php?ProductID='+encodeURIComponent(b.id);}});
});
</script>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
