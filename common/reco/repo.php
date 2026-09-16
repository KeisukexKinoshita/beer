<?php
declare(strict_types=1);
/*
 * リコメンド機能のDB入出力。SQLはここに閉じる。
 * カスケード(cascade.php)と門番(gate.php)はDBを知らないまま単体テストできる。
 */

require_once __DIR__ . '/../nebula/helpers.php';
require_once __DIR__ . '/gate.php';

/** 判定プロンプトに載せる銘柄一覧。**並びが変わるとキャッシュが外れるので ProductID 順に固定する** */
function reco_catalog(): array
{
    return db()->query(
        "SELECT p.ProductID, p.ProductName, m.MakerName
         FROM products p LEFT JOIN maker m ON m.MakerID = p.MakerID
         ORDER BY p.ProductID")->fetchAll();
}

/**
 * 判定プロンプトに載せるスタイル一覧。**並びが変わるとプロンプトのキャッシュが
 * 外れて費用が増えるので、StyleID 順に固定する。**
 */
function reco_style_catalog(): array
{
    return db()->query(
        "SELECT s.StyleID, s.StyleName, s.FamilyName
         FROM style s
         ORDER BY s.StyleID")->fetchAll();
}

/** カスケードに渡す候補。all_beers() の行をそのまま使う */
function reco_pool(): array { return all_beers(); }

/** gate_check() に渡す配列を DB から作る */
function gate_context(string $visitorId, string $mime, int $bytes, string $hash): array
{
    $cfgPath = __DIR__ . '/../../api_config.local.php';
    $cfg = is_file($cfgPath) ? require $cfgPath : ['reco_enabled' => false];

    $one = function (string $sql, array $p) {
        $st = db()->prepare($sql); $st->execute($p);
        return (int)$st->fetchColumn();
    };

    $lastAt = db()->prepare(
        "SELECT created_at FROM upload WHERE visitor_id = :v ORDER BY upload_id DESC LIMIT 1");
    $lastAt->execute([':v' => $visitorId]);
    $last = $lastAt->fetchColumn();

    $streak = db()->prepare(
        "SELECT is_beer FROM upload WHERE visitor_id = :v ORDER BY upload_id DESC LIMIT 5");
    $streak->execute([':v' => $visitorId]);
    $recent = $streak->fetchAll(PDO::FETCH_COLUMN);
    $notBeer = 0;
    foreach ($recent as $b) { if ((int)$b === 0) { $notBeer++; } else { break; } }

    return [
        'enabled'         => !empty($cfg['reco_enabled']),
        'daily_cap'       => (int)($cfg['daily_cap'] ?? GATE_LIMITS['global_daily']),
        'mime'            => $mime,
        'bytes'           => $bytes,
        'seconds_since'   => $last ? (time() - strtotime((string)$last)) : PHP_INT_MAX,
        'visitor_today'   => $one("SELECT COUNT(*) FROM upload WHERE visitor_id = :v AND DATE(created_at) = CURDATE()", [':v' => $visitorId]),
        'global_today'    => $one("SELECT COUNT(*) FROM upload WHERE DATE(created_at) = CURDATE()", []),
        'global_month'    => $one("SELECT COUNT(*) FROM upload WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())", []),
        'hash_seen'       => upload_by_hash($hash) !== null,
        'not_beer_streak' => $notBeer,
    ];
}

function upload_by_hash(string $hash): ?array
{
    $st = db()->prepare("SELECT * FROM upload WHERE image_hash = :h ORDER BY upload_id DESC LIMIT 1");
    $st->execute([':h' => $hash]);
    $row = $st->fetch();
    return $row ?: null;
}

function upload_record(string $visitorId, array $r, string $hash, ?string $imagePath): int
{
    $st = db()->prepare(
        "INSERT INTO upload
           (visitor_id, created_at, image_path, image_hash, is_beer, product_id,
            brand_text, brewery_text, style_guess, color, clarity, confidence, model)
         VALUES
           (:v, :now, :path, :hash, :isbeer, :pid, :brand, :brewery, :style, :color, :clarity, :conf, :model)");
    $st->execute([
        ':v' => $visitorId, ':now' => date('Y-m-d H:i:s'),
        ':path' => $imagePath, ':hash' => $hash,
        ':isbeer' => ($r['is_beer'] === true) ? 1 : 0,
        ':pid' => $r['matched_product_id'] ?: null,
        ':brand' => $r['brand_text'] ?: null, ':brewery' => $r['brewery_text'] ?: null,
        ':style' => $r['style_guess'] ?: null,
        ':color' => $r['color'], ':clarity' => $r['clarity'],
        ':conf' => $r['confidence'], ':model' => $r['model'] ?? null,
    ]);
    return (int)db()->lastInsertId();
}

/** 読めたがDBに無い銘柄。**これが次のデータ投入バッチの優先リストになる**(設計書 §7) */
function unknown_bump(string $brand, ?string $brewery): void
{
    // brewery_text は NOT NULL DEFAULT ''(migration 005)。
    // NULL を渡すと MySQL の UNIQUE 索引が NULL を別物として扱い、
    // hits が積み上がらずに行が増え続ける。
    $brewery = $brewery ?? '';
    $st = db()->prepare(
        "INSERT INTO unknown_beer (brand_text, brewery_text, hits, last_seen)
         VALUES (:b, :w, 1, :now)
         ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = :now2");
    $st->execute([':b' => $brand, ':w' => $brewery, ':now' => date('Y-m-d H:i:s'), ':now2' => date('Y-m-d H:i:s')]);
}

function reco_record(int $uploadId, array $picked): void
{
    $st = db()->prepare(
        "INSERT INTO recommendation (upload_id, position, product_id, stage, created_at)
         VALUES (:u, :pos, :pid, :stage, :now)");
    $pos = 0;
    foreach ($picked as $p) {
        $pos++;
        $st->execute([':u' => $uploadId, ':pos' => $pos,
                      ':pid' => $p['ProductID'], ':stage' => $p['stage'],
                      ':now' => date('Y-m-d H:i:s')]);
    }
}

function event_record(string $visitorId, string $kind, ?string $target, ?int $uploadId): void
{
    $st = db()->prepare(
        "INSERT INTO event (visitor_id, created_at, kind, target, upload_id)
         VALUES (:v, :now, :k, :t, :u)");
    $st->execute([':v' => $visitorId, ':now' => date('Y-m-d H:i:s'),
                  ':k' => $kind, ':t' => $target, ':u' => $uploadId]);
}
