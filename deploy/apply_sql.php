<?php
/**
 * SQL適用ランナー (サーバ内でのみ実行する)
 *
 * 使い方:
 *   sudo docker run --rm -v /srv:/srv seisan3-php \
 *        php /srv/beer/deploy/apply_sql.php <dev|prod> <file.sql> [--dry-run] [--allow-ddl] [--yes]
 *
 * 役割:
 *   - db_config.local.php から接続情報を取る (認証情報を引数やログに出さない)
 *   - schema_migrations で適用済みを記録し、二重適用を防ぐ (冪等)
 *   - 既定は DML のみ許可。マイグレーション等の DDL は --allow-ddl を明示したときだけ
 *   - --dry-run は実行して件数を見せた上でロールバックする (DDLを含む場合は使用不可)
 *   - --mark-applied は実行せずに「適用済み」として記録する
 *     (schema_migrations を作る前に手作業で当てた分を後から登録するため)
 *   - prod は適用前に対象テーブルを丸ごとバックアップする
 *   - 接続先(ホスト/DB名/主要テーブル件数)を必ず表示してから実行する
 *     ※ 開発コンテナでは /etc/hosts が RDS のホスト名をローカルDBへ向けていることがあり、
 *        「本番のつもりでテストDBを触る」事故が起こり得る。表示は毎回の確認手段
 */

const TABLES = ['products', 'maker', 'style'];
const DDL_RE = '/^\s*(ALTER|CREATE|DROP|TRUNCATE|RENAME|GRANT|REVOKE|SET\s+GLOBAL)\b/i';

function fail(string $msg): void { fwrite(STDERR, "エラー: {$msg}\n"); exit(1); }
function say(string $msg): void { echo $msg . "\n"; }

/* ---- 引数 ---- */
$args    = array_slice($argv, 1);
$flags   = array_values(array_filter($args, fn($a) => str_starts_with($a, '--')));
$pos     = array_values(array_filter($args, fn($a) => !str_starts_with($a, '--')));
$env     = $pos[0] ?? '';
$sqlPath = $pos[1] ?? '';
$dryRun   = in_array('--dry-run', $flags, true);
$allowDdl = in_array('--allow-ddl', $flags, true);
$markOnly = in_array('--mark-applied', $flags, true);

if (!in_array($env, ['dev', 'prod'], true) || $sqlPath === '') {
    fail("使い方: apply_sql.php <dev|prod> <file.sql> [--dry-run] [--allow-ddl] [--mark-applied]");
}
if (!is_file($sqlPath)) fail("SQLファイルが見つかりません: {$sqlPath}");

