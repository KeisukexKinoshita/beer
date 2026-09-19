<?php
/*
 * LINE ログインの戻り先。**spike。**
 *
 * 認可コードを受け取り、トークンに交換し、利用者IDを取り出して
 * visitor.line_user_id に書く。表示名もアイコンも取らない。
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
    out('連携をやめました', '<p>' . htmlspecialchars((string)$_GET['error_description'] ?? '') . '</p>');
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
    foreach (['upload', 'event'] as $t) {
        db()->prepare("UPDATE `$t` SET visitor_id = :old WHERE visitor_id = :new")
            ->execute([':old' => $existing, ':new' => $visitorId]);
    }
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

$n = db()->prepare("SELECT COUNT(*) FROM upload WHERE visitor_id = :v");
$n->execute([':v' => $visitorId]);

out('LINE とつながりました', sprintf(
    '<p>利用者ID(先頭のみ): <code>%s…</code></p><p>これまでに撮った写真: <b>%d</b> 枚</p><p>%s</p>',
    htmlspecialchars(substr($lineUserId, 0, 8)),
    (int)$n->fetchColumn(),
    $merged ? '以前の記録に合流しました。' : 'この端末の記録に紐づけました。'
));
