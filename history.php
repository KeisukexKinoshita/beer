<?php
declare(strict_types=1);

require_once __DIR__ . '/common/nebula/helpers.php';
require_once __DIR__ . '/common/reco/visitor.php';
require_once __DIR__ . '/common/reco/repo.php';
require_once __DIR__ . '/common/reco/evaluate.php';
require_once __DIR__ . '/common/reco/cascade.php';
require_once __DIR__ . '/common/reco/handle.php';
require_once __DIR__ . '/common/reco/age.php';

$visitorId = visitor_current();

// 酒類の記録を見せる画面なので、try.php と同じ門番を置く(設計書 §12)
if (!age_confirmed($visitorId)) {
    header('Location: /agecheck.php?next=/history.php');
    exit;
}

$rows   = visitor_history($visitorId);
$linked = visitor_line_linked($visitorId);
$pool   = reco_pool();
?><!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>これまでのビール | Darth Beer.com</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap">
<?php
$exposureCss = '/assets/css/exposure.css';
$exposureVer = @filemtime($_SERVER['DOCUMENT_ROOT'] . $exposureCss) ?: time();
?>
<link rel="stylesheet" href="<?= e($exposureCss) ?>?v=<?= (int)$exposureVer ?>">
<style>:root{
<?php foreach (group_map() as $k => $v): ?>
  --g-<?= e($k) ?>:<?= e($v[0]) ?>; --h-<?= e($k) ?>:<?= e($v[1]) ?>;
<?php endforeach; ?>
}</style>
</head>
<body>

<div class="ex-bar"><div class="ex-mark">DARTH BEER</div></div>
<nav class="ex-tabs">
  <a href="/try.php">さがす</a>
  <a href="/beer/products.php">銘柄</a>
  <a href="/brewery/makers.php">蔵</a>
  <a href="/style/styles.php">スタイル</a>
</nav>

<div class="ex-wrap">
  <div class="ex-lbl">これまでのビール</div>

  <?php if (count($rows) === 0): ?>
    <p class="ex-sub">まだ1本もありません。</p>
    <div class="ex-acts"><a class="ex-btn" href="/try.php">写真から探す</a></div>
  <?php else: ?>
    <?php foreach ($rows as $u): ?>
      <?php
        $seed = reco_seed_from_upload($u);
        $ev   = eval_build(['abv' => $seed['Alcohol'], 'ibu' => $seed['IBU_all'],
                            'fruity' => $seed['Fruity']], $pool,
                           empty($u['product_id']) ? true : reco_estimated_map($seed));
        $g    = style_group($seed['FamilyName'] ?? '', $seed['StyleName'] ?? '');
        list($col) = group_meta($g);
        $name = $u['product_id'] ? ($seed['ProductName'] ?? '') : ($u['brand_text'] ?: '銘柄がわかりませんでした');
      ?>
      <a class="ex-hist" href="/try.php?u=<?= (int)$u['upload_id'] ?>">
        <span class="ex-dot" style="background:<?= e($col) ?>"></span>
        <span class="ex-hist-b">
          <b><?= e($name) ?></b>
          <span class="ex-hist-m">
            <?= e(date('Y/n/j', strtotime((string)$u['created_at']))) ?>
            <?php if (!empty($seed['StyleName'])): ?> · <?= e($seed['StyleName']) ?><?php endif; ?>
          </span>
          <?php if (!empty($ev['lines'])): ?>
            <span class="ex-hist-e">
              <?php $bits = [];
                    foreach ($ev['lines'] as $l) { $bits[] = $l['label'] . 'は' . $l['word']; }
                    echo e(implode(' / ', $bits)); ?>
            </span>
          <?php endif; ?>
          <?php if (!empty($u['note_body'])): ?>
            <span class="ex-hist-n"><?= e(mb_strimwidth((string)$u['note_body'], 0, 60, '…')) ?></span>
          <?php endif; ?>
        </span>
      </a>
    <?php endforeach; ?>

    <?php if (!$linked): ?>
      <div class="ex-hist-line">
        <div class="ex-lbl">別の端末でも見るには</div>
        <p class="ex-sub">
          いまの記録はこの端末に紐づいています。
          <a href="/line/login.php?back=<?= e(urlencode('/history.php')) ?>">LINE と繋ぐ</a>と、
          別の端末からでも同じ記録が見られます。
        </p>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

</body>
</html>
