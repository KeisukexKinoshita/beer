<?php
declare(strict_types=1);

require_once __DIR__ . '/common/nebula/helpers.php';
require_once __DIR__ . '/common/reco/visitor.php';
require_once __DIR__ . '/common/reco/repo.php';
require_once __DIR__ . '/common/reco/gate.php';
require_once __DIR__ . '/common/reco/identify.php';
require_once __DIR__ . '/common/reco/cascade.php';
require_once __DIR__ . '/common/reco/handle.php';

$visitorId = visitor_current();
$view = 'intake';   // intake / result / error
$msg  = '';
$result = $picked = $seed = [];
$uploadId = 0;
$imageWebPath = null;   // 表示部が参照する。保存しなかったときは null のまま

// 確認の答え(confirm)を受け取る分岐。POST処理の先頭に置く
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $uid = (int)($_POST['upload_id'] ?? 0);
    // 他人の upload に投票できないようにする。この記録は unknown_beer の信頼度になり、
    // ゆくゆくは蔵へ渡す数字の根拠になるので、持ち主だけが答えられること
    // (前の計画のレビューで指摘され、繰り延べていたもの。修正ラウンド2で対応)
    $own = $uid > 0 ? upload_owned_by($uid, $visitorId) : null;
    if ($own) {
        $yes = ($_POST['confirm'] === 'yes');
        event_record($visitorId, $yes ? 'confirm_yes' : 'confirm_no', null, $uid);

        // 実際に何が起きたかを見て印を決める(修正ラウンド3: C-2)。
        // unknown_bump() は brand_text が空でないときしか呼ばれない(handle.php)。
        // ここを product_id の有無だけで判定すると、銘柄名が読めなかった(brand_text も
        // 空)ケースで product_id も当然 null になり「控えておきます」と出てしまうが、
        // 何も控えていない(嘘になる)。brand_text の有無まで見て区別する。
        if (!$yes) {
            $flash = 'corrected';
        } elseif (!empty($own['product_id'])) {
            $flash = 'confirmed_known';     // DBにある銘柄
        } elseif (!empty($own['brand_text'])) {
            $flash = 'confirmed_queued';    // 銘柄名は読めた → unknown_beer に載っている
        } else {
            $flash = 'confirmed_unread';    // 銘柄名が読めなかった → 何も控えていない
        }
        visitor_flash_set($visitorId, $flash);
    }
    header('Location: /try.php');
    exit;
}

// 門番・判定・保存・記録の判断は common/reco/handle.php の reco_handle_upload() に
// 切り出してある(最終レビュー指摘: ここに金・嘘・写真の判断が全部あるのに
// テストが無かった)。本物のHTTPアップロードかどうかの検査(is_uploaded_file())だけは
// ここに残す。reco_handle_upload() に含めると、単体テストで作る $_FILES はどれも
// 本物のHTTPアップロードではないので is_uploaded_file() が常に偽になり、
// 金・嘘・写真の判断を一切テストできなくなる
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $f = $_FILES['photo'];
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        $view = 'error'; $msg = '写真を受け取れませんでした。';
    } else {
        $h = reco_handle_upload($visitorId, $f);
        $view         = $h['view'];
        $msg          = $h['msg'];
        $result       = $h['result'];
        $picked       = $h['picked'];
        $uploadId     = $h['uploadId'];
        $imageWebPath = $h['imageWebPath'];
        $seed         = $h['seed'];
    }
}

$seedGroup = ($view === 'result' && !empty($seed))
    ? style_group($seed['FamilyName'] ?? '', $seed['StyleName'] ?? '') : 'other';
?><!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>ビールをさがす | Darth Beer.com</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap">
<?php /* 内容が変わったら必ず読み直させる。
         古い CSS が残っていて、隠すはずの入力欄が見えたままだった(実機で発生) */
$exposureCss = '/assets/css/exposure.css';
$exposureVer = @filemtime($_SERVER['DOCUMENT_ROOT'] . $exposureCss) ?: time();
?>
<link rel="stylesheet" href="<?= e($exposureCss) ?>?v=<?= (int)$exposureVer ?>">
<?php /* スタイル色の出所は group_map() 一つ。CSS に直値を書かず、ここで流し込む */ ?>
<style>:root{
<?php foreach (group_map() as $k => $v): ?>
  --g-<?= e($k) ?>:<?= e($v[0]) ?>; --h-<?= e($k) ?>:<?= e($v[1]) ?>;
<?php endforeach; ?>
}</style>
</head>
<body>

