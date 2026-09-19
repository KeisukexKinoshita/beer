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
    $st = db()->prepare("UPDATE visitor SET age_confirmed = 1 WHERE visitor_id = :v");
    $st->execute([':v' => $visitorId]);
}

/**
 * 年齢確認のあとに戻す先。**許可した内部のパスだけ**を受け付ける。
 *
 * 以前は「/ で始まり // でない」という形の検査だったが、`/\evil.com` が通ってしまった。
 * ブラウザは URL の中の `\` を `/` として扱うので、それは `//evil.com`(外部サイト)と同じ。
 * タブや改行を混ぜる細工もある。**形で見るのをやめ、名前で許す。**
 */
const AGE_NEXT_ALLOWED = ['/', '/index.php', '/try.php'];

function age_next($next): string
{
    return (is_string($next) && in_array($next, AGE_NEXT_ALLOWED, true)) ? $next : '/index.php';
}
