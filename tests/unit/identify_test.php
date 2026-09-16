<?php
require_once __DIR__ . '/../../common/reco/identify.php';

$fx = function (string $name): array {
    return json_decode(file_get_contents(__DIR__ . '/../fixtures/identify/' . $name . '.json'), true);
};

// --- 応答の読み取り ---
$r = identify_parse($fx('high'));
eq($r['is_beer'], true,                'ビールだと判定できている');
eq($r['matched_product_id'], 'pr0013', 'DBの銘柄に結びついている');
eq($r['confidence'], 0.92,             '確度が取れている');

$r = identify_parse($fx('low'));
eq($r['matched_product_id'], null, '一覧に無い銘柄は matched_product_id が null');
ok($r['brand_text'] !== null,      '一覧に無くても読み取った文字は残る');

$r = identify_parse($fx('not_beer'));
eq($r['is_beer'], false,               'ビール以外を見分けている');
eq($r['matched_product_id'], null,     'ビール以外は銘柄に結びつけない');

// --- JSON として妥当でも、形が違えば失敗として扱う ---
$bad = function (string $text): array {
    return ['content' => [['type' => 'text', 'text' => $text]]];
};
eq(identify_parse($bad('{}'))['error'],        true, '空オブジェクトは判定失敗として扱う');
eq(identify_parse($bad('[1,2,3]'))['error'],   true, 'JSON配列は判定失敗として扱う');
eq(identify_parse($bad('123'))['error'],       true, '数値だけの JSON は判定失敗として扱う');
eq(identify_parse($bad('{"foo":1}'))['error'], true, 'is_beer が無ければ判定失敗として扱う');

// --- 分岐(設計書 §6) ---
eq(identify_branch(['is_beer' => true,  'confidence' => 0.92]), 'confirm',  '0.8以上は1件を確認');
eq(identify_branch(['is_beer' => true,  'confidence' => 0.80]), 'confirm',  '境界の0.8は確認側');
eq(identify_branch(['is_beer' => true,  'confidence' => 0.79]), 'choose',   '0.8未満は候補から選ばせる');
eq(identify_branch(['is_beer' => true,  'confidence' => 0.40]), 'choose',   '境界の0.4は選ばせる側');
eq(identify_branch(['is_beer' => true,  'confidence' => 0.39]), 'unknown',  '0.4未満は未該当として扱う');
eq(identify_branch(['is_beer' => false, 'confidence' => 0.99]), 'not_beer', '確度が高くてもビール以外はビール以外');

// --- 差し替えた経路で1周する ---
$calls = 0;
$transport = function (string $path, string $prompt) use ($fx, &$calls): array {
    $calls++;
    return $fx('high');
};
$catalog = [['ProductID' => 'pr0013', 'ProductName' => 'HAZY JANE', 'MakerName' => 'BREWDOG']];
$r = identify_call('/dev/null', $catalog, $transport);
eq($calls, 1,                          'transport がちょうど1回呼ばれる');
eq($r['matched_product_id'], 'pr0013', '差し替えた経路でも結果が取れる');

// --- API が失敗したとき ---
$boom = function (string $path, string $prompt): array { throw new RuntimeException('500'); };
$r = identify_call('/dev/null', $catalog, $boom);
eq($r['error'], true,                  '例外を投げずにエラーとして返す');
eq($r['is_beer'], null,                'エラー時は判定を作らない');

// --- プロンプトの固定部分 ---
$p = identify_prompt($catalog);
ok(strpos($p, 'pr0013') !== false,     '銘柄一覧がプロンプトに入っている');
ok(strpos($p, 'HAZY JANE') !== false,  '銘柄名も入っている');
eq(identify_prompt($catalog), $p,      '同じ入力なら同じ文字列(キャッシュが効く条件)');

// --- 範囲外の値は「未取得」に落とす(スキーマで縛れないぶんをここで受ける) ---
$withVals = function (array $over): array {
    $base = ['is_beer' => true, 'brand_text' => 'X', 'brewery_text' => null,
             'matched_product_id' => null, 'style_guess' => null,
             'color' => 5, 'clarity' => 2, 'confidence' => 0.5];
    return ['content' => [['type' => 'text', 'text' => json_encode(array_merge($base, $over))]]];
};
eq(identify_parse($withVals(['color' => 15]))['color'],      null, 'color 15 は範囲外なので null');
eq(identify_parse($withVals(['color' => 0]))['color'],       null, 'color 0 は範囲外なので null');
eq(identify_parse($withVals(['color' => 10]))['color'],      10,   'color 10 は範囲内');
eq(identify_parse($withVals(['clarity' => 5]))['clarity'],   null, 'clarity 5 は範囲外なので null');
eq(identify_parse($withVals(['clarity' => 4]))['clarity'],   4,    'clarity 4 は範囲内');
eq(identify_parse($withVals(['confidence' => 1.5]))['confidence'], null, '確度 1.5 は範囲外なので null');
eq(identify_parse($withVals(['confidence' => 1.0]))['confidence'], 1.0,  '確度 1.0 は範囲内');
