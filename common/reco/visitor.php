<?php
declare(strict_types=1);
/*
 * 匿名の訪問者ID(設計書 §4)。氏名もメールも取らない。
 * LINE 連携は visitor.line_user_id を埋めるだけで、過去の記録がそのまま繋がる。
 */

require_once __DIR__ . '/../nebula/helpers.php';

const VISITOR_COOKIE = 'db_vid';
const VISITOR_TTL    = 31536000;   // 1年

function visitor_current(): string
{
    $id = $_COOKIE[VISITOR_COOKIE] ?? '';
    if (!preg_match('/^[0-9a-f]{32}$/', $id)) {
        $id = bin2hex(random_bytes(16));
    }
    setcookie(VISITOR_COOKIE, $id, [
        'expires'  => time() + VISITOR_TTL,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $now = date('Y-m-d H:i:s');
    $st = db()->prepare(
        "INSERT INTO visitor (visitor_id, first_seen, last_seen)
         VALUES (:id, :now, :now)
         ON DUPLICATE KEY UPDATE last_seen = :now2");
    $st->execute([':id' => $id, ':now' => $now, ':now2' => $now]);

    return $id;
}
