<?php
declare(strict_types=1);
/*
 * common/reco/handle.php の reco_handle_upload() を検査する(最終レビュー指摘:
 * 金・嘘・写真の判断がすべてそこにあるのに、検査がゼロだった)。
 *
 * ローカルのテストDB(products 200 / maker 40 / style 102)を使う。db() が
 * $_SERVER['DOCUMENT_ROOT']/db_config.local.php を見るので、ここで先に設定する。
 * upload/recommendation/event/unknown_beer/visitor は使い捨てのテスト用データなので、
 * このファイルの実行のたびに空にしてから始める(再実行しても結果が変わらないように)。
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2);

require_once __DIR__ . '/../../common/reco/handle.php';

foreach (['event', 'recommendation', 'unknown_beer', 'upload', 'visitor'] as $t) {
    db()->exec("DELETE FROM {$t}");
}

// ---- テスト用の小道具 -----------------------------------------------------

function reco_ht_visitor(): string { return bin2hex(random_bytes(16)); }

function reco_ht_file(string $name): array
{
    $path = __DIR__ . '/../../data/sample/' . $name;
    return ['tmp_name' => $path, 'size' => filesize($path)];
}

/** identify_call() が読む応答の形(is_beer 等)を組み立てる */
function reco_ht_resp(array $overrides = []): array
{
    $base = ['is_beer' => true, 'brand_text' => null, 'brewery_text' => null,
             'matched_product_id' => null, 'style_guess' => null,
             'color' => 5, 'clarity' => 2, 'confidence' => 0.9];
    return ['model' => 'claude-sonnet-5',
            'content' => [['type' => 'text', 'text' => json_encode(array_merge($base, $overrides))]]];
}

/** 呼ばれた回数を $calls に積みながら固定の応答を返す transport */
function reco_ht_transport(array $resp, array &$calls): callable
{
    return function (string $path, string $prompt) use ($resp, &$calls): array {
        $calls[] = 1;
        return $resp;
    };
}

/** 常にAPI自体が失敗した形の応答を返す transport(identify_call が1回再試行する) */
function reco_ht_error_transport(array &$calls): callable
{
    return function (string $path, string $prompt) use (&$calls): array {
        $calls[] = 1;
        return ['type' => 'error', 'error' => ['type' => 'api_error', 'message' => 'boom']];
    };
}

/** バースト制限(20秒)に引っかからないよう、直前の記録の時刻をずらす。
 *  同じ visitor で2回続けて reco_handle_upload() を呼ぶテストで使う */
