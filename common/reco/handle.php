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
        $imageWebPath = $prev['image_path'] ? '/' . $prev['image_path'] : null;
        $result = ['is_beer' => (bool)$prev['is_beer'], 'matched_product_id' => $prev['product_id'],
                   'brand_text' => $prev['brand_text'], 'brewery_text' => $prev['brewery_text'],
                   'style_guess' => $prev['style_guess'], 'color' => $prev['color'],
                   'clarity' => $prev['clarity'],
                   'confidence' => $prev['confidence'] !== null ? (float)$prev['confidence'] : null,
                   'error' => false];
        $uploadId = (int)$prev['upload_id'];
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
                'model' => $result['model'] ?? null,
            ], $hash, null, 'api_error');
            $view = 'error'; $msg = 'いま混み合っています。しばらくしてからお試しください。';
        } else {
            // ビール以外は画像を保存しない(設計書 §9)
            $path = null;
            if ($result['is_beer'] === true) {
                $path = 'img/upload/' . $hash . '.jpg';
                $saved = identify_shrink($f['tmp_name'], dirname(__DIR__, 2) . '/' . $path);
                if ($saved) {
                    $imageWebPath = '/' . $path;
                } else {
                    // 保存に失敗した。存在しないファイルを指す行を DB に作らない
                    $path = null;
                }
            }
            $uploadId = upload_record($visitorId, $result, $hash, $path);
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

    return compact('view', 'msg', 'result', 'picked', 'uploadId', 'imageWebPath', 'seed');
}
