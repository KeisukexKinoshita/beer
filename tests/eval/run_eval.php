<?php
declare(strict_types=1);
/*
 * 画像判定の同定精度を測る(設計書 §10「同定精度の検証(前倒し)」)。
 *
 * **実行すると課金される。** 15枚 × 約0.8円 = 約12円。実行前に利用者に一声かけること。
 * モデルやプロンプトを変えたときに回す。日常のテスト(tests/unit)からは呼ばない。
 *
 * 使い方:
 *   php tests/eval/run_eval.php            実際にAPIを呼ぶ(課金あり)
 *   php tests/eval/run_eval.php --replay   記録済み応答(tests/fixtures/identify/high.json)を
 *                                          15枚全部に対して再生する。課金なし。
 *                                          プロンプトや採点ロジックを変えたときに、
 *                                          スクリプト自体が正しく動くかを確認するための
 *                                          常設の経路(使い捨ての確認コードではない)。
 *
 * 採点方針(このファイルで決めたこと。詳細は各関数のコメントを参照):
 *   - is_beer: 期待値と単純一致。ビールでない2件はここだけを見る(銘柄は問わない)
 *   - 銘柄(DBにある4件): matched_product_id の一致で判定する。テキスト一致より厳格で、
 *     後段が実際に正しい商品ページへ誘導できるかを直接測れるため
 *   - 銘柄(DBに無い9件、in_db: none/maker_only): matched_product_id が null であること
 *     (=幻の一致をしていないこと)に加え、brand_text が正解の銘柄名と十分似ていること
 *   - 例外: IMG_1455 は expected_product_id があるが、DB側の登録名が「有頂点」と誤記されて
 *     いる(db_issue)。ID一致に失敗しても、matched_product_id が null かつ brand_text が
 *     正しい表記(「有頂天」)に十分近ければ「モデルは正しく読めている」として合格に倒す。
 *     これは判定モデルの失点ではなくデータ側の不具合だと設計書・ブリーフの両方が明記している
 *   - 文字一致の判定は正規化(大文字化・空白/記号除去)した上で、(a) 一方が他方を包含するか、
 *     (b) similar_text() の一致率が60%以上か、のどちらかで合格とする。写真の難所(曲面で
 *     先頭が欠ける等)は「部分一致」になりがちなので、包含判定を主軸に据えている
 *   - 蔵名(brewery_text)の一致は**参考値として記録するだけで合否には使わない**。
 *     ラベルが英語表記(例: OIRASE BEER)でDBが日本語表記(奥入瀬ビール)のような
 *     言語違いは、同定精度ではなく表記の問題であり、これを不合格にすると
 *     「読み取れているのに落ちた」が増えて判定の質を見誤る
 *   - style_guess は「st0000 形式のIDで返ったか」を件数で集計する。identify_parse() は
 *     形式が違えば null に落とすので、ここが 0 に近ければプロンプト修正が効いていない
 *   - IMG_4816 / IMG_4817 は同一銘柄(ASTRO AURA)の別写真(表裏)。2枚のbrand_textが
 *     同じ銘柄に読めているかを参考情報として突き合わせる(設計書 §10 が明記する観点)
 */

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 2); // db() が db_config.local.php をここから拾う

require_once __DIR__ . '/../../common/nebula/helpers.php';
require_once __DIR__ . '/../../common/reco/identify.php';
require_once __DIR__ . '/../../common/reco/repo.php';

// ---- 採点まわりのヘルパー ---------------------------------------------

/** 大文字化 + 空白/記号除去。ß は mb_strtoupper が SS に展開してくれる(PHP 8.3で確認済み) */
function reco_eval_normalize(?string $s): string
{
    if ($s === null || $s === '') { return ''; }
    $s = mb_strtoupper($s, 'UTF-8');
    $s = preg_replace(
        '/[\s\x{3000}\x{30FB}\x{FF0D}\x{2010}-\x{2015}\-_.,:;!?！？、。・「」『』（）()\[\]【】]+/u',
        '', $s
    );
    return $s;
}

