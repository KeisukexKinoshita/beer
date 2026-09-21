<?php
declare(strict_types=1);
/*
 * 写真1枚を受け取ってから、推薦を決めるまでの判断。try.php から切り出した
 * (最終レビュー指摘: 金・嘘・写真の判断がここに全部あるのに単体テストが1件も無かった)。
 */

require_once __DIR__ . '/../nebula/helpers.php';
require_once __DIR__ . '/repo.php';
require_once __DIR__ . '/gate.php';
require_once __DIR__ . '/identify.php';
require_once __DIR__ . '/cascade.php';
require_once __DIR__ . '/evaluate.php';

/**
 * その銘柄の estimated_fields から、評価の軸ごとの推定フラグを作る。
 * 列名(Alcohol / IBU_all / Fruity)と評価の軸名(abv / ibu / fruity)の対応はここだけが知る。
 * `is_estimated()` は common/nebula/helpers.php にある純関数(修正ラウンド1: 最終レビュー
 * Important。この名前は後続タスクも同じ名前で呼ぶ)。
 */
function reco_estimated_map(array $beer): array
{
    return [
        'abv'    => is_estimated($beer, 'Alcohol'),
        'ibu'    => is_estimated($beer, 'IBU_all'),
        'fruity' => is_estimated($beer, 'Fruity'),
    ];
}

/**
 * 写真1枚を受け取ってから、推薦を決めるまでの判断。
 *
 * try.php から切り出したのは、ここに**金・嘘・写真**の判断が全部あるのに
 * テストが1件も無かったため(最終レビューの指摘)。
 * DBもAPIも外から差し替えられる形にして、全分岐を単体テストで固定する。
 *
 * **本物のHTTPアップロードかどうかの検査(UPLOAD_ERR_OK / is_uploaded_file())は
 * 呼び出し元(try.php)の責務のまま残す。** ここに含めると is_uploaded_file() が
 * 本物のHTTPアップロード以外では常に偽を返すため、単体テストで门番・判定・保存・記録の
 * 判断(=金・嘘・写真)を一切テストできなくなる。$file はすでに検査済みという前提で受け取る。
 *
 * @param array          $file      検査済みの $_FILES['photo'] 相当。少なくとも
 *                                  'tmp_name'(画像パス)と 'size'(バイト数)を持つ
 * @param callable|null $transport identify_call に渡す差し替え口。テスト用
 * @return array ['view'=>..., 'msg'=>..., 'result'=>..., 'picked'=>..., 'uploadId'=>..., 'imageWebPath'=>..., 'seed'=>...]
 */
