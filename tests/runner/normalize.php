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
//   5. Set-Cookie の expires=…/Max-Age=…   → expires=<DATE>/Max-Age=<N>
// これ以外(日時の見た目、件数、文言、属性)は触らない。
$in = file_get_contents($argv[1]);

// Set-Cookie の db_age_tok の値(本文の tok と突き合わせるため)
$cookieTok = null;
$headSrc = isset($argv[2]) && is_file($argv[2]) ? file_get_contents($argv[2]) : $in;
if (preg_match('/^Set-Cookie: db_age_tok=([0-9a-f]{32})/mi', $headSrc, $m)) { $cookieTok = $m[1]; }

$out = preg_replace('/\?v=\d+/', '?v=<MTIME>', $in);
$out = preg_replace_callback('/(name="tok" value=")([0-9a-f]{32})(")/', function ($m) use ($cookieTok) {
    return $m[1] . ($cookieTok === null || $cookieTok === $m[2] ? '<AGE_TOK>' : '<AGE_TOK-MISMATCH>') . $m[3];
}, $out);
$out = preg_replace('/^(Set-Cookie: db_age_tok=)[0-9a-f]{32}/mi', '$1<AGE_TOK>', $out);
$out = preg_replace('/(name="csrf" value=")[0-9a-f]{64}(")/', '$1<CSRF>$2', $out);
$out = preg_replace('/^(Set-Cookie: db_vid=)[0-9a-f]{32}/mi', '$1<VISITOR_ID>', $out);
$out = preg_replace('/(expires=)[^;\r\n]+/i', '$1<DATE>', $out);
$out = preg_replace('/(Max-Age=)\d+/i', '$1<N>', $out);
echo $out;
