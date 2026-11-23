<?php
/*
 * Google アナリティクス(GA4)のタグ。<head> の中で require する。
 *
 * 本番(drtbeer.com)でだけ出す。dev.drtbeer.com やローカルで出すと、
 * 確認作業のアクセスが本番の数字に混ざるため。
 *
 * 送信先と送る情報は privacy.php の「2. 利用者情報の外部送信について」の表と
 * 「4. アクセス解析について」に書いてある。タグを増やす・外すときは両方を直すこと。
 */
$gaId   = 'G-3XWPBKZR5Z';
$gaHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
if ($gaHost === 'drtbeer.com') : ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= $gaId ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= $gaId ?>');
</script>
<?php endif;
