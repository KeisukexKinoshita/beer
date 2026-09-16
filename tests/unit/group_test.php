<?php
require_once __DIR__ . '/../../common/nebula/helpers.php';

$m = group_map();
eq(count($m['ipa']), 3, 'group_map は 減彩・強調・ラベル の3値を持つ');
eq($m['ipa'][0], '#86adbd', 'ipa の通常色は減彩');
eq($m['ipa'][1], '#5fd0ff', 'ipa の強調色は現行値');
eq($m['ipa'][2], 'IPA系',   'ipa のラベル');

list($col, $label) = group_meta('stout');
eq($col,   '#a396b8', 'group_meta は減彩色を返す(既存9箇所の呼び出しを壊さない)');
eq($label, 'Stout / 黒', 'group_meta のラベルは従来どおり');

eq(group_hi('stout'), '#b98cff', 'group_hi は強調色を返す');
eq(group_hi('存在しないキー'), group_hi('other'), '未知のキーは other に落ちる');

$js = group_map_js();
eq($js['wheat']['c'], '#aeb890', 'JS へ渡す c は減彩色');
eq($js['wheat']['h'], '#c8e86a', 'JS へ渡す h は強調色');
eq($js['wheat']['l'], '小麦 / Weizen', 'JS へ渡す l はラベル');
eq(count($js), 6, 'グループは6つ');