<div class="ex-bar"><div class="ex-mark">DARTH BEER</div></div>
<nav class="ex-tabs">
  <a class="on" href="/try.php">さがす</a>
  <a href="/beer/products.php">銘柄</a>
  <a href="/brewery/makers.php">蔵</a>
  <a href="/style/styles.php">スタイル</a>
</nav>

<?php if ($view === 'intake' || $view === 'error'): ?>
  <?php if ($view === 'error'): ?>
    <div class="ex-wrap"><p class="ex-msg"><?= e($msg) ?></p></div>
  <?php endif; ?>
  <?php
  // 確認への回答直後、フォームの上にお礼を出す(修正ラウンド2→3)。
  // URLの $_GET ではなく、確認を実際に処理したときだけ立つ visitor.flash を見る
  // (修正ラウンド3: C-1。URLを打つだけで「記録しました」が出て、実際には何も
  // 記録していないのに記録したと伝えていた嘘を直した)
  $flash = ($view === 'intake') ? visitor_flash_take($visitorId) : null;
  ?>
  <?php if ($flash): ?>
    <div class="ex-wrap ex-thanks">
      <?php if ($flash === 'confirmed_queued'): ?>
        <p><b>ありがとうございます。</b></p>
        <p>この銘柄はまだ登録がありません。<b>追加する候補として控えておきます。</b></p>
        <p class="ex-sub">よく上がる銘柄から順に、実際の情報を調べて載せています。</p>
      <?php elseif ($flash === 'confirmed_known'): ?>
        <p><b>ありがとうございます。</b>記録しました。</p>
        <p class="ex-sub">こうした答えが積み重なるほど、薦める精度が上がります。</p>
      <?php elseif ($flash === 'confirmed_unread'): ?>
        <?php /* 銘柄名が読めていないので、控えるものがない。控えると言ってはいけない
                 (修正ラウンド3: C-2 の本体) */ ?>
        <p><b>ありがとうございます。</b></p>
        <p>ラベルの銘柄名までは読み取れませんでした。読み取りの改善に使わせていただきます。</p>
      <?php else: ?>
        <p><b>教えていただきありがとうございます。</b></p>
        <p>読み取りを外していたことを記録しました。<b>読み取りの改善に使わせていただきます。</b></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <form class="ex-intake" method="post" enctype="multipart/form-data" id="intake">
    <div class="ex-ring"><span>◎</span></div>
    <h1>飲んだビールの写真から</h1>
    <p>ラベルが写っていれば、似た一本を探します</p>
    <div class="ex-acts">
      <?php /*
        accept は image/* にする。**iPhone の写真は HEIC** で、jpeg/png/webp に絞ると
        アルバムから選んでも黙って弾かれ、画面が何も反応しない(実機で発生)。
        image/* にしておくと iOS が JPEG に変換して渡してくれる。
        変換されずに来た場合も、門番(gate_check)が bad_type で弾き、
        「JPEG・PNG・WebP の写真をお選びください」と**画面に出る**。沈黙よりよい。

        capture は付けない。カメラに固定され、アルバムから選べない端末がある。

        input は label で開く。JS に頼らないので、JS が転んでも選択画面は出る。
        送信ボタンは <noscript> の中にだけ置く(修正ラウンド2: 選んだ瞬間に送るので、
        普段の利用者には出さない。JS が動かない端末のためだけの逃げ道)。
      */ ?>
      <input id="photo" name="photo" type="file" accept="image/*" class="ex-file">
      <label class="ex-btn" for="photo">写真をえらぶ</label>
      <noscript>
        <button class="ex-btn" type="submit">この写真でさがす</button>
      </noscript>
      <a class="ex-btn ghost" href="/beer/products.php">名前でさがす</a>
    </div>
    <p class="ex-status" id="status" hidden>判定しています…</p>
    <div class="ex-meta"><?= count(reco_pool()) ?> BEERS</div>
  </form>
  <script>
  (function () {
    var f = document.getElementById('photo');
    var form = document.getElementById('intake');
    var status = document.getElementById('status');
    if (!f || !form) { return; }
    // 選んだ時点で送る。ボタンが無いぶん、待っていることは画面のテキストで示す
    function waiting() { if (status) { status.hidden = false; } }
    f.addEventListener('change', function () {
      if (f.files && f.files.length) { waiting(); form.submit(); }
    });
  })();
  </script>

