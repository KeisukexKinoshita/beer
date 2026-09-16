<?php
require_once __DIR__ . '/../../common/reco/identify.php';

$fx = function (string $name): array {
    return json_decode(file_get_contents(__DIR__ . '/../fixtures/identify/' . $name . '.json'), true);
};

// --- 応答の読み取り ---
// high.json は本物のAPI応答(2026-09-16 実測)。記録が正なので期待値も実測に合わせる。
$r = identify_parse($fx('high'));
eq($r['is_beer'], true,                'ビールだと判定できている');
eq($r['matched_product_id'], 'pr0013', 'DBの銘柄に結びついている');
eq($r['confidence'], 0.95,             '確度が取れている');

$r = identify_parse($fx('low'));
eq($r['matched_product_id'], null, '一覧に無い銘柄は matched_product_id が null');
ok($r['brand_text'] !== null,      '一覧に無くても読み取った文字は残る');

$r = identify_parse($fx('not_beer'));
eq($r['is_beer'], false,               'ビール以外を見分けている');
eq($r['matched_product_id'], null,     'ビール以外は銘柄に結びつけない');

// --- API自体が失敗した応答(最終レビュー指摘: error.json を使うテストが無かった) ---
$r = identify_parse($fx('error'));
eq($r['error'], true,   'type=error の応答は判定失敗として扱う');
eq($r['is_beer'], null, 'API失敗のとき is_beer は作らない(falseにしない)');

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
$styles  = [['StyleID' => 'st0007', 'StyleName' => 'New England IPA', 'FamilyName' => 'IPA']];
$r = identify_call('/dev/null', $catalog, $styles, $transport);
eq($calls, 1,                          'transport がちょうど1回呼ばれる');
eq($r['matched_product_id'], 'pr0013', '差し替えた経路でも結果が取れる');

// --- API が失敗したとき ---
$boom = function (string $path, string $prompt): array { throw new RuntimeException('500'); };
$r = identify_call('/dev/null', $catalog, $styles, $boom);
eq($r['error'], true,                  '例外を投げずにエラーとして返す');
eq($r['is_beer'], null,                'エラー時は判定を作らない');

// --- プロンプトの固定部分 ---
$p = identify_prompt($catalog, $styles);
ok(strpos($p, 'pr0013') !== false,     '銘柄一覧がプロンプトに入っている');
ok(strpos($p, 'HAZY JANE') !== false,  '銘柄名も入っている');
ok(strpos($p, 'st0007') !== false,     'スタイル一覧がプロンプトに入っている');
ok(strpos($p, 'New England IPA') !== false, 'スタイル名も入っている');

// --- プロンプトのキャッシュが効く条件は「並びが決定的」であること。
//     本当のリスクは呼び出し元の SQL 側(ORDER BY が無いと並びが不定になりうる)にある。
//     最終レビュー指摘: 「同じ関数を2回呼んで同じ文字列」という前のテストは、
//     決定的な関数なら何を書いても通ってしまい、何も主張していなかった ---
require_once __DIR__ . '/../../common/reco/repo.php';
$reco_t_fn_src = function (string $fn): string {
    $r = new ReflectionFunction($fn);
    $lines = file($r->getFileName());
    return implode('', array_slice($lines, $r->getStartLine() - 1, $r->getEndLine() - $r->getStartLine() + 1));
};
ok(stripos($reco_t_fn_src('reco_catalog'), 'ORDER BY') !== false,
   'reco_catalog() の SQL に ORDER BY がある(並びが変わるとキャッシュが外れる)');
ok(stripos($reco_t_fn_src('reco_style_catalog'), 'ORDER BY') !== false,
   'reco_style_catalog() の SQL に ORDER BY がある(並びが変わるとキャッシュが外れる)');

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

