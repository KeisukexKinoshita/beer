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

    // PHPの time()/strtotime() ではなく、DBの NOW() を使って経過秒数をDB側で計算する。
    // アプリサーバとDBサーバでタイムゾーンが食い違う(このコンテナは PHP=UTC / DB=JST)と、
    // PHP側で created_at 文字列を strtotime() し直した瞬間にズレが混入し、バースト制限
    // (20秒)が効かなくなったり逆に誤発動したりする(最終レビューC-2の確認中に発見)。
    $lastAt = db()->prepare(
        "SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) FROM upload
         WHERE visitor_id = :v ORDER BY upload_id DESC LIMIT 1");
    $lastAt->execute([':v' => $visitorId]);
    $secsSince = $lastAt->fetchColumn();

    // API失敗(status='api_error')は「ビール以外」ではない。連続カウントに混ぜると、
    // 失敗が続いただけの利用者の枠を誤って絞ってしまう。
    $streak = db()->prepare(
        "SELECT is_beer FROM upload WHERE visitor_id = :v AND status = 'ok' ORDER BY upload_id DESC LIMIT 5");
    $streak->execute([':v' => $visitorId]);
    $recent = $streak->fetchAll(PDO::FETCH_COLUMN);
    $notBeer = 0;
    foreach ($recent as $b) { if ((int)$b === 0) { $notBeer++; } else { break; } }

    return [
        'enabled'         => !empty($cfg['reco_enabled']),
        'daily_cap'       => (int)($cfg['daily_cap'] ?? GATE_LIMITS['global_daily']),
        'mime'            => $mime,
        'bytes'           => $bytes,
        'seconds_since'   => $secsSince !== false ? (int)$secsSince : PHP_INT_MAX,
        // 件数は status で絞らない。API失敗(api_error)も課金は発生しているので、
        // 日次・月次・visitor の上限には必ず数える(最終レビュー C-2)。
        'visitor_today'   => $one("SELECT COUNT(*) FROM upload WHERE visitor_id = :v AND DATE(created_at) = CURDATE()", [':v' => $visitorId]),
        'global_today'    => $one("SELECT COUNT(*) FROM upload WHERE DATE(created_at) = CURDATE()", []),
        'global_month'    => $one("SELECT COUNT(*) FROM upload WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())", []),
        'hash_seen'       => upload_by_hash($hash) !== null,
        'not_beer_streak' => $notBeer,
    ];
}

/**
 * status='ok' の行だけを返す。失敗した記録(api_error)を「前回の結果」として
 * 再利用すると、同じ写真の再送が毎回 API を呼び直さずに嘘の失敗を返し続けるか、
 * 逆に失敗の記録を成功のように見せてしまう(最終レビュー C-2)。
 */
function upload_by_hash(string $hash): ?array
{
    $st = db()->prepare(
        "SELECT * FROM upload WHERE image_hash = :h AND status = 'ok' ORDER BY upload_id DESC LIMIT 1");
    $st->execute([':h' => $hash]);
    $row = $st->fetch();
    return $row ?: null;
}

/**
 * @param string $status 'ok'(通常)または 'api_error'(APIは呼ばれ課金された可能性が
 *                        あるが、判定に失敗した)。上限のカウントに必ず含めるため、
 *                        失敗でも upload に1行残す(最終レビュー C-2)。
 */
function upload_record(string $visitorId, array $r, string $hash, ?string $imagePath, string $status = 'ok'): int
{
    // created_at は DB の NOW() で入れる。PHP の date() を使うと、アプリサーバと
    // DBサーバのタイムゾーンが食い違ったとき、この直後に読む CURDATE() 比較
    // (日次・月次の上限)や TIMESTAMPDIFF(バースト制限)とズレる(最終レビューC-2)。
    $st = db()->prepare(
        "INSERT INTO upload
           (visitor_id, created_at, image_path, image_hash, is_beer, product_id,
            brand_text, brewery_text, style_guess, color, clarity, confidence, model, status)
         VALUES
           (:v, NOW(), :path, :hash, :isbeer, :pid, :brand, :brewery, :style, :color, :clarity, :conf, :model, :status)");
    $st->execute([
        ':v' => $visitorId,
        ':path' => $imagePath, ':hash' => $hash,
        ':isbeer' => ($r['is_beer'] === true) ? 1 : 0,
        ':pid' => $r['matched_product_id'] ?: null,
        ':brand' => $r['brand_text'] ?: null, ':brewery' => $r['brewery_text'] ?: null,
        ':style' => $r['style_guess'] ?: null,
        ':color' => $r['color'], ':clarity' => $r['clarity'],
        ':conf' => $r['confidence'], ':model' => $r['model'] ?? null,
        ':status' => $status,
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
         VALUES (:b, :w, 1, NOW())
         ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = NOW()");
    $st->execute([':b' => $brand, ':w' => $brewery]);
}

function reco_record(int $uploadId, array $picked): void
{
    $st = db()->prepare(
        "INSERT INTO recommendation (upload_id, position, product_id, stage, created_at)
         VALUES (:u, :pos, :pid, :stage, NOW())");
    $pos = 0;
    foreach ($picked as $p) {
        $pos++;
        $st->execute([':u' => $uploadId, ':pos' => $pos,
                      ':pid' => $p['ProductID'], ':stage' => $p['stage']]);
    }
}

function event_record(string $visitorId, string $kind, ?string $target, ?int $uploadId): void
{
    $st = db()->prepare(
        "INSERT INTO event (visitor_id, created_at, kind, target, upload_id)
         VALUES (:v, NOW(), :k, :t, :u)");
    $st->execute([':v' => $visitorId,
                  ':k' => $kind, ':t' => $target, ':u' => $uploadId]);
}
