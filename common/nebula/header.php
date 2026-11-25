<?php /* 本文冒頭: サイトヘッダー。try.php の .ex-bar / .ex-tabs と同じ作り。
         今いる場所のタブに on を付ける(URL の頭で判定。ページ側の設定は要らない) */
$navPath = (string)strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$navOn = '';
foreach (['/beer/' => 'beer', '/brewery/' => 'brewery', '/style/' => 'style'] as $pre => $key) {
    if (strpos($navPath, $pre) === 0) { $navOn = $key; break; }
}
$navTab = function ($key, $href, $label) use ($navOn) {
    return '<a' . ($navOn === $key ? ' class="on" aria-current="page"' : '') . ' href="' . $href . '">' . $label . '</a>';
};
?>
<div class="site-top">
<div class="ex-bar"><a class="ex-mark" href="/index.php">DARTH BEER</a></div>
<nav class="ex-tabs">
  <?= $navTab('try', '/try.php', 'さがす') ?>

  <?= $navTab('beer', '/beer/products.php', '銘柄') ?>

  <?= $navTab('brewery', '/brewery/makers.php', '蔵') ?>

  <?= $navTab('style', '/style/styles.php', 'スタイル') ?>

</nav>
</div>
<main>
