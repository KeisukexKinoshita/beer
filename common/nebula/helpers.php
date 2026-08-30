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

/** DECIMAL値の末尾ゼロを落とす (20.000→20, 7.500→7.5)
 *  **値があることが分かっている場合にだけ使う。** 未計測かもしれない値は fmt_unit() を通すこと
 *  (この関数は (float)null → 0 とするので、未計測が「0」になる)。 */
function fmt_num($v) {
    $t = rtrim(rtrim(number_format((float)$v, 3, '.', ''), '0'), '.');
    return $t === '' ? '0' : $t;
}

/** 未計測(NULL)かどうか。0 は「本当に0」であって未計測ではない */
function is_unmeasured($v) { return $v === null || $v === ''; }

/**
 * スペック値の表示。**未計測(NULL)は 0 ではない。**
 *
 * 未計測を 0 と描くと「苦味がゼロのビール」「アルコール度0%」という存在しない事実を
 * 読み手に伝えてしまう。DB 側の同じ誤り(未計測が NULL ではなく 0 で記録されていた15件)は
 * db/seeds/008_fix_ibu_zero_20260829.sql で直したが、表示側は (float)null → 0 のまま
 * 残っていた —— つまり DB を NULL に直しても画面には「0」と出ていた。
 */
function fmt_unit($v, $unit = '') {
    if (is_unmeasured($v)) return '—';
    return fmt_num($v) . $unit;
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
    // Black IPA は American Black Ale の呼称違い(規律 §9-1 2-b)。IPA 系に寄せる。
    // 「Black Lager」は Ale ではないのでここには入らない。
    if ((string)$family === 'IPA' || stripos($name, 'ipa') !== false
        || stripos($name, 'black ale') !== false) return 'ipa';
    // 小麦ビール。IPA 判定の後に置くことで「White IPA」は IPA 側に残る。
    // 「White Ale」はベルギー系の白ビール(=ヴィットビア)で、実質 Witbier と同じ
    // スタイルなので、FamilyName が Belgian Styles でも小麦として扱う。
    if ((string)$family === 'Weizen' || (string)$family === 'Wheat Beers'
        || stripos($name, 'weizen') !== false || stripos($name, 'weisse') !== false
        || stripos($name, 'wheat')  !== false || stripos($name, 'witbier') !== false
        || stripos($name, 'white ale') !== false) return 'wheat';
    if (stripos($hay, 'stout') !== false || stripos($hay, 'porter') !== false) return 'stout';
    if (stripos($family, 'pale') !== false || stripos($family, 'amber') !== false
        || stripos($family, 'belgian') !== false || stripos($family, 'brown') !== false) return 'pale';
    return 'other';
}

/**
 * スタイルグループの定義。**色とラベルの単一の出所はここ。**
 *
 * 以前は同じ値が4ファイル(ここ / nebula.css の CSS変数 / galaxy.js の GALAXY_COL・
 * GALAXY_LABEL / beerglass.js の GC)に重複し、さらに beer/products.php の
 * 絞り込みチップも別に持っていた。グループを1つ増やすのに5箇所を揃える必要があり、
 * 実際に wheat を足したときに全部を手で直した。
 *
 * いまは PHP から window.STYLE_GROUPS として JS へ渡し(common/nebula/footer.php)、
 * 絞り込みチップもこの配列から生成する。**足すときはこの1箇所だけ**。
 * 表示順は凡例・チップの並び順になる。
 */
function group_map() {
    static $m = [
        'ipa'   => ['#5fd0ff', 'IPA系'],
        'stout' => ['#b98cff', 'Stout / 黒'],
        'sour'  => ['#ff6fb0', 'Sour'],
        'pale'  => ['#ffd06b', 'Pale / Amber'],
        'wheat' => ['#c8e86a', '小麦 / Weizen'],
        'other' => ['#5cf0c2', 'Lager / その他'],
    ];
    return $m;
}

/** グループ→ {color,label} */
function group_meta($g) {
    $m = group_map();
    return $m[$g] ?? $m['other'];
}

/** JS へ渡す形 {key: {c: 色, l: ラベル}} */
function group_map_js() {
    $out = [];
    foreach (group_map() as $k => $v) { $out[$k] = ['c' => $v[0], 'l' => $v[1]]; }
    return $out;
}

/** アクセシブルな星評価マークアップ (視覚★ + 数値 + aria-label) */
function stars_html($rating) {
    // 未評価(NULL)を 0 点として描かない。152銘柄中114銘柄が未評価で、
    // そのすべてが「5段階評価で0.0点」と読み上げられていた(fmt_unit() と同じ誤り)。
    if (is_unmeasured($rating)) {
        return '<span class="rate rate-none" role="img" aria-label="評価はまだありません">'
             . '☆☆☆☆☆</span>';
    }
    $r = (float)$rating;
    $full = (int)round($r);
    $full = max(0, min(5, $full));
    $vis = str_repeat('★', $full) . str_repeat('☆', 5 - $full);
    return '<span class="rate" role="img" aria-label="5段階評価で' . number_format($r, 1) . '点">'
         . $vis . '</span>';
}