<?php elseif ($result['is_beer'] !== true): ?>
  <div class="ex-wrap">
    <p class="ex-msg">ビールの写真に見えませんでした。<br>ラベルが写るように撮ってみてください。</p>
    <div class="ex-acts"><a class="ex-btn" href="/try.php">もう一度</a></div>
  </div>

<?php else: ?>
  <div class="ex-shot">
    <?php /* 強調色を使ってよい3か所のひとつ。同定された1本の背後のハロー(設計書 §11) */ ?>
    <span class="ex-halo" style="background:var(--h-<?= e($seedGroup) ?>)"></span>
    <?php if (!empty($imageWebPath)): ?><img src="<?= e($imageWebPath) ?>" alt=""><?php endif; ?>
  </div>
  <div class="ex-wrap">
    <div class="ex-hit">
      <b><?= e($result['matched_product_id'] ? $seed['ProductName'] : ($result['brand_text'] ?: '銘柄がわかりませんでした')) ?></b>
      <?php if ($result['confidence'] !== null): ?>
        <span class="ex-conf"><?= number_format((float)$result['confidence'], 2) ?></span>
      <?php endif; ?>
    </div>
    <div class="ex-sub">
      <?= e($result['brewery_text'] ?: ($seed['MakerName'] ?? '')) ?>
      <?php if (!empty($seed['StyleName'])): ?> · <?= e($seed['StyleName']) ?><?php endif; ?>
    </div>

    <?php $branch = identify_branch($result); ?>

    <?php if (!$result['matched_product_id']): ?>
      <?php /* DBに無いことは、確度によらず必ず伝える。
               確度が高くても未登録の銘柄はある(ラベルははっきり読めたが、うちに登録が無い)。
               (修正ラウンド2: ラウンド1の elseif が「確認を求める」と排他にしてしまい、
               確度0.8以上でDBに無いときに「登録されていません」が出ない嘘が残っていた) */ ?>
      <p class="ex-sub">この銘柄はまだ登録されていません。読み取れた特徴から探しました。</p>
    <?php endif; ?>

    <?php /* is_beer なら必ず確認を求める。外したときに黙って誤った推薦を出さないのが
             この画面の肝(設計書 §3)。銘柄名が読めなかったときこそ確認が要る
             (最終レビュー C-3: 読めなかったときにだけ確認が出ない嘘が残っていた) */ ?>
    <form class="ex-ask" method="post" action="/try.php">
      <input type="hidden" name="upload_id" value="<?= (int)$uploadId ?>">
      <?php if ($branch === 'unknown' && !$result['brand_text']): ?>
        <span class="ex-sub" style="flex:1 1 100%">
          銘柄名は読み取れませんでした。<?= !empty($seed['StyleName']) ? e($seed['StyleName']) . ' に見えます。' : '' ?>合っていますか?
        </span>
      <?php endif; ?>
      <button class="ex-btn yes" name="confirm" value="yes">これで合っている</button>
      <button class="ex-btn"     name="confirm" value="no">ちがう</button>
    </form>

    <div class="ex-lbl">Similar</div>
    <?php foreach ($picked as $p): $r = $p['row'];
          list($col) = group_meta(style_group($r['FamilyName'] ?? '', $r['StyleName'] ?? '')); ?>
      <a class="ex-rec" href="/beer/detail/product.php?ProductID=<?= e($r['ProductID']) ?>">
        <?php /* 並ぶところは通常色(減彩)。ここで強調色を使わない */ ?>
        <span class="ex-dot" style="background:<?= e($col) ?>"></span>
        <span class="ex-nm">
          <b><?= e($r['ProductName']) ?></b>
          <span><?= e($r['MakerName'] ?: '') ?></span>
        </span>
        <span class="ex-abv"><?= fmt_unit($r['Alcohol'], '%') ?></span>
      </a>
    <?php endforeach; ?>

    <?php if (!$picked): ?><p class="ex-msg">似た銘柄が見つかりませんでした。</p><?php endif; ?>
    <div class="ex-acts" style="margin:26px auto 40px"><a class="ex-btn ghost" href="/try.php">もう一枚</a></div>
  </div>
<?php endif; ?>

</body>
</html>
