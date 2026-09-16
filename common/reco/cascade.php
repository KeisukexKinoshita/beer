<?php
declare(strict_types=1);
/*
 * 推薦の中核。**DBに触らない純粋関数**にしてある。
 *
 * そうした理由は2つ:
 *  (1) 固定データだけで単体テストが回る
 *  (2) PR枠を返す関数と物理的に分かれる。「掲載料は順位を動かさない」を
 *      約束ではなくコードの形で担保する(設計書 §8)
 *
 * 段階の設計は実データから決めた(設計書 §5):
 *  - IPA に 73銘柄(全体の36%)あるので、系統だけでは絞り込めない
 *  - 同じ StyleID に他がいない銘柄が 23件あるので、StyleID だけでは 0件になる
 */

require_once __DIR__ . '/../nebula/helpers.php';   // style_group() / is_unmeasured()

/**
 * 似た銘柄を上から順に集める。3件に達したらそこで止める。
 *
 * @param array $seed  基準の1本。使うキー:
 *                     ProductID(null可) / StyleID(null可) / FamilyName /
 *                     StyleName / MakerID(null可) / Alcohol(null可)
 * @param array $pool  候補の全銘柄。all_beers() の行がそのまま入る
 * @param int   $limit 何件返すか
 * @return array 各要素 ['ProductID' => string, 'stage' => int, 'row' => array]
 */
function reco_pick(array $seed, array $pool, int $limit = 3): array
{
    $seedGroup = style_group($seed['FamilyName'] ?? '', $seed['StyleName'] ?? '');
    $picked = [];
    $seen   = [];
    if (!empty($seed['ProductID'])) { $seen[$seed['ProductID']] = true; }

    foreach ([1, 2, 3, 4] as $stage) {
        if (count($picked) >= $limit) { break; }

        $cands = [];
        foreach ($pool as $r) {
            if (isset($seen[$r['ProductID']])) { continue; }
            if (!reco_in_stage($stage, $seed, $seedGroup, $r)) { continue; }
            $cands[] = $r;
        }
        reco_sort($cands, $seed);

        foreach ($cands as $r) {
            if (count($picked) >= $limit) { break; }
            $picked[] = ['ProductID' => $r['ProductID'], 'stage' => $stage, 'row' => $r];
            $seen[$r['ProductID']] = true;
        }
    }
    return $picked;
}

/** その行が当該段階の条件に当たるか */
function reco_in_stage(int $stage, array $seed, string $seedGroup, array $r): bool
{
    if ($stage === 1) {
        return !empty($seed['StyleID']) && ($r['StyleID'] ?? null) === $seed['StyleID'];
    }
    if ($stage === 2) {
        return !empty($seed['FamilyName']) && ($r['FamilyName'] ?? null) === $seed['FamilyName'];
    }
    if ($stage === 3) {
        return style_group($r['FamilyName'] ?? '', $r['StyleName'] ?? '') === $seedGroup;
    }
    return true;   // 段階4 = 残り全部。度数の近さだけで並ぶ
}

/** 同じ段階の中での並べ替え。順は 別の蔵 → 度数が近い → 新しいID(設計書 §5) */
function reco_sort(array &$cands, array $seed): void
{
    usort($cands, function (array $x, array $y) use ($seed): int {
        $kx = reco_sort_key($x, $seed);
        $ky = reco_sort_key($y, $seed);
        if ($kx[0] !== $ky[0]) { return $kx[0] <=> $ky[0]; }   // 0(別の蔵)が先
        if ($kx[1] !== $ky[1]) { return $kx[1] <=> $ky[1]; }   // 度数差が小さい順
        return strcmp($ky[2], $kx[2]);                         // IDが大きい(新しい)ほうが先
    });
}

/** 並べ替えの鍵。[別蔵フラグ, 度数差, ProductID] */
function reco_sort_key(array $r, array $seed): array
{
    $sameMaker = (!empty($seed['MakerID']) && ($r['MakerID'] ?? null) === $seed['MakerID']) ? 1 : 0;

    $sa = $seed['Alcohol'] ?? null;
    $ca = $r['Alcohol'] ?? null;
    // 度数が未計測のものは比べようがないので、必ず後ろに回す
    $diff = (is_unmeasured($sa) || is_unmeasured($ca))
        ? PHP_FLOAT_MAX
        : abs((float)$ca - (float)$sa);

    return [$sameMaker, $diff, (string)$r['ProductID']];
}
