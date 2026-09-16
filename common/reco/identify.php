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
    // JSON として妥当でも、期待した形でなければ失敗として扱う。
    // `{}` や `[1,2,3]` を「判定できた(全項目 null)」と読むと、
    // 利用者に「ビールではない」と誤って伝え、DB にも誤った記録が残る。
    if (!is_array($j) || !array_key_exists('is_beer', $j)) {
        return array_merge($empty, ['error' => true]);
    }

    // スキーマ側で範囲を縛れないことが分かった(API が integer の minimum/maximum を拒否する)。
    // 範囲外はモデルの誤りなので、嘘の値を DB に入れるより「未取得」にする。
    $inRange = function ($v, int $lo, int $hi) {
        if ($v === null || $v === '') { return null; }
        $n = (int)$v;
        return ($n >= $lo && $n <= $hi) ? $n : null;
    };
    $conf = isset($j['confidence']) ? (float)$j['confidence'] : null;
    if ($conf !== null && ($conf < 0 || $conf > 1)) { $conf = null; }

    // brand_text / brewery_text は VARCHAR(191)。dev の sql_mode に STRICT_TRANS_TABLES が
    // 無いため、超過しても例外にならず黙って切り詰められる。切り詰められた銘柄名は
    // unknown_beer(次のデータ投入の優先リスト)に載るので、ここで明示的に丸める。
    $text191 = function ($v) {
        if ($v === null || $v === '') { return null; }
        return mb_substr((string)$v, 0, 191);
    };

    // スタイルはIDで答えてもらう約束。名前や自由文が来たら受け取らない。
    // char(6) の列に入らず、黙って切り詰められて後段の推薦が狂うため。
    $sid = $j['style_guess'] ?? null;
    if ($sid !== null && !preg_match('/^st\d{4}$/', (string)$sid)) { $sid = null; }

    // 銘柄IDも char(6)。形が違うものを通すと、6文字に切り詰められて
    // 無関係な実在の銘柄と偶然一致し、まったく違うビールを「これですね」と見せてしまう。
    $pid = $j['matched_product_id'] ?? null;
    if ($pid !== null && !preg_match('/^pr\d{4}$/', (string)$pid)) { $pid = null; }

    return [
        'is_beer'            => isset($j['is_beer']) ? (bool)$j['is_beer'] : null,
        'brand_text'         => $text191($j['brand_text']   ?? null),
        'brewery_text'       => $text191($j['brewery_text'] ?? null),
        'matched_product_id' => $pid,
        'style_guess'        => $sid,
        'color'              => $inRange($j['color']   ?? null, 1, 10),
        'clarity'            => $inRange($j['clarity'] ?? null, 1, 4),
        'confidence'         => $conf,
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
 * プロンプトの固定部分。**銘柄一覧とスタイル一覧を先頭に置いてキャッシュに載せる**ので、
 * 同じ入力からは必ず同じ文字列が出なければならない(1バイト違うとキャッシュが外れる)。
 *
 * スタイルは**IDで答えてもらう**。名前で答えられると char(6) の列に入らない。
 *
 * @param array $catalog [['ProductID','ProductName','MakerName'], ...]
 * @param array $styles  [['StyleID','StyleName','FamilyName'], ...]
 */
function identify_prompt(array $catalog, array $styles): string
{
    $beerLines = [];
    foreach ($catalog as $c) {
        $beerLines[] = $c['ProductID'] . "\t" . $c['ProductName'] . "\t" . ($c['MakerName'] ?? '');
    }
    $styleLines = [];
    foreach ($styles as $s) {
        $styleLines[] = $s['StyleID'] . "\t" . $s['StyleName'] . "\t" . ($s['FamilyName'] ?? '');
    }
    return "次はこのサイトに登録されているビールの一覧です。ID・銘柄名・醸造所の順にタブ区切りで並んでいます。\n\n"
         . implode("\n", $beerLines)
         . "\n\n次はスタイルの一覧です。ID・スタイル名・系統の順です。\n\n"
         . implode("\n", $styleLines)
         . "\n\n写真を見て、次をJSONで答えてください。"
         . "一覧にない銘柄なら matched_product_id を null にし、読み取れた文字は brand_text に入れてください。"
         . "style_guess には**スタイル一覧のID(st で始まる6文字)をそのまま**入れてください。"
         . "スタイル名や自由な文字列を入れないでください。当てはまるものが無ければ null にしてください。"
         . "ビール以外の飲み物(チューハイ・エナジードリンク等)は is_beer を false にしてください。";
}

/**
 * 1枚を判定する。
 * @param callable|null $transport fn(string $imagePath, string $prompt): array
 *                                 null なら本物のAPIを呼ぶ
 */
function identify_call(string $imagePath, array $catalog, array $styles, ?callable $transport = null): array
{
    $prompt = identify_prompt($catalog, $styles);
    $send   = $transport ?? 'identify_transport_anthropic';

    $last = null;
    for ($i = 0; $i <= IDENTIFY_RETRY; $i++) {
        try {
            $res = $send($imagePath, $prompt);
            $r = identify_parse($res);
            $r['model'] = $res['model'] ?? IDENTIFY_MODEL;
            // 一覧に無いIDは受け取らない。形式が正しくても実在しなければ、
            // 後段が銘柄を引けずに落ちる。
            if ($r['matched_product_id'] !== null) {
                $known = false;
                foreach ($catalog as $c) {
                    if (($c['ProductID'] ?? null) === $r['matched_product_id']) { $known = true; break; }
                }
                if (!$known) { $r['matched_product_id'] = null; }
            }
            if (!$r['error']) { return $r; }
            $last = $r;
        } catch (Throwable $e) {
            // 鍵が混ざらないよう、メッセージは残さず種類だけ記録する
            error_log('[reco] identify failed: ' . get_class($e));
            $last = ['is_beer' => null, 'brand_text' => null, 'brewery_text' => null,
                     'matched_product_id' => null, 'style_guess' => null,
                     'color' => null, 'clarity' => null, 'confidence' => null,
                     'error' => true, 'model' => IDENTIFY_MODEL];
        }
    }
    return $last;
}

/**
 * 本物のAPI。SDK の呼び出し方は php/claude-api の作法に従う。
 *
 * キー名(outputConfig / mediaType / cacheControl 等)は Task 6 の Step 2 で
 * `vendor/anthropic-ai/sdk/` の実物(examples・src の型定義)を確認して決めた。
 * 推測ではなく SDK 付属の値オブジェクト(::with() 静的コンストラクタ、
 * README が推奨する書き方)をそのまま使うことで、キー名の綴り違いを避けている。
 */
function identify_transport_anthropic(string $imagePath, string $prompt): array
{
    require_once __DIR__ . '/../../vendor/autoload.php';
    $cfg = require __DIR__ . '/../../api_config.local.php';

    $client = new \Anthropic\Client(apiKey: $cfg['anthropic_api_key']);

    $shrunk = sys_get_temp_dir() . '/reco_' . bin2hex(random_bytes(8)) . '.jpg';
    try {
        identify_shrink($imagePath, $shrunk);
        $b64 = base64_encode(file_get_contents($shrunk));
    } finally {
        @unlink($shrunk);
    }

    // 返させる項目は構造化出力で固定する(設計書 §6)。
    $schema = ['type' => 'object', 'additionalProperties' => false,
        'required' => ['is_beer','brand_text','brewery_text','matched_product_id',
                       'style_guess','color','clarity','confidence'],
        'properties' => [
            'is_beer'            => ['type' => 'boolean'],
            'brand_text'         => ['type' => ['string','null']],
            'brewery_text'       => ['type' => ['string','null']],
            'matched_product_id' => ['type' => ['string','null']],
            'style_guess'        => ['type' => ['string','null']],
            // 構造化出力の JSON Schema は 'integer'/'number' 型に minimum/maximum を
            // サポートしない(API から invalid_request_error で拒否されたため確認済み)。
            // 範囲はプロンプト文と identify_parse() 側のチェックで担保する。
            'color'              => ['type' => ['integer','null']],
            'clarity'            => ['type' => ['integer','null']],
            'confidence'         => ['type' => 'number'],
        ]];

    $message = $client->messages->create(
        model: IDENTIFY_MODEL,
        maxTokens: 1024,
        outputConfig: \Anthropic\Messages\OutputConfig::with(
            format: \Anthropic\Messages\JSONOutputFormat::with(schema: $schema)
        ),
        system: [
            // 銘柄一覧は毎回同じなのでキャッシュに載せる。呼ぶたびに送り直さない
            \Anthropic\Messages\TextBlockParam::with(
                text: $prompt,
                cacheControl: \Anthropic\Messages\CacheControlEphemeral::with()
            ),
        ],
        messages: [[
            'role' => 'user',
            'content' => [
                \Anthropic\Messages\ImageBlockParam::with(
                    source: \Anthropic\Messages\Base64ImageSource::with(
                        data: $b64,
                        mediaType: 'image/jpeg'
                    )
                ),
                \Anthropic\Messages\TextBlockParam::with(text: 'この写真のビールを判定してください。'),
            ],
        ]],
    );

    // SDK のオブジェクトを identify_parse が読める素の配列に均す
    $content = [];
    foreach ($message->content as $b) {
        if ($b->type === 'text') { $content[] = ['type' => 'text', 'text' => $b->text]; }
    }
    return ['id' => $message->id, 'model' => $message->model,
            'stop_reason' => $message->stopReason, 'content' => $content];
}

/**
 * 長辺を 1568px に縮める。大きい写真をそのまま送ると入力トークンが無駄に増える。
 *
 * @return bool 保存できたら true。imagescale()/imagejpeg() が失敗したら false を返す
 *              (存在しないファイルを指す行を DB に作らないため、呼び出し側で見る)。
 *              画像として読めない・対応形式でないときは従来どおり例外を投げる
 *              (アップロード前の検証漏れであり、上限で守るべき「課金の失敗」とは別種)。
 */
function identify_shrink(string $src, string $dst): bool
{
    $info = getimagesize($src);
    if ($info === false) { throw new RuntimeException('画像として読めません'); }

    $im = match ($info[2]) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => imagecreatefrompng($src),
        IMAGETYPE_WEBP => imagecreatefromwebp($src),
        default        => throw new RuntimeException('対応していない形式です'),
    };
    $im = imagescale($im, ...(
        $info[0] >= $info[1]
            ? [min($info[0], IDENTIFY_MAX_EDGE), -1]
            : [(int)round($info[0] * min($info[1], IDENTIFY_MAX_EDGE) / $info[1]), -1]
    ));
    if ($im === false) { return false; }
    $ok = imagejpeg($im, $dst, 82);
    imagedestroy($im);
    return $ok;
}
