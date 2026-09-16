<?php
declare(strict_types=1);

require_once __DIR__ . '/common/nebula/helpers.php';
require_once __DIR__ . '/common/reco/visitor.php';
require_once __DIR__ . '/common/reco/repo.php';
require_once __DIR__ . '/common/reco/gate.php';
require_once __DIR__ . '/common/reco/identify.php';
require_once __DIR__ . '/common/reco/cascade.php';

$visitorId = visitor_current();
$view = 'intake';   // intake / result / error
$msg  = '';
$result = $picked = $seed = [];
$uploadId = 0;
$imageWebPath = null;   // 表示部が参照する。保存しなかったときは null のまま

// 確認の答え(confirm)を受け取る分岐。POST処理の先頭に置く
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    event_record($visitorId,
        $_POST['confirm'] === 'yes' ? 'confirm_yes' : 'confirm_no',
        null, (int)($_POST['upload_id'] ?? 0));
    header('Location: /try.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $f = $_FILES['photo'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $view = 'error'; $msg = '写真を受け取れませんでした。';
    } else {
        $hash = hash_file('sha256', $f['tmp_name']);
        $mime = mime_content_type($f['tmp_name']);
        $g = gate_check(gate_context($visitorId, $mime, (int)$f['size'], $hash));

        // reco_record() は (upload_id, position) に一意制約(uq_reco_pos)がある。
        // 新規に判定した upload_id のときだけ記録してよい。キャッシュ経路(同じ写真の
        // 再送)は既存の upload_id を再利用するので、ここで再度書くと制約違反で落ちる。
        $isFreshIdentify = false;

        if (!$g['ok'] && $g['reason'] === 'cached') {
            // 同じ写真。APIを呼ばずに前回の結果を使う
            $prev = upload_by_hash($hash);
            $imageWebPath = $prev['image_path'] ? '/' . $prev['image_path'] : null;
            $result = ['is_beer' => (bool)$prev['is_beer'], 'matched_product_id' => $prev['product_id'],
                       'brand_text' => $prev['brand_text'], 'brewery_text' => $prev['brewery_text'],
                       'style_guess' => $prev['style_guess'], 'color' => $prev['color'],
                       'clarity' => $prev['clarity'],
                       'confidence' => $prev['confidence'] !== null ? (float)$prev['confidence'] : null,
                       'error' => false];
            $uploadId = (int)$prev['upload_id'];
            $view = 'result';
        } elseif (!$g['ok']) {
            $view = 'error'; $msg = gate_message($g['reason']);
        } else {
            $result = identify_call($f['tmp_name'], reco_catalog(), reco_style_catalog());
            // API失敗(is_beer=null, error=true)と「ビール以外」(is_beer=false, error=false)
            // を取り違えない。$result['error'] を先に見る。identify_branch() は両方とも
            // 'not_beer' として畳んでしまうので、ここでは使わない。
            if (!empty($result['error'])) {
                $view = 'error'; $msg = 'いま混み合っています。しばらくしてからお試しください。';
            } else {
                // ビール以外は画像を保存しない(設計書 §9)
                $path = null;
                if ($result['is_beer'] === true) {
                    $path = 'img/upload/' . $hash . '.jpg';
                    identify_shrink($f['tmp_name'], __DIR__ . '/' . $path);
                    $imageWebPath = '/' . $path;
                }
                $uploadId = upload_record($visitorId, $result, $hash, $path);
                if ($result['is_beer'] === true && !$result['matched_product_id'] && $result['brand_text']) {
                    unknown_bump($result['brand_text'], $result['brewery_text']);
                }
                $isFreshIdentify = true;
                $view = 'result';
            }
        }

        if ($view === 'result' && $result['is_beer'] === true) {
            $seed = $result['matched_product_id']
                ? beer_by_id($result['matched_product_id'])
                : ['ProductID' => null, 'StyleID' => $result['style_guess'],
                   'FamilyName' => '', 'StyleName' => '', 'MakerID' => null, 'Alcohol' => null];
            if (!$result['matched_product_id'] && $result['style_guess']) {
                $s = style_by_id($result['style_guess']);
                $seed['FamilyName'] = $s['FamilyName'] ?? '';
                $seed['StyleName']  = $s['StyleName'] ?? '';
            }
            $picked = reco_pick($seed, reco_pool());
            if ($isFreshIdentify) {
                reco_record($uploadId, $picked);
            }
            event_record($visitorId, 'reco_view', $result['matched_product_id'], $uploadId);
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
<link rel="stylesheet" href="/assets/css/exposure.css">
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
  <form class="ex-intake" method="post" enctype="multipart/form-data">
    <div class="ex-ring"><span>◎</span></div>
    <h1>飲んだビールの写真から</h1>
    <p>ラベルが写っていれば、似た一本を探します</p>
    <div class="ex-acts">
      <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
             capture="environment" hidden onchange="this.form.submit()">
      <button class="ex-btn" type="button" onclick="document.getElementById('photo').click()">写真をえらぶ</button>
      <a class="ex-btn ghost" href="/beer/products.php">名前でさがす</a>
    </div>
    <div class="ex-meta"><?= count(reco_pool()) ?> BEERS</div>
  </form>

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

    <?php if (identify_branch($result) === 'confirm'): ?>
      <form class="ex-ask" method="post" action="/try.php">
        <input type="hidden" name="upload_id" value="<?= (int)$uploadId ?>">
        <button class="ex-btn yes" name="confirm" value="yes">これで合っている</button>
        <button class="ex-btn"     name="confirm" value="no">ちがう</button>
      </form>
    <?php else: ?>
      <p class="ex-sub">この銘柄はまだ登録されていません。読み取れた特徴から探しました。</p>
    <?php endif; ?>

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
