<?php
declare(strict_types=1);
/*
 * 画像判定の同定精度を測る(設計書 §10「同定精度の検証(前倒し)」)。
 *
 * **実行すると課金される。** 15枚 × 約0.8円 = 約12円。実行前に利用者に一声かけること。
 * モデルやプロンプトを変えたときに回す。日常のテスト(tests/unit)からは呼ばない。
 *
 * 使い方:
 *   php tests/eval/run_eval.php            実際にAPIを呼ぶ(課金あり)。tests/eval/history.tsv に1行追記する
 *   php tests/eval/run_eval.php --replay   記録済み応答(tests/fixtures/identify/high.json)を
 *                                          15枚全部に対して再生する。課金なし。
 *                                          プロンプトや採点ロジックを変えたときに、
 *                                          スクリプト自体が正しく動くかを確認するための
 *                                          常設の経路(使い捨ての確認コードではない)。
 *                                          再生の数字は意味が無いので history.tsv には書かない
 *   php tests/eval/run_eval.php --note="..." history.tsv の備考列に残すメモ(省略可)
 *
 * 採点方針(このファイルで決めたこと。詳細は各関数のコメントを参照):
 *   - is_beer: 期待値と単純一致。ビールでない2件はここだけを見る(銘柄は問わない)
 *   - 銘柄(DBにある4件): matched_product_id の一致で判定する。テキスト一致より厳格で、
 *     後段が実際に正しい商品ページへ誘導できるかを直接測れるため
 *   - 銘柄(DBに無い9件、in_db: none/maker_only): matched_product_id が null であること
 *     (=幻の一致をしていないこと)に加え、brand_text が正解の銘柄名と十分似ていること
 *   - 例外: IMG_1455 は expected_product_id があるが、DB側の登録名が「有頂点」と誤記されて
 *     いる(expected.json の db_issue_type === "typo")。ID一致に失敗しても、
 *     matched_product_id が null かつ brand_text が正しい表記(「有頂天」)に十分近ければ
 *     「モデルは正しく読めている」として合格に倒す。これは判定モデルの失点ではなく
 *     データ側の不具合だと設計書・ブリーフの両方が明記している。
 *     **この救済は db_issue_type==="typo" のときだけ発動する**(修正ラウンド1
 *     Important-2: 表記ゆれ(name_variant, 例 IMG_1765の「（缶）」)はID一致で
 *     解決できるはずの問題であり、救済レバーにしてはいけない)。救済が発動した件数は
 *     集計行に必ず明記する(黙って数字に混ぜない)
 *   - 文字一致の判定(reco_eval_text_match)は正規化(大文字化・空白/記号除去)した上で、
 *     完全一致 or 「短い方が長い方に完全に含まれ、かつ短い方の長さが長い方の半分を
 *     "超える"」ことを要求する(修正ラウンド1 Critical: 断片一致の是正。閾値の根拠は
 *     同関数のコメントを参照)
 *   - 蔵名(brewery_text)の一致は**参考値として記録するだけで合否には使わない**。
 *     ラベルが英語表記(例: OIRASE BEER)でDBが日本語表記(奥入瀬ビール)のような
 *     言語違いは、同定精度ではなく表記の問題であり、これを不合格にすると
 *     「読み取れているのに落ちた」が増えて判定の質を見誤る
 *     (data/sample/expected.json の grading 欄もこの方針に合わせて書き換え済み)
 *   - style_guess は合否判定はせず(名前同士の曖昧一致がまた必要になるため)、
 *     「st0000 形式のIDで返ったか」を件数で集計しつつ、IDを style テーブルで名前に
 *     解決して期待値(style_on_label)と並べて出す。人が目で見て判断する
 *   - IMG_4816 / IMG_4817 は同一銘柄(ASTRO AURA)の別写真(表裏)。2枚のbrand_textが
 *     同じ銘柄に読めているかを参考情報として突き合わせる(設計書 §10 が明記する観点)
 *   - 画像ファイルが見つからない行は rows から消さず status: "missing_file" として残し、
 *     集計・分母には含めず「対象外 n件」として明示する(修正ラウンド1 Important-3:
 *     分母が黙って縮むのを防ぐ)
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
 * 読み取った文字が正解の銘柄名と一致するか。
 *
 * 修正ラウンド1 Critical: 包含だけで判定すると "BEER" が "75BEER AMERICAN PALE ALE" に
 * 当たってしまう。ラベルを部分的にしか読めなかったのは**失敗**なので、当たりと数えてはいけない。
 * そこで、完全一致でない限り「短い方が長い方に完全に含まれる」ことに加えて
 * 「短い方の長さが長い方の "半分を超える"」ことを要求する。
 *
 * 閾値を 0.6 ではなく 0.5(超過)にしたのはレビューの実例に合わせたため:
 *   - "ORIGINAL" vs "Heineken Original" → 8/16 = 50.0% ちょうど。汎用語1語なので落としたい
 *   - "BREWDOG HAZY JANE" vs "HAZY JANE" → 8/15 = 53.3%。蔵名を前置きしただけの正しい
 *     読みなので通したい
 * 0.6 だと後者まで落ちてしまい、「蔵名が前に付いただけ」を厳しすぎる形で不合格にしてしまう。
 * 「長い方の50%を超える」(50%ちょうどは不合格)であれば、両方を意図通りに分けられる。
 * similar_text() の一致率(pct)は合否には使わず、人が結果JSONで見るための参考値として残す。
 */
