<?php
/*
 * LINE ログインの入口。**spike(実現可能か確かめるためのもの)。**
 *
 * 確かめたいこと:
 *  (1) dev の Basic認証を挟んだまま、LINE からの戻りが通るか
 *  (2) 返る利用者IDが安定しているか
 *  (3) それまでの匿名の記録が引き継がれるか
 *
 * 受け取るのは利用者IDだけ(scope=profile)。表示名もアイコンも使わない(設計書 §4)。
 */
require_once __DIR__ . '/../common/reco/visitor.php';

$cfgPath = __DIR__ . '/../line_config.local.php';
if (!is_file($cfgPath)) { http_response_code(503); exit('LINE の設定がまだありません。'); }
$cfg = require $cfgPath;

$visitorId = visitor_current();

// 戻ってきたときに「自分が出した要求か」を確かめるための値。
// 外部サイトからの遷移で戻るので、Cookie は SameSite=Lax で届く
$state = bin2hex(random_bytes(16));
setcookie('line_state', $state, [
    'expires'  => time() + 600,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
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
