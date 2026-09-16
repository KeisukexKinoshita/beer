<?php
declare(strict_types=1);
/*
 * 素のPHPの単体テスト走らせ役。
 * PHPUnit を入れないのは、既存の特性テストハーネス(tests/runner/)に
 * 枠組みを増やさないという方針による(設計書 §10)。
 *
 * 使い方:
 *   php tests/unit/run.php            すべて
 *   php tests/unit/run.php cascade    ファイル名に cascade を含むものだけ
 */

$T = ['pass' => 0, 'fail' => 0];

function eq($actual, $expected, string $name): void {
    global $T;
    if ($actual === $expected) { $T['pass']++; echo "  ok   $name\n"; return; }
    $T['fail']++;
    echo "  FAIL $name\n";
    echo "       期待: " . json_encode($expected, JSON_UNESCAPED_UNICODE) . "\n";
    echo "       実際: " . json_encode($actual,   JSON_UNESCAPED_UNICODE) . "\n";
}

function ok(bool $cond, string $name): void { eq($cond, true, $name); }

$filter = $argv[1] ?? '';
foreach (glob(__DIR__ . '/*_test.php') as $f) {
    if ($filter !== '' && strpos(basename($f), $filter) === false) continue;
    echo basename($f) . "\n";
    require $f;
    echo "\n";
}
echo "{$T['pass']} pass / {$T['fail']} fail\n";
exit($T['fail'] === 0 ? 0 : 1);
