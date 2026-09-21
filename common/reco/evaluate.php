<?php
declare(strict_types=1);
/*
 * ③評価(設計書 2026-09-19 §3)。
 *
 * **このファイルは DB も API も呼ばない。** 値と母集団を受け取って、文の材料を返すだけ。
 * そうしている理由:
 *   - 同じ入力からは必ず同じ文が出る(モデルに書かせると揺れる)
 *   - 追加の費用がゼロ
 *   - 全分岐を単体テストで固定できる
 *
 * 言い方の決めごと: **断定しない。何と比べているかを必ず添える。**
 * 実データで試すと、度数5%の銘柄が「とても軽い」と出る。200銘柄は度数が高いほうに
 * 偏っている(平均5.7%)のでユニバースの中では正しいが、読み手は違和感を持つ。
 */

/** 軸の定義。列名・見出し・値の書式・比較語(低い側 / 高い側) */
const EVAL_AXES = [
    'abv'    => ['col' => 'Alcohol', 'label' => '度数',       'fmt' => '%.1f%%',
                 'low' => '軽い',   'high' => '強い'],
    'ibu'    => ['col' => 'IBU_all', 'label' => '苦味',       'fmt' => 'IBU %d',
                 'low' => '苦くない', 'high' => '苦い'],
    'fruity' => ['col' => 'Fruity',  'label' => 'フルーティーさ', 'fmt' => '%d / 10',
                 'low' => '控えめな', 'high' => 'フルーティーな'],
];

/** 帯ごとの言い方。境界(その割合**未満**なら手前の帯)と語 */
const EVAL_BANDS = [
    'abv' => [[15, 'かなり軽いほう'], [35, '軽いほう'], [65, 'ちょうど真ん中あたり'],
              [85, '強いほう'], [101, 'かなり強いほう']],
    'ibu' => [[15, 'ほとんど苦くないほう'], [35, 'おだやかなほう'], [65, 'ほどよいほう'],
              [85, 'しっかり苦いほう'], [101, 'かなり苦いほう']],
    'fruity' => [[15, 'ほとんど果実感がないほう'], [35, '控えめなほう'], [65, 'ほどよく感じるほう'],
                 [85, 'はっきり感じるほう'], [101, '果実感が主役のほう']],
];

/** 母集団から1軸の値だけを昇順で取り出す。欠けている値は数に入れない */
function eval_sorted(array $pool, string $axis): array
{
    $col = EVAL_AXES[$axis]['col'] ?? null;
    if ($col === null) { return []; }
    $out = [];
    foreach ($pool as $r) {
        $v = $r[$col] ?? null;
        if ($v === null || $v === '') { continue; }
        $out[] = (float)$v;
    }
    sort($out);
    return $out;
}

/** その値より小さいものの割合(0〜100の整数) */
function eval_share_below(array $sorted, float $v): int
{
    $n = count($sorted);
    if ($n === 0) { return 0; }
    $below = 0;
    foreach ($sorted as $x) { if ($x < $v) { $below++; } }
    return (int)round($below * 100 / $n);
}

/** 0〜4 の帯 */
function eval_band(string $axis, int $shareBelow): int
{
    $bands = EVAL_BANDS[$axis] ?? EVAL_BANDS['abv'];
    foreach ($bands as $i => $b) {
        if ($shareBelow < $b[0]) { return $i; }
    }
    return count($bands) - 1;
}

/**
 * 1行分の材料。値が無い、または母集団が空なら null(**語れないものを語らない**)。
 *
 * 比較の向きは帯で決める。低い側の帯なら「これより軽いものは 12%」、
 * それ以外は「これより苦いものが 71%」。どちらも**実際の割合**であり、
 * 丸めた以上のことは言わない。
 */
function eval_line(string $axis, ?float $value, array $sorted, bool $estimated): ?array
{
    if ($value === null || !isset(EVAL_AXES[$axis]) || count($sorted) === 0) { return null; }
    $ax    = EVAL_AXES[$axis];
    $below = eval_share_below($sorted, $value);
    $band  = eval_band($axis, $below);
    $word  = EVAL_BANDS[$axis][$band][1];

    if ($band <= 1) {
        $clause = sprintf('これより%sものは %d%%', $ax['low'], $below);
    } else {
        $clause = sprintf('これより%sものが %d%%', $ax['high'], 100 - $below);
    }

    return [
        'axis'      => $axis,
        'label'     => $ax['label'],
        'word'      => $word,
        'clause'    => $clause,
        'value'     => sprintf($ax['fmt'], $axis === 'abv' ? $value : (int)round($value)),
        'estimated' => $estimated,
    ];
}

/**
 * 3軸ぶんまとめて。
 *
 * @param array $vals      ['abv'=>?float, 'ibu'=>?float, 'fruity'=>?float]
 * @param bool|array $estimated  全軸まとめて bool で渡すか、
 *                               ['abv'=>bool,'ibu'=>bool,'fruity'=>bool] で軸ごとに渡す。
 *                               **DBの行でも、その銘柄の estimated_fields に載っている列は推定値**
 *                               (2026-09-22時点、200銘柄すべてのフルーティーさが推定)。
 *                               まとめて false にすると、推定を測定値の顔で出すことになる
 *                               (修正ラウンド1: 最終レビュー Important)。
 * @return array ['lines'=>[...], 'estimated'=>bool] 'estimated' は**実際に出した行の中に
 *               推定が1つでもあるか**。値が無くて行が出なかった軸は数えない
 *               (語れないものを語らない、と同じ考え方)
 */
function eval_build(array $vals, array $pool, $estimated): array
{
    $lines = [];
    $any = false;
    foreach (['abv', 'ibu', 'fruity'] as $axis) {
        $v = $vals[$axis] ?? null;
        $est = is_array($estimated) ? !empty($estimated[$axis]) : (bool)$estimated;
        $line = eval_line($axis, $v === null || $v === '' ? null : (float)$v,
                          eval_sorted($pool, $axis), $est);
        if ($line !== null) { $lines[] = $line; if ($est) { $any = true; } }
    }
    return ['lines' => $lines, 'estimated' => $any];
}
