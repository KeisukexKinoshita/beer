<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/common/nebula/helpers.php';
require_once dirname(__DIR__, 2) . '/common/reco/cascade.php';

$seed = ['ProductID' => 'pr0001', 'StyleID' => 'st0001', 'FamilyName' => 'IPA',
         'StyleName' => 'Hazy IPA', 'MakerID' => 'mk0001',
         'Alcohol' => 6.0, 'IBU_all' => 40.0, 'Fruity' => 5.0];

$pool = [
  ['ProductID'=>'pr1001','StyleID'=>'st0001','FamilyName'=>'IPA','StyleName'=>'Hazy IPA',
   'MakerID'=>'mk0002','MakerName'=>'B','ProductName'=>'軽い','Alcohol'=>4.0,'IBU_all'=>20.0,'Fruity'=>3.0],
  ['ProductID'=>'pr1002','StyleID'=>'st0001','FamilyName'=>'IPA','StyleName'=>'Hazy IPA',
   'MakerID'=>'mk0003','MakerName'=>'C','ProductName'=>'強い','Alcohol'=>8.0,'IBU_all'=>60.0,'Fruity'=>8.0],
  ['ProductID'=>'pr1003','StyleID'=>'st0001','FamilyName'=>'IPA','StyleName'=>'Hazy IPA',
   'MakerID'=>'mk0004','MakerName'=>'D','ProductName'=>'同じくらい','Alcohol'=>6.1,'IBU_all'=>41.0,'Fruity'=>5.0],
];

// ずらした仮の1本
eq(reco_shift($seed, 'lighter')['Alcohol'],  5.0, 'もっと軽く は度数を1.0下げる');
eq(reco_shift($seed, 'stronger')['Alcohol'], 7.0, 'もっと強く は度数を1.0上げる');
eq(reco_shift($seed, 'bitter')['Alcohol'],   6.0, '苦味の方向では度数を動かさない');
eq(reco_shift($seed, 'なにか'), null, '知らない方向は受け付けない');

// 軽い方向では、軽いものが先に出る
$r = reco_expand($seed, $pool, 'lighter');
eq($r['ok'], true, '軽い方向は使える');
eq($r['picked'][0]['ProductID'], 'pr1001', 'もっと軽く で軽いものが先頭');

// 苦い方向では、基準より苦いものだけになる
$r = reco_expand($seed, $pool, 'bitter');
eq($r['ok'], true, '苦い方向は使える');
eq(count($r['picked']), 2, '基準(40)より苦い2件だけ');
foreach ($r['picked'] as $p) { ok((float)$p['row']['IBU_all'] > 40.0, '選ばれたものは必ず基準より苦い'); }

// 控えめ方向
$r = reco_expand($seed, $pool, 'less_bitter');
eq(count($r['picked']), 1, '基準より苦くないものは1件');
eq($r['picked'][0]['ProductID'], 'pr1001', '控えめ方向は苦くないものを出す');

// フルーティー方向
$r = reco_expand($seed, $pool, 'fruitier');
eq($r['picked'][0]['ProductID'], 'pr1002', 'フルーティー方向は果実感が強いものを出す');

// 基準の値が無ければ、その方向は使えない。**黙って普通の推薦を出さない**
$noIbu = $seed; $noIbu['IBU_all'] = null;
$r = reco_expand($noIbu, $pool, 'bitter');
eq($r['ok'], false, '基準の苦味が無ければ使えない');
eq($r['reason'], 'no_seed_value', '理由を返す');
eq($r['picked'], [], '使えないときは1件も返さない');

// 候補が尽きたときも黙らない
$r = reco_expand($seed, [$pool[0]], 'bitter');
eq($r['ok'], false, '該当が無ければ使えない');
eq($r['reason'], 'no_candidate', '理由を返す');

// 知らない方向
$r = reco_expand($seed, $pool, 'sideways');
eq($r['ok'], false, '知らない方向は使えない');
eq($r['reason'], 'bad_dir', '理由を返す');