/* ---- 接続 ---- */
$cfgPath = "/srv/beer/{$env}/html/db_config.local.php";
if (!is_file($cfgPath)) fail("接続設定が見つかりません: {$cfgPath}");
require $cfgPath;   // $db_dsn, $db_user, $db_pass, $db_name, $db_host
if (empty($db_dsn)) fail("db_config.local.php に \$db_dsn がありません");
if (!str_contains($db_dsn, 'charset=utf8mb4')) {
    fail("DSN に charset=utf8mb4 がありません。絵文字や一部の漢字が壊れるため中止します: {$cfgPath}");
}
$pdo = new PDO($db_dsn, $db_user, $db_pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

/* ---- 接続先の提示(事故防止) ---- */
$who = $pdo->query("SELECT DATABASE() d, @@hostname h, VERSION() v")->fetch();
say("── 接続先の確認 ─────────────────");
say("  環境       : {$env}");
say("  DB         : {$who['d']}");
say("  設定のhost : {$db_host}");
say("  サーバ     : {$who['h']} (MySQL {$who['v']})");
foreach (TABLES as $t) {
    try {
        $c = $pdo->query("SELECT COUNT(*) c FROM `{$t}`")->fetch()['c'];
        say(sprintf("  %-9s %d件", $t, $c));
    } catch (PDOException $e) { say(sprintf("  %-9s (取得不可)", $t)); }
}
say("──────────────────────────────");

/* ---- 適用履歴テーブル ---- */
$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    filename   VARCHAR(255) NOT NULL PRIMARY KEY,
    sha256     CHAR(64)     NOT NULL,
    applied_at DATETIME     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$raw  = file_get_contents($sqlPath);
$name = basename($sqlPath);
$hash = hash('sha256', $raw);

$st = $pdo->prepare("SELECT sha256, applied_at FROM schema_migrations WHERE filename = ?");
$st->execute([$name]);
if ($row = $st->fetch()) {
    if ($row['sha256'] === $hash) {
        say("スキップ: {$name} は適用済みです ({$row['applied_at']})");
        exit(0);
    }
    fail("{$name} は適用済みですが内容が変わっています。"
       . "適用済みのSQLを書き換えず、新しい番号のファイルを作ってください");
}

/* ---- 実行せず記録だけする ---- */
if ($markOnly) {
    $ins = $pdo->prepare("INSERT INTO schema_migrations (filename, sha256, applied_at) VALUES (?, ?, NOW())");
    $ins->execute([$name, $hash]);
    say("記録のみ: {$name} を {$env} で適用済みとして登録しました (SQLは実行していません)");
    exit(0);
}

/* ---- 文の分解と検査 ---- */
$clean = implode("\n", array_filter(explode("\n", $raw), fn($l) => !preg_match('/^\s*--/', $l)));
$stmts = array_values(array_filter(array_map('trim', explode(';', $clean)), fn($s) => $s !== ''));
if (!$stmts) fail("実行できる文がありません");

$ddl = array_values(array_filter($stmts, fn($s) => preg_match(DDL_RE, $s)));
if ($ddl && !$allowDdl) {
    say("DDL が含まれています (先頭3件):");
    foreach (array_slice($ddl, 0, 3) as $d) say("  " . mb_strimwidth(preg_replace('/\s+/', ' ', $d), 0, 100, '…'));
    fail("既定では DML のみ許可しています。マイグレーションなら --allow-ddl を付けてください");
}
foreach (['/\bDROP\s+(DATABASE|SCHEMA)\b/i', '/\bTRUNCATE\b/i', '/\bGRANT\b/i', '/\bSET\s+GLOBAL\b/i'] as $re) {
    foreach ($stmts as $s) if (preg_match($re, $s)) fail("危険な文が含まれています: " . mb_strimwidth($s, 0, 80, '…'));
}
// MySQL は DDL で暗黙コミットするため、DDL入りの --dry-run は「試すつもりが本当に適用される」。
// 実際にこの事故が起きたので、組み合わせ自体を禁止する。
if ($dryRun && $ddl) {
    fail("DDL を含む SQL では --dry-run を使えません。"
       . "MySQL は DDL で暗黙コミットするため巻き戻せず、試すつもりが本適用になります。"
       . "内容の確認は SQL ファイルを読むか、dev で先に適用してください");
}
say("文の数: " . count($stmts) . ($ddl ? " (うちDDL " . count($ddl) . ")" : "") . ($dryRun ? " / DRY-RUN" : ""));

/* ---- prod はバックアップ ---- */
if ($env === 'prod' && !$dryRun) {
    $dir = '/srv/beer/backup';
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    $out = sprintf('%s/beer_%s.sql', $dir, date('Ymd_His'));
    $fh  = fopen($out, 'w');
    fwrite($fh, "-- {$name} 適用前の自動バックアップ / " . date('c') . "\nSET NAMES utf8mb4;\n");
    foreach (TABLES as $t) {
        fwrite($fh, "\n-- === {$t} ===\n");
        foreach ($pdo->query("SELECT * FROM `{$t}`") as $r) {
            $cols = implode(',', array_map(fn($c) => "`{$c}`", array_keys($r)));
            $vals = implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r)));
            fwrite($fh, "INSERT INTO `{$t}` ({$cols}) VALUES ({$vals});\n");
        }
    }
    fclose($fh);
    say("バックアップ: {$out} (" . number_format(filesize($out)) . " bytes)");
}

/* ---- 適用 ---- */
$useTx = !$ddl;   // MySQL は DDL で暗黙コミットされるため、DDL を含む場合はトランザクションに頼らない
if (!$useTx && !$dryRun) say("注意: DDL を含むためトランザクションでは巻き戻せません");
if ($useTx) $pdo->beginTransaction();

$total = 0; $skipped = 0;
foreach ($stmts as $i => $s) {
    $label = mb_strimwidth(preg_replace('/\s+/', ' ', $s), 0, 58, '…');
    try {
        $n = $pdo->exec($s);
        $total += is_int($n) ? $n : 0;
        say(sprintf("  [%2d] %-58s → %s行", $i + 1, $label, is_int($n) ? $n : '-'));
    } catch (PDOException $e) {
        // 既に存在する列/索引は「適用済み」とみなして飛ばす (マイグレーションの冪等運用)
        $dup = str_contains($e->getMessage(), 'Duplicate column')
            || str_contains($e->getMessage(), 'Duplicate key name');
        if ($dup && $allowDdl) {
            say(sprintf("  [%2d] %-58s → 既存のためスキップ", $i + 1, $label));
            $skipped++;
            continue;
        }
        if ($useTx) $pdo->rollBack();
        fail("適用中に失敗しました (" . ($useTx ? "巻き戻しました" : "巻き戻せていません。要確認") . "): "
           . $e->getMessage());
    }
}

if ($dryRun) {   // ここに来るのは DML のみの場合だけ (DDL入りは上で弾いている)
    $pdo->rollBack();
    say("DRY-RUN のため巻き戻しました。影響行数の合計: {$total}");
    exit(0);
}
if ($useTx) $pdo->commit();

$ins = $pdo->prepare("INSERT INTO schema_migrations (filename, sha256, applied_at) VALUES (?, ?, NOW())");
$ins->execute([$name, $hash]);
say("完了: {$name} を {$env} に適用しました (影響行数の合計 {$total}"
  . ($skipped ? " / 既存のためスキップ {$skipped}文" : "") . ")");

/* ---- 適用後の件数 ---- */
foreach (TABLES as $t) {
    try { say(sprintf("  %-9s %d件", $t, $pdo->query("SELECT COUNT(*) c FROM `{$t}`")->fetch()['c'])); }
    catch (PDOException $e) {}
}
