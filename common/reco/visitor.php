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

    // PHP の date() ではなく DB の NOW() を使う。アプリサーバとDBサーバでタイムゾーンが
    // 食い違うと(例: PHPがUTC、DBがJST)、upload.created_at がここと同じズレを持ち、
    // gate_context() の CURDATE() 比較(日次・月次の上限)が毎日9時間ぶん効かなくなる
    // (最終レビューC-2の確認中に発見。同じ理由で repo.php の各 INSERT も直した)。
    $st = db()->prepare(
        "INSERT INTO visitor (visitor_id, first_seen, last_seen)
         VALUES (:id, NOW(), NOW())
         ON DUPLICATE KEY UPDATE last_seen = NOW()");
    $st->execute([':id' => $id]);

    return $id;
}
