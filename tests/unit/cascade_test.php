<?php
require_once __DIR__ . '/../../common/reco/cascade.php';

/*
 * テスト用のヘルパー。run.php は全テストファイルを同じスコープに require するので、
 * 名前は reco_ で始めて衝突を避ける。
 */
/** テスト用の1行。all_beers() の行から、カスケードが使う列だけを抜いた形 */
function reco_t_row(string $pid, string $style, string $family, string $name, string $maker, $abv): array {
    return ['ProductID' => $pid, 'StyleID' => $style, 'FamilyName' => $family,
            'StyleName' => $name, 'MakerID' => $maker, 'Alcohol' => $abv];
}

/** 結果から ProductID の並びだけを取り出す */
function reco_t_ids(array $picked): array {
    return array_map(function ($p) { return $p['ProductID']; }, $picked);
}
/** 結果から stage の並びだけを取り出す */
function reco_t_stages(array $picked): array {
    return array_map(function ($p) { return $p['stage']; }, $picked);
}

// --- 1. 同じ StyleID に十分な候補がある ---
$seed = reco_t_row('pr0001', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0);
$pool = [
    reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk02', 6.2),
    reco_t_row('pr0003', 'st0010', 'IPA', 'American IPA', 'mk03', 5.8),
    reco_t_row('pr0004', 'st0010', 'IPA', 'American IPA', 'mk04', 7.5),
    reco_t_row('pr0005', 'st0020', 'IPA', 'Hazy IPA',     'mk05', 6.0),
];
$got = reco_pick($seed, $pool);
eq(reco_t_stages($got), [1, 1, 1], '同StyleIDで3件そろえば全部が段階1');
eq(reco_t_ids($got), ['pr0003', 'pr0002', 'pr0004'], '度数が近い順(0.2→0.2→1.5)。同差は新しいIDが先');

// --- 2. 同StyleIDに1件しかない(実データで23銘柄が該当) ---
$seed = reco_t_row('pr0001', 'st0030', 'Wheat Beers', 'Wheat Wine', 'mk01', 9.0);
$pool = [
    reco_t_row('pr0002', 'st0030', 'Wheat Beers', 'Wheat Wine', 'mk02', 9.2),
    reco_t_row('pr0003', 'st0031', 'Wheat Beers', 'Weizenbock', 'mk03', 8.5),
    reco_t_row('pr0004', 'st0040', 'Weizen',      'Hefeweizen', 'mk04', 5.0),
];
$got = reco_pick($seed, $pool);
eq(reco_t_stages($got), [1, 2, 3], '段階1で尽きたら2、そこでも尽きたら3へ広がる');
eq(reco_t_ids($got), ['pr0002', 'pr0003', 'pr0004'], '段階順に拾う');

// --- 3. 同じ系統に誰もいない。表示グループでも足りず度数で埋める ---
$seed = reco_t_row('pr0001', 'st0050', 'Strong Ales', 'Barley Wine', 'mk01', 10.0);
$pool = [
    reco_t_row('pr0002', 'st0060', 'Lager', 'Pilsner', 'mk02', 5.0),
    reco_t_row('pr0003', 'st0061', 'Lager', 'Helles',  'mk03', 9.5),
];
$got = reco_pick($seed, $pool);
eq(count($got), 2, '候補が3件に満たなくても落ちない');
eq(reco_t_ids($got), ['pr0003', 'pr0002'], '最後の手段は度数が近い順');

// --- 4. 別の蔵が最優先 ---
$seed = reco_t_row('pr0001', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0);
$pool = [
    reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0),  // 同じ蔵・度数ぴったり
    reco_t_row('pr0003', 'st0010', 'IPA', 'American IPA', 'mk02', 8.0),  // 別の蔵・度数は遠い
];
$got = reco_pick($seed, $pool);
eq(reco_t_ids($got), ['pr0003', 'pr0002'], '度数がどれだけ近くても、別の蔵が先に来る');

// --- 5. 度数が未計測のものは後ろ ---
$seed = reco_t_row('pr0001', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0);
$pool = [
    reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk02', null),
    reco_t_row('pr0003', 'st0010', 'IPA', 'American IPA', 'mk03', 9.0),
];
$got = reco_pick($seed, $pool);
eq(reco_t_ids($got), ['pr0003', 'pr0002'], '度数NULLは度数差を比べられないので後ろ');

// --- 6. 基準の1本そのものは出さない ---
$seed = reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0);
$pool = [
    reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0),
    reco_t_row('pr0003', 'st0010', 'IPA', 'American IPA', 'mk02', 6.1),
];
$got = reco_pick($seed, $pool);
eq(reco_t_ids($got), ['pr0003'], '自分自身は候補から外れる');

// --- 7. DBに無い銘柄(StyleID も ProductID も無い)でも動く ---
$seed = ['ProductID' => null, 'StyleID' => null, 'FamilyName' => 'IPA',
         'StyleName' => 'Hazy IPA', 'MakerID' => null, 'Alcohol' => 6.5];
$pool = [
    reco_t_row('pr0002', 'st0010', 'IPA',   'American IPA', 'mk02', 6.4),
    reco_t_row('pr0003', 'st0060', 'Lager', 'Pilsner',      'mk03', 5.0),
];
$got = reco_pick($seed, $pool);
eq(reco_t_stages($got), [2, 4], 'StyleID が無いので段階1は空振りし、系統から始まる');
eq(reco_t_ids($got), ['pr0002', 'pr0003'], '系統で1件、残りは度数で埋める');

// --- 8. 同じ銘柄を2回出さない ---
$seed = reco_t_row('pr0001', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0);
$pool = [reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk02', 6.0)];
$got = reco_pick($seed, $pool);
eq(reco_t_ids($got), ['pr0002'], '段階1で拾ったものが段階2以降で再登場しない');