/**
 * 正規化した上で、(a) 一方が他方を包含する(4文字以上のときだけ判定。短い文字列同士の
 * 包含は誤検出しやすいため) か、(b) 一致率が60%以上かで似ているかを判定する。
 */
function reco_eval_text_match(?string $got, ?string $expect): array
{
    $g = reco_eval_normalize($got);
    $e = reco_eval_normalize($expect);
    if ($g === '' || $e === '') {
        return ['ok' => false, 'pct' => 0.0, 'contains' => false, 'got_norm' => $g, 'expect_norm' => $e];
    }
    $contains = (mb_strlen($g) >= 4 && mb_strlen($e) >= 4) && (str_contains($e, $g) || str_contains($g, $e));
    similar_text($g, $e, $pct);
    $ok = $contains || $pct >= 60.0;
    return ['ok' => $ok, 'pct' => round($pct, 1), 'contains' => $contains, 'got_norm' => $g, 'expect_norm' => $e];
}

/**
 * 1件の銘柄同定を判定する。方式は上のファイル冒頭コメント参照。
 * @return array{ok:?bool, method:string, detail:array}
 */
function reco_eval_product(array $case, array $r): array
{
    if (isset($case['expected_product_id'])) {
        $idOk = ($r['matched_product_id'] === $case['expected_product_id']);
        if ($idOk) {
            return ['ok' => true, 'method' => 'id_match', 'detail' => []];
        }
        // DB側の誤記(db_issue)が明記されているケースは、IDが一致しなくても
        // 正しい表記に近い文字が読めていれば合格に倒す(判定モデルの失点にしない)
        if (!empty($case['db_issue']) && $r['matched_product_id'] === null) {
            $m = reco_eval_text_match($r['brand_text'], $case['product']);
            if ($m['ok']) {
                return ['ok' => true, 'method' => 'db_issue_text_fallback', 'detail' => $m];
            }
            return ['ok' => false, 'method' => 'db_issue_text_fallback', 'detail' => $m];
        }
        return ['ok' => false, 'method' => 'id_match', 'detail' => ['got_pid' => $r['matched_product_id']]];
    }

    // DBに無い銘柄(in_db: none / maker_only)。幻の一致をしていたら即失格
    if ($r['matched_product_id'] !== null) {
        return ['ok' => false, 'method' => 'text_match', 'detail' => ['note' => '登録が無いはずが別の銘柄と結びついた', 'got_pid' => $r['matched_product_id']]];
    }
    $m = reco_eval_text_match($r['brand_text'], $case['product']);
    return ['ok' => $m['ok'], 'method' => 'text_match', 'detail' => $m];
}

// ---- 本体 ---------------------------------------------------------------

$replay = in_array('--replay', $argv, true);

$spec = json_decode(file_get_contents(__DIR__ . '/../../data/sample/expected.json'), true);
$catalog = reco_catalog();
$styles  = reco_style_catalog();

if (!$replay) {
    fwrite(STDERR, "*** 実課金の呼び出しです。15枚 × 約0.8円 = 約12円かかります。***\n");
    fwrite(STDERR, "*** 利用者の確認を得てから実行してください。 ***\n\n");
}

// transport をラップして、識別ロジックには手を入れずに「素の応答」を毎回拾っておく
// (結果JSONに残し、人が採点を検算できるようにするため)。
$lastRaw = null;
$transport = $replay
    ? function (string $path, string $prompt) use (&$lastRaw): array {
        $res = json_decode(file_get_contents(__DIR__ . '/../fixtures/identify/high.json'), true);
        $lastRaw = $res;
        return $res;
      }
    : function (string $path, string $prompt) use (&$lastRaw): array {
        $res = identify_transport_anthropic($path, $prompt);
        $lastRaw = $res;
        return $res;
      };

$rows = [];
$hitBeer = 0; $total = 0;
$hitProduct = 0; $beerTotal = 0;
$styleIdHit = 0; $breweryHit = 0;
$brandByFile = [];

