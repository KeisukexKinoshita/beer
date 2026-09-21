<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/common/reco/evaluate.php';

// 母集団は10件。割合の計算を手で検算できる大きさにする
$pool = [];
foreach ([3.0, 4.0, 4.5, 5.0, 5.5, 6.0, 6.5, 7.0, 8.0, 9.0] as $i => $a) {
    $pool[] = ['Alcohol' => $a, 'IBU_all' => 10 + $i * 10, 'Fruity' => $i + 1];
}

$abv = eval_sorted($pool, 'abv');
eq(count($abv), 10, '母集団から度数を10件取り出す');
eq($abv[0], 3.0, '昇順の先頭は最小値');
eq($abv[9], 9.0, '昇順の末尾は最大値');

// 値が欠けている行は捨てる(数に入れない)
$holed = $pool;
$holed[] = ['Alcohol' => null, 'IBU_all' => null, 'Fruity' => null];
$holed[] = ['Alcohol' => '',   'IBU_all' => '',   'Fruity' => ''];
eq(count(eval_sorted($holed, 'abv')), 10, '欠けている値は母集団に数えない');

eq(eval_share_below($abv, 3.0), 0,  '最小値より小さいものは0%');
eq(eval_share_below($abv, 9.5), 100, '最大値より大きければ100%');
eq(eval_share_below($abv, 5.6), 50, '半分より上で50%');

// 帯は0〜4。境界は下側を含む
eq(eval_band('abv', 0),   0, '0%は最初の帯');
eq(eval_band('abv', 14),  0, '14%までは最初の帯');
eq(eval_band('abv', 15),  1, '15%から次の帯');
eq(eval_band('abv', 100), 4, '100%は最後の帯');

$line = eval_line('ibu', 25.0, eval_sorted($pool, 'ibu'), false);
eq($line['label'], '苦味', '軸の名前');
eq($line['value'], 'IBU 25', '実際の値を必ず添える');
ok(strpos($line['clause'], '%') !== false, '何と比べているかを必ず添える');
eq($line['estimated'], false, '測った値は推定ではない');

// 推定の印は行ごとに付く
$est = eval_line('abv', 5.0, $abv, true);
eq($est['estimated'], true, '推定した値には印が付く');
eq($est['value'], '5.0%', '度数は小数1桁に%を付ける');

// 値が無い軸は行そのものを出さない(語れないものを語らない)
eq(eval_line('fruity', null, eval_sorted($pool, 'fruity'), false), null, '値が無ければ行を出さない');

// 母集団が空でも落ちない(新しい環境・データ投入前)
eq(eval_line('abv', 5.0, [], false), null, '母集団が空なら行を出さない');

$built = eval_build(['abv' => 5.0, 'ibu' => 25.0, 'fruity' => null], $pool, false);
eq(count($built['lines']), 2, '値のある軸だけが行になる');
eq($built['estimated'], false, '全体の推定フラグ');

// **同じ入力からは必ず同じ文が出る**(設計書 §3。モデルに書かせない理由そのもの)
eq(json_encode(eval_build(['abv' => 5.0, 'ibu' => 25.0, 'fruity' => 3.0], $pool, false)),
   json_encode(eval_build(['abv' => 5.0, 'ibu' => 25.0, 'fruity' => 3.0], $pool, false)),
   '同じ入力からは必ず同じ結果');
