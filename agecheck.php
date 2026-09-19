<?php
declare(strict_types=1);

require_once __DIR__ . '/common/nebula/helpers.php';
require_once __DIR__ . '/common/reco/visitor.php';
require_once __DIR__ . '/common/reco/age.php';

$visitorId = visitor_current();

// "はい" ボタンが押された
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    age_confirm($visitorId);

    // next パラメータへ安全にリダイレクト（許可したパスだけを受け付ける）
    header('Location: ' . age_next($_GET['next'] ?? null));
    exit;
}

// "いいえ" の選択
$declined = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['declined']);

$title = '年齢確認';
$desc  = 'Darth Beer.com は酒類に関する情報を扱っています。20歳以上であることをご確認ください。';
require __DIR__ . '/common/nebula/head.php';
?>
<body>
<?php require __DIR__ . '/common/nebula/header.php'; ?>

<div class="ex-wrap" style="padding:0 18px">
  <?php if ($declined): ?>
    <!-- "いいえ" を選んだ後の画面。戻るボタンはない -->
    <div class="ex-intake" style="padding-top:90px;padding-bottom:90px">
      <div>
        <h1 style="font-size:18px;margin-bottom:12px">ご利用いただけません</h1>
        <p style="font-size:13px;color:var(--dim);line-height:1.7;max-width:32ch">
          当サイトは酒類に関する情報を扱っています。20歳以上の方のご利用をお願いしています。
        </p>
      </div>
    </div>
  <?php else: ?>
    <!-- 年齢確認画面 -->
    <div class="ex-intake" style="padding-top:100px;padding-bottom:100px">
      <div class="ex-ring">
        <span>20</span>
      </div>
      <div>
        <h1>20歳以上ですか</h1>
        <p style="color:var(--dim);font-size:12.5px;max-width:28ch">
          当サイトは酒類に関する情報を扱っています。
        </p>
        <p style="color:var(--faint);font-size:11px;margin-top:8px">
          <a href="/terms.php" target="_blank" rel="noopener">利用規約</a>をご確認ください
        </p>
      </div>
      <form class="ex-acts" method="POST">
        <button type="submit" name="confirm" value="yes" class="ex-btn">はい</button>
        <button type="submit" name="declined" value="yes" class="ex-btn ghost">いいえ</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/common/nebula/footer.php'; ?>
</body>
</html>
