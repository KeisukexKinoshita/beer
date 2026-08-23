<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$types = styles_used();
$total = count($types);
$title = 'Type一覧';
$desc  = "クラフトビールのスタイル（Type）を人気順に。IPA・スタウト・サワーなど{$total}種を、味わいのキャッチと取扱数で紹介します。";
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap" style="padding-top:40px;padding-bottom:70px">
  <div class="eyebrow">All Types</div>
  <div class="sec-h">Type一覧 <span style="color:var(--muted);font-weight:400;font-size:.6em">全<?= $total ?>種</span></div>
  <p style="color:var(--muted);font-size:14px;margin-top:10px">ビールのスタイル（Type）を、扱っている銘柄数の多い順に並べました。</p>

  <div class="types" style="margin-top:26px">
    <?php $rank = 0; foreach ($types as $t):
      $rank++;
      $g = style_group($t['FamilyName'] ?? '', $t['StyleName'] ?? '');
      list($gcol, $glabel) = group_meta($g);
    ?>
    <a class="trow" href="/style/detail/style.php?StyleID=<?= e($t['StyleID']) ?>">
      <div class="n" style="color:<?= $gcol ?>;-webkit-text-fill-color:<?= $gcol ?>"><?= str_pad($rank, 2, '0', STR_PAD_LEFT) ?></div>
      <div class="tn"><?= e($t['StyleName']) ?></div>
      <div class="cap"><?= e($t['catchcopy'] ?: ($t['FamilyName'] ?: 'クラフトビール')) ?></div>
      <div class="ct"><span style="color:<?= $gcol ?>"><?= e($glabel) ?></span> · <?= (int)$t['beer_count'] ?>件</div>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
