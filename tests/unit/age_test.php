<?php
$_SERVER['DOCUMENT_ROOT'] = '/workspace/tool/beer';
require_once __DIR__ . '/../../common/reco/age.php';
require_once __DIR__ . '/../../common/reco/repo.php';

$vid = 'age' . str_repeat('0', 29);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen) VALUES (:v, NOW(), NOW())
               ON DUPLICATE KEY UPDATE age_confirmed = 0")->execute([':v' => $vid]);

eq(age_confirmed($vid), false, '初めての訪問者は未確認');
age_confirm($vid);
eq(age_confirmed($vid), true,  '確認したら記録される');
eq(age_confirmed('存在しない訪問者' . str_repeat('0', 16)), false, '未知の訪問者は未確認');

db()->prepare("DELETE FROM visitor WHERE visitor_id = :v")->execute([':v' => $vid]);

// --- 戻り先は、許可した内部のパスだけ受け付ける ---
// (形の検査では /\evil.com が通り、ブラウザが \ を / と読むため外部誘導になった)
eq(age_next('/try.php'),   '/try.php',   '許可した内部パスは通す');
eq(age_next('/index.php'), '/index.php', '同上');
eq(age_next('/'),          '/',          '同上');
foreach ([
    'https://example.com', '//evil.com', '///evil.com', '/\\evil.com',
    '/%2F%2Fevil.com', "/\t//evil.com", "/try.php\r\nSet-Cookie: x=1",
    'http:/evil.com', 'javascript:alert(1)', '/try.php?x=1', '/beer/products.php',
    '', null, ['/try.php'],
] as $bad) {
    ok(age_next($bad) === '/index.php',
       '許可していない戻り先は /index.php に落とす: ' . (is_array($bad) ? '(配列)' : var_export($bad, true)));
}

// --- 合言葉が無ければ通さない(外部サイトからの POST を防ぐ) ---
$vid = 'csr' . str_repeat('0', 29);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen) VALUES (:v, NOW(), NOW())
               ON DUPLICATE KEY UPDATE csrf_token = NULL")->execute([':v' => $vid]);

eq(csrf_check($vid, 'なにか'), false, '発行していない合言葉は通らない');
eq(csrf_check($vid, null),     false, '空でも通らない');
eq(csrf_check($vid, ''),       false, '空文字でも通らない');

$t = csrf_issue($vid);
eq(strlen($t), 64,             '合言葉は64文字');
eq(csrf_check($vid, $t . 'x'), false, '1文字違えば通らない');
eq(csrf_check($vid, $t),       true,  '正しい合言葉は通る');
eq(csrf_check($vid, $t),       false, '**同じ合言葉は2度使えない**');

$t2 = csrf_issue($vid);
$other = 'oth' . str_repeat('0', 29);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen) VALUES (:v, NOW(), NOW())
               ON DUPLICATE KEY UPDATE csrf_token = NULL")->execute([':v' => $other]);
eq(csrf_check($other, $t2),    false, '他人の合言葉は使えない');

db()->prepare("DELETE FROM visitor WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);
