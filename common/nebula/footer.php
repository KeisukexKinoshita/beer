<?php /* 本文末尾: フッター + スクリプト。ページ側で $pageJs=[...] を設定すると追加読み込み */ ?>
</main>
<footer class="site-footer"<?= !empty($ageLock) ? ' inert' : '' ?>>
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
<?php /* 年齢確認の幕。本文より後ろ(body の末尾側)に置き、検索結果の説明文に拾われにくくする。
         中身は agecheck.php の確認画面と同じ。「はい」「いいえ」はそこへ POST する */ ?>
<?php if (!empty($ageLock)): ?>
<div class="age-gate" role="dialog" aria-modal="true" aria-labelledby="age-q" data-nosnippet>
  <div class="ex-bar"><span class="ex-mark">DARTH BEER</span></div>
  <div class="ex-wrap">
    <div class="ex-intake" style="padding-top:152px;padding-bottom:100px">
      <div class="ex-ring"><span>20</span></div>
      <div>
        <div class="age-q" id="age-q">20歳以上ですか</div>
        <p style="color:var(--dim);font-size:12.5px;max-width:28ch">当サイトは酒類に関する情報を扱っています。</p>
        <p style="color:var(--faint);font-size:11px;margin-top:8px"><a href="/terms.php" target="_blank" rel="noopener">利用規約</a>をご確認ください</p>
      </div>
      <form class="ex-acts" method="POST" action="/agecheck.php?next=<?= rawurlencode($ageBack) ?>">
        <input type="hidden" name="tok" value="<?= e($ageTok) ?>">
        <button type="submit" name="confirm" value="yes" class="ex-btn">はい</button>
        <button type="submit" name="declined" value="yes" class="ex-btn ghost">いいえ</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
<?php /* スタイルグループの色とラベル。定義は helpers.php の group_map() が単一の出所 */ ?>
<script>window.STYLE_GROUPS = <?= json_encode(group_map_js(), JSON_UNESCAPED_UNICODE) ?>;</script>
<?php foreach (($pageJs ?? []) as $js): ?><script src="<?= e($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
