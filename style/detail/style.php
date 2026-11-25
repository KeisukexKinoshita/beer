<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$sid   = $_GET['StyleID'] ?? '';
$style = style_by_id($sid);

if (!$style) {
    // **HTTPステータスも 404 にする。** 画面に「見つかりません」と出しながら
    // 200 を返すと、検索エンジンには「中身のあるページ」に見える(ソフト404)。
    // IDはクエリで自由に作れるため、放置すると無限の空ページを抱えることになる。
    http_response_code(404);
    $title = 'お探しのスタイルが見つかりません';
    require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
    echo '<body>';
    require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php';
    echo '<div class="wrap nf"><div class="ex-lbl">404</div>'
       . '<h1 class="sec-h">スタイルが見つかりませんでした</h1>'
       . '<p><a class="lk" href="/style/styles.php">スタイルの一覧へ</a></p></div>';
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
$desc     = trim((string)$style['StyleExplain']) ?: ($sn . '(' . $glabel . ')。' . (count($beers) > 0 ? 'このスタイルのクラフトビール' . count($beers) . '銘柄を載せています。' : 'スタイルの解説です。'));
$pageJs   = ['/assets/js/galaxy.js', '/assets/js/beerglass.js'];
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap pg-head">
  <?php if (($style['FamilyName'] ?? '') !== ''): ?><div class="ex-lbl"><?= e($style['FamilyName']) ?></div><?php endif; ?>
  <h1 class="dt-title"><i class="ex-dot" style="background:<?= $gcol ?>"></i><?= e($sn) ?></h1>
  <?php if (!empty($style['catchcopy'])): ?>
    <p class="dt-catch"><?= e($style['catchcopy']) ?></p>
  <?php endif; ?>
  <?php if (trim((string)$style['StyleExplain']) !== ''): ?>
    <p class="dt-desc"><?= nl2br(e(trim((string)$style['StyleExplain']))) ?></p>
  <?php endif; ?>

  <?php if ($beers): ?>
  <div class="panel" style="margin-top:24px">
    <h3>味覚マップ上の位置</h3>
    <div class="sub">明るい点がこのスタイルの<?= count($beers) ?>銘柄(原点=平均)</div>
    <div class="galaxy-shell galaxy-wide">
      <canvas id="gx"></canvas>
      <div class="galaxy-tag">味覚銀河</div>
      <div class="galaxy-hint">ドラッグで回転 · 星クリックで詳細</div>
      <div class="legend" id="lg"></div>
      <div class="tt" id="tt"></div>
    </div>
  </div>

  <section class="blk">
    <div class="sec-top">
      <h2 class="sec-h">銘柄<span class="cnt">全<?= count($beers) ?>件</span></h2>
    </div>
    <div class="grid-b">
      <?php foreach ($beers as $b): ?>
      <a class="bcard" href="/beer/detail/product.php?ProductID=<?= e($b['ProductID']) ?>">
        <div class="thumb"><?= beer_glass_tag($b) ?></div>
        <div class="body">
          <div class="stylechip"><i class="ex-dot" style="background:<?= $gcol ?>"></i><?= e($glabel) ?></div>
          <h4><?= e($b['ProductName']) ?></h4>
          <div class="mk"><?= flag($b['country_code']) ?> <?= e($b['MakerName']) ?></div>
          <div class="meta"><span><?= fmt_unit($b['Alcohol'],'%') ?> · IBU<?= fmt_unit($b['IBU_all']) ?><?= est_mark($b,'IBU_all') ?></span><?= stars_html($b['Favorite']) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
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
