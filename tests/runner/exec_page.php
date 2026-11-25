<?php
// 特性テスト用ページ実行器。1ケース=1プロセスで対象ページをCLI実行し、
// 出力(HTML + 警告)を標準出力にそのまま流す。
//   php exec_page.php <sandbox内のページ相対パス> [params.json] [head出力パス]
// params.json:
//   get / post   : $_GET / $_POST
//   cookies      : $_COOKIE  (例 {"db_vid": "<32hex>"})
//   host         : $_SERVER['HTTP_HOST']  (既定 beer.test。GA4 は drtbeer.com のときだけ出る)
//   uri          : $_SERVER['REQUEST_URI'] (既定は /<page>?<get のクエリ>)
//   method       : $_SERVER['REQUEST_METHOD'] (既定 GET)
// head出力パス: 指定すると、終了時(exit や Fatal の後も)に HTTP ステータスと
//   応答ヘッダを書き出す。1行目 "Status: <code>"、以降 "Name: value"(X-Powered-By は除く)。
//   ステータスとヘッダ(Cache-Control / Vary / Location / Set-Cookie)は比較対象。
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '0'); // display側と二重に出るのを防ぐ

if ($argc < 2) {
    fwrite(STDERR, "usage: php exec_page.php <page> [params.json] [head-out]\n");
    exit(2);
}

$sandbox = dirname(__DIR__) . '/sandbox';
$page = $sandbox . '/' . $argv[1];
if (!is_file($page)) {
    fwrite(STDERR, "page not found: {$page}\n");
    exit(2);
}

$params = [];
if ($argc >= 3) {
    $params = json_decode(file_get_contents($argv[2]), true);
    if (!is_array($params)) {
        fwrite(STDERR, "bad params json: {$argv[2]}\n");
        exit(2);
    }
    $_GET = $params['get'] ?? [];
    $_POST = $params['post'] ?? [];
    $_REQUEST = array_merge($_GET, $_POST);
    $_COOKIE = $params['cookies'] ?? [];
}

// リクエストの文脈。CLI では未設定の値を、本番の Web サーバと同じ形で用意する。
$_SERVER['HTTP_HOST']      = $params['host'] ?? 'beer.test';
$_SERVER['REQUEST_METHOD'] = $params['method'] ?? 'GET';
$_SERVER['REQUEST_URI']    = $params['uri'] ?? ('/' . $argv[1] . ($_GET ? '?' . http_build_query($_GET) : ''));

// 応答ヘッダの書き出し。ページが exit しても Fatal で落ちても、終了時に必ず走る。
if ($argc >= 4) {
    // ページ側が chdir するので、相対パスはここで絶対にしておく
    $headOut = $argv[3][0] === '/' ? $argv[3] : getcwd() . '/' . $argv[3];
    register_shutdown_function(function () use ($headOut) {
        $code = http_response_code();
        $lines = ['Status: ' . ($code === false ? 200 : $code)];
        foreach (headers_list() as $h) {
            if (stripos($h, 'X-Powered-By:') === 0) { continue; }  // PHP のバージョンで変わる
            $lines[] = $h;
        }
        file_put_contents($headOut, implode("\n", $lines) . "\n");
    });
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