// --- スタイルはIDでしか受け取らない ---
// high.json は、プロンプトにスタイル一覧を渡していなかった頃の**本物の応答**で、
// style_guess に "New England IPA" というスタイル名が入っている。
// 名前で来たら null に落とすことを、この実物で固定する。
eq(identify_parse($fx('high'))['style_guess'], null, 'スタイル名で返されたら受け取らない');

$sid = function (string $v): array {
    return ['content' => [['type' => 'text',
        'text' => json_encode(['is_beer' => true, 'style_guess' => $v])]]];
};
eq(identify_parse($sid('st0007'))['style_guess'], 'st0007', '正しいIDはそのまま通す');
eq(identify_parse($sid('st007'))['style_guess'],  null,     '桁が足りないIDは受け取らない');
eq(identify_parse($sid('pr0007'))['style_guess'], null,     '銘柄IDは受け取らない');

// --- 銘柄IDも形式を検査する(style_guess と同じ理由) ---
$pidOf = function ($v): array {
    return ['content' => [['type' => 'text',
        'text' => json_encode(['is_beer' => true, 'matched_product_id' => $v])]]];
};
eq(identify_parse($pidOf('pr0013'))['matched_product_id'], 'pr0013', '正しい銘柄IDは通す');
eq(identify_parse($pidOf('HAZY JANE'))['matched_product_id'], null,  '銘柄名は受け取らない');
eq(identify_parse($pidOf('pr013'))['matched_product_id'],  null,     '桁が足りないIDは受け取らない');
eq(identify_parse($pidOf('st0007'))['matched_product_id'], null,     'スタイルIDは受け取らない');

// --- 一覧に無いIDは、形式が正しくても受け取らない ---
$known = [['ProductID' => 'pr0013', 'ProductName' => 'HAZY JANE', 'MakerName' => 'BREWDOG']];
$styles = [['StyleID' => 'st0007', 'StyleName' => 'New England IPA', 'FamilyName' => 'IPA']];
$ghost = function (string $pid) {
    return function (string $path, string $prompt) use ($pid): array {
        return ['model' => 'claude-sonnet-5', 'content' => [['type' => 'text',
            'text' => json_encode(['is_beer' => true, 'matched_product_id' => $pid])]]];
    };
};
eq(identify_call('/dev/null', $known, $styles, $ghost('pr9999'))['matched_product_id'], null,
   '一覧に無い銘柄IDは受け取らない');
eq(identify_call('/dev/null', $known, $styles, $ghost('pr0013'))['matched_product_id'], 'pr0013',
   '一覧にある銘柄IDは通す');

// --- 自由文は VARCHAR(191) に収める(黙って切り詰められるのを防ぐ) ---
// 最終レビュー指摘: 長さ(mb_strlen)だけを見ていたので、「常に191文字の何かに切る」
// (中身が違っても通る)壊し方でも検出できなかった。位置ごとに違う文字にして、
// **先頭191文字と内容が一致すること**まで見る。
$reco_t_seq = function (int $len): string {
    $chars = ['あ', 'い', 'う'];
    $s = '';
    for ($i = 0; $i < $len; $i++) { $s .= $chars[$i % 3]; }
    return $s;
};
$longText = function (string $key, string $val): array {
    return ['content' => [['type' => 'text',
        'text' => json_encode(['is_beer' => true, $key => $val])]]];
};
$s300brand = $reco_t_seq(300);
eq(identify_parse($longText('brand_text', $s300brand))['brand_text'],
   mb_substr($s300brand, 0, 191), '長い銘柄名は先頭191文字に丸める(内容も一致)');
$s300brew = $reco_t_seq(300);
eq(identify_parse($longText('brewery_text', $s300brew))['brewery_text'],
   mb_substr($s300brew, 0, 191), '長い蔵名は先頭191文字に丸める(内容も一致)');
$s191 = $reco_t_seq(191);
eq(identify_parse($longText('brand_text', $s191))['brand_text'], $s191, 'ちょうど191文字はそのまま');
eq(identify_parse($longText('brand_text', ''))['brand_text'], null, '空文字は null にする');
