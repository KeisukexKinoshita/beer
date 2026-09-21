<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/common/nebula/helpers.php';

$seed = ['StyleName' => 'New England IPA', 'FamilyName' => 'IPA'];
eq(reco_stage_label(1, $seed), '同じ New England IPA から', '1段目はスタイル名');
eq(reco_stage_label(2, $seed), '同じ IPA の系統から',        '2段目は系統名');
eq(reco_stage_label(3, $seed), '近い味わいのものから',        '3段目');
eq(reco_stage_label(4, $seed), '度数が近いものから',          '4段目');

// 名前が無いとき(未登録で style_guess も取れなかった)に壊れた文を出さない
$bare = ['StyleName' => '', 'FamilyName' => ''];
eq(reco_stage_label(1, $bare), '近い味わいのものから', 'スタイル名が無ければ言い切らない');
eq(reco_stage_label(2, $bare), '近い味わいのものから', '系統名が無ければ言い切らない');

// 想定外の段は黙る(知らない状態では何も言わない)
eq(reco_stage_label(9, $seed), '', '知らない段では何も言わない');
