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

// --- 持ち主だけが自分の感想を読める(タスク7のレビュー指摘)---
// 同じ写真が送られると門番は前の人の upload_id をそのまま返す(費用を抑える仕組み)。
// 判定の結果は機械的な値なので共有してよいが、**感想は本人が書いた文章**である。
// 画面に出すときは note_for_upload_owned() を使う
// 直前の後片付けで消えているので、この検査のぶんを作り直す
$uid2 = upload_record($vid, ['is_beer' => true, 'matched_product_id' => null,
    'brand_text' => 'T2', 'brewery_text' => 'T2', 'style_guess' => null,
    'color' => null, 'clarity' => null, 'confidence' => 0.9,
    'abv_guess' => null, 'ibu_guess' => null, 'fruity_guess' => null,
    'model' => 'test'], str_repeat('b', 64), null);
note_save($vid, $uid2, '元の持ち主が書いた感想');
eq(note_for_upload_owned($uid2, $vid)['body'], '元の持ち主が書いた感想', '持ち主は自分の感想を読める');
eq(note_for_upload_owned($uid2, $other), null, '**他人の感想は読めない**');
eq(note_for_upload_owned(0, $vid), null, 'upload_id が0なら読まない');
eq(note_for_upload_owned(-1, $vid), null, '負の upload_id でも読まない');
eq(note_for_upload_owned(99999999, $vid), null, '存在しない upload_id なら読まない');

db()->prepare("DELETE FROM note WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);
db()->prepare("DELETE FROM upload WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);

// --- 種類で選んで取り出す印(最終レビュー指摘: history.php から LINE 連携したとき、
//     印を描けるのが try.php だけで、確認の文が出ない/後日 try.php で唐突に出る、
//     という不具合が起きていた) ---
visitor_flash_set($vid, 'line_linked');
eq(visitor_flash_take_if($vid, ['note_saved']), null, '種類が違えば取り出さない');
eq(visitor_flash_take_if($vid, ['line_linked', 'line_merged']), 'line_linked', '種類が合えば取り出す');
eq(visitor_flash_take_if($vid, ['line_linked']), null, '一度取り出したら残らない');
