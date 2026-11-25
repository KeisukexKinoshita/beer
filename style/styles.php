<?php
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';

// 取扱いのあるスタイル（銘柄数の多い順）
$types = styles_used();

// 銘柄はまだ無いが解説を書いてあるスタイル。
// **これを出さないと、書いた解説がどこからもリンクされない孤立ページになる。**
// 解説の無い行（未使用・重複と印を付けたもの）はここにも出さない。
$others = array_values(array_filter(all_styles(), function ($s) {
    return (int)$s['beer_count'] === 0 && trim((string)($s['StyleExplain'] ?? '')) !== '';
}));
usort($others, fn($a, $b) => strcmp($a['StyleName'], $b['StyleName']));

$total = count($types) + count($others);
$title = 'Type一覧';
$desc  = "クラフトビールのスタイル（Type）{$total}種を解説。IPA・スタウト・サワーなど、味わいの違いと取扱銘柄数で紹介します。";
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>

<div class="wrap pg-head">
  <h1 class="sec-h">スタイル<span class="cnt">全<?= $total ?>種</span></h1>

  <div class="types">
    <?php foreach ($types as $t):
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

  <?php if ($others): ?>
  <div class="sec-top">
    <h2 class="sec-h">銘柄のないスタイル<span class="cnt">全<?= count($others) ?>種</span></h2>
  </div>

  <div class="types">
    <?php foreach ($others as $t):
      $g = style_group($t['FamilyName'] ?? '', $t['StyleName'] ?? '');
      list($gcol, $glabel) = group_meta($g);
    ?>
    <a class="trow" href="/style/detail/style.php?StyleID=<?= e($t['StyleID']) ?>">
      <i class="ex-dot" style="background:<?= $gcol ?>"></i>
      <div class="tn"><?= e($t['StyleName']) ?></div>
      <div class="cap"><?= e($t['catchcopy'] ?: ($t['FamilyName'] ?: '')) ?></div>
      <div class="ct"><?= e($glabel) ?> · 取扱なし</div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
