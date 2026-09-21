<?php
declare(strict_types=1);
/*
 * db() が $_SERVER['DOCUMENT_ROOT']/db_config.local.php を見るので、ここで先に設定する
 * (handle_test.php と同じ理由。`php tests/unit/run.php note` のように単独で動かすと、
 * このファイルより前に DOCUMENT_ROOT を設定する他のテストが読み込まれないため)。
 */
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);

require_once dirname(__DIR__, 2) . '/common/nebula/helpers.php';
require_once dirname(__DIR__, 2) . '/common/reco/repo.php';

$vid = str_repeat('e', 32);
$other = str_repeat('f', 32);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen)
               VALUES (:v, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")
    ->execute([':v' => $vid]);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen)
               VALUES (:v, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")
    ->execute([':v' => $other]);

// 後片付けを先にやる(前回が途中で落ちていても二重にならない)
db()->prepare("DELETE FROM note WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);
db()->prepare("DELETE FROM upload WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);

$uid = upload_record($vid, ['is_beer' => true, 'matched_product_id' => null,
    'brand_text' => 'T', 'brewery_text' => 'T', 'style_guess' => null,
    'color' => null, 'clarity' => null, 'confidence' => 0.9,
    'abv_guess' => null, 'ibu_guess' => null, 'fruity_guess' => null,
    'model' => 'test'], str_repeat('a', 64), null);

eq(note_for_upload($uid), null, '書く前は感想が無い');
eq(visitor_note_count($vid), 0, '書く前は0件');

$id = note_save($vid, $uid, '柑橘の香りが強かった');
ok($id > 0, '感想を保存できる');
eq(note_for_upload($uid)['body'], '柑橘の香りが強かった', '保存した本文が読める');
eq(visitor_note_count($vid), 1, '1件になる');

// 同じ写真に2回書いたら**上書き**(写真1枚につき感想1件)
note_save($vid, $uid, '思ったより苦くなかった');
eq(note_for_upload($uid)['body'], '思ったより苦くなかった', '書き直すと上書きされる');
eq(visitor_note_count($vid), 1, '書き直しても件数は増えない');

// 空文字は保存しない(空の行を作らない)
eq(note_save($vid, $uid, '   '), 0, '空白だけなら保存しない');
eq(note_for_upload($uid)['body'], '思ったより苦くなかった', '空白で上書きされない');

// 他人の写真には書けない
eq(note_save($other, $uid, '横から書く'), 0, '他人の写真には書けない');
eq(note_for_upload($uid)['body'], '思ったより苦くなかった', '他人の書き込みで変わらない');

// 後片付け
db()->prepare("DELETE FROM note WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);
db()->prepare("DELETE FROM upload WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);
