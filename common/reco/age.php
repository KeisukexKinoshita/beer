<?php
declare(strict_types=1);
/*
 * 年齢確認(設計書 §12)。酒類を扱うので、20歳未満への訴求を避ける。
 *
 * **記憶先はサーバ側**(visitor.age_confirmed)。ブラウザのローカル保存だけにすると
 * Safari では7日で消えて、そのたびに確認画面が出る。
 */

require_once __DIR__ . '/../nebula/helpers.php';

function age_confirmed(string $visitorId): bool
{
    $st = db()->prepare("SELECT age_confirmed FROM visitor WHERE visitor_id = :v");
    $st->execute([':v' => $visitorId]);
    return (int)$st->fetchColumn() === 1;
}

function age_confirm(string $visitorId): void
{
    $st = db()->prepare("UPDATE visitor SET age_confirmed = 1, age_confirmed_at = NOW() WHERE visitor_id = :v");
    $st->execute([':v' => $visitorId]);
}

/**
 * 年齢確認のあとに戻す先。**許可した内部のパスだけ**を受け付ける。
 *
 * 以前は「/ で始まり // でない」という形の検査だったが、`/\evil.com` が通ってしまった。
 * ブラウザは URL の中の `\` を `/` として扱うので、それは `//evil.com`(外部サイト)と同じ。
 * タブや改行を混ぜる細工もある。**形で見るのをやめ、名前で許す。**
 *
 * 公開ページ(幕から戻すとき)は、パスを許可リストにし、詳細ページだけ
 * 「そのページが使う ID のクエリ1つ」を厳密な形で許す。
 */
const AGE_NEXT_ALLOWED = [
    '/', '/index.php', '/try.php', '/history.php',
    '/about.php', '/beer/products.php', '/brewery/makers.php', '/style/styles.php',
];

/** 詳細ページのパス => 受け取るクエリ名。ID は英数字と _ - だけ、20文字まで */
const AGE_DETAIL_PAGES = [
    '/beer/detail/product.php'  => 'ProductID',
    '/brewery/detail/maker.php' => 'MakerID',
    '/style/detail/style.php'   => 'StyleID',
];
const AGE_ID_RE = '/^[A-Za-z0-9_-]{1,20}$/';

function age_next($next): string
{
    if (!is_string($next)) { return '/index.php'; }
    if (in_array($next, AGE_NEXT_ALLOWED, true)) { return $next; }
    foreach (AGE_DETAIL_PAGES as $path => $key) {
        $prefix = $path . '?' . $key . '=';
        if (strpos($next, $prefix) === 0) {
            $id = substr($next, strlen($prefix));
            // \z で末尾の改行も許さない($ だと直前の改行を通す)
            if (preg_match('/^[A-Za-z0-9_-]{1,20}\z/', $id)) { return $next; }
        }
    }
    return '/index.php';
}

/**
 * いま見ているページの URL(REQUEST_URI)から、確認後に戻す先を作る。
 * 余計なクエリ(utm など)は捨て、詳細ページは ID だけ残す。作れなければトップ。
 */
function age_back_url(string $requestUri): string
{
    $path = (string)strtok($requestUri, '?');
    $q    = strpos($requestUri, '?') !== false ? substr($requestUri, strpos($requestUri, '?') + 1) : '';
    if (isset(AGE_DETAIL_PAGES[$path])) {
        parse_str($q, $params);
        $id = $params[AGE_DETAIL_PAGES[$path]] ?? null;
        return (is_string($id) && preg_match('/^[A-Za-z0-9_-]{1,20}\z/', $id))
            ? age_next($path . '?' . AGE_DETAIL_PAGES[$path] . '=' . $id)
            : '/index.php';
    }
    return age_next($path);
}

/**
 * 公開ページに幕を出すか。**読むだけで、何も書かない。**
 *
 * 一覧・詳細にはボットが大量に来る(本番のアクセスの8割以上)。ここで visitor_current() を
 * 呼ぶと、答えていない訪問のたびに行と Cookie が増えるので、既存の Cookie を読んで
 * 確認済みかを引くだけにする。Cookie が無い(ボット・初回)ならDBも引かない。
 * 渡すのは Cookie の値(テストしやすいように引数にしてある)。
 */
function age_gate_needed($visitorCookie): bool
{
    if (!is_string($visitorCookie) || !preg_match('/^[0-9a-f]{32}$/', $visitorCookie)) {
        return true;
    }
    return !age_confirmed($visitorCookie);
}

/*
 * 幕の「はい」「いいえ」の合言葉。**行が無い訪問者でも使えるよう、DB を使わない**(二重送信Cookie)。
 * ページを返すときに乱数を Cookie(ブラウザを閉じれば消える)に置き、同じ値をフォームにも入れる。
 * 外部サイトからの POST には、この Cookie が付かない(SameSite=Lax)うえ、値も知り得ないので通らない。
 */
const AGE_TOK_COOKIE = 'db_age_tok';

function age_tok_new(): string
{
    return bin2hex(random_bytes(16));
}

function age_tok_check($cookie, $field): bool
{
    if (!is_string($cookie) || !is_string($field)) { return false; }
    if (!preg_match('/^[0-9a-f]{32}$/', $cookie)) { return false; }
    return hash_equals($cookie, $field);
}

/** 幕を出すページの合言葉を用意する(Cookie が無ければ発行)。DB には触れない */
function age_tok_issue(): string
{
    $t = $_COOKIE[AGE_TOK_COOKIE] ?? '';
    if (!is_string($t) || !preg_match('/^[0-9a-f]{32}$/', $t)) {
        $t = age_tok_new();
        $_COOKIE[AGE_TOK_COOKIE] = $t;
        setcookie(AGE_TOK_COOKIE, $t, [
            'expires'  => 0,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    return $t;
}
