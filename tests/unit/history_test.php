<?php
declare(strict_types=1);
/*
 * db() が $_SERVER['DOCUMENT_ROOT']/db_config.local.php を見るので、ここで先に設定する
 * (note_test.php / handle_test.php と同じ理由。`php tests/unit/run.php history` のように
 * 単独で動かすと、このファイルより前に DOCUMENT_ROOT を設定する他のテストが読み込まれない)。
 */
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);

require_once dirname(__DIR__, 2) . '/common/nebula/helpers.php';
require_once dirname(__DIR__, 2) . '/common/reco/repo.php';

$vid = str_repeat('c', 32);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen)
               VALUES (:v, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")
    ->execute([':v' => $vid]);
db()->prepare("DELETE FROM note WHERE visitor_id = :v")->execute([':v' => $vid]);
db()->prepare("DELETE FROM upload WHERE visitor_id = :v")->execute([':v' => $vid]);

eq(visitor_upload_count($vid), 0, '最初は0本');
eq(visitor_history($vid), [], '最初は空');

$base = ['matched_product_id' => null, 'brewery_text' => 'B', 'style_guess' => null,
         'color' => null, 'clarity' => null, 'confidence' => 0.9,
         'abv_guess' => null, 'ibu_guess' => null, 'fruity_guess' => null, 'model' => 'test'];

$u1 = upload_record($vid, array_merge($base, ['is_beer' => true, 'brand_text' => '1本目']),
                    str_repeat('1', 64), null);
$u2 = upload_record($vid, array_merge($base, ['is_beer' => true, 'brand_text' => '2本目']),
                    str_repeat('2', 64), null);
// ビール以外は数にも一覧にも入れない(撮り直しを案内しただけのもの)
upload_record($vid, array_merge($base, ['is_beer' => false, 'brand_text' => null]),
              str_repeat('3', 64), null);

eq(visitor_upload_count($vid), 2, 'ビールと判定できたものだけを数える');
$h = visitor_history($vid);
eq(count($h), 2, '一覧もビールだけ');
eq($h[0]['brand_text'], '2本目', '新しい順に並ぶ');

note_save($vid, $u1, '柑橘が強い');
$h = visitor_history($vid);
eq($h[1]['note_body'], '柑橘が強い', '感想が一緒に取れる');
eq($h[0]['note_body'], null, '感想が無ければ null');

// 他人の記録は混ざらない
$other = str_repeat('d', 32);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen)
               VALUES (:v, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")
    ->execute([':v' => $other]);
eq(visitor_history($other), [], '他人の記録は見えない');

db()->prepare("DELETE FROM note WHERE visitor_id = :v")->execute([':v' => $vid]);
db()->prepare("DELETE FROM upload WHERE visitor_id = :v")->execute([':v' => $vid]);
