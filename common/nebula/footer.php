<?php /* 本文末尾: フッター + スクリプト。ページ側で $pageJs=[...] を設定すると追加読み込み */ ?>
</main>
<footer class="site-footer">
  <div class="wrap" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:14px;align-items:center">
    <a class="brand" href="/index.php" style="font-size:16px"><span class="dot"></span>Darth Beer.com</a>
    <nav class="foot-nav">
      <a href="/about.php">このサイトについて</a>
      <a href="/privacy.php">プライバシーポリシー</a>
    </nav>
  </div>
  <div class="wrap foot-note">
    クラフトビールを宇宙のように旅する。20歳未満の飲酒は法律で禁止されています。
  </div>
</footer>
<?php /* スタイルグループの色とラベル。定義は helpers.php の group_map() が単一の出所 */ ?>
<script>window.STYLE_GROUPS = <?= json_encode(group_map_js(), JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="/assets/js/nebula-bg.js"></script>
<?php foreach (($pageJs ?? []) as $js): ?><script src="<?= e($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
