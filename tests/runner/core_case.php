<?php
// TC-CORE-* 用の薄いラッパー。common/sql.php を無改変のまま require するだけで、
// 対象コードの外側で分岐制御変数を手動セットする (台帳 §3.2 参照)。
//   php core_case.php <vars.json>
// vars.json: {"src_prd": "no", "sql_where": "''=''", ...} — キーがそのまま変数名になる
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '0');

if ($argc < 2) {
    fwrite(STDERR, "usage: php core_case.php <vars.json>\n");
    exit(2);
}
$vars = json_decode(file_get_contents($argv[1]), true);
if (!is_array($vars)) {
    fwrite(STDERR, "bad vars json: {$argv[1]}\n");
    exit(2);
}
foreach ($vars as $k => $v) {
    $$k = $v;
}

$sandbox = dirname(__DIR__) . '/sandbox';
chdir($sandbox);
require $sandbox . '/common/sql.php';
