<?php
declare(strict_types=1);

require_once __DIR__ . '/common/nebula/helpers.php';
require_once __DIR__ . '/common/reco/visitor.php';
require_once __DIR__ . '/common/reco/repo.php';
require_once __DIR__ . '/common/reco/gate.php';
require_once __DIR__ . '/common/reco/identify.php';
require_once __DIR__ . '/common/reco/cascade.php';
require_once __DIR__ . '/common/reco/handle.php';
require_once __DIR__ . '/common/reco/age.php';

$visitorId = visitor_current();

// 酒類を扱うので、写真の受付より前に年齢を確認する(設計書 §12)
if (!age_confirmed($visitorId)) {
    header('Location: /agecheck.php?next=/try.php');
    exit;
}
$view = 'intake';   // intake / result / error
$msg  = '';
$result = $picked = $seed = [];
$eval = ['lines' => [], 'estimated' => false, 'source' => 'db'];
$uploadId = 0;
$imageWebPath = null;   // 表示部が参照する。保存しなかったときは null のまま
$dirNow = null; $dirOk = true; $dirReason = '';   // ⑤拡張(方向つき推薦)の状態
$candidates = [];   // 確度が中くらいで候補が複数あるときだけ入る(設計書 §3)
$existingNote = null;   // ④保管。この写真に既に残した感想があれば入る

// 確認の答え(confirm)を受け取る分岐。POST処理の先頭に置く
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    // CSRF対策：外部サイトからの POST だけで投票が記録されるのを防ぐ
    if (!csrf_check($visitorId, $_POST['csrf'] ?? null)) {
        // 合言葉が無い、または合わない。この投票は unknown_beer の信頼度の根拠になるので、
        // 何も言わずに飛ばすと「押したのに戻された」だけが利用者に伝わり、記録が消えたこと
        // に気づけない。起きたことだけを一言残して /try.php へ戻す
        // (agecheck.php:14-17 は画面を出し直すだけで失われる記録が無いので、そのままでよい)
        visitor_flash_set($visitorId, 'expired');
        header('Location: /try.php');
        exit;
    }

    $uid = (int)($_POST['upload_id'] ?? 0);
    // 他人の upload に投票できないようにする。この記録は unknown_beer の信頼度になり、
    // ゆくゆくは蔵へ渡す数字の根拠になるので、持ち主だけが答えられること
    // (前の計画のレビューで指摘され、繰り延べていたもの。修正ラウンド2で対応)
    $own = $uid > 0 ? upload_owned_by($uid, $visitorId) : null;
    if ($own) {
        $yes = ($_POST['confirm'] === 'yes');
        event_record($visitorId, $yes ? 'confirm_yes' : 'confirm_no', null, $uid);

        // 実際に何が起きたかを見て印を決める(修正ラウンド3: C-2)。判定は
        // common/reco/repo.php の reco_confirm_flash() に切り出してあり、検査対象になっている
        // (修正ラウンド4: ここに埋め込んだままだと単体テストできない、という指摘への対応)。
        visitor_flash_set($visitorId, reco_confirm_flash($yes, $own));
    }
    // 結果画面(?u=)へ戻す。いまは評価・感想・推薦が同じ画面に載っているので、
    // ?u= なしの入口へ戻すと確認以外のすべてから追い出される(最終レビュー指摘)。
    // ただし $uid が壊れている(0)ときは ?u=0 にせず、そのまま入口へ戻す
    header('Location: /try.php' . ($uid > 0 ? '?u=' . $uid : ''));
    exit;
}

// ④保管(設計書 2026-09-19 §4)。感想は任意。飛ばして⑤へ行ける
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note'])) {
    if (!csrf_check($visitorId, $_POST['csrf'] ?? null)) {
        visitor_flash_set($visitorId, 'expired');
        header('Location: /try.php'); exit;
    }
    $uid  = (int)($_POST['upload_id'] ?? 0);
    $body = trim((string)($_POST['note'] ?? ''));
    if ($body === '') {
        // 何も書かずに押した。空の行を作らない
        visitor_flash_set($visitorId, 'note_empty');
    } elseif (note_save($visitorId, $uid, $body) > 0) {
        visitor_flash_set($visitorId, 'note_saved');
    } else {
        // 書いてあったのに保存できなかった。理由を偽らない(最終レビュー指摘)
        visitor_flash_set($visitorId, 'note_failed');
    }
    header('Location: /try.php?u=' . $uid);
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
        $eval         = $h['eval'];
        $candidates   = $h['candidates'] ?? [];
        // 同じ写真の再送(キャッシュ経路)は既存の upload_id を再利用するので、
        // 既に残した感想があれば拾う($uploadId > 0 のときだけ)
        if ($uploadId > 0) {
            $existingNote = note_for_upload_owned($uploadId, $visitorId);
        }
    }
}