function reco_handle_upload(string $visitorId, array $file, ?callable $transport = null): array
{
    $view = 'intake';   // intake / result / error
    $msg  = '';
    $result = $picked = $seed = [];
    $uploadId = 0;
    $imageWebPath = null;   // 表示部が参照する。保存しなかったときは null のまま

    $f = $file;
    $hash = hash_file('sha256', $f['tmp_name']);
    $mime = mime_content_type($f['tmp_name']);
    $g = gate_check(gate_context($visitorId, $mime, (int)$f['size'], $hash));

    // reco_record() は (upload_id, position) に一意制約(uq_reco_pos)がある。
    // 新規に判定した upload_id のときだけ記録してよい。キャッシュ経路(同じ写真の
    // 再送)は既存の upload_id を再利用するので、ここで再度書くと制約違反で落ちる。
    $isFreshIdentify = false;

    if (!$g['ok'] && $g['reason'] === 'cached') {
        // 同じ写真。APIを呼ばずに前回の結果を使う
        $prev = upload_by_hash($hash);
        $uploadId = (int)$prev['upload_id'];
        $imageWebPath = $prev['image_path'] ? '/photo.php?u=' . $uploadId : null;
        $result = ['is_beer' => (bool)$prev['is_beer'], 'matched_product_id' => $prev['product_id'],
                   'brand_text' => $prev['brand_text'], 'brewery_text' => $prev['brewery_text'],
                   'style_guess' => $prev['style_guess'], 'color' => $prev['color'],
                   'clarity' => $prev['clarity'],
                   'confidence' => $prev['confidence'] !== null ? (float)$prev['confidence'] : null,
                   'abv_guess' => $prev['abv_guess'] !== null ? (float)$prev['abv_guess'] : null,
                   'ibu_guess' => $prev['ibu_guess'] !== null ? (int)$prev['ibu_guess'] : null,
                   'fruity_guess' => $prev['fruity_guess'] !== null ? (int)$prev['fruity_guess'] : null,
                   'error' => false];
        $view = 'result';
    } elseif (!$g['ok']) {
        $view = 'error'; $msg = gate_message($g['reason']);
    } else {
        $result = identify_call($f['tmp_name'], reco_catalog(), reco_style_catalog(), $transport);
        // API失敗(is_beer=null, error=true)と「ビール以外」(is_beer=false, error=false)
        // を取り違えない。$result['error'] を先に見る。identify_branch() は両方とも
        // 'not_beer' として畳んでしまうので、ここでは使わない。
        if (!empty($result['error'])) {
            // API は呼ばれた=課金された可能性がある。上限に数えないと、
            // 「もう一度」を押し続けるだけで無制限に課金できてしまう(最終レビュー C-2)。
            upload_record($visitorId, [
                'is_beer' => false, 'matched_product_id' => null,
                'brand_text' => null, 'brewery_text' => null, 'style_guess' => null,
                'color' => null, 'clarity' => null, 'confidence' => null,
                'abv_guess' => null, 'ibu_guess' => null, 'fruity_guess' => null,
                'model' => $result['model'] ?? null,
            ], $hash, null, 'api_error');
            $view = 'error'; $msg = 'いま混み合っています。しばらくしてからお試しください。';
        } else {
            // ビール以外は画像を保存しない(設計書 §9)
            $path = null;
            $saved = false;
            if ($result['is_beer'] === true) {
                $path = 'img/upload/' . $hash . '.jpg';
                $saved = identify_shrink($f['tmp_name'], dirname(__DIR__, 2) . '/' . $path);
                if (!$saved) {
                    // 保存に失敗した。存在しないファイルを指す行を DB に作らない
                    $path = null;
                }
            }
            $uploadId = upload_record($visitorId, $result, $hash, $path);
            // 画像を保存できた場合のみ、本人が見るための経路を用意する
            if ($result['is_beer'] === true && $saved) {
                $imageWebPath = '/photo.php?u=' . $uploadId;
            }
            if ($result['is_beer'] === true && !$result['matched_product_id'] && $result['brand_text']) {
                unknown_bump($result['brand_text'], $result['brewery_text']);
            }
            $isFreshIdentify = true;
            $view = 'result';
        }
    }

    if ($view === 'result' && $result['is_beer'] === true) {
        $seed = $result['matched_product_id']
            ? beer_by_id($result['matched_product_id'])
            : ['ProductID' => null, 'StyleID' => $result['style_guess'],
               'FamilyName' => '', 'StyleName' => '', 'MakerID' => null, 'Alcohol' => null];
        if (!$result['matched_product_id'] && $result['style_guess']) {
            $s = style_by_id($result['style_guess']);
            $seed['FamilyName'] = $s['FamilyName'] ?? '';
            $seed['StyleName']  = $s['StyleName'] ?? '';
        }
        $picked = reco_pick($seed, reco_pool());
        if ($isFreshIdentify) {
            reco_record($uploadId, $picked);
        }
        event_record($visitorId, 'reco_view', $result['matched_product_id'], $uploadId);
    }

    // ③評価(設計書 2026-09-19 §3)。DBに行があれば測った値、無ければモデルの推定。
    // **推定のときは必ず印を付ける。** 未登録のほうが常態なので、ここを黙ると嘘になる
    $eval = ['lines' => [], 'estimated' => false, 'source' => 'db'];
    if ($view === 'result' && $result['is_beer'] === true) {
        if ($result['matched_product_id'] && !empty($seed['ProductID'])) {
            // DBの行でも、estimated_fields に載っている列は推定値(修正ラウンド1: 最終レビュー
            // Important。まとめて false を渡すと、推定を測定値の顔で出すことになる)
            $eval = eval_build(
                ['abv' => $seed['Alcohol'], 'ibu' => $seed['IBU_all'], 'fruity' => $seed['Fruity']],
                reco_pool(), reco_estimated_map($seed));
            $eval['source'] = 'db';
        } else {
            $eval = eval_build(
                ['abv' => $result['abv_guess'], 'ibu' => $result['ibu_guess'],
                 'fruity' => $result['fruity_guess']],
                reco_pool(), true);
            $eval['source'] = 'guess';
        }
    }

    return compact('view', 'msg', 'result', 'picked', 'uploadId', 'imageWebPath', 'seed', 'eval');
}
