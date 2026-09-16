<?php
require_once __DIR__ . '/../../common/reco/gate.php';

/** 何も問題がない状態。各テストはここから1つだけ崩す */
function ctx(array $over = []): array {
    return array_merge([
        'enabled'         => true,
        'mime'            => 'image/jpeg',
        'bytes'           => 2 * 1024 * 1024,
        'seconds_since'   => 60,     // 同じ訪問者の直前のアップロードからの秒数
        'daily_cap'       => 200,   // dev では api_config.local.php で 30 にする
        'visitor_today'   => 0,
        'global_today'    => 0,
        'global_month'    => 0,
        'hash_seen'       => false,  // 同じ画像を前に見たか
        'not_beer_streak' => 0,
    ], $over);
}

eq(gate_check(ctx()),                              ['ok' => true,  'reason' => 'ok'],             '普通の写真は通る');
eq(gate_check(ctx(['enabled' => false])),          ['ok' => false, 'reason' => 'disabled'],       '停止フラグで全部止まる');
eq(gate_check(ctx(['mime' => 'image/gif'])),       ['ok' => false, 'reason' => 'bad_type'],       'jpeg/png/webp 以外は弾く');
eq(gate_check(ctx(['mime' => 'image/webp'])),      ['ok' => true,  'reason' => 'ok'],             'webp は通る');
eq(gate_check(ctx(['bytes' => 10 * 1024 * 1024])), ['ok' => true,  'reason' => 'ok'],             'ちょうど10MBは通る');
eq(gate_check(ctx(['bytes' => 10 * 1024 * 1024 + 1])), ['ok' => false, 'reason' => 'too_large'],  '10MBを1バイト超えたら弾く');

// 同じ画像はAPIを呼ばずに前回の結果を返す。弾くのではなく「呼ばない」
eq(gate_check(ctx(['hash_seen' => true])),         ['ok' => false, 'reason' => 'cached'],         '同じ画像はAPIを呼ばない');

// 境界: 20枚目は通り、21枚目は弾く
eq(gate_check(ctx(['visitor_today' => 19])),       ['ok' => true,  'reason' => 'ok'],             '訪問者の20枚目は通る');
eq(gate_check(ctx(['visitor_today' => 20])),       ['ok' => false, 'reason' => 'visitor_daily'],  '訪問者の21枚目は弾く');
eq(gate_check(ctx(['global_today' => 199])),       ['ok' => true,  'reason' => 'ok'],             '全体の200枚目は通る');
eq(gate_check(ctx(['global_today' => 200])),       ['ok' => false, 'reason' => 'global_daily'],   '全体の201枚目は弾く');
eq(gate_check(ctx(['daily_cap' => 30, 'global_today' => 29])), ['ok' => true,  'reason' => 'ok'],           'dev の枠(30)の内側は通る');
eq(gate_check(ctx(['daily_cap' => 30, 'global_today' => 30])), ['ok' => false, 'reason' => 'global_daily'], 'dev の枠を超えたら弾く');
eq(gate_check(ctx(['global_month' => 3999])),      ['ok' => true,  'reason' => 'ok'],             '暦月の4000枚目は通る');
eq(gate_check(ctx(['global_month' => 4000])),      ['ok' => false, 'reason' => 'global_monthly'], '暦月の4001枚目は弾く');

// バースト: 1分3枚 = 20秒に1回まで
eq(gate_check(ctx(['seconds_since' => 20])),       ['ok' => true,  'reason' => 'ok'],             '20秒空いていれば通る');
eq(gate_check(ctx(['seconds_since' => 19])),       ['ok' => false, 'reason' => 'burst'],          '19秒では弾く');

// ビール以外が続いた訪問者は枠を絞る
eq(gate_check(ctx(['not_beer_streak' => 5, 'visitor_today' => 5])),
   ['ok' => false, 'reason' => 'visitor_daily'], 'ビール以外が5回続いたら当日の枠を5枚に絞る');
eq(gate_check(ctx(['not_beer_streak' => 5, 'visitor_today' => 4])),
   ['ok' => true, 'reason' => 'ok'], '絞られた枠の内側なら通る');

// 停止フラグは他のどの理由よりも先に見る
eq(gate_check(ctx(['enabled' => false, 'global_month' => 9999])),
   ['ok' => false, 'reason' => 'disabled'], '停止フラグが最優先');

// --- 必須キーが欠けたら弾く(fail-closed)。上限が素通りしないことの担保 ---
foreach (['enabled','mime','bytes','seconds_since','visitor_today',
          'global_today','global_month','hash_seen','not_beer_streak'] as $missing) {
    $c = ctx();
    unset($c[$missing]);
    eq(gate_check($c), ['ok' => false, 'reason' => 'bad_context'],
       "必須キー {$missing} が欠けたら弾く");
}

// --- 停止フラグは他のどの拒否理由よりも先に見る ---
$others = [
    'bad_type'      => ['mime' => 'image/gif'],
    'too_large'     => ['bytes' => 20 * 1024 * 1024],
    'cached'        => ['hash_seen' => true],
    'burst'         => ['seconds_since' => 1],
    'visitor_daily' => ['visitor_today' => 999],
    'global_daily'  => ['global_today' => 999],
];
foreach ($others as $reason => $over) {
    eq(gate_check(ctx($over)), ['ok' => false, 'reason' => $reason],
       "単独なら {$reason} で弾く");
    eq(gate_check(ctx($over + ['enabled' => false])), ['ok' => false, 'reason' => 'disabled'],
       "{$reason} と同時でも停止フラグが勝つ");
}
