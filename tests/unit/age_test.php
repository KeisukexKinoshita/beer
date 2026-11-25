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
    'http:/evil.com', 'javascript:alert(1)', '/try.php?x=1', '/evil.php',
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

// --- 合言葉は使い捨てない(タブ・戻るボタンで黙って消えない) ---
$t = csrf_issue($vid);
eq(strlen($t), 64,             '合言葉は64文字');
eq(csrf_issue($vid), $t,       'csrf_issue を2回呼んでも同じ合言葉が返る(上書きしない)');
eq(csrf_check($vid, $t . 'x'), false, '1文字違えば通らない');
eq(csrf_check($vid, $t),       true,  '正しい合言葉は通る');
eq(csrf_check($vid, $t),       true,  '2度目も通る(使い捨てない)');

$other = 'oth' . str_repeat('0', 29);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen) VALUES (:v, NOW(), NOW())
               ON DUPLICATE KEY UPDATE csrf_token = NULL")->execute([':v' => $other]);
eq(csrf_check($other, $t),     false, '他人の合言葉は通らない');

db()->prepare("DELETE FROM visitor WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);


// === 全公開ページの年齢確認(幕)。ここから先は「何も書かずに判定できること」の検査 ===

// --- 戻り先: 公開ページのパスと、ID つきの詳細だけを許す ---
foreach (['/about.php', '/beer/products.php', '/brewery/makers.php', '/style/styles.php',
          '/beer/detail/product.php?ProductID=pr0202',
          '/brewery/detail/maker.php?MakerID=mk0000',
          '/style/detail/style.php?StyleID=st0004'] as $good) {
    eq(age_next($good), $good, '公開ページへは戻せる: ' . $good);
}
foreach ([
    '/beer/detail/product.php', '/beer/detail/product.php?ProductID=',
    '/beer/detail/product.php?ProductID=pr1&x=1', '/beer/detail/product.php?ProductID=../etc',
    '/beer/detail/product.php?MakerID=mk0000', '/brewery/detail/maker.php?ProductID=pr0202',
    '/beer/detail/product.php?ProductID=pr1%0d%0aSet-Cookie:x=1', "/beer/detail/product.php?ProductID=pr1\n",
    '/beer/detail/product.php?ProductID=//evil.com', '/style/detail/style.php?StyleID=st0004#x',
    '/privacy.php?x=1', '/agecheck.php',
] as $bad) {
    eq(age_next($bad), '/index.php', '許可していない詳細・クエリは落とす: ' . json_encode($bad));
}

// --- いま見ているURLから戻り先を作る(余計なクエリは捨て、同じページに戻す) ---
eq(age_back_url('/beer/products.php?sort=ibu&q=x'), '/beer/products.php', '一覧は余計なクエリを捨てる');
eq(age_back_url('/index.php?utm_source=a'),         '/index.php',          'トップも同様');
eq(age_back_url('/'),                               '/',                   'ルートはそのまま');
eq(age_back_url('/beer/detail/product.php?ProductID=pr0202&utm=1'), '/beer/detail/product.php?ProductID=pr0202', '詳細は ID だけ残す');
eq(age_back_url('/style/detail/style.php?StyleID=st0004'), '/style/detail/style.php?StyleID=st0004', '同上');
eq(age_back_url('/beer/detail/product.php?ProductID=../x'), '/index.php', '不正な ID はトップへ');
eq(age_back_url('/beer/detail/product.php'),        '/index.php',          'ID が無ければトップへ');
eq(age_back_url('/unknown.php?x=1'),                '/index.php',          '知らないパスはトップへ');
eq(age_back_url(''),                                '/index.php',          '空はトップへ');

// --- 幕を出すかの判定。読むだけで、行を作らない ---
$cnt = fn() => (int)db()->query("SELECT COUNT(*) FROM visitor")->fetchColumn();
$before = $cnt();
eq(age_gate_needed(null), true,  'Cookie が無ければ幕を出す');
eq(age_gate_needed('zzz'), true, '形の違う Cookie でも幕を出す');
eq(age_gate_needed(str_repeat('a', 32)), true, '行の無い訪問者は幕を出す');
eq($cnt(), $before, '判定しても visitor の行は増えない');

$vid = 'ace' . str_repeat('0', 29);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen) VALUES (:v, NOW(), NOW())
               ON DUPLICATE KEY UPDATE age_confirmed = 0")->execute([':v' => $vid]);
eq(age_gate_needed($vid), true, '行はあっても未確認なら幕を出す');
age_confirm($vid);
eq(age_gate_needed($vid), false, '写真判定側などで確認済みなら幕を出さない(記憶を共有)');
db()->prepare("DELETE FROM visitor WHERE visitor_id = :v")->execute([':v' => $vid]);

// --- 幕の「はい」の合言葉(Cookie と送信値の一致。行が無くても使える) ---
$t = age_tok_new();
ok((bool)preg_match('/^[0-9a-f]{32}$/', $t), '合言葉は32桁の16進');
ok($t !== age_tok_new(), '毎回違う値');
eq(age_tok_check($t, $t), true,  '一致すれば通る');
eq(age_tok_check($t, str_repeat('0', 32)), false, '違えば通らない');
eq(age_tok_check(null, $t), false, 'Cookie が無ければ通らない');
eq(age_tok_check($t, null), false, '送信値が無ければ通らない');
eq(age_tok_check('', ''),   false, '空同士は通さない');
eq(age_tok_check('abc', 'abc'), false, '形の違うものは一致しても通さない');
eq(age_tok_check([$t], $t), false, '配列は通さない');
