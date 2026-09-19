<?php
// 特性テスト用ページ実行器。1ケース=1プロセスで対象ページをCLI実行し、
// 出力(HTML + 警告)を標準出力にそのまま流す。
//   php exec_page.php <sandbox内のページ相対パス> [params.json]
// params.json: {"get": {...}, "post": {...}} (省略可)
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '0'); // display側と二重に出るのを防ぐ

if ($argc < 2) {
    fwrite(STDERR, "usage: php exec_page.php <page> [params.json]\n");
    exit(2);
}

$sandbox = dirname(__DIR__) . '/sandbox';
$page = $sandbox . '/' . $argv[1];
if (!is_file($page)) {
    fwrite(STDERR, "page not found: {$page}\n");
    exit(2);
}

if ($argc >= 3) {
    $params = json_decode(file_get_contents($argv[2]), true);
    if (!is_array($params)) {
        fwrite(STDERR, "bad params json: {$argv[2]}\n");
        exit(2);
    }
    $_GET = $params['get'] ?? [];
    $_POST = $params['post'] ?? [];
    $_REQUEST = array_merge($_GET, $_POST);
}

// リニューアル後のページ (index.php, beer/, brewery/, style/ 等) は
// `$_SERVER['DOCUMENT_ROOT'] . '/common/nebula/...'` の形で共通部品を読む。
// 本番のWebサーバではDOCUMENT_ROOTが公開ルートに設定されるが、CLI実行では
// 未設定(空文字列)のため、絶対パス `/common/nebula/helpers.php` を
// ファイルシステムのルート直下に対して開こうとして Fatal error になる。
// sandbox 自体がその公開ルートに相当するので、ここで明示的に合わせる。
$_SERVER['DOCUMENT_ROOT'] = $sandbox;

chdir(dirname($page));
require basename($page);
