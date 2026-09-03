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

  <?php if ($others): ?>
  <div class="sec-top" style="margin-top:56px">
    <div>
      <div class="eyebrow">Types without beers</div>
      <div class="sec-h" style="font-size:clamp(20px,3vw,26px)">まだ扱いのないスタイル
        <span style="color:var(--muted);font-weight:400;font-size:.6em">全<?= count($others) ?>種</span></div>
    </div>
  </div>
  <p style="color:var(--muted);font-size:14px;margin-top:10px">
    このサイトにまだ該当する銘柄はありませんが、どんなスタイルなのかは書いてあります。名前順です。</p>

  <div class="types" style="margin-top:26px">
    <?php foreach ($others as $t):
      $g = style_group($t['FamilyName'] ?? '', $t['StyleName'] ?? '');
      list($gcol, $glabel) = group_meta($g);
    ?>
    <a class="trow" href="/style/detail/style.php?StyleID=<?= e($t['StyleID']) ?>">
      <div class="n" style="color:var(--faint);-webkit-text-fill-color:var(--faint)">–</div>
      <div class="tn"><?= e($t['StyleName']) ?></div>
      <div class="cap"><?= e($t['catchcopy'] ?: ($t['FamilyName'] ?: 'クラフトビール')) ?></div>
      <div class="ct"><span style="color:<?= $gcol ?>"><?= e($glabel) ?></span> · 取扱なし</div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
