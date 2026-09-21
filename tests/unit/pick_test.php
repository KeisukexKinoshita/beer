<?php
declare(strict_types=1);
/*
 * db() が $_SERVER['DOCUMENT_ROOT']/db_config.local.php を見るので、ここで先に設定する
 * (note_test.php / handle_test.php と同じ理由。`php tests/unit/run.php pick` のように
 * 単独で動かすと、このファイルより前に DOCUMENT_ROOT を設定する他のテストが
 * 読み込まれないため)。
 */
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);

require_once dirname(__DIR__, 2) . '/common/nebula/helpers.php';
require_once dirname(__DIR__, 2) . '/common/reco/repo.php';

$vid   = str_repeat('7', 32);
$other = str_repeat('8', 32);
foreach ([$vid, $other] as $v) {
    db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen)
                   VALUES (:v, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")
        ->execute([':v' => $v]);
}
db()->prepare("DELETE FROM upload WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);

// 実在する銘柄を2つ借りる(このテストは products を書き換えない)
$ids = db()->query("SELECT ProductID FROM products ORDER BY ProductID LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
ok(count($ids) === 2, '検査に使う銘柄が2つある');

$uid = upload_record($vid, ['is_beer' => true, 'matched_product_id' => $ids[0],
    'brand_text' => 'T', 'brewery_text' => 'T', 'style_guess' => null,
    'color' => null, 'clarity' => null, 'confidence' => 0.6,
    'abv_guess' => null, 'ibu_guess' => null, 'fruity_guess' => null,
    'candidates' => $ids, 'model' => 'test'], str_repeat('9', 64), null);

$u = upload_owned_by($uid, $vid);
eq($u['candidates'], implode(',', $ids), '候補が保存される');
eq((int)$u['picked_by_user'], 0, '最初は利用者が選んでいない');

// 候補の中からなら選べる
eq(upload_pick($uid, $vid, $ids[1]), true, '候補の中からは選べる');
$u = upload_owned_by($uid, $vid);
eq($u['product_id'], $ids[1], '選んだ銘柄が入る');
eq((int)$u['picked_by_user'], 1, '利用者が選んだ印が付く');

// 候補に無いものは選べない(URLを書き換えて任意の銘柄にすり替えられないこと)
$outsider = db()->query("SELECT ProductID FROM products WHERE ProductID NOT IN ('" .
            implode("','", $ids) . "') ORDER BY ProductID LIMIT 1")->fetchColumn();
eq(upload_pick($uid, $vid, (string)$outsider), false, '候補に無いものは選べない');
eq(upload_owned_by($uid, $vid)['product_id'], $ids[1], 'はねられたあとも値は変わらない');

// 他人の写真は選び直せない
eq(upload_pick($uid, $other, $ids[0]), false, '他人の写真は選び直せない');
eq(upload_owned_by($uid, $vid)['product_id'], $ids[1], '他人の操作で値は変わらない');

db()->prepare("DELETE FROM upload WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);