function reco_ht_unburst(string $visitorId): void
{
    $st = db()->prepare("UPDATE upload SET created_at = DATE_SUB(created_at, INTERVAL 30 SECOND)
                          WHERE visitor_id = :v ORDER BY upload_id DESC LIMIT 1");
    $st->execute([':v' => $visitorId]);
}

function reco_ht_reco_count(int $uploadId): int
{
    $st = db()->prepare("SELECT COUNT(*) FROM recommendation WHERE upload_id = :u");
    $st->execute([':u' => $uploadId]);
    return (int)$st->fetchColumn();
}

// ---- API失敗 ---------------------------------------------------------------
// upload に行が1つ増える(status='api_error')。画面は「混み合っています」で、
// 「ビールの写真に見えませんでした」ではない(取り違え防止。修正ラウンド1の再発防止)。
$visitor = reco_ht_visitor();
$calls = [];
$before = (int)db()->query('SELECT COUNT(*) FROM upload')->fetchColumn();
$h = reco_handle_upload($visitor, reco_ht_file('IMG_1006.jpeg'), reco_ht_error_transport($calls));
$after = (int)db()->query('SELECT COUNT(*) FROM upload')->fetchColumn();
eq($after - $before, 1, 'API失敗: upload に行が1つ増える');
eq($h['view'], 'error', 'API失敗: view は error');
eq($h['msg'], 'いま混み合っています。しばらくしてからお試しください。',
   'API失敗: 「混み合っています」であって「ビールの写真に見えませんでした」ではない');
$row = db()->query('SELECT status FROM upload ORDER BY upload_id DESC LIMIT 1')->fetch();
eq($row['status'], 'api_error', 'API失敗: status が api_error で記録される');

// ---- API失敗が上限に数えられる ---------------------------------------------
// 失敗を2回起こしたあと gate_context() の visitor_today が2になる
$visitor = reco_ht_visitor();
$calls = [];
$h1 = reco_handle_upload($visitor, reco_ht_file('IMG_1016.jpeg'), reco_ht_error_transport($calls));
eq($h1['view'], 'error', 'API失敗2回: 1回目も error');
reco_ht_unburst($visitor);   // 20秒以内の連投とみなされてバーストで弾かれないようにする
$h2 = reco_handle_upload($visitor, reco_ht_file('IMG_1189.jpeg'), reco_ht_error_transport($calls));
eq($h2['view'], 'error', 'API失敗2回: 2回目も error(バーストで弾かれていない)');
$ctx = gate_context($visitor, 'image/jpeg', 1000, bin2hex(random_bytes(32)));
eq($ctx['visitor_today'], 2, 'API失敗2回のあと、visitor_today が2になる(上限に数えられている)');

// ---- ビール以外 -------------------------------------------------------------
// 画像が保存されない。upload の is_beer が0
$visitor = reco_ht_visitor();
$calls = [];
$h = reco_handle_upload($visitor, reco_ht_file('IMG_2441.jpeg'),
    reco_ht_transport(reco_ht_resp(['is_beer' => false, 'brand_text' => 'Red Bull', 'confidence' => 0.9]), $calls));
eq($h['result']['is_beer'], false, 'ビール以外: is_beer は false');
eq($h['imageWebPath'], null, 'ビール以外: 画像を保存しない(imageWebPath が null)');
$row = db()->query('SELECT is_beer, image_path FROM upload ORDER BY upload_id DESC LIMIT 1')->fetch();
eq((int)$row['is_beer'], 0, 'ビール以外: upload.is_beer が0');
eq($row['image_path'], null, 'ビール以外: upload.image_path も null');

// ---- DBにある銘柄 -----------------------------------------------------------
// recommendation に3行入る。「登録されていません」が出ない
// (try.php のテンプレートはこの $result['matched_product_id'] の有無だけで出し分ける)
$visitor = reco_ht_visitor();
$calls = [];
$h = reco_handle_upload($visitor, reco_ht_file('IMG_1455.jpeg'),
    reco_ht_transport(reco_ht_resp([
        'matched_product_id' => 'pr0013', 'brand_text' => 'HAZY JANE', 'brewery_text' => 'BREWDOG',
    ]), $calls));
eq($h['result']['matched_product_id'], 'pr0013',
   'DBにある銘柄: matched_product_id が立つ(「登録されていません」を出さない条件)');
eq(count($h['picked']), 3, 'DBにある銘柄: 推薦が3件返る');
eq(reco_ht_reco_count($h['uploadId']), 3, 'DBにある銘柄: recommendation に3行入る');

// ---- DBに無い銘柄 -----------------------------------------------------------
// unknown_beer に行が入る。「登録されていません」が出る
$visitor = reco_ht_visitor();
$calls = [];
$h = reco_handle_upload($visitor, reco_ht_file('IMG_1738.jpeg'),
    reco_ht_transport(reco_ht_resp([
        'matched_product_id' => null, 'brand_text' => 'Nova Pale Ale', 'brewery_text' => 'Nova Brewing',
        'confidence' => 0.7,
    ]), $calls));
eq($h['result']['matched_product_id'], null,
   'DBに無い銘柄: matched_product_id が null(「登録されていません」を出す条件)');
$row = db()->prepare('SELECT hits FROM unknown_beer WHERE brand_text = :b AND brewery_text = :w');
$row->execute([':b' => 'Nova Pale Ale', ':w' => 'Nova Brewing']);
eq((int)$row->fetchColumn(), 1, 'DBに無い銘柄: unknown_beer に行が入る');

// ---- 同じ写真の再送 ----------------------------------------------------------
// APIが呼ばれない(transportの呼ばれた回数が増えない)。recommendation が増えない
$visitor = reco_ht_visitor();
$calls1 = [];
$h1 = reco_handle_upload($visitor, reco_ht_file('IMG_1765.jpeg'),
    reco_ht_transport(reco_ht_resp(['matched_product_id' => 'pr0013',
        'brand_text' => 'HAZY JANE', 'brewery_text' => 'BREWDOG']), $calls1));
eq(count($calls1), 1, '同じ写真の再送: 1回目はAPIが呼ばれる');
$calls2 = [];
$h2 = reco_handle_upload($visitor, reco_ht_file('IMG_1765.jpeg'),
    reco_ht_transport(reco_ht_resp(['is_beer' => false]), $calls2));   // 呼ばれたら違う応答になる
eq(count($calls2), 0, '同じ写真の再送: 2回目はAPIが呼ばれない');
eq($h2['uploadId'], $h1['uploadId'], '同じ写真の再送: 同じ upload_id を再利用する');
eq(reco_ht_reco_count($h1['uploadId']), 3, '同じ写真の再送: recommendation が6行に増えていない(3のまま)');

// ---- 失敗した記録は再利用しない ----------------------------------------------
// status='api_error' の行があっても、同じ写真をもう一度上げたらAPIが呼ばれる
$visitor = reco_ht_visitor();
$calls1 = [];
$h1 = reco_handle_upload($visitor, reco_ht_file('IMG_2173.jpeg'), reco_ht_error_transport($calls1));
eq($h1['view'], 'error', '失敗した記録は再利用しない: 1回目は失敗');
reco_ht_unburst($visitor);
$calls2 = [];
$h2 = reco_handle_upload($visitor, reco_ht_file('IMG_2173.jpeg'),
    reco_ht_transport(reco_ht_resp(['matched_product_id' => 'pr0013',
        'brand_text' => 'HAZY JANE', 'brewery_text' => 'BREWDOG']), $calls2));
eq(count($calls2), 1, '失敗した記録は再利用しない: 同じ写真でも2回目はAPIが呼ばれる');
eq($h2['view'], 'result', '失敗した記録は再利用しない: 2回目は成功として扱われる');

// ---- 門番で弾かれる ----------------------------------------------------------
// APIが呼ばれない(画像でないファイルはMIMEで弾かれる。門番はAPIを呼ぶ前の判断)
$visitor = reco_ht_visitor();
$calls = [];
$h = reco_handle_upload($visitor, ['tmp_name' => __FILE__, 'size' => filesize(__FILE__)],
    reco_ht_transport(reco_ht_resp(), $calls));
eq($h['view'], 'error', '門番で弾かれる: view は error');
eq(count($calls), 0, '門番で弾かれる: APIが呼ばれない');

// ---- 再試行は最大2回(費用の約束そのもの) -------------------------------------
$calls = [];
$boom = function (string $path, string $prompt) use (&$calls): array {
    $calls[] = 1;
    throw new RuntimeException('down');
};
identify_call('/dev/null', reco_catalog(), reco_style_catalog(), $boom);
eq(count($calls), 2, '再試行は最大2回: transport がちょうど2回呼ばれる(1回目+再試行1回)');

// ---- reco_pool() は絞り込まない(掲載料で順位を動かさないことの境界) ------------
$total = (int)db()->query('SELECT COUNT(*) FROM products')->fetchColumn();
eq(count(reco_pool()), $total, 'reco_pool() は products の全件数と一致する(絞り込まない)');

// --- 写真は本人にしか配らない ---
$owner  = 'own' . str_repeat('0', 29);
$other  = 'oth' . str_repeat('0', 29);
foreach ([$owner, $other] as $v) {
    db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen) VALUES (:v, NOW(), NOW())
                   ON DUPLICATE KEY UPDATE last_seen = NOW()")->execute([':v' => $v]);
}
db()->prepare("INSERT INTO upload (visitor_id, created_at, image_hash, is_beer, status, image_path)
               VALUES (:v, NOW(), :h, 1, 'ok', 'img/upload/" . str_repeat('a', 64) . ".jpg')")
   ->execute([':v' => $owner, ':h' => str_repeat('f', 64)]);
$uid = (int)db()->lastInsertId();

ok(upload_owned_by($uid, $owner) !== null, '本人なら取り出せる');
eq(upload_owned_by($uid, $other), null,    '他人のものは取り出せない');
eq(upload_owned_by(999999, $owner), null,  '存在しない upload は取り出せない');

db()->prepare("DELETE FROM upload WHERE upload_id = :u")->execute([':u' => $uid]);
db()->prepare("DELETE FROM visitor WHERE visitor_id IN (:a, :b)")->execute([':a' => $owner, ':b' => $other]);

// ---- 後始末。使い捨てのテストデータなので残さない ----------------------------
// is_beer=true の分岐は identify_shrink() で img/upload/ に実ファイルを書く。
// DBの行を消す前に、このテストが作った画像も消す(残すと実行のたびに増える)。
$paths = db()->query("SELECT image_path FROM upload WHERE image_path IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
foreach ($paths as $p) {
    $full = dirname(__DIR__, 2) . '/' . $p;
    if (is_file($full)) { unlink($full); }
}
foreach (['event', 'recommendation', 'unknown_beer', 'upload', 'visitor'] as $t) {
    db()->exec("DELETE FROM {$t}");
}