// ⑤拡張、および ④保管のあとに戻ってくるときの経路。
// 写真は上げ直さない。upload の行から同じ評価と推薦を組み立て直す
if ($view === 'intake' && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['u'])) {
    $u = upload_owned_by((int)$_GET['u'], $visitorId);
    if ($u && (int)$u['is_beer'] === 1) {
        $uploadId = (int)$u['upload_id'];

        // 候補から選び直す(設計書 §3「選んでから評価へ」)。
        // **upload_pick() が false のときは何も言わずに続けない。** 候補に無いものが
        // 来たということは、URL が書き換えられている。そのまま(選び直しは起きていない
        // 状態で)いつもの画面を出す
        if (isset($_GET['pick']) && preg_match('/^pr\d{4}$/', (string)$_GET['pick'])) {
            if (upload_pick($uploadId, $visitorId, (string)$_GET['pick'])) {
                event_record($visitorId, 'pick', (string)$_GET['pick'], $uploadId);
                // 選んだ銘柄で評価と推薦を作り直す
                header('Location: /try.php?u=' . $uploadId);
                exit;
            }
        }

        $dir = isset($_GET['more']) && isset(RECO_DIRS[$_GET['more']]) ? (string)$_GET['more'] : null;
        $v = reco_view_upload($u, $dir);
        $view     = 'result';
        $existingNote = note_for_upload_owned($uploadId, $visitorId);
        $imageWebPath = $u['image_path'] ? '/photo.php?u=' . $uploadId : null;
        $result = ['is_beer' => true, 'matched_product_id' => $u['product_id'],
                   'brand_text' => $u['brand_text'], 'brewery_text' => $u['brewery_text'],
                   'style_guess' => $u['style_guess'], 'color' => $u['color'],
                   'clarity' => $u['clarity'],
                   'confidence' => $u['confidence'] !== null ? (float)$u['confidence'] : null,
                   // 選び直したあとは、モデルの確度を利用者が選んだものに添えない
                   'picked_by_user' => (int)($u['picked_by_user'] ?? 0) === 1,
                   'error' => false];
        $seed = $v['seed']; $eval = $v['eval']; $picked = $v['picked'];
        $dirNow = $v['dir']; $dirOk = $v['dirOk']; $dirReason = $v['dirReason'];
        $candidates   = $v['candidates'] ?? [];
        if ($dir !== null) {
            // 押された方向は、そのまま蔵に渡す分析の中身になる(設計書 §5)
            event_record($visitorId, 'expand_' . $dir, null, $uploadId);
        }
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

<?php
// 確認・④保管・LINE連携への回答直後、お礼を出す(修正ラウンド2→3、Task 7で拡張)。
// URLの $_GET ではなく、その操作を実際に処理したときだけ立つ visitor.flash を見る
// (修正ラウンド3: C-1。URLを打つだけで「記録しました」が出て、実際には何も
// 記録していないのに記録したと伝えていた嘘を直した)。
//
// **GETで来たときだけ読む。** 写真アップロードの直接POSTは $view を 'result'/'error' に
// するが、それは今回の操作とは無関係の古い印が紛れ込む余地になる。④保管・LINE連携は
// どちらも「処理→redirect→GET」で結果画面(?u=)に戻ってくるので、GETで判定すれば
// 確認(→intake)・保管・LINE連携(→result)のどの戻り先でも正しく拾える
$flash = ($_SERVER['REQUEST_METHOD'] === 'GET') ? visitor_flash_take($visitorId) : null;
?>
<?php
// 画面に出してよい印の一覧。**ここだけが正**(最終レビュー指摘。入口用・結果用の
// 2か所に同じ一覧が重複していて、片方だけ直す事故が起きる形になっていた)。
// 知らない値のときは枠ごと出さない(通常の経路では起きないが、知らない状態では
// 何も言わないほうがこの画面の設計意図に忠実。修正ラウンド4: Important指摘)。
const FLASH_KNOWN = ['confirmed_queued', 'confirmed_known', 'confirmed_unread', 'corrected',
                     'note_saved', 'note_empty', 'note_failed',
                     'line_linked', 'line_merged'];
?>

<?php if ($view === 'intake' || $view === 'error'): ?>
  <?php if ($view === 'error'): ?>
    <div class="ex-wrap"><p class="ex-msg"><?= e($msg) ?></p></div>
  <?php endif; ?>
  <?php if ($flash !== null && in_array($flash, FLASH_KNOWN, true)): ?>
    <div class="ex-wrap ex-thanks">
      <?= reco_flash_body($flash) ?>
    </div>
  <?php endif; ?>
  <?php if ($flash === 'expired'): ?>
    <?php /* 合言葉が時間切れ・二重タブ等で合わなかったとき。起きたことだけを言う
             (画面が嘘をついた不具合を過去3回直しているため、事実だけに留める) */ ?>
    <div class="ex-wrap"><p class="ex-msg">時間が経ったため、この操作は受け付けられませんでした。もう一度お試しください。</p></div>
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
  <?php $mine = visitor_upload_count($visitorId); ?>
  <?php if ($mine > 0): ?>
    <?php /* 主役はビールの読み込み。履歴はその副産物なので、小さく下のほうに置く
             (設計書 §2-c)。0本の人には出さない ― 空の入口を作らない */ ?>
    <p class="ex-hist-link"><a href="/history.php">これまでの <?= (int)$mine ?>本 →</a></p>
  <?php endif; ?>
  <?php /* ②解析中の絵(設計書 §2-b)。フォームの外。送信は上に被さるだけで、
           通信をやり直さない。JS が動かない端末では出ないが、送信自体は
           noscript のボタンで通る */ ?>
  <div class="ex-warp" id="warp" hidden>
    <div class="ex-warp-t">味わいの宇宙に照らしています…</div>
  </div>
  <script src="/assets/js/warp.js?v=<?= (int)(@filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/warp.js') ?: time()) ?>"></script>
  <script>
  (function () {
    var f = document.getElementById('photo');
    var form = document.getElementById('intake');
    var status = document.getElementById('status');
    if (!f || !form) { return; }
    // 選んだ時点で送る。ボタンが無いぶん、待っていることは画面のテキストで示す。
    //
    // **絵が出せなくても、送信だけは必ず通す。** ここで例外が漏れると
    // 直後の form.submit() に届かず、「写真を選んだのに何も起きない」になる。
    // それは実機で一度踏んでいる壊れ方そのもの(2026-09-18、HEIC が弾かれていた件)。
    // canvas を封じるプライバシー拡張などで getContext が例外を投げる環境がある
    function waiting() {
      try {
      if (status) { status.hidden = false; }
      var w = document.getElementById('warp');
      if (w && typeof startWarp === 'function') {
        w.hidden = false;
        // 色は group_map() が :root に流し込んだものを読む。CSS に直値を書かない
        var cs = getComputedStyle(document.documentElement);
        var names = <?= json_encode(array_keys(group_map())) ?>;
        var colors = [];
        for (var i = 0; i < names.length; i++) {
          var c = cs.getPropertyValue('--g-' + names[i]).trim();
          if (c) { colors.push(c); }
        }
        var accent = cs.getPropertyValue('--h-' + names[0]).trim() || '#cfe6f2';
        startWarp({ mount: 'warp', colors: colors, accent: accent });
      }
      } catch (e) { /* 絵は諦める。送信は止めない */ }
    }
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
      <?php /* 選び直したあとは、利用者が選んだものにモデルの確度を添えない */ ?>
      <?php if ($result['confidence'] !== null && empty($result['picked_by_user'])): ?>
        <span class="ex-conf"><?= number_format((float)$result['confidence'], 2) ?></span>
      <?php endif; ?>
    </div>
    <div class="ex-sub">
      <?= e($result['brewery_text'] ?: ($seed['MakerName'] ?? '')) ?>
      <?php if (!empty($seed['StyleName'])): ?> · <?= e($seed['StyleName']) ?><?php endif; ?>
    </div>

    <?php $branch = identify_branch($result); ?>

    <?php /* 確度が中くらいで候補が複数あるとき、1本に決め打たず選ばせる(設計書 §3
             「選んでから評価へ」)。評価より前に置く。2件未満なら並べない */ ?>
    <?php if (count($candidates) >= 2): ?>
      <div class="ex-cands">
        <div class="ex-lbl">どれでしょうか</div>
        <p class="ex-sub">読み取りに確信が持てませんでした。選ぶと、その銘柄で評価し直します。</p>
        <?php foreach ($candidates as $c): ?>
          <a class="ex-rec" href="/try.php?u=<?= (int)$uploadId ?>&amp;pick=<?= e($c['ProductID']) ?>">
            <?php list($ccol) = group_meta(style_group($c['FamilyName'] ?? '', $c['StyleName'] ?? '')); ?>
            <span class="ex-dot" style="background:<?= e($ccol) ?>"></span>
            <span class="ex-nm">
              <b><?= e($c['ProductName']) ?></b>
              <span><?= e($c['MakerName'] ?: '') ?><?= !empty($c['StyleName']) ? ' · ' . e($c['StyleName']) : '' ?></span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($eval['lines'])): ?>
      <div class="ex-eval">
        <div class="ex-lbl">このビールは</div>
        <?php foreach ($eval['lines'] as $l): ?>
          <div class="ex-ev">
            <span class="ex-ev-k"><?= e($l['label']) ?></span>
            <span class="ex-ev-w"><?= e($l['word']) ?></span>
            <span class="ex-ev-c"><?= e($l['clause']) ?></span>
            <span class="ex-ev-v"><?= e($l['value']) ?><?php if (!empty($l['estimated'])): ?><span class="est-note" title="公式の数値が無いため、推定した値です">推定</span><?php endif; ?></span>
          </div>
        <?php endforeach; ?>
        <?php if ($eval['source'] === 'guess'): ?>
          <p class="ex-sub">数値は<b>ラベルからの推定</b>です。</p>
        <?php endif; ?>
        <p class="ex-sub">くらべているのは、このサイトに登録されている <?= count(reco_pool()) ?> 銘柄です。</p>
      </div>
    <?php endif; ?>
    <?php /* 「まだ登録がありません」は、評価の行の有無と関係なく出す(最終レビュー指摘)。
             推定3項目がすべて null だと $eval['lines'] は空になるが、
             未登録であること自体は $eval['source'] だけで決まる */ ?>
    <?php if ($eval['source'] === 'guess'): ?>
      <p class="ex-sub">この銘柄はまだ登録がありません。</p>
    <?php endif; ?>

    <?php /* is_beer なら必ず確認を求める。外したときに黙って誤った推薦を出さないのが
             この画面の肝(設計書 §3)。銘柄名が読めなかったときこそ確認が要る
             (最終レビュー C-3: 読めなかったときにだけ確認が出ない嘘が残っていた) */ ?>
    <form class="ex-ask" method="post" action="/try.php">
      <input type="hidden" name="upload_id" value="<?= (int)$uploadId ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf_issue($visitorId)) ?>">
      <span class="ex-sub" style="flex:1 1 100%">
        <?php if ($branch === 'unknown' && !$result['brand_text']): ?>
          銘柄名は読み取れませんでした。<?= !empty($seed['StyleName']) ? e($seed['StyleName']) . ' に見えます。' : '' ?>この評価で合っていますか?
        <?php else: ?>
          この評価で合っていますか?
        <?php endif; ?>
      </span>
      <button class="ex-btn yes" name="confirm" value="yes">これで合っている</button>
      <button class="ex-btn"     name="confirm" value="no">ちがう</button>
    </form>

    <?php /* 確認・④保管・LINE連携の結果を伝える印。確認(confirm)も note_save() /
             LINE連携の redirect も、いまはすべてこの結果画面(?u=)に戻ってくる
             (最終レビュー指摘: 確認の POST 先を ?u= 付きに直したのに合わせて、
             ここでも入口画面と同じ FLASH_KNOWN 全種を描く。片方にしか描かないと、
             今度は結果画面側でお礼が消える) */ ?>
    <?php if ($flash !== null && in_array($flash, FLASH_KNOWN, true)): ?>
      <div class="ex-thanks">
        <?= reco_flash_body($flash) ?>
      </div>
    <?php endif; ?>

    <form class="ex-note" method="post" action="/try.php">
      <input type="hidden" name="upload_id" value="<?= (int)$uploadId ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf_issue($visitorId)) ?>">
      <label class="ex-lbl" for="note">飲んだ感想(任意)</label>
      <textarea id="note" name="note" rows="3" maxlength="1000"
        placeholder="香り、飲んだ場所、また飲みたいか"><?= e($existingNote['body'] ?? '') ?></textarea>
      <div class="ex-acts">
        <button class="ex-btn" type="submit">残す</button>
      </div>
    </form>
    <?php /* 誘いは、いま使っている結果画面の中に置く(設計書 §4)。
             別の場所でURLを開かせると、その端末の匿名の記録が孤児になる
             (2026-09-19に実際に起きた) */ ?>
    <?php $lineLinked = visitor_line_linked($visitorId); ?>
    <?php if (empty($lineLinked)): ?>
      <p class="ex-sub">
        <a href="/line/login.php?back=<?= e(urlencode('/try.php?u=' . $uploadId)) ?>">LINE と繋ぐ</a>
        と、別の端末からでもこの記録を見られます。
      </p>
    <?php endif; ?>

    <div class="ex-lbl">次の一杯に</div>
    <?php
      // 「別の蔵を優先している」ことを言う。これがこの機能の判断そのもので、
      // 黙っていては伝わらない(設計書 §6)
      $seedMaker = $seed['MakerID'] ?? null;
      $others = 0;
      foreach ($picked as $p) { if (($p['row']['MakerID'] ?? null) !== $seedMaker) { $others++; } }
    ?>
    <?php if ($seedMaker !== null && $others > 0): ?>
      <p class="ex-sub">同じ味わいの方向で、<b>別の蔵のもの</b>を先に出しています。</p>
    <?php endif; ?>
    <?php foreach ($picked as $p): $r = $p['row'];
          list($col) = group_meta(style_group($r['FamilyName'] ?? '', $r['StyleName'] ?? ''));
          $why = reco_stage_label($p['stage'] ?? 0, $seed); ?>
      <a class="ex-rec" href="/beer/detail/product.php?ProductID=<?= e($r['ProductID']) ?>">
        <?php /* 並ぶところは通常色(減彩)。ここで強調色を使わない */ ?>
        <span class="ex-dot" style="background:<?= e($col) ?>"></span>
        <span class="ex-nm">
          <b><?= e($r['ProductName']) ?></b>
          <span><?= e($r['MakerName'] ?: '') ?><?= $why !== '' ? ' · ' . e($why) : '' ?></span>
        </span>
        <span class="ex-abv"><?= fmt_unit($r['Alcohol'], '%') ?></span>
      </a>
    <?php endforeach; ?>

    <?php if (!$picked): ?><p class="ex-msg">似た銘柄が見つかりませんでした。</p><?php endif; ?>

    <div class="ex-lbl">別の方向で探す</div>
    <div class="ex-dirs">
      <?php foreach (RECO_DIRS as $k => $d): ?>
        <a class="ex-btn ghost<?= $dirNow === $k ? ' on' : '' ?>"
           href="/try.php?u=<?= (int)$uploadId ?>&amp;more=<?= e($k) ?>"><?= e($d['label']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php if ($dirNow !== null && !$dirOk): ?>
      <p class="ex-msg">
        <?php if ($dirReason === 'no_seed_value'): ?>
          この写真からは、その方向で比べるための値を読み取れませんでした。
        <?php else: ?>
          この方向に当てはまる銘柄が、いまの登録の中にありませんでした。
        <?php endif; ?>
      </p>
    <?php elseif ($dirNow !== null): ?>
      <p class="ex-sub"><b><?= e(RECO_DIRS[$dirNow]['label']) ?></b> で選び直しました。</p>
    <?php endif; ?>

    <div class="ex-acts" style="margin:26px auto 40px"><a class="ex-btn ghost" href="/try.php">もう一枚</a></div>
  </div>
<?php endif; ?>

</body>
</html>