foreach ($spec['cases'] as $c) {
    $path = __DIR__ . '/../../data/sample/' . $c['file'];
    if (!is_file($path)) { echo "見つかりません: {$c['file']}\n"; continue; }

    $lastRaw = null;
    $r = identify_call($path, $catalog, $styles, $transport);
    $total++;

    $beerOk = ($r['is_beer'] === $c['is_beer']);
    if ($beerOk) { $hitBeer++; }

    $prod = null;
    if ($c['is_beer']) {
        $beerTotal++;
        $prod = reco_eval_product($c, $r);
        if ($prod['ok']) { $hitProduct++; }
    }

    if ($r['style_guess'] !== null) { $styleIdHit++; }

    $breweryMatch = reco_eval_text_match($r['brewery_text'] ?? null, $c['brewery'] ?? null);
    if ($c['is_beer'] && $breweryMatch['ok']) { $breweryHit++; }

    $brandByFile[$c['file']] = $r['brand_text'];

    $rows[] = [
        'file'               => $c['file'],
        'expect_beer'        => $c['is_beer'],
        'got_beer'           => $r['is_beer'],
        'beer_ok'            => $beerOk,
        'expect_product'     => $c['product'] ?? null,
        'expect_brewery'     => $c['brewery'] ?? null,
        'expect_product_id'  => $c['expected_product_id'] ?? null,
        'got_brand'          => $r['brand_text'],
        'got_brewery'        => $r['brewery_text'],
        'got_pid'            => $r['matched_product_id'],
        'style_guess'        => $r['style_guess'],
        'confidence'         => $r['confidence'],
        'error'              => $r['error'],
        'product_judgement'  => $prod,
        'brewery_match_ref'  => $breweryMatch,
        'raw_response'       => $lastRaw,
    ];

    printf(
        "%-16s beer:%s  銘柄:%s(%s)  style:%s  確度:%s%s\n",
        $c['file'],
        $beerOk ? 'ok  ' : 'NG  ',
        $prod === null ? '— ' : ($prod['ok'] ? 'ok' : 'NG'),
        $prod === null ? '—' : $prod['method'],
        $r['style_guess'] !== null ? "ID({$r['style_guess']})" : 'null',
        $r['confidence'] === null ? '  —' : number_format((float)$r['confidence'], 2),
        ($prod !== null && !$prod['ok']) ? "  (期待 {$c['product']} / 実際 {$r['brand_text']})" : ''
    );

    if (!$replay) { sleep(1); }
}

// IMG_4816 / IMG_4817 は同一銘柄の表裏。2枚が同じ銘柄に読めているかの参考チェック
$pairMatch = null;
if (isset($brandByFile['IMG_4816.jpeg']) && isset($brandByFile['IMG_4817.jpeg'])) {
    $pairMatch = reco_eval_text_match($brandByFile['IMG_4816.jpeg'], $brandByFile['IMG_4817.jpeg']);
}

printf(
    "\nビール判定 %d/%d   銘柄同定 %d/%d   style_guessがID形式 %d/%d   蔵名の参考一致 %d/%d\n",
    $hitBeer, $total, $hitProduct, $beerTotal, $styleIdHit, $total, $breweryHit, $beerTotal
);
if ($pairMatch !== null) {
    printf("IMG_4816/IMG_4817(同一銘柄の表裏)の brand_text 一致: %s\n", $pairMatch['ok'] ? 'ok' : 'NG');
}

$out = __DIR__ . '/result-' . date('Ymd') . '.json';
file_put_contents($out, json_encode([
    'model'       => IDENTIFY_MODEL,
    'mode'        => $replay ? 'replay' : 'real',
    'ran_at'      => date('c'),
    'is_beer'     => "$hitBeer/$total",
    'product'     => "$hitProduct/$beerTotal",
    'style_id'    => "$styleIdHit/$total",
    'brewery_ref' => "$breweryHit/$beerTotal",
    'pair_match_4816_4817' => $pairMatch,
    'rows'        => $rows,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "結果: $out\n";
