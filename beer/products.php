<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$beers   = all_beers();
$total   = count($beers);
$title   = 'Beer一覧';
$desc    = "登録されている{$total}銘柄のクラフトビールを、味わいの銀河とカードで探せます。スタイルで絞り込み、度数・IBU・評価で並び替え。";
$pageJs  = ['/assets/js/galaxy.js', '/assets/js/beerlist.js', '/assets/js/beerglass.js'];
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap" style="padding-top:40px">
  <div class="eyebrow">All Beers</div>
  <div class="sec-h">Beer一覧 <span style="color:var(--muted);font-weight:400;font-size:.6em">全<?= $total ?>件</span></div>

  <!-- 味覚銀河: 全ビールの3D分布 -->
  <div class="galaxy-shell galaxy-wide" style="margin-top:24px">
    <canvas id="gx"></canvas>
    <div class="galaxy-tag">Flavour Galaxy · 味覚銀河</div>
    <div class="galaxy-hint">ドラッグで回転 · 星をクリックで詳細へ</div>
    <div class="legend" id="lg"></div>
    <div class="tt" id="tt"></div>
  </div>

  <!-- フィルタ -->
  <div class="filterbar">
    <div class="f-chips">
      <button class="f-chip on" data-group="all">すべて</button>
      <button class="f-chip" data-group="ipa">IPA系</button>
      <button class="f-chip" data-group="stout">Stout / 黒</button>
      <button class="f-chip" data-group="sour">Sour</button>
      <button class="f-chip" data-group="pale">Pale / Amber</button>
      <button class="f-chip" data-group="wheat">小麦 / Weizen</button>
      <button class="f-chip" data-group="other">Lager / その他</button>
    </div>
    <div class="f-right">
      <span class="f-count-wrap"><b id="f-count"><?= $total ?></b> 件</span>
      <input class="f-search" id="f-search" type="search" placeholder="銘柄・ブリュワリーで検索" aria-label="ビールを検索">
      <select class="f-sort" id="f-sort" aria-label="並び替え">
        <option value="newest">新着順</option>
        <option value="rating">評価が高い順</option>
        <option value="ibu">IBUが高い順</option>
        <option value="alcohol">度数が高い順</option>
        <option value="name">名前順</option>
      </select>
    </div>
  </div>

  <!-- カードグリッド -->
  <div class="grid-b" id="beer-grid" style="margin-top:22px;margin-bottom:70px">
    <?php $order = $total; foreach ($beers as $b):
      $g  = style_group($b['FamilyName'] ?? '', $b['StyleName'] ?? '');
      list($gcol, $glabel) = group_meta($g);
      $pid = $b['ProductID'];
    ?>
    <a class="bcard" href="/beer/detail/product.php?ProductID=<?= e($pid) ?>"
       data-group="<?= $g ?>" data-name="<?= e($b['ProductName']) ?>" data-maker="<?= e($b['MakerName']) ?>"
       data-style="<?= e($b['StyleName']) ?>" data-rating="<?= e($b['Favorite']) ?>" data-ibu="<?= e($b['IBU_all']) ?>"
       data-alcohol="<?= e($b['Alcohol']) ?>" data-order="<?= $order-- ?>">
      <div class="thumb"><?= beer_glass_tag($b) ?></div>
      <div class="body">
        <div class="stylechip"><?= e($glabel) ?></div>
        <h4><?= e($b['ProductName']) ?></h4>
        <div class="mk"><?= flag($b['country_code']) ?> <?= e($b['MakerName']) ?></div>
        <div class="meta">
          <span><?= fmt_num($b['Alcohol']) ?>% · IBU<?= fmt_num($b['IBU_all']) ?><?= est_mark($b,'IBU_all') ?></span>
          <?= stars_html($b['Favorite']) ?>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<script>window.BEERS = <?= json_encode(beers_for_js($beers), JSON_UNESCAPED_UNICODE) ?>;</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  initGalaxy({canvas:'#gx', tooltip:'#tt', legend:'#lg', data:window.BEERS,
    onPick:function(b){location.href='/beer/detail/product.php?ProductID='+encodeURIComponent(b.id);}});
});
</script>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
