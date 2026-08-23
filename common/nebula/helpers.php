<?php
/* Nebula リニューアル共通ヘルパー (フェーズ1) — データ取得・整形・分類 */

/** PDO接続 (db_config.local.php を使う。dev=beer_dev / prod=beer) */
function db() {
    static $pdo = null;
    if ($pdo === null) {
        require $_SERVER['DOCUMENT_ROOT'] . '/db_config.local.php'; // $db_dsn,$db_user,$db_pass
        $pdo = new PDO($db_dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

/** htmlspecialchars 短縮 */
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** DECIMAL値の末尾ゼロを落とす (20.000→20, 7.500→7.5) */
function fmt_num($v) {
    $t = rtrim(rtrim(number_format((float)$v, 3, '.', ''), '0'), '.');
    return $t === '' ? '0' : $t;
}

/** 国コード(JP等) → 国旗絵文字 */
function flag($cc) {
    $cc = strtoupper(trim((string)$cc));
    if (strlen($cc) !== 2 || !ctype_alpha($cc)) return '🏳️';
    return mb_chr(0x1F1E6 + ord($cc[0]) - 65) . mb_chr(0x1F1E6 + ord($cc[1]) - 65);
}

/** 国コード → 表示名(簡易) */
function country_name($cc) {
    static $m = ['JP'=>'日本','US'=>'アメリカ','GB'=>'イギリス','CA'=>'カナダ','DK'=>'デンマーク'];
    $cc = strtoupper((string)$cc);
    return $m[$cc] ?? $cc;
}

/**
 * スタイル(FamilyName/StyleName)を5つの表示グループに分類する。
 * 銀河の天体色・カード・凡例で共通利用。JS側 (assets/js/galaxy.js STYLE_GROUP) と一致させること。
 * @return string ipa|stout|sour|pale|other
 */
function style_group($family, $name) {
    $hay = (string)$family . ' ' . (string)$name;
    if (stripos($hay, 'sour') !== false || stripos($hay, 'wild') !== false) return 'sour';
    if ((string)$family === 'IPA' || stripos($name, 'ipa') !== false) return 'ipa';
    if (stripos($hay, 'stout') !== false || stripos($hay, 'porter') !== false) return 'stout';
    if (stripos($family, 'pale') !== false || stripos($family, 'amber') !== false
        || stripos($family, 'belgian') !== false || stripos($family, 'brown') !== false) return 'pale';
    return 'other';
}

/** グループ→ {color,label} */
function group_meta($g) {
    static $m = [
        'ipa'   => ['#5fd0ff', 'IPA系'],
        'stout' => ['#b98cff', 'Stout / 黒'],
        'sour'  => ['#ff6fb0', 'Sour'],
        'pale'  => ['#ffd06b', 'Pale / Amber'],
        'other' => ['#5cf0c2', 'Lager / その他'],
    ];
    return $m[$g] ?? $m['other'];
}

/** アクセシブルな星評価マークアップ (視覚★ + 数値 + aria-label) */
function stars_html($rating) {
    $r = (float)$rating;
    $full = (int)round($r);
    $full = max(0, min(5, $full));
    $vis = str_repeat('★', $full) . str_repeat('☆', 5 - $full);
    return '<span class="rate" role="img" aria-label="5段階評価で' . number_format($r, 1) . '点">'
         . $vis . '</span>';
}

/* ---- クエリ ---- */

/** 全ビール (maker/style を結合)。一覧・銀河・詳細で共通 */
function all_beers() {
    $sql = "SELECT p.ProductID, p.ProductName, p.Alcohol, p.IBU_all, p.IBU, p.Fruity, p.Color,
                   p.Clarity, p.Favorite, p.ProductExplain,
                   p.StyleID, s.StyleName, s.FamilyName,
                   p.MakerID, m.MakerName, m.country_code, m.latitude, m.longitude
            FROM products p
            LEFT JOIN style s ON s.StyleID = p.StyleID
            LEFT JOIN maker m ON m.MakerID = p.MakerID
            ORDER BY p.ProductID DESC";
    return db()->query($sql)->fetchAll();
}

/** 1銘柄 */
function beer_by_id($pid) {
    $st = db()->prepare(
        "SELECT p.*, s.StyleName, s.FamilyName, s.StyleExplain, s.catchcopy,
                m.MakerName, m.country_code, m.latitude, m.longitude, m.URL1, m.logo_path
         FROM products p
         LEFT JOIN style s ON s.StyleID = p.StyleID
         LEFT JOIN maker m ON m.MakerID = p.MakerID
         WHERE p.ProductID = ?");
    $st->execute([$pid]);
    return $st->fetch() ?: null;
}

/** 全ブリュワリー (取扱ビール数つき) */
function all_makers() {
    return db()->query(
        "SELECT m.*, COUNT(p.ProductID) AS beer_count
         FROM maker m LEFT JOIN products p ON p.MakerID = m.MakerID
         GROUP BY m.MakerID ORDER BY m.MakerID")->fetchAll();
}

/** 1ブリュワリー */
function maker_by_id($mid) {
    $st = db()->prepare("SELECT * FROM maker WHERE MakerID = ?");
    $st->execute([$mid]);
    return $st->fetch() ?: null;
}

/** 指定ブリュワリーのビール */
function beers_by_maker($mid) {
    $st = db()->prepare(
        "SELECT p.*, s.StyleName, s.FamilyName FROM products p
         LEFT JOIN style s ON s.StyleID = p.StyleID
         WHERE p.MakerID = ? ORDER BY p.ProductID DESC");
    $st->execute([$mid]);
    return $st->fetchAll();
}

/** 銀河/一覧JS用に軽量化した配列 */
function beers_for_js($beers) {
    $out = [];
    foreach ($beers as $b) {
        $g = style_group($b['FamilyName'] ?? '', $b['StyleName'] ?? '');
        $out[] = [
            'id'   => $b['ProductID'],
            'n'    => $b['ProductName'],
            'a'    => (float)$b['Alcohol'],
            'i'    => (float)$b['IBU_all'],
            'f'    => (float)$b['Fruity'],
            'c'    => (float)$b['Color'],
            'r'    => (float)$b['Favorite'],
            'g'    => $g,
            'st'   => $b['StyleName'] ?: '—',
            'mk'   => $b['MakerName'] ?: '',
            'cc'   => $b['country_code'] ?: '',
        ];
    }
    return $out;
}
