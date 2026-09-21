<?php
declare(strict_types=1);
/*
 * LINE ログインの入口(設計書 2026-09-19 §4)。
 *
 * 受け取るのは利用者IDだけ(scope=profile)。表示名もアイコンも使わない。
 * **誘いは「使っている画面の中」からしか出さない。** 別の場所で URL を開かせると、
 * その端末の匿名の記録が孤児になる(2026-09-19 に実際に起きた)。
 */
require_once __DIR__ . '/../common/reco/visitor.php';
require_once __DIR__ . '/../common/reco/age.php';

$cfgPath = __DIR__ . '/../line_config.local.php';
if (!is_file($cfgPath)) { http_response_code(503); exit('LINE の設定がまだありません。'); }
$cfg = require $cfgPath;

$visitorId = visitor_current();

// 戻り先。**外部サイトへ飛ばされないよう、許可した形だけを受け取る**
// (年齢確認で正規表現が破られた前例があるため、ここは素直に前方一致で絞る)
$back = (string)($_GET['back'] ?? '/try.php');
if (!preg_match('#^/(try\.php|history\.php)(\?[A-Za-z0-9_=&%.\-]*)?$#', $back)) {
    $back = '/try.php';
}
setcookie('line_back', $back, [
    'expires' => time() + 600, 'path' => '/',
    'secure' => true, 'httponly' => true, 'samesite' => 'Lax',
]);

$state = bin2hex(random_bytes(16));
setcookie('line_state', $state, [
    'expires' => time() + 600, 'path' => '/',
    'secure' => true, 'httponly' => true, 'samesite' => 'Lax',
]);

$redirect = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . '/line/callback.php';
$url = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id'     => $cfg['channel_id'],
    'redirect_uri'  => $redirect,
    'state'         => $state,
    'scope'         => 'profile',
]);
header('Location: ' . $url, true, 302);
