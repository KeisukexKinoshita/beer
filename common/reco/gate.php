<?php
declare(strict_types=1);
/*
 * APIを呼ぶ前に弾く判断。**費用防衛の本体**(設計書 §9)。
 *
 * DBも時計も外から渡す形にしてある。そうすると境界(20枚目は通り21枚目は弾く、
 * といった値)を単体テストで全部見られる。実際の件数を数えるのは repo.php の役目。
 */

/*
 * 上限の既定値。api_config.local.php の 'daily_cap' があればそれで
 * global_daily を上書きする(dev は 30 にする。設計書 §9)。
 */
const GATE_LIMITS = [
    'visitor_daily'  => 20,
    'global_daily'   => 200,
    'global_monthly' => 4000,
    'burst_seconds'  => 20,            // 1分3枚 = 20秒に1回
    'max_bytes'      => 10485760,      // 10MB
    'not_beer_cap'   => 5,             // ビール以外が5回続いたら当日はここまで
];

const GATE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

/**
 * @param array $ctx enabled / mime / bytes / seconds_since / visitor_today /
 *                   global_today / global_month / hash_seen / not_beer_streak
 * @return array ['ok' => bool, 'reason' => string]
 */
function gate_check(array $ctx): array
{
    // 停止フラグは他のどれよりも先。障害時と費用暴走時の最終手段なので、
    // ここが効かない経路を作らない
    if (empty($ctx['enabled'])) { return gate_no('disabled'); }

    if (!in_array($ctx['mime'] ?? '', GATE_TYPES, true)) { return gate_no('bad_type'); }
    if (($ctx['bytes'] ?? 0) > GATE_LIMITS['max_bytes'])  { return gate_no('too_large'); }

    // 同じ画像は「弾く」のではなく「APIを呼ばない」。呼び出し側は前回の結果を返す
    if (!empty($ctx['hash_seen'])) { return gate_no('cached'); }

    if (($ctx['seconds_since'] ?? PHP_INT_MAX) < GATE_LIMITS['burst_seconds']) {
        return gate_no('burst');
    }

    // ビール以外が続いた訪問者は、その日の枠を絞る
    $visitorCap = (($ctx['not_beer_streak'] ?? 0) >= GATE_LIMITS['not_beer_cap'])
        ? GATE_LIMITS['not_beer_cap']
        : GATE_LIMITS['visitor_daily'];
    if (($ctx['visitor_today'] ?? 0) >= $visitorCap)              { return gate_no('visitor_daily'); }

    $dailyCap = $ctx['daily_cap'] ?? GATE_LIMITS['global_daily'];
    if (($ctx['global_today'] ?? 0) >= $dailyCap)                     { return gate_no('global_daily'); }
    if (($ctx['global_month'] ?? 0) >= GATE_LIMITS['global_monthly']) { return gate_no('global_monthly'); }

    return ['ok' => true, 'reason' => 'ok'];
}

function gate_no(string $reason): array { return ['ok' => false, 'reason' => $reason]; }

/** 画面に出す文言。理由ごとに言うことを変える */
function gate_message(string $reason): string
{
    switch ($reason) {
        case 'disabled':       return 'いまこの機能を止めています。しばらくしてからお試しください。';
        case 'bad_type':       return 'JPEG・PNG・WebP の写真をお選びください。';
        case 'too_large':      return '写真が大きすぎます(10MBまで)。';
        case 'burst':          return '少し間をあけてからお試しください。';
        case 'visitor_daily':  return '本日の受付は終了しました。また明日お試しください。';
        case 'global_daily':
        case 'global_monthly': return '本日の受付は終了しました。';
        default:               return '';
    }
}