/**
 * ビールのプレースホルダー(グラス+微かな宇宙)を出力する。
 * 仕様9-1: 商品写真は使わず、そのビール自身の数値から生成する。
 * 実描画は assets/js/beerglass.js (canvas)。JSが無効でも枠が崩れないよう背景色を持たせる。
 */
function beer_glass_tag($b, $class = '') {
    $g = style_group($b['FamilyName'] ?? '', $b['StyleName'] ?? '');
    // 読み上げにも「未計測」がそのまま伝わるよう fmt_unit() を通す
    // (以前は fmt_num() で、未計測が「IBU0 から生成」と読まれていた)
    $label = ($b['ProductName'] ?? 'このビール') . ' のイメージ（'
           . '色' . fmt_unit($b['Color'] ?? null) . '・'
           . 'アルコール度' . fmt_unit($b['Alcohol'] ?? null, '%') . '・'
           . 'IBU' . fmt_unit($b['IBU_all'] ?? null) . ' から生成）';
    return '<canvas class="beerglass ' . e($class) . '"'
         . ' data-bid="' . e($b['ProductID'] ?? '') . '"'
         . ' data-c="'   . e($b['Color'] ?? '') . '"'
         . ' data-cl="'  . e($b['Clarity'] ?? '') . '"'
         . ' data-f="'   . e($b['Fruity'] ?? '') . '"'
         . ' data-i="'   . e($b['IBU_all'] ?? '') . '"'
         . ' data-a="'   . e($b['Alcohol'] ?? '') . '"'
         . ' data-g="'   . e($g) . '"'
         . ' role="img" aria-label="' . e($label) . '"></canvas>';
}

/**
 * その列がスタイルからの推定値かを判定する (products.estimated_fields はカンマ区切り)。
 * 仕様: 推定した場合は必ず記録し、詳細ページで「推定値」と補記する。
 */
function is_estimated($beer, $field) {
    $raw = trim((string)($beer['estimated_fields'] ?? ''));
    if ($raw === '') return false;
    return in_array($field, array_map('trim', explode(',', $raw)), true);
}

/** 推定値の注記(該当しなければ空文字)。詳細ページ用 */
function est_note($beer, $field) {
    return is_estimated($beer, $field)
        ? ' <span class="est-note" title="公式情報が無いため、同じスタイルの典型値から推定した値です">推定</span>'
        : '';
}

/**
 * 推定値の注記(一覧カード用の小さい版)。
 *
 * 一覧・トップ・ブリュワリー詳細・Type別詳細のカードは、以前は注記を出しておらず
 * **実測値と推定値が同じ見た目**だった。IBU_all は152件中86件が推定なので、
 * 一覧を眺める人は半数以上が推定だと分からない状態だった(仕様の「黙って推定しない」に反する)。
 *
 * 記号(* など)にすると凡例なしでは意味が伝わらず、タッチ環境ではツールチップも出ない。
 * **詳細ページと同じ「推定」という語を、小さいサイズで使う**。
 * サイト内で語彙を増やさないほうが読み手に負担が少ない。
 */
function est_mark($beer, $field) {
    return is_estimated($beer, $field)
        ? '<span class="est-mark" title="公式情報が無いため、同じスタイルの典型値から推定した値です">推定</span>'
        : '';
}

/* ---- クエリ ---- */

/** 全ビール (maker/style を結合)。一覧・銀河・詳細で共通 */
function all_beers() {
    $sql = "SELECT p.ProductID, p.ProductName, p.Alcohol, p.IBU_all, p.IBU, p.Fruity, p.Color,
                   p.Clarity, p.Favorite, p.ProductExplain, p.estimated_fields,
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

/** 1スタイル(Type) */
function style_by_id($sid) {
    $st = db()->prepare("SELECT * FROM style WHERE StyleID = ?");
    $st->execute([$sid]);
    return $st->fetch() ?: null;
}

/** 指定スタイルのビール */
function beers_by_style($sid) {
    $st = db()->prepare(
        "SELECT p.*, s.StyleName, s.FamilyName, m.MakerName, m.country_code FROM products p
         LEFT JOIN style s ON s.StyleID = p.StyleID
         LEFT JOIN maker m ON m.MakerID = p.MakerID
         WHERE p.StyleID = ? ORDER BY p.Favorite DESC");
    $st->execute([$sid]);
    return $st->fetchAll();
}

/** 実際にビールが紐づくスタイル(Type)を人気順(取扱数→平均評価)で返す */
function styles_used() {
    return db()->query(
        "SELECT s.StyleID, s.StyleName, s.FamilyName, s.StyleExplain, s.catchcopy,
                COUNT(p.ProductID) AS beer_count, AVG(p.Favorite) AS avg_rating
         FROM style s JOIN products p ON p.StyleID = s.StyleID
         GROUP BY s.StyleID
         ORDER BY beer_count DESC, avg_rating DESC")->fetchAll();
}

/** 全スタイル(銘柄が紐づいていないものも含む)。sitemap.php が使う */
function all_styles() {
    return db()->query(
        "SELECT s.StyleID, s.StyleName, s.FamilyName, s.StyleExplain, s.catchcopy,
                (SELECT COUNT(*) FROM products p WHERE p.StyleID = s.StyleID) AS beer_count
         FROM style s
         ORDER BY s.StyleID")->fetchAll();
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
