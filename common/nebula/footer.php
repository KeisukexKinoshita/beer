<?php /* 本文末尾: フッター + スクリプト。ページ側で $pageJs=[...] を設定すると追加読み込み */ ?>
</main>
<footer class="site-footer">
  <div class="wrap">
    <nav class="foot-nav">
      <a href="/about.php">このサイトについて</a>
      <a href="/privacy.php">プライバシーポリシー</a>
      <a href="/terms.php">利用規約</a>
      <a href="https://docs.google.com/forms/d/e/1FAIpQLSc59D4Xn78uL0XDJ9Ztfu_Mp2yY2XJuBZtzbZqmY-7YN0XPcw/viewform?usp=header" target="_blank" rel="noopener nofollow">お問い合わせ</a>
    </nav>
    <div class="foot-note">20歳未満の飲酒は法律で禁止されています。</div>
  </div>
</footer>
<?php /* スタイルグループの色とラベル。定義は helpers.php の group_map() が単一の出所 */ ?>
<script>window.STYLE_GROUPS = <?= json_encode(group_map_js(), JSON_UNESCAPED_UNICODE) ?>;</script>
<?php foreach (($pageJs ?? []) as $js): ?><script src="<?= e($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