function reco_eval_text_match(?string $got, ?string $expect): array
{
    $g = reco_eval_normalize($got);
    $e = reco_eval_normalize($expect);
    similar_text($g, $e, $pct);
    if ($g === '' || $e === '') {
        return ['ok' => false, 'contains' => false, 'length_ratio' => 0.0, 'pct' => 0.0, 'got_norm' => $g, 'expect_norm' => $e];
    }
    if ($g === $e) {
        return ['ok' => true, 'contains' => true, 'length_ratio' => 1.0, 'pct' => round($pct, 1), 'got_norm' => $g, 'expect_norm' => $e];
    }

    $short = mb_strlen($g) <= mb_strlen($e) ? $g : $e;
    $long  = mb_strlen($g) <= mb_strlen($e) ? $e : $g;
    $contains = mb_strpos($long, $short) !== false;
    $ratio = mb_strlen($long) > 0 ? mb_strlen($short) / mb_strlen($long) : 0.0;
    $ok = $contains && $ratio > 0.5;

    return ['ok' => $ok, 'contains' => $contains, 'length_ratio' => round($ratio, 3), 'pct' => round($pct, 1), 'got_norm' => $g, 'expect_norm' => $e];
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
        // DB側の**誤記**(db_issue_type === "typo")だけが救済レバー。
        // 表記ゆれ(name_variant)等はID一致で解決できるはずなので対象外(修正ラウンド1 Important-2)
        if (($case['db_issue_type'] ?? null) === 'typo' && $r['matched_product_id'] === null) {
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
$note = '';
foreach ($argv as $a) {
    if (str_starts_with($a, '--note=')) { $note = substr($a, strlen('--note=')); }
}

$spec = json_decode(file_get_contents(__DIR__ . '/../../data/sample/expected.json'), true);
$catalog = reco_catalog();
$styles  = reco_style_catalog();
$styleNameById = [];
foreach ($styles as $s) { $styleNameById[$s['StyleID']] = $s['StyleName']; }

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
$rescueHit = 0; $rescueFiles = [];
$missing = 0; $missingFiles = [];
$brandByFile = [];

foreach ($spec['cases'] as $c) {
    $path = __DIR__ . '/../../data/sample/' . $c['file'];
    if (!is_file($path)) {
        $missing++;
        $missingFiles[] = $c['file'];
        $rows[] = ['file' => $c['file'], 'status' => 'missing_file'];
        echo "見つかりません(対象外): {$c['file']}\n";
        continue;
    }

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
        if ($prod['method'] === 'db_issue_text_fallback' && $prod['ok']) {
            $rescueHit++;
            $rescueFiles[] = $c['file'];
        }
    }

    if ($r['style_guess'] !== null) { $styleIdHit++; }
    $styleGuessName = $r['style_guess'] !== null ? ($styleNameById[$r['style_guess']] ?? '(不明ID)') : null;

    $breweryMatch = reco_eval_text_match($r['brewery_text'] ?? null, $c['brewery'] ?? null);
    if ($c['is_beer'] && $breweryMatch['ok']) { $breweryHit++; }

    $brandByFile[$c['file']] = $r['brand_text'];

    $rows[] = [
        'file'               => $c['file'],
        'status'             => 'evaluated',
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
        'style_guess_name'   => $styleGuessName,
        'style_on_label'     => $c['style_on_label'] ?? null,
        'confidence'         => $r['confidence'],
        'error'              => $r['error'],
        'product_judgement'  => $prod,
        'brewery_match_ref'  => $breweryMatch,
        'raw_response'       => $lastRaw,
    ];

    printf(
        "%-16s beer:%s  銘柄:%s(%s)  style:%-8s %-24s (期待 %s)  確度:%s%s\n",
        $c['file'],
        $beerOk ? 'ok  ' : 'NG  ',
        $prod === null ? '— ' : ($prod['ok'] ? 'ok' : 'NG'),
        $prod === null ? '—' : $prod['method'],
        $r['style_guess'] ?? 'null',
        $styleGuessName ?? '',
        $c['style_on_label'] ?? '—',
        $r['confidence'] === null ? '  —' : number_format((float)$r['confidence'], 2),
        ($prod !== null && !$prod['ok']) ? "  (期待銘柄 {$c['product']} / 実際 {$r['brand_text']})" : ''
    );

    if (!$replay) { sleep(1); }
}

// IMG_4816 / IMG_4817 は同一銘柄の表裏。2枚が同じ銘柄に読めているかの参考チェック
$pairMatch = null;
if (isset($brandByFile['IMG_4816.jpeg']) && isset($brandByFile['IMG_4817.jpeg'])) {
    $pairMatch = reco_eval_text_match($brandByFile['IMG_4816.jpeg'], $brandByFile['IMG_4817.jpeg']);
}

printf(
    "\nビール判定 %d/%d   銘柄同定 %d/%d   style_guessがID形式 %d/%d   蔵名の参考一致 %d/%d   対象外(画像なし) %d件\n",
    $hitBeer, $total, $hitProduct, $beerTotal, $styleIdHit, $total, $breweryHit, $beerTotal, $missing
);
printf(
    "うちのDBの誤記により救済(db_issue_type=typo): %d件%s\n",
    $rescueHit,
    $rescueHit > 0 ? ' (' . implode(', ', $rescueFiles) . ')' : ''
);
if ($missing > 0) {
    printf("対象外の内訳: %s\n", implode(', ', $missingFiles));
}
if ($pairMatch !== null) {
    printf("IMG_4816/IMG_4817(同一銘柄の表裏)の brand_text 一致: %s\n", $pairMatch['ok'] ? 'ok' : 'NG');
}

$out = __DIR__ . '/result-' . date('Ymd') . '.json';
file_put_contents($out, json_encode([
    'model'         => IDENTIFY_MODEL,
    'mode'          => $replay ? 'replay' : 'real',
    'ran_at'        => date('c'),
    'is_beer'       => "$hitBeer/$total",
    'product'       => "$hitProduct/$beerTotal",
    'style_id'      => "$styleIdHit/$total",
    'brewery_ref'   => "$breweryHit/$beerTotal",
    'rescued'       => "$rescueHit",
    'rescued_files' => $rescueFiles,
    'missing_file_count' => $missing,
    'missing_files' => $missingFiles,
    'pair_match_4816_4817' => $pairMatch,
    'rows'          => $rows,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "結果: $out\n";

// 履歴(素の応答は含めずサマリだけ)。--replay の数字は意味が無いので書かない
if (!$replay) {
    $historyPath = __DIR__ . '/history.tsv';
    if (!is_file($historyPath)) {
        file_put_contents($historyPath, "実行日時\tモデル\tビール判定\t銘柄同定\tstyleがID形式\t救済\t備考\n");
    }
    $line = implode("\t", [
        date('c'), IDENTIFY_MODEL,
        "$hitBeer/$total", "$hitProduct/$beerTotal", "$styleIdHit/$total",
        (string)$rescueHit, str_replace(["\t", "\n"], ' ', $note),
    ]) . "\n";
    file_put_contents($historyPath, $line, FILE_APPEND);
    echo "履歴: $historyPath に追記しました\n";
}
