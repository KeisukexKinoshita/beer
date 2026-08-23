<?php /* 本文末尾: フッター + スクリプト。ページ側で $pageJs=[...] を設定すると追加読み込み */ ?>
</main>
<footer class="site-footer">
  <div class="wrap" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:14px;align-items:center">
    <a class="brand" href="/index.php" style="font-size:16px"><span class="dot"></span>Darth Beer.com</a>
    <div>クラフトビールを宇宙のように旅する</div>
  </div>
</footer>
<script src="/assets/js/nebula-bg.js"></script>
<?php foreach (($pageJs ?? []) as $js): ?><script src="<?= e($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
