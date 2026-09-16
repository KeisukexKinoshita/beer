<?php
declare(strict_types=1);
/*
 * アップロードされた写真から銘柄を読み取る(設計書 §6)。
 *
 * API を呼ぶところを $transport として外から差し替えられるようにしてある。
 * テストは記録した応答を再生するので、1円も使わずに
 * 「確度が高い/低い/ビール以外/API失敗」の4系統を通せる。
 */

const IDENTIFY_MODEL      = 'claude-sonnet-5';
const IDENTIFY_MAX_EDGE   = 1568;   // 送る前にここまで縮める
const IDENTIFY_RETRY      = 1;      // 失敗しても再試行は1回まで(設計書 §9)

/** 応答 → こちらの形。壊れた応答でも例外を投げない */
function identify_parse(array $res): array
{
    $empty = ['is_beer' => null, 'brand_text' => null, 'brewery_text' => null,
              'matched_product_id' => null, 'style_guess' => null,
              'color' => null, 'clarity' => null, 'confidence' => null, 'error' => false];

    if (($res['type'] ?? '') === 'error') { return array_merge($empty, ['error' => true]); }

    $text = null;
    foreach ($res['content'] ?? [] as $b) {
        if (($b['type'] ?? '') === 'text') { $text = $b['text']; break; }
    }
    if ($text === null) { return array_merge($empty, ['error' => true]); }

    $j = json_decode($text, true);
    if (!is_array($j)) { return array_merge($empty, ['error' => true]); }

    return [
        'is_beer'            => isset($j['is_beer']) ? (bool)$j['is_beer'] : null,
        'brand_text'         => $j['brand_text']         ?? null,
        'brewery_text'       => $j['brewery_text']       ?? null,
        'matched_product_id' => $j['matched_product_id'] ?? null,
        'style_guess'        => $j['style_guess']        ?? null,
        'color'              => isset($j['color'])      ? (int)$j['color']   : null,
        'clarity'            => isset($j['clarity'])    ? (int)$j['clarity'] : null,
        'confidence'         => isset($j['confidence']) ? (float)$j['confidence'] : null,
        'error'              => false,
    ];
}

/** 確度から画面の出し方を決める(設計書 §6 の3分岐 + ビール以外) */
function identify_branch(array $r): string
{
    if (($r['is_beer'] ?? null) !== true) { return 'not_beer'; }
    $c = (float)($r['confidence'] ?? 0);
    if ($c >= 0.8) { return 'confirm'; }
    if ($c >= 0.4) { return 'choose'; }
    return 'unknown';
}

/**
 * プロンプトの固定部分。**銘柄一覧を先頭に置いてキャッシュに載せる**ので、
 * 同じ入力からは必ず同じ文字列が出なければならない(1バイト違うとキャッシュが外れる)。
 */
function identify_prompt(array $catalog): string
{
    $lines = [];
    foreach ($catalog as $c) {
        $lines[] = $c['ProductID'] . "\t" . $c['ProductName'] . "\t" . ($c['MakerName'] ?? '');
    }
    return "次はこのサイトに登録されているビールの一覧です。ID・銘柄名・醸造所の順にタブ区切りで並んでいます。\n\n"
         . implode("\n", $lines)
         . "\n\n写真を見て、次をJSONで答えてください。"
         . "一覧にない銘柄なら matched_product_id を null にし、読み取れた文字は brand_text に入れてください。"
         . "ビール以外の飲み物(チューハイ・エナジードリンク等)は is_beer を false にしてください。";
}

/**
 * 1枚を判定する。
 * @param callable|null $transport fn(string $imagePath, string $prompt): array
 *                                 null なら本物のAPIを呼ぶ
 */
function identify_call(string $imagePath, array $catalog, ?callable $transport = null): array
{
    $prompt = identify_prompt($catalog);
    $send   = $transport ?? 'identify_transport_anthropic';

    $last = null;
    for ($i = 0; $i <= IDENTIFY_RETRY; $i++) {
        try {
            $res = $send($imagePath, $prompt);
            $r = identify_parse($res);
            $r['model'] = $res['model'] ?? IDENTIFY_MODEL;
            if (!$r['error']) { return $r; }
            $last = $r;
        } catch (Throwable $e) {
            $last = ['is_beer' => null, 'brand_text' => null, 'brewery_text' => null,
                     'matched_product_id' => null, 'style_guess' => null,
                     'color' => null, 'clarity' => null, 'confidence' => null,
                     'error' => true, 'model' => IDENTIFY_MODEL];
        }
    }
    return $last;
}

/** 本物のAPI。SDK の導入は Task 6 で行う */
function identify_transport_anthropic(string $imagePath, string $prompt): array
{
    throw new RuntimeException('identify_transport_anthropic は Task 6 で実装する');
}
