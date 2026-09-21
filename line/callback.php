<?php
/*
 * LINE ログインの戻り先(設計書 2026-09-19 §4)。
 *
 * 認可コードを受け取り、トークンに交換し、利用者IDを取り出して
 * visitor.line_user_id に書く。表示名もアイコンも取らない。
 *
 * 既存の LINE 利用者と分かったときは、いまの端末の匿名の記録(upload / event / note)を
 * 既存の行へ合流させ、**使っていた画面(back)へそのまま戻す**。
 */
require_once __DIR__ . '/../common/reco/visitor.php';
require_once __DIR__ . '/../common/reco/repo.php';

header('Content-Type: text/html; charset=utf-8');
$cfgPath = __DIR__ . '/../line_config.local.php';
if (!is_file($cfgPath)) { http_response_code(503); exit('LINE の設定がまだありません。'); }
$cfg = require $cfgPath;

function out(string $title, string $body): void {
    echo "<!doctype html><meta charset=utf-8><meta name=viewport content='width=device-width,initial-scale=1'>";
    echo "<title>", htmlspecialchars($title), "</title>";
    echo "<body style='background:#000;color:#fff;font-family:sans-serif;padding:24px;line-height:1.9'>";
    echo "<h1 style='font-size:18px'>", htmlspecialchars($title), "</h1>", $body;
    echo "<p><a href='/try.php' style='color:#86adbd'>← 戻る</a></p></body>";
    exit;
}

// 利用者が同意しなかった場合など
if (isset($_GET['error'])) {
    out('連携をやめました', '<p>' . htmlspecialchars((string)($_GET['error_description'] ?? '')) . '</p>');
}

$state = $_GET['state'] ?? '';
if ($state === '' || !hash_equals((string)($_COOKIE['line_state'] ?? ''), (string)$state)) {
    out('確認できませんでした', '<p>要求の照合に失敗しました。もう一度お試しください。</p>');
}
setcookie('line_state', '', ['expires' => time() - 3600, 'path' => '/']);

$code = $_GET['code'] ?? '';
if ($code === '') { out('コードがありません', '<p>もう一度お試しください。</p>'); }

$visitorId = visitor_current();
$redirect  = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . '/line/callback.php';

// 認可コード → アクセストークン
$ch = curl_init('https://api.line.me/oauth2/v2.1/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_POSTFIELDS => http_build_query([
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'redirect_uri'  => $redirect,
        'client_id'     => $cfg['channel_id'],
        'client_secret' => $cfg['channel_secret'],
    ]),
]);
$res  = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$tok = json_decode((string)$res, true);
if ($http !== 200 || empty($tok['access_token'])) {
    // 鍵が混ざらないよう、返ってきた本文は出さない
    out('トークンを取得できませんでした', '<p>HTTP ' . (int)$http . '</p>');
}

// アクセストークン → 利用者ID(表示名・アイコンは使わない)
$ch = curl_init('https://api.line.me/v2/profile');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tok['access_token']],
]);
$res  = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$prof = json_decode((string)$res, true);
if ($http !== 200 || empty($prof['userId'])) {
    out('利用者IDを取得できませんでした', '<p>HTTP ' . (int)$http . '</p>');
}
$lineUserId = (string)$prof['userId'];

// 同じ LINE 利用者が別の端末で入ってきた場合、既存の行がある。
// その場合は**既存の行に乗り換える**(過去の記録を引き継ぐ)
$st = db()->prepare("SELECT visitor_id FROM visitor WHERE line_user_id = :l LIMIT 1");
$st->execute([':l' => $lineUserId]);
$existing = $st->fetchColumn();

$merged = false;
if ($existing && $existing !== $visitorId) {
    // いまの匿名の記録を、既存の行へ付け替える
    foreach (['upload', 'event', 'note'] as $t) {
        db()->prepare("UPDATE `$t` SET visitor_id = :old WHERE visitor_id = :new")
            ->execute([':old' => $existing, ':new' => $visitorId]);
    }
    // **消す前に、この端末で持っていた状態を引き継ぐ。**
    // 引き継がないと、LINE と繋いだ直後に年齢確認へ戻される(spike の既知の欠陥)。
    //
    // 年齢確認の判定(common/reco/age.php の age_confirmed())が実際に見るのは
    // `age_confirmed` 列(TINYINT 0/1)であって、`age_confirmed_at` ではない。
    // 日時だけを引き継いでも `age_confirmed` が 0 のままなら欠陥は直らないため、両方を
    // 引き継ぐ(GREATEST で「どちらかが確認済みなら確認済み」にする。片方でも
    // 1 であれば 1 になり、両方 0 ならそのまま 0)。
    db()->prepare(
        "UPDATE visitor AS dst,
                (SELECT age_confirmed, age_confirmed_at FROM visitor WHERE visitor_id = :src) AS s
         SET dst.age_confirmed = GREATEST(dst.age_confirmed, s.age_confirmed),
             dst.age_confirmed_at = COALESCE(dst.age_confirmed_at, s.age_confirmed_at)
         WHERE dst.visitor_id = :dst")
        ->execute([':src' => $visitorId, ':dst' => $existing]);
    // `flash` と `csrf_token` は引き継がない。どちらもその場限りの値で、
    // 繋いだ直後に発行し直されるため。
    db()->prepare("DELETE FROM visitor WHERE visitor_id = :v")->execute([':v' => $visitorId]);
    setcookie(VISITOR_COOKIE, (string)$existing, [
        'expires' => time() + VISITOR_TTL, 'path' => '/',
        'secure' => true, 'httponly' => true, 'samesite' => 'Lax',
    ]);
    $visitorId = (string)$existing;
    $merged = true;
} else {
    db()->prepare("UPDATE visitor SET line_user_id = :l WHERE visitor_id = :v")
        ->execute([':l' => $lineUserId, ':v' => $visitorId]);
}

// **誘いを出した画面(back)へそのまま戻す。** spike は利用者IDの先頭を出す
// 独自の画面で止まっていたが、それでは④保管(→⑤拡張)の続きに戻れない
$back = (string)($_COOKIE['line_back'] ?? '/try.php');
if (!preg_match('#^/(try\.php|history\.php)(\?[A-Za-z0-9_=&%.\-]*)?$#', $back)) {
    $back = '/try.php';
}
setcookie('line_back', '', ['expires' => time() - 3600, 'path' => '/']);
visitor_flash_set($visitorId, $merged ? 'line_merged' : 'line_linked');
header('Location: ' . $back, true, 302);
exit;
