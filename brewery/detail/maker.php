<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$mid   = $_GET['MakerID'] ?? '';
$maker = maker_by_id($mid);

if (!$maker) {
    // **HTTPステータスも 404 にする。** 画面に「見つかりません」と出しながら
    // 200 を返すと、検索エンジンには「中身のあるページ」に見える(ソフト404)。
    // IDはクエリで自由に作れるため、放置すると無限の空ページを抱えることになる。
    http_response_code(404);
    $title = 'お探しのブリュワリーが見つかりません';
    require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
    echo '<body>';
    require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php';
    echo '<div class="wrap" style="padding:80px 0"><div class="eyebrow">404</div>'
       . '<div class="sec-h">ブリュワリーが見つかりませんでした</div>'
       . '<p style="color:var(--muted);margin-top:14px"><a href="/brewery/makers.php" style="color:var(--teal)">Brewery一覧へ戻る</a></p></div>';
    require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php';
    exit;
}

$beers    = beers_by_maker($mid);
$allBeers = all_beers();
$hlIds    = array_column($beers, 'ProductID');
$mn       = $maker['MakerName'];
$init     = mb_substr(preg_replace('/\s.*$/', '', $mn), 0, 1);
$title    = $mn;
$desc     = mb_strimwidth(trim((string)$maker['MakerExplain']) ?: ($mn . ' のクラフトビール'), 0, 110, '…');
$pageJs   = ['/assets/js/galaxy.js', '/assets/js/beermap.js', '/assets/js/beerglass.js'];
$useMap   = !empty($maker['latitude']) && !empty($maker['longitude']);
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap" style="padding-top:36px">
  <a class="dt-back" href="/brewery/makers.php" style="margin-bottom:18px">← Brewery一覧へ</a>

  <!-- PC: ロゴ | 概要 | 製造地(ロゴと同じ高さ) -->
  <div class="brew-top">
    <div class="brew-logo">
      <?php if (!empty($maker['logo_path'])): ?>
        <img src="<?= e($maker['logo_path']) ?>" alt="<?= e($mn) ?> のロゴ">
      <?php else: ?>
        <span class="init"><?= e($init) ?></span>
        <span class="ph-note">ロゴ素材は後日設定</span>
      <?php endif; ?>
    </div>

    <div class="brew-info">
      <div class="eyebrow"><?= e(country_name($maker['country_code'])) ?> のブリュワリー</div>
      <h1 class="dt-title" style="font-size:clamp(26px,3.6vw,40px);margin:8px 0 12px"><?= flag($maker['country_code']) ?> <?= e($mn) ?></h1>
      <?php if (trim((string)$maker['MakerExplain']) !== ''): ?>
        <p style="color:var(--muted);font-size:14.5px;line-height:1.9"><?= nl2br(e(trim((string)$maker['MakerExplain']))) ?></p>
      <?php endif; ?>
      <?php if (!empty($maker['URL1'])): ?>
        <p style="margin-top:12px;font-size:13px">公式サイト：
          <a href="<?= e($maker['URL1']) ?>" target="_blank" rel="noopener nofollow" style="color:var(--teal);word-break:break-all"><?= e($maker['URL1']) ?></a></p>
      <?php endif; ?>
    </div>

    <div class="brew-map">
      <div class="cap">製造地 — <?= e(country_name($maker['country_code'])) ?></div>
      <div class="map">
        <?php if ($useMap): ?>
          <div class="lmap" data-lat="<?= e($maker['latitude']) ?>" data-lng="<?= e($maker['longitude']) ?>" data-label="<?= e($mn) ?>"></div>
        <?php else: ?>
          <div class="grid"></div>
          <div class="pin" style="left:56%;top:50%"></div>
          <div class="lbl" style="left:56%;top:50%;font-size:11px"><?= e($mn) ?></div>
          <div class="note">※ 所在地データ未設定</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- 味覚マップ: このブリュワリーのビールを強調 -->
  <div class="panel" style="margin-top:26px">
    <h3>味覚マップ上の位置</h3>
    <div class="sub">全<?= count($allBeers) ?>銘柄の中で、<?= e($mn) ?> のビール（<?= count($beers) ?>件）がどの味域にいるか（原点=平均）</div>
    <div class="galaxy-shell galaxy-wide" style="margin-top:8px">
      <canvas id="gx"></canvas>
      <div class="galaxy-tag">Flavour Galaxy · 味覚銀河</div>
      <div class="galaxy-hint">ドラッグで回転 · 星クリックで詳細</div>
      <div class="legend" id="lg"></div>
      <div class="tt" id="tt"></div>
    </div>
  </div>

  <!-- 取扱ビール -->
  <section class="blk" style="border-top:1px solid var(--line);margin-top:30px">
    <div class="eyebrow">Beers</div>
    <div class="sec-h" style="margin-bottom:6px"><?= e($mn) ?> のビール <span style="color:var(--muted);font-weight:400;font-size:.6em">全<?= count($beers) ?>件</span></div>
    <div class="grid-b" style="margin-top:24px;margin-bottom:30px">
      <?php foreach ($beers as $b):
        $g = style_group($b['FamilyName'] ?? '', $b['StyleName'] ?? '');
        list($gcol, $glabel) = group_meta($g);
      ?>
      <a class="bcard" href="/beer/detail/product.php?ProductID=<?= e($b['ProductID']) ?>">
        <div class="thumb"><?= beer_glass_tag($b) ?></div>
        <div class="body">
          <div class="stylechip"><?= e($glabel) ?></div>
          <h4><?= e($b['ProductName']) ?></h4>
          <div class="meta"><span><?= fmt_unit($b['Alcohol'],'%') ?> · IBU<?= fmt_unit($b['IBU_all']) ?><?= est_mark($b,'IBU_all') ?></span><?= stars_html($b['Favorite']) ?></div>
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
