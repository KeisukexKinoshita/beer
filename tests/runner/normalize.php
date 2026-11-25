<?php
// 実行のたびに変わる値だけを、固定の印に置き換える(特性テストの出力を決定的にする)。
//   php normalize.php <in> [<head.txt>] > <out>   (head.txt があれば、その Set-Cookie と本文の tok を突き合わせる)
// **検査したい内容まで消さない**ことが条件。置き換えるのは次だけ(tests/cases.md §14.3):
//   1. CSS/JS の ?v=<filemtime>            → ?v=<MTIME>
//   2. 幕の hidden tok(32桁hex)          → <AGE_TOK>
//      Set-Cookie: db_age_tok=<32hex>      → <AGE_TOK>
//      ※ 本文の tok と Cookie の値が食い違っていたら、印を <AGE_TOK-MISMATCH> にして落とす
//   3. agecheck の hidden csrf(64桁hex)  → <CSRF>
//   4. Set-Cookie db_vid=<32hex>           → <VISITOR_ID>
//   5. Set-Cookie の Max-Age=<秒>         → Max-Age=<MAX_AGE:<日数>d>(秒の値は潰さず、日に丸めて残す。
//      db_vid の1年が30日になれば golden と違って落ちる)
//      expires=<日時>                      → Max-Age との差が30秒以内なら expires=<DATE:ok>、
//      食い違っていれば expires=<DATE:MISMATCH>(日時そのものは潰すが、寿命と矛盾しないことは見る)
//   6. リポジトリのルート(checkout の絶対パス)→ <ROOT>(別の場所に clone しても同じ出力にする)
// これ以外(日時の見た目、件数、文言、属性)は触らない。
$in = file_get_contents($argv[1]);

// 複数リクエストのケース(steps)は "=== step N: ..." の見出しで区切られている。
// 本文の tok は、同じ番号の step の Set-Cookie と突き合わせる(step ごとに別の tok が発行されるため)。
// 見出しが無ければ全体を1区画として扱う。head.txt が渡されていればそちらから Set-Cookie を探す。
function split_steps($t) {
    $parts = preg_split('/^(?==== step \d+:)/m', $t);
    return $parts;
}
$headSrc = isset($argv[2]) && is_file($argv[2]) ? file_get_contents($argv[2]) : $in;
$headParts = split_steps($headSrc);
$bodyParts = split_steps($in);
// 先頭の空区画(見出しの前)を落とす
if (count($bodyParts) > 1 && trim($bodyParts[0]) === '') { array_shift($bodyParts); }
if (count($headParts) > 1 && trim($headParts[0]) === '') { array_shift($headParts); }
$res = [];
foreach ($bodyParts as $i => $part) {
    $cookieTok = null;
    $hp = $headParts[$i] ?? $headParts[0] ?? '';
    if (preg_match('/^Set-Cookie: db_age_tok=([0-9a-f]{32})/mi', $hp, $m)) { $cookieTok = $m[1]; }
    $o = preg_replace('/\?v=\d+/', '?v=<MTIME>', $part);
    $o = preg_replace_callback('/(name="tok" value=")([0-9a-f]{32})(")/', function ($m) use ($cookieTok) {
        return $m[1] . ($cookieTok === null || $cookieTok === $m[2] ? '<AGE_TOK>' : '<AGE_TOK-MISMATCH>') . $m[3];
    }, $o);
    $res[] = $o;
}
$out = implode('', $res);
$out = preg_replace('/^(Set-Cookie: db_age_tok=)[0-9a-f]{32}/mi', '$1<AGE_TOK>', $out);
$out = preg_replace('/(name="csrf" value=")[0-9a-f]{64}(")/', '$1<CSRF>$2', $out);
$out = preg_replace('/^(Set-Cookie: db_vid=)[0-9a-f]{32}/mi', '$1<VISITOR_ID>', $out);
// Set-Cookie 1行ごとに、expires と Max-Age を突き合わせる
$out = preg_replace_callback('/^Set-Cookie:.*$/mi', function ($m) {
    $line = $m[0];
    $maxAge = preg_match('/Max-Age=(\d+)/i', $line, $a) ? (int)$a[1] : null;
    $exp = preg_match('/expires=([^;\r\n]+)/i', $line, $b) ? strtotime($b[1]) : null;
    if ($exp !== null) {
        $ok = $maxAge !== null && $exp !== false && abs(($exp - time()) - $maxAge) <= 30;
        $line = preg_replace('/(expires=)[^;\r\n]+/i', '$1' . ($ok ? '<DATE:ok>' : '<DATE:MISMATCH>'), $line);
    }
    if ($maxAge !== null) {
        $line = preg_replace('/Max-Age=\d+/i', 'Max-Age=<MAX_AGE:' . (int)round($maxAge / 86400) . 'd>', $line);
    }
    return $line;
}, $out);
// checkout の絶対パス(tests/runner/ の2つ上)
$root = realpath(dirname(__DIR__, 2));
$out = str_replace($root, '<ROOT>', $out);
echo $out;
