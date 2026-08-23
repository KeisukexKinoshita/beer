<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$mid   = $_GET['MakerID'] ?? '';
$maker = maker_by_id($mid);

if (!$maker) {
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

$beers = beers_by_maker($mid);
$mn    = $maker['MakerName'];
$init  = mb_substr(preg_replace('/\s.*$/', '', $mn), 0, 1);
$title = $mn;
$desc  = mb_strimwidth(trim((string)$maker['MakerExplain']) ?: ($mn . ' のクラフトビール'), 0, 110, '…');
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap">
  <div class="detail-hero" style="grid-template-columns:.7fr 1.3fr">
    <div class="dt-img" style="aspect-ratio:1;border-radius:20px">
      <?php if (!empty($maker['logo_path'])): ?>
        <img src="<?= e($maker['logo_path']) ?>" alt="<?= e($mn) ?> のロゴ">
      <?php else: ?>
        <div class="logo" style="width:96px;height:96px;border-radius:24px;font-size:40px"><?= e($init) ?></div>
        <div class="note" style="position:absolute;bottom:10px;left:0;right:0;text-align:center;font-size:10.5px;color:var(--faint)">ロゴ素材は後日設定（表示枠のみ）</div>
      <?php endif; ?>
    </div>
    <div>
      <a class="dt-back" href="/brewery/makers.php">← Brewery一覧へ</a>
      <div class="eyebrow"><?= e(country_name($maker['country_code'])) ?> のブリュワリー</div>
      <h1 class="dt-title"><?= flag($maker['country_code']) ?> <?= e($mn) ?></h1>
      <?php if (trim((string)$maker['MakerExplain']) !== ''): ?>
        <p class="dt-desc"><?= nl2br(e(trim((string)$maker['MakerExplain']))) ?></p>
      <?php endif; ?>
      <?php if (!empty($maker['URL1'])): ?>
        <p style="margin-top:16px;font-size:13.5px">公式サイト：
          <a href="<?= e($maker['URL1']) ?>" target="_blank" rel="noopener" style="color:var(--teal);word-break:break-all"><?= e($maker['URL1']) ?></a></p>
      <?php endif; ?>
    </div>
  </div>

  <div style="padding:16px 0 8px">
    <div class="panel" style="max-width:520px">
      <h3>製造地</h3>
      <div class="sub"><?= e(country_name($maker['country_code'])) ?></div>
      <div class="map" style="aspect-ratio:16/8">
        <div class="grid"></div>
        <div class="pin" style="left:58%;top:48%"></div>
        <div class="lbl" style="left:58%;top:48%"><?= e($mn) ?></div>
        <div class="note">※ 実装ではLeafletで実地図に緯度経度をプロット（フェーズ2）</div>
      </div>
    </div>
  </div>

  <section class="blk" style="border-top:1px solid var(--line);margin-top:20px">
    <div class="eyebrow">Beers</div>
    <div class="sec-h" style="margin-bottom:6px"><?= e($mn) ?> のビール <span style="color:var(--muted);font-weight:400;font-size:.6em">全<?= count($beers) ?>件</span></div>
    <div class="grid-b" style="margin-top:24px;margin-bottom:30px">
      <?php foreach ($beers as $b):
        $g = style_group($b['FamilyName'] ?? '', $b['StyleName'] ?? '');
        list($gcol, $glabel) = group_meta($g);
      ?>
      <a class="bcard" href="/beer/detail/product.php?ProductID=<?= e($b['ProductID']) ?>">
        <div class="thumb">
          <div class="halo" style="position:absolute;width:60%;aspect-ratio:1;border-radius:50%;background:radial-gradient(circle,<?= $gcol ?>55,transparent 66%);filter:blur(8px)"></div>
          <img src="/img/product/<?= e($b['ProductID']) ?>.png" alt="<?= e($b['ProductName']) ?> のボトル画像" loading="lazy" onerror="this.style.opacity=0">
        </div>
        <div class="body">
          <div class="stylechip"><?= e($glabel) ?></div>
          <h4><?= e($b['ProductName']) ?></h4>
          <div class="meta"><span><?= fmt_num($b['Alcohol']) ?>% · IBU<?= fmt_num($b['IBU_all']) ?></span><?= stars_html($b['Favorite']) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
