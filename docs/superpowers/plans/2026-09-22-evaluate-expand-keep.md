# 評価・拡張・保管の動線 実装計画

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 写真を上げたあとの体験を「判定して終わり」から「**評価 → 保管 → 拡張**」に作り直し、この機能がやっている判断を画面に出す。

**Architecture:** 評価の文は**データから規則で作る純関数**(`common/reco/evaluate.php`)に閉じ込め、モデルには書かせない。拡張は既存の段階カスケード(`reco_pick()`)に「押した方向へずらした仮の1本」を渡すだけで実現する。解析中の絵は既存の `galaxy.js` を持ち込まず、軽い2Dの `assets/js/warp.js` を新設する。保管は `line/` の spike を作り直し、感想を `note` テーブルに残す。

**Tech Stack:** 素の PHP 8.3 / MariaDB(本番は MySQL 8.4)/ 素の JS(フレームワークなし)/ Anthropic PHP SDK(`vendor/`)/ 単体テストは `tests/unit/run.php`(PHPUnit は使わない)

**Spec:** `docs/superpowers/specs/2026-09-19-evaluate-expand-keep-design.md`
(§3・§6 は `docs/superpowers/specs/2026-09-16-photo-recommender-design.md` の同番号を**置き換える**。
それ以外の節 — データの持ち方・推薦ロジック・費用の上限・PR枠・デザイン — は旧設計が引き続き有効)

## Global Constraints

以下は全タスクの要件に含まれる。設計書から値をそのまま写したもの。

- **評価の文はデータから規則で作る。モデルに書かせない。** 同じ入力からは必ず同じ文が出ること。追加の費用はゼロ
- **断定しない。何と比べているかを必ず添える。** 例: 「苦味は **ほどよいほう**。200銘柄のうち、これより苦いものが 71%。(IBU 25)」
- **未登録のビールも評価する。ただし「ラベルからの推定です」と明示する**(未登録のほうが常態)
- **ビールに見えないときは画像を保存しない。** 記録(`upload` の行)は残す
- **画面は起きたことだけを言う。** この案件は画面が嘘をついた不具合を3回直している(URLを打つだけで「記録しました」/ 控えていないのに「控えておきます」/ 未登録なのに確認だけ出す)
- **④保管は任意で、飛ばして⑤へ行ける。** ⑤の前にログインの壁を立てない
- **LINE ログインの誘いは「使っている画面の中」に置く。** 別の場所で URL を開かせると、その端末の匿名の記録が孤児になる
- **強調色(`--h-*`)を使ってよいのは4か所だけ**: 同定された1本のハロー / 対象ビール3つ / 強調するとき / **解析中に飛び込む1点**。並ぶところは減彩の通常色(`--g-*`)を使う
- **フルーティーさの物差しは `docs/fruity-rubric.md` が唯一の正。** 1〜10。200銘柄の採点にもアップロードされた1本の推定にも同じものを使う(2026-09-20 に全200件を付け直し済み)
- **API を呼ぶテストを書かない。** 1枚 0.8円。テストは記録した応答を再生する(`$transport` の差し替え)
- **秘密情報をリポジトリ・チャットに置かない。** `api_config.local.php` / `line_config.local.php` / `db_config.local.php` は読まない・作らない・コミットしない
- **DB は `scripts/remote_sql.sh` 経由でのみ触る。** このコンテナの `/etc/hosts` は RDS のホスト名を `127.0.0.1`(ローカルのテスト用MariaDB)に向けており、直接つなぐと**テストDBを本番と誤認して書き込む**
- **単体テストは全件通ること。** いまの基準は `php tests/unit/run.php` で **180 pass / 0 fail**
- **UI の修正とロジックの修正はコミットを分ける**

## ファイル構成

| ファイル | 役割 |
|---|---|
| `common/reco/evaluate.php`(新規) | ③評価。**DBもAPIも呼ばない純関数**。値と母集団を受け取り、文の材料を返す |
| `common/reco/cascade.php`(追記) | ⑤拡張の `reco_shift()`。既存の `reco_pick()` はそのまま使う |
| `common/reco/identify.php`(変更) | 未登録ビールのために度数・苦味・フルーティーさの推定を追加で返させる |
| `common/reco/repo.php`(追記) | 感想の保存・履歴の取得 |
| `common/reco/handle.php`(変更) | 推定3項目を `upload` に記録する |
| `try.php`(変更) | ②③④⑤を出す。いちばん大きく変わる |
| `history.php`(新規) | これまでに撮ったビールの一覧 |
| `line/login.php` / `line/callback.php`(書き直し) | spike を本番の作りにする |
| `assets/js/warp.js`(新規) | 解析中の2Dの星野。`galaxy.js` は持ち込まない |
| `assets/css/exposure.css`(追記) | 上記の画面のスタイル |
| `db/migrations/009_upload_guess.sql`(新規) | `upload` に推定3列 |
| `db/migrations/010_note.sql`(新規) | 感想の `note` テーブル |
| `tests/unit/evaluate_test.php` / `shift_test.php` / `note_test.php`(新規) | 単体テスト |

---

### Task 1: ③評価の文を作る純関数

**Files:**
- Create: `common/reco/evaluate.php`
- Create: `tests/unit/evaluate_test.php`

**Interfaces:**
- Consumes: なし(この関数はDBもAPIも呼ばない)
- Produces:
  - `eval_sorted(array $pool, string $axis): array` — 母集団から1軸の値だけを取り出し、**昇順に並べた float の配列**を返す。null と空文字は捨てる
  - `eval_share_below(array $sorted, float $v): int` — その値より**小さいものの割合**(0〜100の整数)
  - `eval_band(string $axis, int $shareBelow): int` — 0〜4 の帯の番号
  - `eval_line(string $axis, ?float $value, array $sorted, bool $estimated): ?array` — 1行分の材料。値が無ければ `null`
  - `eval_build(array $vals, array $pool, bool $estimated): array` — `['lines' => [...], 'estimated' => bool]`
    - `$vals` は `['abv' => ?float, 'ibu' => ?float, 'fruity' => ?float]`
    - 各 line は `['axis','label','word','clause','value','estimated']` を持つ

**背景(実装者へ):** 設計書 §3。実データで試したところ、**断定すると一般感覚とずれる**ことが分かっている。度数 5% の銘柄が「とても軽い」と出る。クラフトビール200銘柄は度数が高いほうに偏っている(平均5.7%)ので**このユニバースの中では正しい**が、読み手は違和感を持つ。だから**何と比べているかを必ず添える**。

- [ ] **Step 1: 失敗するテストを書く**

`tests/unit/evaluate_test.php` を作る。このリポジトリの単体テストは PHPUnit を使わず、`tests/unit/run.php` が読み込む素のPHPで、`eq($actual, $expected, $name)` と `ok(bool $cond, $name)` を使う(`run.php` が定義済み)。

```php
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/common/reco/evaluate.php';

// 母集団は10件。割合の計算を手で検算できる大きさにする
$pool = [];
foreach ([3.0, 4.0, 4.5, 5.0, 5.5, 6.0, 6.5, 7.0, 8.0, 9.0] as $i => $a) {
    $pool[] = ['Alcohol' => $a, 'IBU_all' => 10 + $i * 10, 'Fruity' => $i + 1];
}

$abv = eval_sorted($pool, 'abv');
eq(count($abv), 10, '母集団から度数を10件取り出す');
eq($abv[0], 3.0, '昇順の先頭は最小値');
eq($abv[9], 9.0, '昇順の末尾は最大値');

// 値が欠けている行は捨てる(数に入れない)
$holed = $pool;
$holed[] = ['Alcohol' => null, 'IBU_all' => null, 'Fruity' => null];
$holed[] = ['Alcohol' => '',   'IBU_all' => '',   'Fruity' => ''];
eq(count(eval_sorted($holed, 'abv')), 10, '欠けている値は母集団に数えない');

eq(eval_share_below($abv, 3.0), 0,  '最小値より小さいものは0%');
eq(eval_share_below($abv, 9.5), 100, '最大値より大きければ100%');
eq(eval_share_below($abv, 5.6), 50, '半分より上で50%');

// 帯は0〜4。境界は下側を含む
eq(eval_band('abv', 0),   0, '0%は最初の帯');
eq(eval_band('abv', 14),  0, '14%までは最初の帯');
eq(eval_band('abv', 15),  1, '15%から次の帯');
eq(eval_band('abv', 100), 4, '100%は最後の帯');

$line = eval_line('ibu', 25.0, eval_sorted($pool, 'ibu'), false);
eq($line['label'], '苦味', '軸の名前');
eq($line['value'], 'IBU 25', '実際の値を必ず添える');
ok(strpos($line['clause'], '%') !== false, '何と比べているかを必ず添える');
eq($line['estimated'], false, '測った値は推定ではない');

// 推定の印は行ごとに付く
$est = eval_line('abv', 5.0, $abv, true);
eq($est['estimated'], true, '推定した値には印が付く');
eq($est['value'], '5.0%', '度数は小数1桁に%を付ける');

// 値が無い軸は行そのものを出さない(語れないものを語らない)
eq(eval_line('fruity', null, eval_sorted($pool, 'fruity'), false), null, '値が無ければ行を出さない');

// 母集団が空でも落ちない(新しい環境・データ投入前)
eq(eval_line('abv', 5.0, [], false), null, '母集団が空なら行を出さない');

$built = eval_build(['abv' => 5.0, 'ibu' => 25.0, 'fruity' => null], $pool, false);
eq(count($built['lines']), 2, '値のある軸だけが行になる');
eq($built['estimated'], false, '全体の推定フラグ');

// **同じ入力からは必ず同じ文が出る**(設計書 §3。モデルに書かせない理由そのもの)
eq(json_encode(eval_build(['abv' => 5.0, 'ibu' => 25.0, 'fruity' => 3.0], $pool, false)),
   json_encode(eval_build(['abv' => 5.0, 'ibu' => 25.0, 'fruity' => 3.0], $pool, false)),
   '同じ入力からは必ず同じ結果');
```

- [ ] **Step 2: 落ちることを確かめる**

Run: `php tests/unit/run.php evaluate`
Expected: `PHP Fatal error: Uncaught Error: Call to undefined function eval_sorted()`

- [ ] **Step 3: 実装する**

`common/reco/evaluate.php` を作る。

```php
<?php
declare(strict_types=1);
/*
 * ③評価(設計書 2026-09-19 §3)。
 *
 * **このファイルは DB も API も呼ばない。** 値と母集団を受け取って、文の材料を返すだけ。
 * そうしている理由:
 *   - 同じ入力からは必ず同じ文が出る(モデルに書かせると揺れる)
 *   - 追加の費用がゼロ
 *   - 全分岐を単体テストで固定できる
 *
 * 言い方の決めごと: **断定しない。何と比べているかを必ず添える。**
 * 実データで試すと、度数5%の銘柄が「とても軽い」と出る。200銘柄は度数が高いほうに
 * 偏っている(平均5.7%)のでユニバースの中では正しいが、読み手は違和感を持つ。
 */

/** 軸の定義。列名・見出し・値の書式・比較語(低い側 / 高い側) */
const EVAL_AXES = [
    'abv'    => ['col' => 'Alcohol', 'label' => '度数',       'fmt' => '%.1f%%',
                 'low' => '軽い',   'high' => '強い'],
    'ibu'    => ['col' => 'IBU_all', 'label' => '苦味',       'fmt' => 'IBU %d',
                 'low' => '苦くない', 'high' => '苦い'],
    'fruity' => ['col' => 'Fruity',  'label' => 'フルーティーさ', 'fmt' => '%d / 10',
                 'low' => '控えめな', 'high' => 'フルーティーな'],
];

/** 帯ごとの言い方。境界(その割合**未満**なら手前の帯)と語 */
const EVAL_BANDS = [
    'abv' => [[15, 'かなり軽いほう'], [35, '軽いほう'], [65, 'ちょうど真ん中あたり'],
              [85, '強いほう'], [101, 'かなり強いほう']],
    'ibu' => [[15, 'ほとんど苦くないほう'], [35, 'おだやかなほう'], [65, 'ほどよいほう'],
              [85, 'しっかり苦いほう'], [101, 'かなり苦いほう']],
    'fruity' => [[15, 'ほとんど果実感がないほう'], [35, '控えめなほう'], [65, 'ほどよく感じるほう'],
                 [85, 'はっきり感じるほう'], [101, '果実感が主役のほう']],
];

/** 母集団から1軸の値だけを昇順で取り出す。欠けている値は数に入れない */
function eval_sorted(array $pool, string $axis): array
{
    $col = EVAL_AXES[$axis]['col'] ?? null;
    if ($col === null) { return []; }
    $out = [];
    foreach ($pool as $r) {
        $v = $r[$col] ?? null;
        if ($v === null || $v === '') { continue; }
        $out[] = (float)$v;
    }
    sort($out);
    return $out;
}

/** その値より小さいものの割合(0〜100の整数) */
function eval_share_below(array $sorted, float $v): int
{
    $n = count($sorted);
    if ($n === 0) { return 0; }
    $below = 0;
    foreach ($sorted as $x) { if ($x < $v) { $below++; } }
    return (int)round($below * 100 / $n);
}

/** 0〜4 の帯 */
function eval_band(string $axis, int $shareBelow): int
{
    $bands = EVAL_BANDS[$axis] ?? EVAL_BANDS['abv'];
    foreach ($bands as $i => $b) {
        if ($shareBelow < $b[0]) { return $i; }
    }
    return count($bands) - 1;
}

/**
 * 1行分の材料。値が無い、または母集団が空なら null(**語れないものを語らない**)。
 *
 * 比較の向きは帯で決める。低い側の帯なら「これより軽いものは 12%」、
 * それ以外は「これより苦いものが 71%」。どちらも**実際の割合**であり、
 * 丸めた以上のことは言わない。
 */
function eval_line(string $axis, ?float $value, array $sorted, bool $estimated): ?array
{
    if ($value === null || !isset(EVAL_AXES[$axis]) || count($sorted) === 0) { return null; }
    $ax    = EVAL_AXES[$axis];
    $below = eval_share_below($sorted, $value);
    $band  = eval_band($axis, $below);
    $word  = EVAL_BANDS[$axis][$band][1];

    if ($band <= 1) {
        $clause = sprintf('これより%sものは %d%%', $ax['low'], $below);
    } else {
        $clause = sprintf('これより%sものが %d%%', $ax['high'], 100 - $below);
    }

    return [
        'axis'      => $axis,
        'label'     => $ax['label'],
        'word'      => $word,
        'clause'    => $clause,
        'value'     => sprintf($ax['fmt'], $axis === 'abv' ? $value : (int)round($value)),
        'estimated' => $estimated,
    ];
}

/**
 * 3軸ぶんまとめて。
 * @param array $vals ['abv'=>?float, 'ibu'=>?float, 'fruity'=>?float]
 */
function eval_build(array $vals, array $pool, bool $estimated): array
{
    $lines = [];
    foreach (['abv', 'ibu', 'fruity'] as $axis) {
        $v = $vals[$axis] ?? null;
        $line = eval_line($axis, $v === null || $v === '' ? null : (float)$v,
                          eval_sorted($pool, $axis), $estimated);
        if ($line !== null) { $lines[] = $line; }
    }
    return ['lines' => $lines, 'estimated' => $estimated];
}
```

- [ ] **Step 4: 通ることを確かめる**

Run: `php tests/unit/run.php evaluate`
Expected: すべて ok。続けて `php tests/unit/run.php` で **全体が 0 fail**

- [ ] **Step 5: 実データで目視する**

Run:
```bash
php -r '
require "common/nebula/helpers.php"; require "common/reco/evaluate.php";
$pool = all_beers();
foreach (["pr0169","pr0038","pr0024"] as $id) {
  $b = beer_by_id($id);
  $r = eval_build(["abv"=>$b["Alcohol"],"ibu"=>$b["IBU_all"],"fruity"=>$b["Fruity"]], $pool, false);
  echo $b["ProductName"], "\n";
  foreach ($r["lines"] as $l) { printf("  %sは %s。%s。(%s)\n", $l["label"], $l["word"], $l["clause"], $l["value"]); }
}'
```
Expected: 3銘柄ぶん、設計書 §3 の言い回しの文が出る。**桜桃の雫(フルーティーさ9)が「はっきり感じるほう」以上、シュバルツ(1)が「ほとんど果実感がないほう」**になること。ならなければ帯の境界を見直す(報告に書く)

- [ ] **Step 6: コミット**

```bash
git add common/reco/evaluate.php tests/unit/evaluate_test.php
git commit -m "評価の文をデータから規則で作る純関数を追加する

設計書 2026-09-19 §3。同じ入力からは必ず同じ文が出て、追加の費用はゼロ。
断定せず、何と比べているかを必ず添える。

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: 未登録ビールの推定値を判定で受け取る

**Files:**
- Modify: `common/reco/identify.php`(`identify_parse()` / `identify_prompt()` / `identify_transport_anthropic()` の構造化出力スキーマ)
- Modify: `common/reco/repo.php`(`upload_record()`)
- Modify: `common/reco/handle.php`(`$result` をそのまま渡す箇所)
- Create: `db/migrations/009_upload_guess.sql`
- Modify: `tests/unit/identify_test.php`

**Interfaces:**
- Consumes: Task 1 の `eval_build()`(この時点ではまだ呼ばない)
- Produces: `identify_parse()` の戻り値に3つ増える
  - `abv_guess`: `?float`(0.0〜20.0 の外は null)
  - `ibu_guess`: `?int`(0〜120 の外は null)
  - `fruity_guess`: `?int`(1〜10 の外は null)
  - `upload` テーブルに同名の3列(`abv_guess DECIMAL(4,2)` / `ibu_guess SMALLINT` / `fruity_guess TINYINT`)

**背景(実装者へ):** 設計書 §3「未登録のビールも評価する」。実際に上がった最初の1件(SUPERFLUX / バンクーバーの蔵)が未登録だった。200銘柄に対して世の中のクラフトビールは桁違いに多いので、**未登録のほうが常態**になる。ここで弾くと入口が成立しない。

**費用は増えない。** いまも1回の判定で1度だけAPIを呼んでいる。返させる項目が3つ増えるだけ。

- [ ] **Step 1: 失敗するテストを書く**

`tests/unit/identify_test.php` の末尾に追記する。既存のテストの書き方(`identify_parse()` に応答の形を直接渡す)に合わせること。

```php
// --- 推定値(未登録ビールの評価に使う。設計書 2026-09-19 §3)---
$mk = function (array $extra): array {
    return ['content' => [['type' => 'text', 'text' => json_encode(array_merge([
        'is_beer' => true, 'brand_text' => 'X', 'brewery_text' => 'Y',
        'matched_product_id' => null, 'style_guess' => null,
        'color' => 5, 'clarity' => 2, 'confidence' => 0.5,
    ], $extra))]]];
};

$r = identify_parse($mk(['abv_guess' => 6.5, 'ibu_guess' => 45, 'fruity_guess' => 7]));
eq($r['abv_guess'], 6.5, '度数の推定を受け取る');
eq($r['ibu_guess'], 45,  '苦味の推定を受け取る');
eq($r['fruity_guess'], 7, 'フルーティーさの推定を受け取る');

// 範囲外はモデルの誤り。嘘の値をDBに入れるより「未取得」にする(既存の color/clarity と同じ方針)
eq(identify_parse($mk(['abv_guess' => 99]))['abv_guess'], null, '度数が範囲外なら捨てる');
eq(identify_parse($mk(['ibu_guess' => 500]))['ibu_guess'], null, '苦味が範囲外なら捨てる');
eq(identify_parse($mk(['fruity_guess' => 0]))['fruity_guess'], null, 'フルーティーさは1未満を捨てる');
eq(identify_parse($mk(['fruity_guess' => 11]))['fruity_guess'], null, 'フルーティーさは10超を捨てる');

// 項目そのものが無くても落ちない(古い応答を再生したとき)
$old = identify_parse($mk([]));
eq($old['abv_guess'], null, '項目が無ければ null');
eq($old['is_beer'], true,  '他の項目は今までどおり読める');

// プロンプトにフルーティーさの物差しが入っていること(1〜10 であることを伝えないと4段階で返る)
$p = identify_prompt([['ProductID'=>'pr0001','ProductName'=>'A','MakerName'=>'B']],
                     [['StyleID'=>'st0001','StyleName'=>'S','FamilyName'=>'F']]);
ok(strpos($p, 'fruity_guess') !== false, 'プロンプトが推定項目に触れている');
ok(strpos($p, '1〜10') !== false, 'フルーティーさの目盛りを伝えている');
```

- [ ] **Step 2: 落ちることを確かめる**

Run: `php tests/unit/run.php identify`
Expected: `FAIL 度数の推定を受け取る 期待: 6.5 実際: null`(または undefined index)

- [ ] **Step 3: マイグレーションを書く**

`db/migrations/009_upload_guess.sql`:

```sql
-- 未登録ビールの評価(設計書 2026-09-19 §3)のために、モデルが推定した値を残す。
-- 既存の color / clarity と同じ扱いで、範囲外は入れない(identify_parse で捨てる)。
ALTER TABLE upload ADD COLUMN abv_guess    DECIMAL(4,2) NULL;
ALTER TABLE upload ADD COLUMN ibu_guess    SMALLINT     NULL;
ALTER TABLE upload ADD COLUMN fruity_guess TINYINT      NULL;
```

ローカルに当てる(**ローカルの `beer` スキーマにだけ**。dev / prod は管理者が当てる):

```bash
mysql --socket=/run/mariadb/mariadb.sock beer < db/migrations/009_upload_guess.sql
mysql --socket=/run/mariadb/mariadb.sock beer -e "SHOW COLUMNS FROM upload LIKE '%_guess'"
```
Expected: 3行返る

- [ ] **Step 4: 実装する**

(a) `identify_parse()` の `$empty` に3項目を足し、戻り値にも足す。範囲検査は既存の `$inRange` を使い、度数だけは小数なので別に書く:

```php
    // 度数は小数。0〜20% の外はモデルの誤りとして捨てる(世の中のビールの実質的な上限)
    $abv = $j['abv_guess'] ?? null;
    if ($abv === null || $abv === '') {
        $abv = null;
    } else {
        $abv = (float)$abv;
        if ($abv < 0 || $abv > 20) { $abv = null; }
    }
```

戻り値の配列に追加する:

```php
        'abv_guess'          => $abv,
        'ibu_guess'          => $inRange($j['ibu_guess']    ?? null, 0, 120),
        'fruity_guess'       => $inRange($j['fruity_guess'] ?? null, 1, 10),
```

`$empty`(壊れた応答のときの形)にも同じ3キーを `null` で足すこと。**足し忘れると、API失敗の経路で undefined index が出る。**

(b) `identify_prompt()` の末尾の説明に追記する。**このプロンプトはキャッシュに載せているので、1バイト変わるとキャッシュが外れる**(外れても動作は正しい。費用が一時的に上がるだけ)。

```php
         . "あわせて、写真から読み取れる範囲で次を推定してください。"
         . "abv_guess はアルコール度数(%の数値。0〜20)、"
         . "ibu_guess は苦味の指標(0〜120の整数)、"
         . "fruity_guess は果実感の強さ(**1〜10の整数**)です。"
         . "fruity_guess の目盛りは、1=果実感が無い(ドライスタウト・ピルスナー)、"
         . "3=わずかにある(ペールエール)、5=はっきり感じる(アメリカンIPA・ヴァイツェン)、"
         . "7=主役のひとつ(ヘイジーIPA・フルーツを使ったサワー)、"
         . "9=果実が中心(果汁を大量に使ったサワー)です。"
         . "甘さと果実感を混同せず、麦芽の甘み・カラメル・蜂蜜は果実感に数えないでください。"
         . "読み取れないものは null にしてください。推測で埋めないでください。";
```

(c) `identify_transport_anthropic()` の `$schema` に3項目を足す。`required` にも足すこと。**minimum / maximum は書かない**(API が integer に対して拒否することを確認済み。範囲はプロンプト文と `identify_parse()` で担保する):

```php
            'abv_guess'          => ['type' => ['number','null']],
            'ibu_guess'          => ['type' => ['integer','null']],
            'fruity_guess'       => ['type' => ['integer','null']],
```

(d) `identify_call()` の `catch` の中にある「壊れたときの形」にも3キーを足す(`$empty` と同じ理由)。

(e) `repo.php` の `upload_record()` に3列を足す。既存の INSERT は列名を明示しているので、同じ書き方で足す:

```php
// INSERT の列リストに: abv_guess, ibu_guess, fruity_guess
// VALUES に: :abv_guess, :ibu_guess, :fruity_guess
// バインドに:
        ':abv_guess'    => $r['abv_guess']    ?? null,
        ':ibu_guess'    => $r['ibu_guess']    ?? null,
        ':fruity_guess' => $r['fruity_guess'] ?? null,
```

`handle.php` の**API失敗時の `upload_record()` 呼び出し**(`'status' => 'api_error'` の経路)にも3キーを `null` で足す。**足さないと `?? null` で拾われるので動くが、書いておくほうが読み手に親切**。

(f) `handle.php` のキャッシュ経路(`$g['reason'] === 'cached'`)が組み立てる `$result` にも3項目を足す。ここは**前回の `upload` の行から復元する**:

```php
                   'abv_guess' => $prev['abv_guess'] !== null ? (float)$prev['abv_guess'] : null,
                   'ibu_guess' => $prev['ibu_guess'] !== null ? (int)$prev['ibu_guess'] : null,
                   'fruity_guess' => $prev['fruity_guess'] !== null ? (int)$prev['fruity_guess'] : null,
```

`upload_by_hash()` は `SELECT *` なので、列を足すだけで取れる(確認すること)。

- [ ] **Step 5: 通ることを確かめる**

Run: `php tests/unit/run.php`
Expected: **0 fail**。件数は Step 1 で足したぶん増える

- [ ] **Step 6: 記録した応答で通し確認する(APIは呼ばない)**

Run:
```bash
ls tests/eval/replay/ 2>/dev/null | head
php tests/unit/run.php handle
```
Expected: `handle_test.php` が通る。**API を呼んでいないこと**を `git diff` で確かめる(`identify_transport_anthropic` を直接呼ぶコードを足していないこと)

- [ ] **Step 7: コミット**

```bash
git add common/reco/identify.php common/reco/repo.php common/reco/handle.php \
        db/migrations/009_upload_guess.sql tests/unit/identify_test.php
git commit -m "未登録ビールの評価のため、判定で度数・苦味・フルーティーさも推定させる

設計書 2026-09-19 §3。未登録のほうが常態なので、DBに行が無くても評価を出せるようにする。
1回の判定で返る項目が増えるだけで、API の呼び出し回数も費用も増えない。
フルーティーさは docs/fruity-rubric.md と同じ1〜10の物差しで推定させる。

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: ③評価を画面に出す

**Files:**
- Modify: `try.php`(結果の表示部。確認フォームの文言も)
- Modify: `common/reco/handle.php`(評価の材料を `$h['eval']` として返す)
- Modify: `assets/css/exposure.css`(評価ブロックのスタイル)
- Modify: `tests/unit/handle_test.php`

**Interfaces:**
- Consumes: Task 1 の `eval_build(array $vals, array $pool, bool $estimated): array`、Task 2 の `$result['abv_guess'] / ['ibu_guess'] / ['fruity_guess']`
- Produces: `reco_handle_upload()` の戻り値に `'eval' => ['lines'=>[...], 'estimated'=>bool, 'source'=>'db'|'guess']` が増える

**背景(実装者へ):** これが今回の作り直しの中心である。**いまの画面は、この機能がやっている判断を出していない。** 実際には「同じ New England IPA の中から、別の蔵のものを選ぶ」という仕事をしているのに、`Similar` という英語の見出しの下に3行並ぶだけだった。

**評価の材料をどこから取るか:**

| 状態 | 度数・苦味・フルーティーさ | `source` | 推定の印 |
|---|---|---|---|
| DBに行がある(`matched_product_id` あり) | `products` の値 | `'db'` | その銘柄の `estimated_fields` に従う |
| DBに行が無い | `abv_guess` / `ibu_guess` / `fruity_guess` | `'guess'` | **必ず付ける**(「ラベルからの推定です」) |

- [ ] **Step 1: 失敗するテストを書く**

`tests/unit/handle_test.php` に追記する。既存のテストが使っている「偽の `$transport`」の作り方に合わせること。

```php
// --- ③評価(設計書 2026-09-19 §3)---
// 既存のヘルパーをそのまま使う:
//   reco_ht_visitor()            新しい訪問者ID(連投の上限に当たらないよう毎回変える)
//   reco_ht_file('IMG_xxxx.jpeg')  data/sample/ の写真。**写真ごとにハッシュが違う**ので、
//                                 使い回すと gate が 'cached' を返し、判定の経路に入らない
//   reco_ht_resp([...])          応答の形を組み立てる
//   reco_ht_transport($resp, $calls)  その応答を返す偽の transport
// まだどのテストも使っていない写真は IMG_2238 / IMG_2315 / IMG_4684 / IMG_4756 /
// IMG_4816 / IMG_4817 / IMG_4818。ここでは先頭の3つを使う。

// DBに無い銘柄でも評価が出る。推定の印が付く
$calls = [];
$visitor = reco_ht_visitor();
$h = reco_handle_upload($visitor, reco_ht_file('IMG_2238.jpeg'),
    reco_ht_transport(reco_ht_resp([
        'is_beer' => true, 'brand_text' => 'SUPERFLUX', 'brewery_text' => 'Superflux Beer',
        'matched_product_id' => null, 'confidence' => 0.9,
        'abv_guess' => 6.5, 'ibu_guess' => 45, 'fruity_guess' => 7,
    ]), $calls));
ok(isset($h['eval']), '評価の材料が返る');
eq($h['eval']['source'], 'guess', 'DBに無ければ推定が出どころ');
eq($h['eval']['estimated'], true, '推定の印が付く');
eq(count($h['eval']['lines']), 3, '3軸ぶんの行が出る');

// DBにある銘柄は測った値を使う(pr9999 は Alcohol 6.0 / IBU_all 40 / Fruity 5 で作る)
$recoTestPid = reco_ht_seed_beer();
$calls = [];
$visitor = reco_ht_visitor();
$h2 = reco_handle_upload($visitor, reco_ht_file('IMG_2315.jpeg'),
    reco_ht_transport(reco_ht_resp([
        'is_beer' => true, 'matched_product_id' => $recoTestPid, 'confidence' => 0.95,
        'abv_guess' => 9.9, 'ibu_guess' => 99, 'fruity_guess' => 1,
    ]), $calls));
eq($h2['eval']['source'], 'db', 'DBに行があればDBの値を使う');
eq($h2['eval']['estimated'], false, 'DBの値には推定の印を付けない');
eq($h2['eval']['lines'][0]['value'], '6.0%', '推定値(9.9%)ではなくDBの値が出る');
reco_ht_unseed_beer();

// ビール以外には評価を出さない(語れないものを語らない)
$calls = [];
$visitor = reco_ht_visitor();
$h3 = reco_handle_upload($visitor, reco_ht_file('IMG_4684.jpeg'),
    reco_ht_transport(reco_ht_resp([
        'is_beer' => false, 'brand_text' => 'Red Bull', 'confidence' => 0.9,
    ]), $calls));
eq($h3['eval']['lines'], [], 'ビール以外には評価を出さない');
```

あわせて `reco_ht_seed_beer()` の INSERT に苦味とフルーティーさを足す(いまは `Alcohol` しか入れていないので、上の「DBの値が出る」の検査が1行しか作れない):

```php
    db()->prepare("INSERT INTO products (ProductID, MakerID, ProductName, StyleID, Alcohol, IBU_all, Fruity)
                   VALUES (:p, 'mk9999', 'テスト銘柄', 'st9999', 6.0, 40, 5)")->execute([':p' => $pid]);
```

**この行を変えると、既存の「DBにある銘柄: recommendation に3行入る」などのテストにも影響する。
変えたあと必ず `php tests/unit/run.php` を全件流すこと。**


- [ ] **Step 2: 落ちることを確かめる**

Run: `php tests/unit/run.php handle`
Expected: `FAIL 評価の材料が返る`

- [ ] **Step 3: `handle.php` に評価を組み込む**

`require_once __DIR__ . '/evaluate.php';` を足したうえで、`$view === 'result'` の分岐の中、`$picked` を決めたあとに足す:

```php
    // ③評価(設計書 2026-09-19 §3)。DBに行があれば測った値、無ければモデルの推定。
    // **推定のときは必ず印を付ける。** 未登録のほうが常態なので、ここを黙ると嘘になる
    $eval = ['lines' => [], 'estimated' => false, 'source' => 'db'];
    if ($view === 'result' && $result['is_beer'] === true) {
        if ($result['matched_product_id'] && !empty($seed['ProductID'])) {
            $eval = eval_build(
                ['abv' => $seed['Alcohol'], 'ibu' => $seed['IBU_all'], 'fruity' => $seed['Fruity']],
                reco_pool(), false);
            $eval['source'] = 'db';
        } else {
            $eval = eval_build(
                ['abv' => $result['abv_guess'], 'ibu' => $result['ibu_guess'],
                 'fruity' => $result['fruity_guess']],
                reco_pool(), true);
            $eval['source'] = 'guess';
        }
    }
```

戻り値の `compact()` に `'eval'` を足す。

**`$seed` には `IBU_all` と `Fruity` が入っているか確認すること。** `beer_by_id()` は `p.*` を取るので入っている。未登録のときに作っている配列(`['ProductID' => null, ...]`)には入っていないが、そちらは `guess` の枝を通るので問題ない。

- [ ] **Step 4: `try.php` に評価を出す**

結果の表示部で、`ex-hit` / `ex-sub` のあと、確認フォームの**前**に置く。

```php
    <?php if (!empty($eval['lines'])): ?>
      <div class="ex-eval">
        <div class="ex-lbl">このビールは</div>
        <?php foreach ($eval['lines'] as $l): ?>
          <div class="ex-ev">
            <span class="ex-ev-k"><?= e($l['label']) ?></span>
            <span class="ex-ev-w"><?= e($l['word']) ?></span>
            <span class="ex-ev-c"><?= e($l['clause']) ?></span>
            <span class="ex-ev-v"><?= e($l['value']) ?></span>
          </div>
        <?php endforeach; ?>
        <?php if ($eval['source'] === 'guess'): ?>
          <p class="ex-sub">数値は<b>ラベルからの推定</b>です。この銘柄はまだ登録がありません。</p>
        <?php endif; ?>
        <p class="ex-sub">くらべているのは、このサイトに登録されている <?= count(reco_pool()) ?> 銘柄です。</p>
      </div>
    <?php endif; ?>
```

`$eval` を `reco_handle_upload()` の戻りから受け取る変数に追加する(`$h['eval']`)。**初期値 `$eval = ['lines'=>[], 'estimated'=>false, 'source'=>'db'];` をファイル冒頭の変数初期化(`$result = $picked = $seed = [];` のあたり)に置くこと。** 置かないと、写真を上げていない初回表示で undefined variable になる。

**「この銘柄はまだ登録されていません」の既存の一文は消す。** 上の評価ブロックの中に同じことが書かれ、二重になるため。

- [ ] **Step 5: 確認フォームの文言を変える**

設計書 §3「確認は③の中に置く。『この評価で合っていますか』という形になり、旧設計の『この銘柄で合っていますか』より自然に収まる」。

既存の `<form class="ex-ask">` の中の見出しを、`$branch` によらず**常に**出す形に変える:

```php
      <span class="ex-sub" style="flex:1 1 100%">
        <?php if ($branch === 'unknown' && !$result['brand_text']): ?>
          銘柄名は読み取れませんでした。<?= !empty($seed['StyleName']) ? e($seed['StyleName']) . ' に見えます。' : '' ?>この評価で合っていますか?
        <?php else: ?>
          この評価で合っていますか?
        <?php endif; ?>
      </span>
```

**ボタンのラベルと `name`/`value` は変えない**(`confirm=yes` / `confirm=no`)。変えると `reco_confirm_flash()` と既存のテストが壊れる。

- [ ] **Step 6: CSS を足す**

`assets/css/exposure.css` の末尾に足す。既存の `.ex-sub` / `.ex-lbl` の書き方(色は `var(--dim)` / `var(--faint)`、フォントは既存の指定を継承)に合わせること。

```css
/* ③評価(設計書 2026-09-19 §3)。並ぶところなので強調色は使わない */
.ex-eval { margin: 18px 0 6px; }
.ex-ev { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px 10px;
         padding: 9px 0; border-bottom: 1px solid rgba(255,255,255,.06); }
.ex-ev-k { flex: 0 0 6.5em; font-size: 12px; color: var(--dim); }
.ex-ev-w { font-size: 14px; font-weight: 700; }
.ex-ev-c { font-size: 11.5px; color: var(--faint); }
.ex-ev-v { margin-left: auto; font-size: 12px; color: var(--dim);
           font-variant-numeric: tabular-nums; }
@media (max-width: 380px) {
  .ex-ev-k { flex-basis: 100%; }
  .ex-ev-v { margin-left: 0; }
}
```

- [ ] **Step 7: 画面で確かめる**

Run:
```bash
php -S 127.0.0.1:8099 -t . > /tmp/srv.log 2>&1 &
sleep 1
curl -s "http://127.0.0.1:8099/try.php" | head -20
```
Expected: 200 が返り、写真を上げる前の画面が出る(年齢確認へ 302 する場合は、`visitor` の行に `age_confirmed_at` を入れてから再実行する)。**`php -S` を終わらせるのを忘れないこと**(前に、落ちたサーバに curl して「記述が無い」と読み違えた事故がある)

- [ ] **Step 8: テストを通してコミット**

Run: `php tests/unit/run.php` → 0 fail

```bash
git add common/reco/handle.php tests/unit/handle_test.php
git commit -m "評価の材料を reco_handle_upload が返すようにする

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
git add try.php assets/css/exposure.css
git commit -m "③評価を画面に出し、確認を「この評価で合っていますか」に変える

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: 推薦に理由を添える

**Files:**
- Modify: `try.php`(`Similar` の見出しと各行)
- Modify: `common/nebula/helpers.php`(`reco_stage_label()` を足す。表示に使う語の定義なのでここに置く)
- Create: `tests/unit/stage_test.php`

**Interfaces:**
- Consumes: `$picked` の各要素が持つ `stage`(1〜4)と `row`
- Produces: `reco_stage_label(int $stage, array $seed): string`

**背景(実装者へ):** 設計書 §6。**あっけなさの正体**だったところ。`$picked` の各要素は `stage`(カスケードの何段目で拾ったか)を持っているのに、画面で捨てていた。

| 段階 | 何と言うか |
|---|---|
| 1 | 同じ **<スタイル名>** から |
| 2 | 同じ **<系統名>** の系統から |
| 3 | 近い味わいのものから |
| 4 | 度数が近いものから |

加えて、**「別の蔵を優先している」ことを一言**添える。これがこの機能の判断そのものであり、黙っていては伝わらない。

- [ ] **Step 1: 失敗するテストを書く**

`tests/unit/stage_test.php`:

```php
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/common/nebula/helpers.php';

$seed = ['StyleName' => 'New England IPA', 'FamilyName' => 'IPA'];
eq(reco_stage_label(1, $seed), '同じ New England IPA から', '1段目はスタイル名');
eq(reco_stage_label(2, $seed), '同じ IPA の系統から',        '2段目は系統名');
eq(reco_stage_label(3, $seed), '近い味わいのものから',        '3段目');
eq(reco_stage_label(4, $seed), '度数が近いものから',          '4段目');

// 名前が無いとき(未登録で style_guess も取れなかった)に壊れた文を出さない
$bare = ['StyleName' => '', 'FamilyName' => ''];
eq(reco_stage_label(1, $bare), '近い味わいのものから', 'スタイル名が無ければ言い切らない');
eq(reco_stage_label(2, $bare), '近い味わいのものから', '系統名が無ければ言い切らない');

// 想定外の段は黙る(知らない状態では何も言わない)
eq(reco_stage_label(9, $seed), '', '知らない段では何も言わない');
```

- [ ] **Step 2: 落ちることを確かめる**

Run: `php tests/unit/run.php stage`
Expected: `Call to undefined function reco_stage_label()`

- [ ] **Step 3: 実装する**

`common/nebula/helpers.php` の末尾に足す:

```php
/**
 * 推薦の理由(設計書 2026-09-19 §6)。カスケードの何段目で拾ったかを言葉にする。
 *
 * 名前が取れていないときは**言い切らない**。「同じ  から」のような壊れた文を出すより、
 * 一段ゆるい言い方に落とす。
 */
function reco_stage_label($stage, $seed) {
    $style  = trim((string)($seed['StyleName']  ?? ''));
    $family = trim((string)($seed['FamilyName'] ?? ''));
    switch ((int)$stage) {
        case 1: return $style  !== '' ? '同じ ' . $style . ' から'      : '近い味わいのものから';
        case 2: return $family !== '' ? '同じ ' . $family . ' の系統から' : '近い味わいのものから';
        case 3: return '近い味わいのものから';
        case 4: return '度数が近いものから';
        default: return '';
    }
}
```

- [ ] **Step 4: 通ることを確かめる**

Run: `php tests/unit/run.php stage` → すべて ok

- [ ] **Step 5: `try.php` に出す**

`<div class="ex-lbl">Similar</div>` を差し替える。**英語の見出しをやめる**(設計書 §1: 「`Similar` という英語の見出しの下に3行並ぶだけだった」)。

```php
    <div class="ex-lbl">次の一杯に</div>
    <?php
      // 「別の蔵を優先している」ことを言う。これがこの機能の判断そのもので、
      // 黙っていては伝わらない(設計書 §6)
      $seedMaker = $seed['MakerID'] ?? null;
      $others = 0;
      foreach ($picked as $p) { if (($p['row']['MakerID'] ?? null) !== $seedMaker) { $others++; } }
    ?>
    <?php if ($seedMaker !== null && $others > 0): ?>
      <p class="ex-sub">同じ味わいの方向で、<b>別の蔵のもの</b>を先に出しています。</p>
    <?php endif; ?>
    <?php foreach ($picked as $p): $r = $p['row'];
          list($col) = group_meta(style_group($r['FamilyName'] ?? '', $r['StyleName'] ?? ''));
          $why = reco_stage_label($p['stage'] ?? 0, $seed); ?>
```

各行の中、`ex-nm` の `<span>` を差し替えて理由を出す:

```php
        <span class="ex-nm">
          <b><?= e($r['ProductName']) ?></b>
          <span><?= e($r['MakerName'] ?: '') ?><?= $why !== '' ? ' · ' . e($why) : '' ?></span>
        </span>
```

**`$p['stage']` というキーがあることを `reco_pick()` の実装で確かめてから書くこと。** `common/reco/cascade.php` の `reco_pick()` が何を積んでいるかを読む。キー名が違えば、そちらに合わせる(この計画の記述ではなく**現物が正**)。

- [ ] **Step 6: 画面で確かめてコミット**

Run: `php tests/unit/run.php` → 0 fail

```bash
git add common/nebula/helpers.php tests/unit/stage_test.php
git commit -m "推薦の理由(カスケードの段)を言葉にする関数を追加する

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
git add try.php
git commit -m "推薦に理由を添え、「別の蔵を優先している」ことを画面に出す

Similar という英語の見出しの下に3行並ぶだけだったのが、あっけなさの正体だった。
stage は前から持っていたのに画面で捨てていた。

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 5: ⑤拡張 —「もっと〜」で絞り込む

**Files:**
- Modify: `common/reco/cascade.php`(`reco_shift()` と `reco_expand()` を追加。**既存の `reco_pick()` は1行も変えない**)
- Modify: `common/reco/handle.php`(`reco_seed_from_upload()` と `reco_view_upload()` を追加)
- Modify: `try.php`(`?u=<upload_id>&more=<方向>` の GET 分岐とボタン)
- Modify: `assets/css/exposure.css`
- Create: `tests/unit/expand_test.php`

**Interfaces:**
- Consumes: `reco_pick(array $seed, array $pool, int $limit = 3): array`(各要素 `['ProductID','stage','row']`)、Task 1 の `eval_build()`、Task 3 の `$h['eval']`
- Produces:
  - `const RECO_DIRS`(方向の定義。キーは `lighter` / `stronger` / `bitter` / `less_bitter` / `fruitier`)
  - `reco_shift(array $seed, string $dir): ?array` — ずらした仮の1本。方向が不正なら `null`
  - `reco_expand(array $seed, array $pool, string $dir, int $limit = 3): array` — `['picked'=>[...], 'ok'=>bool, 'reason'=>string]`
  - `reco_seed_from_upload(array $u): array` — `upload` の行から基準の1本を作る
  - `reco_view_upload(array $u, ?string $dir = null): array` — `['seed','eval','picked','dir','dirOk','dirReason']`

**背景(実装者へ):** 設計書 §5。自由記述はいまは作らない。**方向は数個しかない**のでボタンで足りる。1回ごとの課金も発生せず、押された内容が**そのまま蔵向けの数字になる**。

**どう絞り込むか(ここが設計の肝):**

段階カスケードは**そのまま使う**。並べ替えの鍵(`reco_sort_key()`)は「別の蔵 → 度数が近い → 新しいID」で、**苦味とフルーティーさは見ていない**。だから方向ごとに次のようにする。

| 方向 | やること |
|---|---|
| もっと軽く / もっと強く | 仮の1本の `Alcohol` を **±1.0%** ずらす。並べ替えが度数の近さを見るので、これだけで効く |
| もっと苦く / 苦味は控えめに | 候補を **`IBU_all` が基準より大きい(小さい)ものだけ**に絞ってから、いつも通り並べる |
| もっとフルーティーに | 候補を **`Fruity` が基準より大きいものだけ**に絞ってから、いつも通り並べる |

**値が無いときは方向を適用しない。** 基準の苦味が読み取れていないのに「もっと苦く」を出すと、何を基準に苦いのか言えない。そのときは `ok=false` を返し、**画面で理由を言う**(黙って普通の推薦を出さない)。

- [ ] **Step 1: 失敗するテストを書く**

`tests/unit/expand_test.php`:

```php
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/common/nebula/helpers.php';
require_once dirname(__DIR__, 2) . '/common/reco/cascade.php';

$seed = ['ProductID' => 'pr0001', 'StyleID' => 'st0001', 'FamilyName' => 'IPA',
         'StyleName' => 'Hazy IPA', 'MakerID' => 'mk0001',
         'Alcohol' => 6.0, 'IBU_all' => 40.0, 'Fruity' => 5.0];

$pool = [
  ['ProductID'=>'pr1001','StyleID'=>'st0001','FamilyName'=>'IPA','StyleName'=>'Hazy IPA',
   'MakerID'=>'mk0002','MakerName'=>'B','ProductName'=>'軽い','Alcohol'=>4.0,'IBU_all'=>20.0,'Fruity'=>3.0],
  ['ProductID'=>'pr1002','StyleID'=>'st0001','FamilyName'=>'IPA','StyleName'=>'Hazy IPA',
   'MakerID'=>'mk0003','MakerName'=>'C','ProductName'=>'強い','Alcohol'=>8.0,'IBU_all'=>60.0,'Fruity'=>8.0],
  ['ProductID'=>'pr1003','StyleID'=>'st0001','FamilyName'=>'IPA','StyleName'=>'Hazy IPA',
   'MakerID'=>'mk0004','MakerName'=>'D','ProductName'=>'同じくらい','Alcohol'=>6.1,'IBU_all'=>41.0,'Fruity'=>5.0],
];

// ずらした仮の1本
eq(reco_shift($seed, 'lighter')['Alcohol'],  5.0, 'もっと軽く は度数を1.0下げる');
eq(reco_shift($seed, 'stronger')['Alcohol'], 7.0, 'もっと強く は度数を1.0上げる');
eq(reco_shift($seed, 'bitter')['Alcohol'],   6.0, '苦味の方向では度数を動かさない');
eq(reco_shift($seed, 'なにか'), null, '知らない方向は受け付けない');

// 軽い方向では、軽いものが先に出る
$r = reco_expand($seed, $pool, 'lighter');
eq($r['ok'], true, '軽い方向は使える');
eq($r['picked'][0]['ProductID'], 'pr1001', 'もっと軽く で軽いものが先頭');

// 苦い方向では、基準より苦いものだけになる
$r = reco_expand($seed, $pool, 'bitter');
eq($r['ok'], true, '苦い方向は使える');
eq(count($r['picked']), 2, '基準(40)より苦い2件だけ');
foreach ($r['picked'] as $p) { ok((float)$p['row']['IBU_all'] > 40.0, '選ばれたものは必ず基準より苦い'); }

// 控えめ方向
$r = reco_expand($seed, $pool, 'less_bitter');
eq(count($r['picked']), 1, '基準より苦くないものは1件');
eq($r['picked'][0]['ProductID'], 'pr1001', '控えめ方向は苦くないものを出す');

// フルーティー方向
$r = reco_expand($seed, $pool, 'fruitier');
eq($r['picked'][0]['ProductID'], 'pr1002', 'フルーティー方向は果実感が強いものを出す');

// 基準の値が無ければ、その方向は使えない。**黙って普通の推薦を出さない**
$noIbu = $seed; $noIbu['IBU_all'] = null;
$r = reco_expand($noIbu, $pool, 'bitter');
eq($r['ok'], false, '基準の苦味が無ければ使えない');
eq($r['reason'], 'no_seed_value', '理由を返す');
eq($r['picked'], [], '使えないときは1件も返さない');

// 候補が尽きたときも黙らない
$r = reco_expand($seed, [$pool[0]], 'bitter');
eq($r['ok'], false, '該当が無ければ使えない');
eq($r['reason'], 'no_candidate', '理由を返す');

// 知らない方向
$r = reco_expand($seed, $pool, 'sideways');
eq($r['ok'], false, '知らない方向は使えない');
eq($r['reason'], 'bad_dir', '理由を返す');
```

- [ ] **Step 2: 落ちることを確かめる**

Run: `php tests/unit/run.php expand`
Expected: `Call to undefined function reco_shift()`

- [ ] **Step 3: `cascade.php` に追記する**

**既存の関数は変更しない。** 下の3つを末尾に足す。

```php
/**
 * ⑤拡張の方向(設計書 2026-09-19 §5)。
 *
 * axis  : どの軸を動かすか
 * delta : 度数をずらす幅(度数の方向だけで使う)
 * cmp   : 候補を絞る向き。'>' なら基準より大きいものだけ
 * label : 画面のボタンに出す文字
 */
const RECO_DIRS = [
    'lighter'     => ['axis' => 'Alcohol', 'delta' => -1.0, 'cmp' => '<', 'label' => 'もっと軽く'],
    'stronger'    => ['axis' => 'Alcohol', 'delta' => +1.0, 'cmp' => '>', 'label' => 'もっと強く'],
    'bitter'      => ['axis' => 'IBU_all', 'delta' => 0.0,  'cmp' => '>', 'label' => 'もっと苦く'],
    'less_bitter' => ['axis' => 'IBU_all', 'delta' => 0.0,  'cmp' => '<', 'label' => '苦味は控えめに'],
    'fruitier'    => ['axis' => 'Fruity',  'delta' => 0.0,  'cmp' => '>', 'label' => 'もっとフルーティーに'],
];

/**
 * 押した方向へずらした「仮の1本」。度数の方向だけが実際に値を動かす
 * (並べ替えが度数の近さを見るため)。苦味・フルーティーさは候補の絞り込みで効かせる。
 */
function reco_shift(array $seed, string $dir): ?array
{
    if (!isset(RECO_DIRS[$dir])) { return null; }
    $d = RECO_DIRS[$dir];
    $out = $seed;
    if ($d['axis'] === 'Alcohol' && !is_unmeasured($seed['Alcohol'] ?? null)) {
        $out['Alcohol'] = (float)$seed['Alcohol'] + $d['delta'];
    }
    return $out;
}

/**
 * 方向つきの推薦。段階カスケードはそのまま使い、**候補を絞ってから渡す**。
 *
 * 基準の値が無い方向は使わせない。何を基準に「もっと苦い」と言うのかが
 * 言えないまま推薦を出すと、画面が嘘をつくことになる。
 *
 * @return array ['picked'=>array, 'ok'=>bool, 'reason'=>string]
 *               reason: '' / 'bad_dir' / 'no_seed_value' / 'no_candidate'
 */
function reco_expand(array $seed, array $pool, string $dir, int $limit = 3): array
{
    if (!isset(RECO_DIRS[$dir])) {
        return ['picked' => [], 'ok' => false, 'reason' => 'bad_dir'];
    }
    $d    = RECO_DIRS[$dir];
    $base = $seed[$d['axis']] ?? null;
    if (is_unmeasured($base)) {
        return ['picked' => [], 'ok' => false, 'reason' => 'no_seed_value'];
    }
    $base = (float)$base;

    $narrowed = [];
    foreach ($pool as $r) {
        $v = $r[$d['axis']] ?? null;
        if (is_unmeasured($v)) { continue; }   // 比べようがないものは出さない
        $v = (float)$v;
        if ($d['cmp'] === '>' ? $v > $base : $v < $base) { $narrowed[] = $r; }
    }
    if (count($narrowed) === 0) {
        return ['picked' => [], 'ok' => false, 'reason' => 'no_candidate'];
    }

    $picked = reco_pick(reco_shift($seed, $dir), $narrowed, $limit);
    if (count($picked) === 0) {
        return ['picked' => [], 'ok' => false, 'reason' => 'no_candidate'];
    }
    return ['picked' => $picked, 'ok' => true, 'reason' => ''];
}
```

- [ ] **Step 4: 通ることを確かめる**

Run: `php tests/unit/run.php expand` → すべて ok

- [ ] **Step 5: `upload` の行から画面を組み立て直せるようにする**

⑤のボタンは GET で戻ってくるので、**写真を上げ直さずに同じ評価と推薦を再現**できなければならない。`handle.php` の末尾に足す:

```php
/**
 * `upload` の1行から基準の1本を作る。写真を上げ直さずに、同じ評価と推薦を再現するため。
 * **DBの行がある銘柄はその値を、無ければ判定時の推定値を使う。**
 */
function reco_seed_from_upload(array $u): array
{
    if (!empty($u['product_id'])) {
        $b = beer_by_id($u['product_id']);
        if ($b) { return $b; }
    }
    $seed = ['ProductID' => null, 'StyleID' => $u['style_guess'] ?: null,
             'FamilyName' => '', 'StyleName' => '', 'MakerID' => null,
             'Alcohol' => $u['abv_guess'] !== null ? (float)$u['abv_guess'] : null,
             'IBU_all' => $u['ibu_guess'] !== null ? (float)$u['ibu_guess'] : null,
             'Fruity'  => $u['fruity_guess'] !== null ? (float)$u['fruity_guess'] : null];
    if ($seed['StyleID']) {
        $s = style_by_id($seed['StyleID']);
        $seed['FamilyName'] = $s['FamilyName'] ?? '';
        $seed['StyleName']  = $s['StyleName'] ?? '';
    }
    return $seed;
}

/**
 * `upload` の1行から、評価・推薦(方向つきも)をまとめて作る。
 * **持ち主の検査は呼び出し元の責務**(upload_owned_by() を通った行だけを渡すこと)。
 *
 * @param string|null $dir RECO_DIRS のキー。null なら方向なしの推薦
 */
function reco_view_upload(array $u, ?string $dir = null): array
{
    $pool = reco_pool();
    $seed = reco_seed_from_upload($u);
    $fromDb = !empty($u['product_id']);

    $eval = eval_build(
        ['abv' => $seed['Alcohol'], 'ibu' => $seed['IBU_all'], 'fruity' => $seed['Fruity']],
        $pool, !$fromDb);
    $eval['source'] = $fromDb ? 'db' : 'guess';

    if ($dir === null) {
        return ['seed' => $seed, 'eval' => $eval, 'picked' => reco_pick($seed, $pool),
                'dir' => null, 'dirOk' => true, 'dirReason' => ''];
    }
    $e = reco_expand($seed, $pool, $dir);
    return ['seed' => $seed, 'eval' => $eval, 'picked' => $e['picked'],
            'dir' => $dir, 'dirOk' => $e['ok'], 'dirReason' => $e['reason']];
}
```

**`$seed['IBU_all']` と `$seed['Fruity']` が `beer_by_id()` の戻りに入っていることを確かめること**(`p.*` なので入っている)。

- [ ] **Step 6: `try.php` に GET 分岐とボタンを足す**

POST処理の**あと**、表示部の**前**に足す:

```php
// ⑤拡張、および ④保管のあとに戻ってくるときの経路。
// 写真は上げ直さない。upload の行から同じ評価と推薦を組み立て直す
if ($view === 'intake' && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['u'])) {
    $u = upload_owned_by((int)$_GET['u'], $visitorId);
    if ($u && (int)$u['is_beer'] === 1) {
        $dir = isset($_GET['more']) && isset(RECO_DIRS[$_GET['more']]) ? (string)$_GET['more'] : null;
        $v = reco_view_upload($u, $dir);
        $view     = 'result';
        $uploadId = (int)$u['upload_id'];
        $imageWebPath = $u['image_path'] ? '/photo.php?u=' . $uploadId : null;
        $result = ['is_beer' => true, 'matched_product_id' => $u['product_id'],
                   'brand_text' => $u['brand_text'], 'brewery_text' => $u['brewery_text'],
                   'style_guess' => $u['style_guess'], 'color' => $u['color'],
                   'clarity' => $u['clarity'],
                   'confidence' => $u['confidence'] !== null ? (float)$u['confidence'] : null,
                   'error' => false];
        $seed = $v['seed']; $eval = $v['eval']; $picked = $v['picked'];
        $dirNow = $v['dir']; $dirOk = $v['dirOk']; $dirReason = $v['dirReason'];
        $existingNote = note_for_upload($uploadId);          // Task 7 で使う
        $candidates   = $v['candidates'] ?? [];              // Task 9 で使う
        if ($dir !== null) {
            // 押された方向は、そのまま蔵に渡す分析の中身になる(設計書 §5)
            event_record($visitorId, 'expand_' . $dir, null, $uploadId);
        }
    }
}
```

**`$dirNow` / `$dirOk` / `$dirReason` の初期値**(`null` / `true` / `''`)を、ファイル冒頭の変数初期化に置くこと。

推薦の並びの**下**にボタンを出す:

```php
    <div class="ex-lbl">別の方向で探す</div>
    <div class="ex-dirs">
      <?php foreach (RECO_DIRS as $k => $d): ?>
        <a class="ex-btn ghost<?= $dirNow === $k ? ' on' : '' ?>"
           href="/try.php?u=<?= (int)$uploadId ?>&amp;more=<?= e($k) ?>"><?= e($d['label']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php if ($dirNow !== null && !$dirOk): ?>
      <p class="ex-msg">
        <?php if ($dirReason === 'no_seed_value'): ?>
          この写真からは、その方向で比べるための値を読み取れませんでした。
        <?php else: ?>
          この方向に当てはまる銘柄が、いまの登録の中にありませんでした。
        <?php endif; ?>
      </p>
    <?php elseif ($dirNow !== null): ?>
      <p class="ex-sub"><b><?= e(RECO_DIRS[$dirNow]['label']) ?></b> で選び直しました。</p>
    <?php endif; ?>
```

**方向が効かなかったときに、方向なしの推薦を出したままにしないこと。** `$picked` は空になるので、`foreach` は何も出さない。それでよい(理由は上のメッセージが言う)。

- [ ] **Step 7: CSS を足す**

```css
/* ⑤拡張のボタン。並ぶところなので強調色は使わない */
.ex-dirs { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0 18px; }
.ex-dirs .ex-btn { flex: 0 1 auto; font-size: 12px; padding: 8px 12px; }
.ex-dirs .ex-btn.on { border-color: rgba(255,255,255,.45); }
```

- [ ] **Step 8: テストを通してコミット**

Run: `php tests/unit/run.php` → 0 fail

```bash
git add common/reco/cascade.php common/reco/handle.php tests/unit/expand_test.php
git commit -m "⑤拡張: 押した方向へずらした仮の1本で推薦を引き直す

段階カスケードはそのまま使い、候補を絞ってから渡す。
基準の値が無い方向は使わせない(何を基準に「もっと苦い」と言うのかが言えないため)。

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
git add try.php assets/css/exposure.css
git commit -m "⑤拡張のボタンを画面に出す

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 6: ②解析中 — 宇宙に投げ込む

**Files:**
- Create: `assets/js/warp.js`
- Modify: `try.php`(送信時に全画面へ切り替える)
- Modify: `assets/css/exposure.css`
- Modify: `deploy/make_archive.sh`(**新しいJSを配布物に入れる。入れ忘れると本番で404になる**)

**Interfaces:**
- Consumes: `group_map()` が流し込む CSS 変数 `--g-*` / `--h-*`(`try.php` の `<style>` で既に出ている)
- Produces: グローバル関数 `startWarp(opts)` — `opts.colors` は減彩の色の配列、`opts.accent` は強調色1つ

**背景(実装者へ):** 設計書 §2-b。解析には2〜5秒かかる。いまは「判定しています…」という文字だけで、**その間が空いている**。

**これは飾りではない。** `assets/js/galaxy.js` には既に、200銘柄が度数・苦味・フルーティーさの座標に浮かぶ宇宙がある。③の定義が「クラフトビールのユニバースに照らして言語化する」である以上、**照らし合わせている様子をそのまま見せている**ことになる。待ち時間が説明に変わる。

**制約(設計書 §2-b):**
- **`galaxy.js` をそのまま持ち込まない。軽い2Dの星野で足りる。** 読み込みが重いと、待ち時間を埋めるはずのものが待ち時間を作る
- **終わりの尺を決めない。** 応答が返った時点でブラウザが遷移するので、長引いても自然に回り続ける
- **JS が動かない端末では絵は出ない。** そのまま待つだけになるが、**送信は通る**
- **飛び込む1点だけが強調色。** 他の星は減彩の色

- [ ] **Step 1: `assets/js/warp.js` を書く**

```js
/*
 * ②解析中の絵(設計書 2026-09-19 §2-b)。
 *
 * 味覚の宇宙へ1点が投げ込まれる。これは飾りではなく、③の評価が
 * 「クラフトビールのユニバースに照らして言語化する」ことの、そのままの絵である。
 *
 * galaxy.js(3D・200銘柄・ドラッグ操作つき)は持ち込まない。
 * 待ち時間を埋めるはずのものが待ち時間を作っては意味がない。
 *
 * 終わりの尺は決めない。応答が返るとブラウザが遷移するので、それまで回り続ける。
 */
function startWarp(opts) {
  var o = opts || {};
  var host = document.getElementById(o.mount || 'warp');
  if (!host) { return; }
  var cv = document.createElement('canvas');
  host.appendChild(cv);
  var x = cv.getContext('2d');
  if (!x) { return; }

  var DPR = Math.min(window.devicePixelRatio || 1, 2);
  var W = 0, H = 0;
  function size() {
    W = host.clientWidth; H = host.clientHeight;
    cv.width = Math.floor(W * DPR); cv.height = Math.floor(H * DPR);
    cv.style.width = W + 'px'; cv.style.height = H + 'px';
    x.setTransform(DPR, 0, 0, DPR, 0, 0);
  }
  size();
  window.addEventListener('resize', size);

  var colors = (o.colors && o.colors.length) ? o.colors : ['#7f8b93'];
  var accent = o.accent || '#cfe6f2';

  // 既存の星。ゆっくり漂うだけ
  var stars = [];
  for (var i = 0; i < 90; i++) {
    stars.push({
      x: Math.random(), y: Math.random(),
      r: 0.6 + Math.random() * 1.6,
      a: 0.15 + Math.random() * 0.45,
      vx: (Math.random() - 0.5) * 0.00018,
      vy: (Math.random() - 0.5) * 0.00018,
      c: colors[i % colors.length]
    });
  }

  // 外から飛び込む1点。減速して星々の中に落ち着き、また投げ込まれる
  var t0 = performance.now();
  var DUR = 2600;   // 1周の長さ。終わりではなく、繰り返しの単位
  var raf = 0, alive = true;

  function easeOut(p) { return 1 - Math.pow(1 - p, 3); }

  function frame(now) {
    if (!alive) { return; }
    x.clearRect(0, 0, W, H);

    for (var i = 0; i < stars.length; i++) {
      var s = stars[i];
      s.x += s.vx; s.y += s.vy;
      if (s.x < 0) { s.x += 1; } if (s.x > 1) { s.x -= 1; }
      if (s.y < 0) { s.y += 1; } if (s.y > 1) { s.y -= 1; }
      x.globalAlpha = s.a;
      x.fillStyle = s.c;
      x.beginPath(); x.arc(s.x * W, s.y * H, s.r, 0, 7); x.fill();
    }

    var p = ((now - t0) % DUR) / DUR;
    var e = easeOut(p);
    var cx = W * 0.5, cy = H * 0.5;
    var px = W * 1.15 - (W * 1.15 - cx) * e;
    var py = H * 0.12 + (cy - H * 0.12) * e;

    // 尾。速いうちは長く、落ち着くほど短い
    var tail = (1 - e) * 120;
    var g = x.createLinearGradient(px + tail, py - tail * 0.35, px, py);
    g.addColorStop(0, 'rgba(255,255,255,0)');
    g.addColorStop(1, accent);
    x.globalAlpha = 0.55 * (1 - e) + 0.2;
    x.strokeStyle = g; x.lineWidth = 1.4;
    x.beginPath(); x.moveTo(px + tail, py - tail * 0.35); x.lineTo(px, py); x.stroke();

    x.globalAlpha = 1;
    x.fillStyle = accent;
    x.beginPath(); x.arc(px, py, 2.6 + (1 - e) * 1.4, 0, 7); x.fill();

    // 落ち着いたところで、ほのかに広がる輪
    if (e > 0.85) {
      var q = (e - 0.85) / 0.15;
      x.globalAlpha = 0.35 * (1 - q);
      x.strokeStyle = accent; x.lineWidth = 1;
      x.beginPath(); x.arc(cx, cy, 6 + q * 42, 0, 7); x.stroke();
    }

    raf = requestAnimationFrame(frame);
  }
  raf = requestAnimationFrame(frame);

  return function stop() { alive = false; cancelAnimationFrame(raf); };
}
```

- [ ] **Step 2: `try.php` の送信時に全画面へ切り替える**

いまの JS は「選んだ瞬間に送る」だけ(`f.addEventListener('change', ...)`)。**通信をやり直さない**(設計書 §2-b の制約)。フォームはそのまま送り、絵は**上に被せる**。

intake のマークアップに足す(フォームの外、`<body>` 直下でよい):

```php
  <div class="ex-warp" id="warp" hidden>
    <div class="ex-warp-t">味わいの宇宙に照らしています…</div>
  </div>
  <script src="/assets/js/warp.js?v=<?= (int)(@filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/warp.js') ?: time()) ?>"></script>
```

既存の `waiting()` を差し替える:

```js
    function waiting() {
      if (status) { status.hidden = false; }
      var w = document.getElementById('warp');
      if (w && typeof startWarp === 'function') {
        w.hidden = false;
        // 色は group_map() が :root に流し込んだものを読む。CSS に直値を書かない
        var cs = getComputedStyle(document.documentElement);
        var names = <?= json_encode(array_keys(group_map())) ?>;
        var colors = [];
        for (var i = 0; i < names.length; i++) {
          var c = cs.getPropertyValue('--g-' + names[i]).trim();
          if (c) { colors.push(c); }
        }
        var accent = cs.getPropertyValue('--h-' + names[0]).trim() || '#cfe6f2';
        startWarp({ mount: 'warp', colors: colors, accent: accent });
      }
    }
```

**`<noscript>` の送信ボタンはそのまま残す。** JS が動かない端末では絵は出ないが、送信は通る。

- [ ] **Step 3: CSS を足す**

```css
/* ②解析中(設計書 2026-09-19 §2-b)。露光案の地と同じ純黒に覆う */
.ex-warp { position: fixed; inset: 0; z-index: 90; background: #000; }
.ex-warp canvas { display: block; width: 100%; height: 100%; }
.ex-warp-t { position: absolute; left: 0; right: 0; bottom: 14vh; text-align: center;
             font-size: 12px; letter-spacing: .12em; color: var(--dim); z-index: 1; }
@media (prefers-reduced-motion: reduce) {
  /* 動きを減らす設定の端末では、星も1点も動かさない。文字だけ出す */
  .ex-warp canvas { display: none; }
}
```

- [ ] **Step 4: 配布物に入れる**

`deploy/make_archive.sh` の `git archive` の列挙に `assets` が入っているかを確認する。**入っていればJSは自動で含まれる。** 入っていなければ足す。確認は実際にビルドして中身を見る:

```bash
bash deploy/make_archive.sh /tmp/warp-check.tar.gz
tar tzf /tmp/warp-check.tar.gz | grep warp.js
```
Expected: `html/assets/js/warp.js` が1行出る。**出なければ `make_archive.sh` を直す**(過去に、配布物に入れ忘れて本番で404になった前例がある)

- [ ] **Step 5: 画面で確かめる**

`php -S 127.0.0.1:8099 -t .` で起動し、ブラウザではなく curl で **JS が読めること**だけ確かめる(動きの確認は利用者の実機で行う):

```bash
curl -s -o /dev/null -w "%{http_code}\n" "http://127.0.0.1:8099/assets/js/warp.js"
curl -s "http://127.0.0.1:8099/try.php" | grep -c "warp.js"
```
Expected: `200` と `1`。**確かめ終わったら `php -S` を止めること**

- [ ] **Step 6: コミット**

```bash
git add assets/js/warp.js try.php assets/css/exposure.css deploy/make_archive.sh
git commit -m "②解析中に、味覚の宇宙へ1点が投げ込まれる絵を出す

飾りではなく、③の評価が「ユニバースに照らして言語化する」ことの絵である。
galaxy.js は持ち込まない(待ち時間を埋めるものが待ち時間を作っては意味がない)。
通信はやり直さず、送信中のフォームの上に被せるだけ。

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 7: ④保管 — 感想を残し、LINE で紐づける

**Files:**
- Create: `db/migrations/010_note.sql`
- Modify: `common/reco/repo.php`(`note_save()` / `note_for_upload()` / `visitor_note_count()`)
- Modify: `try.php`(感想を書く欄と「残す」)
- Rewrite: `line/login.php` / `line/callback.php`
- Modify: `assets/css/exposure.css`
- Create: `tests/unit/note_test.php`

**Interfaces:**
- Consumes: `csrf_issue()` / `csrf_check()`、`visitor_flash_set()` / `visitor_flash_take()`、`upload_owned_by()`
- Produces:
  - `note_save(string $visitorId, int $uploadId, string $body): int`
  - `note_for_upload(int $uploadId): ?array`
  - `visitor_note_count(string $visitorId): int`

**背景(実装者へ):** 設計書 §4。

- 評価の画面から、**感想を書く欄**を出す(任意)
- 書いた感想は**匿名のまま保存される**(いまの訪問者IDの仕組み)
- 「**残す**」を押したときに初めて **LINE ログイン**を案内する
- **飛ばして⑤へ行ける**

**④を⑤より前に置く理由:** 感想がいちばん新鮮なのは、評価を見た直後だから。推薦を見たあとだと「このビール自体の感想」が薄まる。ただし**④は任意**で、⑤がこの体験のいちばんの報酬なので、**その前にログインの壁を立てない**。

**LINE について実地で確かめてあること(2026-09-19):** dev の Basic認証を挟んでも戻れる / 利用者IDは端末をまたいで同じ / 匿名の記録は引き継がれる(写真2枚とイベント6件が合流した)/ 費用は無料。

**いまの `line/` は spike である。** 「繋がるか」を確かめるために書いたもので、`try.php` からの導線も、感想を書く画面もない。**ここで書き直す。**

**spike が残した既知の欠陥(必ず直すこと):** `line/callback.php` の合流処理は `upload` と `event` だけを付け替えて匿名側の `visitor` 行を DELETE するので、**その行が持っていた `age_confirmed_at` / `flash` / `csrf_token` が引き継がれない**。いまは LINE 連携の直後に年齢確認へ戻される。

- [ ] **Step 1: マイグレーションを書いてローカルに当てる**

`db/migrations/010_note.sql`:

```sql
-- ④保管(設計書 2026-09-19 §4)。写真1枚につき感想1件。
-- 匿名のまま保存し、LINE 連携は visitor 側で紐づく(この表は line_user_id を持たない)。
CREATE TABLE IF NOT EXISTS note (
  note_id    BIGINT   NOT NULL AUTO_INCREMENT,
  visitor_id CHAR(32) NOT NULL,
  upload_id  BIGINT   NOT NULL,
  body       TEXT     NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (note_id),
  UNIQUE KEY uq_note_upload (upload_id),
  KEY idx_note_visitor (visitor_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

```bash
mysql --socket=/run/mariadb/mariadb.sock beer < db/migrations/010_note.sql
mysql --socket=/run/mariadb/mariadb.sock beer -e "SHOW COLUMNS FROM note"
```
Expected: 6行

- [ ] **Step 2: 失敗するテストを書く**

`tests/unit/note_test.php`:

```php
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/common/nebula/helpers.php';
require_once dirname(__DIR__, 2) . '/common/reco/repo.php';

$vid = str_repeat('e', 32);
$other = str_repeat('f', 32);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen)
               VALUES (:v, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")
    ->execute([':v' => $vid]);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen)
               VALUES (:v, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")
    ->execute([':v' => $other]);

// 後片付けを先にやる(前回が途中で落ちていても二重にならない)
db()->prepare("DELETE FROM note WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);
db()->prepare("DELETE FROM upload WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);

$uid = upload_record($vid, ['is_beer' => true, 'matched_product_id' => null,
    'brand_text' => 'T', 'brewery_text' => 'T', 'style_guess' => null,
    'color' => null, 'clarity' => null, 'confidence' => 0.9,
    'abv_guess' => null, 'ibu_guess' => null, 'fruity_guess' => null,
    'model' => 'test'], str_repeat('a', 64), null);

eq(note_for_upload($uid), null, '書く前は感想が無い');
eq(visitor_note_count($vid), 0, '書く前は0件');

$id = note_save($vid, $uid, '柑橘の香りが強かった');
ok($id > 0, '感想を保存できる');
eq(note_for_upload($uid)['body'], '柑橘の香りが強かった', '保存した本文が読める');
eq(visitor_note_count($vid), 1, '1件になる');

// 同じ写真に2回書いたら**上書き**(写真1枚につき感想1件)
note_save($vid, $uid, '思ったより苦くなかった');
eq(note_for_upload($uid)['body'], '思ったより苦くなかった', '書き直すと上書きされる');
eq(visitor_note_count($vid), 1, '書き直しても件数は増えない');

// 空文字は保存しない(空の行を作らない)
eq(note_save($vid, $uid, '   '), 0, '空白だけなら保存しない');
eq(note_for_upload($uid)['body'], '思ったより苦くなかった', '空白で上書きされない');

// 他人の写真には書けない
eq(note_save($other, $uid, '横から書く'), 0, '他人の写真には書けない');
eq(note_for_upload($uid)['body'], '思ったより苦くなかった', '他人の書き込みで変わらない');

// 後片付け
db()->prepare("DELETE FROM note WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);
db()->prepare("DELETE FROM upload WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);
```

- [ ] **Step 3: 落ちることを確かめる**

Run: `php tests/unit/run.php note`
Expected: `Call to undefined function note_for_upload()`

- [ ] **Step 4: `repo.php` に実装する**

```php
/**
 * 感想を残す(設計書 2026-09-19 §4)。写真1枚につき1件。書き直しは上書き。
 *
 * **他人の写真には書けない。** 持ち主の検査をここで行う(呼び出し元に任せない)。
 * 空白だけの投稿は保存しない(空の行を作らない)。
 *
 * @return int 保存した note_id。保存しなかったときは 0
 */
function note_save(string $visitorId, int $uploadId, string $body): int
{
    $body = trim($body);
    if ($body === '' || $uploadId <= 0) { return 0; }
    if (upload_owned_by($uploadId, $visitorId) === null) { return 0; }

    // 日時は DB の NOW() を使う。アプリとDBでタイムゾーンが食い違うと一覧の並びが狂う
    db()->prepare(
        "INSERT INTO note (visitor_id, upload_id, body, created_at, updated_at)
         VALUES (:v, :u, :b, NOW(), NOW())
         ON DUPLICATE KEY UPDATE body = VALUES(body), updated_at = NOW()")
        ->execute([':v' => $visitorId, ':u' => $uploadId, ':b' => $body]);

    $st = db()->prepare("SELECT note_id FROM note WHERE upload_id = :u");
    $st->execute([':u' => $uploadId]);
    return (int)$st->fetchColumn();
}

/** 写真1枚に付いている感想。無ければ null */
function note_for_upload(int $uploadId): ?array
{
    $st = db()->prepare("SELECT * FROM note WHERE upload_id = :u");
    $st->execute([':u' => $uploadId]);
    $r = $st->fetch();
    return $r ?: null;
}

/** その訪問者が残した感想の件数 */
function visitor_note_count(string $visitorId): int
{
    $st = db()->prepare("SELECT COUNT(*) FROM note WHERE visitor_id = :v");
    $st->execute([':v' => $visitorId]);
    return (int)$st->fetchColumn();
}
```

- [ ] **Step 5: 通ることを確かめる**

Run: `php tests/unit/run.php note` → すべて ok。続けて `php tests/unit/run.php` → 0 fail

- [ ] **Step 6: `try.php` に感想の欄を出す**

**評価の下、⑤拡張のボタンより前**に置く(設計書 §2 の順)。

POST処理に分岐を足す(確認の分岐の**あと**、写真の分岐の**前**):

```php
// ④保管(設計書 2026-09-19 §4)。感想は任意。飛ばして⑤へ行ける
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note'])) {
    if (!csrf_check($visitorId, $_POST['csrf'] ?? null)) {
        visitor_flash_set($visitorId, 'expired');
        header('Location: /try.php'); exit;
    }
    $uid = (int)($_POST['upload_id'] ?? 0);
    $saved = note_save($visitorId, $uid, (string)$_POST['note']);
    // **保存できたときだけ「残しました」と言う。** 空文字や他人の写真なら 0 が返る
    visitor_flash_set($visitorId, $saved > 0 ? 'note_saved' : 'note_empty');
    header('Location: /try.php?u=' . $uid);
    exit;
}
```

表示部(`$view === 'result'` の中、評価の下):

```php
    <form class="ex-note" method="post" action="/try.php">
      <input type="hidden" name="upload_id" value="<?= (int)$uploadId ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf_issue($visitorId)) ?>">
      <label class="ex-lbl" for="note">飲んだ感想(任意)</label>
      <textarea id="note" name="note" rows="3" maxlength="1000"
        placeholder="香り、飲んだ場所、また飲みたいか"><?= e($existingNote['body'] ?? '') ?></textarea>
      <div class="ex-acts">
        <button class="ex-btn" type="submit">残す</button>
      </div>
    </form>
```

`$existingNote` は、結果を組み立てるところで `note_for_upload($uploadId)` を呼んで入れる(`$uploadId > 0` のときだけ)。**初期値 `$existingNote = null;` を冒頭の変数初期化に置くこと。**

お礼の分岐(`$flashKnown`)に2つ足す:

```php
      <?php elseif ($flash === 'note_saved'): ?>
        <p><b>残しました。</b></p>
        <p class="ex-sub">この端末に紐づけて保存しています。別の端末でも見るには LINE と繋いでください。</p>
      <?php elseif ($flash === 'note_empty'): ?>
        <p>感想が空だったので、何も残していません。</p>
```

`$flashKnown` の配列そのものにも `'note_saved'` と `'note_empty'` を足すこと。**足し忘れると `in_array` が false になり、枠ごと描画されない。**

- [ ] **Step 7: LINE への誘いを、感想の下に置く**

**設計書 §4 の制約: ログインの誘いは「使っている画面の中」に置く。** 別の場所で URL を開かせると、その端末の匿名の記録が孤児になる(実際、PC から先にログインしたため PC 側には何も無いまま紐づいた)。

```php
    <?php if (empty($lineLinked)): ?>
      <p class="ex-sub">
        <a href="/line/login.php?back=<?= e(urlencode('/try.php?u=' . $uploadId)) ?>">LINE と繋ぐ</a>
        と、別の端末からでもこの記録を見られます。
      </p>
    <?php endif; ?>
```

`$lineLinked` は `visitor` の行の `line_user_id` が埋まっているか。`repo.php` に小さな関数を足す:

```php
/** LINE と紐づいているか */
function visitor_line_linked(string $visitorId): bool
{
    $st = db()->prepare("SELECT line_user_id FROM visitor WHERE visitor_id = :v");
    $st->execute([':v' => $visitorId]);
    $v = $st->fetchColumn();
    return is_string($v) && $v !== '';
}
```

- [ ] **Step 8: `line/login.php` を書き直す**

spike からの変更点は2つだけ。**戻り先を受け取る**ことと、**spike の但し書きを消す**こと。

```php
<?php
declare(strict_types=1);
/*
 * LINE ログインの入口(設計書 2026-09-19 §4)。
 *
 * 受け取るのは利用者IDだけ(scope=profile)。表示名もアイコンも使わない。
 * **誘いは「使っている画面の中」からしか出さない。** 別の場所で URL を開かせると、
 * その端末の匿名の記録が孤児になる(2026-09-19 に実際に起きた)。
 */
require_once __DIR__ . '/../common/reco/visitor.php';
require_once __DIR__ . '/../common/reco/age.php';

$cfgPath = __DIR__ . '/../line_config.local.php';
if (!is_file($cfgPath)) { http_response_code(503); exit('LINE の設定がまだありません。'); }
$cfg = require $cfgPath;

$visitorId = visitor_current();

// 戻り先。**外部サイトへ飛ばされないよう、許可した形だけを受け取る**
// (年齢確認で正規表現が破られた前例があるため、ここは素直に前方一致で絞る)
$back = (string)($_GET['back'] ?? '/try.php');
if (!preg_match('#^/(try\.php|history\.php)(\?[A-Za-z0-9_=&%.\-]*)?$#', $back)) {
    $back = '/try.php';
}
setcookie('line_back', $back, [
    'expires' => time() + 600, 'path' => '/',
    'secure' => true, 'httponly' => true, 'samesite' => 'Lax',
]);

$state = bin2hex(random_bytes(16));
setcookie('line_state', $state, [
    'expires' => time() + 600, 'path' => '/',
    'secure' => true, 'httponly' => true, 'samesite' => 'Lax',
]);

$redirect = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . '/line/callback.php';
$url = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id'     => $cfg['channel_id'],
    'redirect_uri'  => $redirect,
    'state'         => $state,
    'scope'         => 'profile',
]);
header('Location: ' . $url, true, 302);
```

- [ ] **Step 9: `line/callback.php` の合流を直す**

**既存の処理のうち、合流の部分だけを直す。** トークン交換とプロフィール取得は動いているので触らない。

```php
if ($existing && $existing !== $visitorId) {
    // いまの匿名の記録を、既存の行へ付け替える
    foreach (['upload', 'event', 'note'] as $t) {
        db()->prepare("UPDATE `$t` SET visitor_id = :old WHERE visitor_id = :new")
            ->execute([':old' => $existing, ':new' => $visitorId]);
    }
    // **消す前に、この端末で持っていた状態を引き継ぐ。**
    // 引き継がないと、LINE と繋いだ直後に年齢確認へ戻される(spike の既知の欠陥)
    db()->prepare(
        "UPDATE visitor AS dst,
                (SELECT age_confirmed_at FROM visitor WHERE visitor_id = :src) AS s
         SET dst.age_confirmed_at = COALESCE(dst.age_confirmed_at, s.age_confirmed_at)
         WHERE dst.visitor_id = :dst")
        ->execute([':src' => $visitorId, ':dst' => $existing]);
    db()->prepare("DELETE FROM visitor WHERE visitor_id = :v")->execute([':v' => $visitorId]);
    setcookie(VISITOR_COOKIE, (string)$existing, [
        'expires' => time() + VISITOR_TTL, 'path' => '/',
        'secure' => true, 'httponly' => true, 'samesite' => 'Lax',
    ]);
    $visitorId = (string)$existing;
    $merged = true;
}
```

**`flash` と `csrf_token` は引き継がない。** どちらもその場限りの値で、繋いだ直後に発行し直されるため。

最後の画面も書き直す。**spike の「利用者IDの先頭」を出すのをやめ**、戻り先へ返す:

```php
$back = (string)($_COOKIE['line_back'] ?? '/try.php');
if (!preg_match('#^/(try\.php|history\.php)(\?[A-Za-z0-9_=&%.\-]*)?$#', $back)) {
    $back = '/try.php';
}
setcookie('line_back', '', ['expires' => time() - 3600, 'path' => '/']);
visitor_flash_set($visitorId, $merged ? 'line_merged' : 'line_linked');
header('Location: ' . $back, true, 302);
exit;
```

`try.php` の `$flashKnown` と分岐に2つ足す:

```php
      <?php elseif ($flash === 'line_linked'): ?>
        <p><b>LINE と繋がりました。</b>この端末の記録に紐づけました。</p>
      <?php elseif ($flash === 'line_merged'): ?>
        <p><b>LINE と繋がりました。</b>以前の記録に合流しました。</p>
```

**`repo.php` の require を `line/callback.php` に入れること**(`visitor_flash_set()` を使うため。既に入っている)。

- [ ] **Step 10: CSS を足す**

```css
/* ④保管。並ぶところなので強調色は使わない */
.ex-note { margin: 18px 0; }
.ex-note textarea { width: 100%; box-sizing: border-box; margin-top: 8px; padding: 10px 12px;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.14);
  border-radius: 8px; color: inherit; font: inherit; font-size: 13px; line-height: 1.7;
  resize: vertical; }
.ex-note textarea::placeholder { color: var(--faint); }
```

- [ ] **Step 11: テストを通してコミット**

Run: `php tests/unit/run.php` → 0 fail

```bash
git add db/migrations/010_note.sql common/reco/repo.php tests/unit/note_test.php
git commit -m "④保管: 感想を残す仕組みを追加する

写真1枚につき1件。書き直しは上書き。他人の写真には書けない。
空白だけの投稿は保存しない(空の行を作らない)。

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
git add line/login.php line/callback.php
git commit -m "LINE 連携を spike から本番の作りにする

戻り先を受け取り、使っている画面へ返す。合流のときに年齢確認の記録を引き継ぐ
(引き継がないと、繋いだ直後に年齢確認へ戻される)。note も付け替える。

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
git add try.php assets/css/exposure.css
git commit -m "④保管を画面に出し、LINE の誘いを使っている画面の中に置く

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 8: 履歴一覧と、トップからの小さな導線

**Files:**
- Create: `history.php`
- Modify: `common/reco/repo.php`(`visitor_history()` / `visitor_upload_count()`)
- Modify: `try.php`(intake の下に小さな導線)
- Modify: `deploy/make_archive.sh`(`history.php` を配布物に入れる)
- Modify: `robots.txt`(`/history.php` を Disallow)
- Modify: `assets/css/exposure.css`
- Create: `tests/unit/history_test.php`

**Interfaces:**
- Consumes: Task 5 の `reco_seed_from_upload()`、Task 1 の `eval_build()`、Task 7 の `note_for_upload()` / `visitor_line_linked()`
- Produces:
  - `visitor_upload_count(string $visitorId): int` — **ビールと判定できたものだけ**を数える
  - `visitor_history(string $visitorId, int $limit = 50): array` — 新しい順。各行は `upload` の列 + `note_body`

**背景(実装者へ):** 設計書 §2-c。トップから**これまでに撮ったビール**へ行けるようにする。

**ただし目立たせない。** この画面の主役は「ビールを読み込むこと」であり、履歴はそのあとに生まれる副産物である。

- トップの**下のほう**に、小さく `これまでの ○本 →` と出す
- **写真が0枚の人には何も出さない**(空の入口を作らない)
- 押すと**一覧**へ。撮った日・銘柄名・評価の要点が並ぶ
- **匿名でも見える。LINE ログインは要らない**(Cookie があれば自分の記録は見える)
- **この一覧が、LINE を案内する自然な場所になる。** 一覧の下に「別の端末でも見るには」として置く

ここで言う「トップ」は **`try.php` の intake 画面**である(写真を読み込む画面の主役はビールの読み込みであり、`index.php` の銀河のトップではない)。

- [ ] **Step 1: 失敗するテストを書く**

`tests/unit/history_test.php`:

```php
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/common/nebula/helpers.php';
require_once dirname(__DIR__, 2) . '/common/reco/repo.php';

$vid = str_repeat('c', 32);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen)
               VALUES (:v, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")
    ->execute([':v' => $vid]);
db()->prepare("DELETE FROM note WHERE visitor_id = :v")->execute([':v' => $vid]);
db()->prepare("DELETE FROM upload WHERE visitor_id = :v")->execute([':v' => $vid]);

eq(visitor_upload_count($vid), 0, '最初は0本');
eq(visitor_history($vid), [], '最初は空');

$base = ['matched_product_id' => null, 'brewery_text' => 'B', 'style_guess' => null,
         'color' => null, 'clarity' => null, 'confidence' => 0.9,
         'abv_guess' => null, 'ibu_guess' => null, 'fruity_guess' => null, 'model' => 'test'];

$u1 = upload_record($vid, array_merge($base, ['is_beer' => true, 'brand_text' => '1本目']),
                    str_repeat('1', 64), null);
$u2 = upload_record($vid, array_merge($base, ['is_beer' => true, 'brand_text' => '2本目']),
                    str_repeat('2', 64), null);
// ビール以外は数にも一覧にも入れない(撮り直しを案内しただけのもの)
upload_record($vid, array_merge($base, ['is_beer' => false, 'brand_text' => null]),
              str_repeat('3', 64), null);

eq(visitor_upload_count($vid), 2, 'ビールと判定できたものだけを数える');
$h = visitor_history($vid);
eq(count($h), 2, '一覧もビールだけ');
eq($h[0]['brand_text'], '2本目', '新しい順に並ぶ');

note_save($vid, $u1, '柑橘が強い');
$h = visitor_history($vid);
eq($h[1]['note_body'], '柑橘が強い', '感想が一緒に取れる');
eq($h[0]['note_body'], null, '感想が無ければ null');

// 他人の記録は混ざらない
$other = str_repeat('d', 32);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen)
               VALUES (:v, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")
    ->execute([':v' => $other]);
eq(visitor_history($other), [], '他人の記録は見えない');

db()->prepare("DELETE FROM note WHERE visitor_id = :v")->execute([':v' => $vid]);
db()->prepare("DELETE FROM upload WHERE visitor_id = :v")->execute([':v' => $vid]);
```

- [ ] **Step 2: 落ちることを確かめる**

Run: `php tests/unit/run.php history`
Expected: `Call to undefined function visitor_upload_count()`

- [ ] **Step 3: `repo.php` に実装する**

```php
/**
 * これまでに撮った本数(設計書 2026-09-19 §2-c)。
 * **ビールと判定できたものだけ**を数える。撮り直しを案内しただけのものは本数に入れない。
 */
function visitor_upload_count(string $visitorId): int
{
    $st = db()->prepare(
        "SELECT COUNT(*) FROM upload
         WHERE visitor_id = :v AND is_beer = 1 AND status = 'ok'");
    $st->execute([':v' => $visitorId]);
    return (int)$st->fetchColumn();
}

/** これまでに撮ったビールの一覧。新しい順。感想があれば一緒に返す */
function visitor_history(string $visitorId, int $limit = 50): array
{
    $st = db()->prepare(
        "SELECT u.*, n.body AS note_body
         FROM upload u
         LEFT JOIN note n ON n.upload_id = u.upload_id
         WHERE u.visitor_id = :v AND u.is_beer = 1 AND u.status = 'ok'
         ORDER BY u.created_at DESC, u.upload_id DESC
         LIMIT " . (int)$limit);
    $st->execute([':v' => $visitorId]);
    return $st->fetchAll();
}
```

**`LIMIT` に変数を直接埋めている点について:** PDO は `LIMIT` のプレースホルダを扱えない(エミュレーションを切ると文字列として引用され、構文エラーになる)。`(int)` を通しているので注入の経路は無い。**この形以外で書かないこと。**

- [ ] **Step 4: 通ることを確かめる**

Run: `php tests/unit/run.php history` → すべて ok

- [ ] **Step 5: `history.php` を書く**

`try.php` と同じ露光案の地(黒)・同じヘッダを使う。**年齢確認を先に通す**(酒類の記録を見せる画面なので、`try.php` と同じ門番を置く)。

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/common/nebula/helpers.php';
require_once __DIR__ . '/common/reco/visitor.php';
require_once __DIR__ . '/common/reco/repo.php';
require_once __DIR__ . '/common/reco/evaluate.php';
require_once __DIR__ . '/common/reco/cascade.php';
require_once __DIR__ . '/common/reco/handle.php';
require_once __DIR__ . '/common/reco/age.php';

$visitorId = visitor_current();

// 酒類の記録を見せる画面なので、try.php と同じ門番を置く(設計書 §12)
if (!age_confirmed($visitorId)) {
    header('Location: /agecheck.php?next=/history.php');
    exit;
}

$rows   = visitor_history($visitorId);
$linked = visitor_line_linked($visitorId);
$pool   = reco_pool();
?><!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>これまでのビール | Darth Beer.com</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap">
<?php
$exposureCss = '/assets/css/exposure.css';
$exposureVer = @filemtime($_SERVER['DOCUMENT_ROOT'] . $exposureCss) ?: time();
?>
<link rel="stylesheet" href="<?= e($exposureCss) ?>?v=<?= (int)$exposureVer ?>">
<style>:root{
<?php foreach (group_map() as $k => $v): ?>
  --g-<?= e($k) ?>:<?= e($v[0]) ?>; --h-<?= e($k) ?>:<?= e($v[1]) ?>;
<?php endforeach; ?>
}</style>
</head>
<body>

<div class="ex-bar"><div class="ex-mark">DARTH BEER</div></div>
<nav class="ex-tabs">
  <a href="/try.php">さがす</a>
  <a href="/beer/products.php">銘柄</a>
  <a href="/brewery/makers.php">蔵</a>
  <a href="/style/styles.php">スタイル</a>
</nav>

<div class="ex-wrap">
  <div class="ex-lbl">これまでのビール</div>

  <?php if (count($rows) === 0): ?>
    <p class="ex-sub">まだ1本もありません。</p>
    <div class="ex-acts"><a class="ex-btn" href="/try.php">写真から探す</a></div>
  <?php else: ?>
    <?php foreach ($rows as $u): ?>
      <?php
        $seed = reco_seed_from_upload($u);
        $ev   = eval_build(['abv' => $seed['Alcohol'], 'ibu' => $seed['IBU_all'],
                            'fruity' => $seed['Fruity']], $pool, empty($u['product_id']));
        $g    = style_group($seed['FamilyName'] ?? '', $seed['StyleName'] ?? '');
        list($col) = group_meta($g);
        $name = $u['product_id'] ? ($seed['ProductName'] ?? '') : ($u['brand_text'] ?: '銘柄がわかりませんでした');
      ?>
      <a class="ex-hist" href="/try.php?u=<?= (int)$u['upload_id'] ?>">
        <span class="ex-dot" style="background:<?= e($col) ?>"></span>
        <span class="ex-hist-b">
          <b><?= e($name) ?></b>
          <span class="ex-hist-m">
            <?= e(date('Y/n/j', strtotime((string)$u['created_at']))) ?>
            <?php if (!empty($seed['StyleName'])): ?> · <?= e($seed['StyleName']) ?><?php endif; ?>
          </span>
          <?php if (!empty($ev['lines'])): ?>
            <span class="ex-hist-e">
              <?php $bits = [];
                    foreach ($ev['lines'] as $l) { $bits[] = $l['label'] . 'は' . $l['word']; }
                    echo e(implode(' / ', $bits)); ?>
            </span>
          <?php endif; ?>
          <?php if (!empty($u['note_body'])): ?>
            <span class="ex-hist-n"><?= e(mb_strimwidth((string)$u['note_body'], 0, 60, '…')) ?></span>
          <?php endif; ?>
        </span>
      </a>
    <?php endforeach; ?>

    <?php if (!$linked): ?>
      <div class="ex-hist-line">
        <div class="ex-lbl">別の端末でも見るには</div>
        <p class="ex-sub">
          いまの記録はこの端末に紐づいています。
          <a href="/line/login.php?back=<?= e(urlencode('/history.php')) ?>">LINE と繋ぐ</a>と、
          別の端末からでも同じ記録が見られます。
        </p>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

</body>
</html>
```

- [ ] **Step 6: `try.php` の intake の下に小さな導線を出す**

`<div class="ex-meta"><?= count(reco_pool()) ?> BEERS</div>` の**下、フォームの外**に置く。

```php
  <?php $mine = visitor_upload_count($visitorId); ?>
  <?php if ($mine > 0): ?>
    <?php /* 主役はビールの読み込み。履歴はその副産物なので、小さく下のほうに置く
             (設計書 §2-c)。0本の人には出さない ― 空の入口を作らない */ ?>
    <p class="ex-hist-link"><a href="/history.php">これまでの <?= (int)$mine ?>本 →</a></p>
  <?php endif; ?>
```

- [ ] **Step 7: CSS を足す**

```css
/* 2-c 履歴。主役ではないので、色も大きさも抑える */
.ex-hist-link { text-align: center; margin: 26px 0 40px; font-size: 12px; }
.ex-hist-link a { color: var(--faint); text-decoration: none; }
.ex-hist-link a:hover { color: var(--dim); }
.ex-hist { display: flex; gap: 10px; align-items: flex-start; padding: 12px 0;
           border-bottom: 1px solid rgba(255,255,255,.06); text-decoration: none; color: inherit; }
.ex-hist-b { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.ex-hist-m { font-size: 11px; color: var(--faint); }
.ex-hist-e { font-size: 11.5px; color: var(--dim); }
.ex-hist-n { font-size: 11.5px; color: var(--faint); font-style: italic; }
.ex-hist-line { margin-top: 28px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.08); }
```

- [ ] **Step 8: 配布物と robots.txt**

`deploy/make_archive.sh` の `git archive` の列挙に `history.php` を足す。

`robots.txt` に足す(`/agecheck.php` などと同じ書き方で):

```
Disallow: /history.php
```

確かめる:

```bash
bash deploy/make_archive.sh /tmp/hist-check.tar.gz
tar tzf /tmp/hist-check.tar.gz | grep -E "history\.php|warp\.js"
grep history robots.txt
```
Expected: `html/history.php` と `html/assets/js/warp.js` が出る。robots.txt に1行出る

- [ ] **Step 9: 画面で確かめる**

```bash
php -S 127.0.0.1:8099 -t . > /tmp/srv.log 2>&1 &
sleep 1
curl -s -o /dev/null -w "%{http_code}\n" "http://127.0.0.1:8099/history.php"
curl -s "http://127.0.0.1:8099/try.php" | grep -c "history.php"
kill %1
```
Expected: 302(年齢未確認)または 200。`try.php` の導線は、その訪問者の写真が0枚なら 0 でよい(**0本の人には出さない**のが仕様)

- [ ] **Step 10: テストを通してコミット**

Run: `php tests/unit/run.php` → 0 fail

```bash
git add common/reco/repo.php tests/unit/history_test.php
git commit -m "これまでに撮ったビールを取り出す関数を追加する

ビールと判定できたものだけを数える。撮り直しを案内しただけのものは本数に入れない。

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
git add history.php try.php assets/css/exposure.css deploy/make_archive.sh robots.txt
git commit -m "履歴一覧と、トップからの小さな導線を追加する

主役はビールの読み込みなので、履歴は小さく下に置き、0本の人には出さない。
匿名でも見える。一覧の下が、LINE を案内する2つ目の自然な場所になる。

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 9: 確度が中くらいのとき、候補を並べて選ばせる

**Files:**
- Create: `db/migrations/011_upload_candidates.sql`
- Modify: `common/reco/identify.php`(候補を返させる)
- Modify: `common/reco/repo.php`(`upload_record()` に候補を保存、`upload_pick()` を追加)
- Modify: `common/reco/handle.php`(候補を `$h['candidates']` で返す)
- Modify: `try.php`(候補の並びと、選んだときの分岐)
- Modify: `tests/unit/identify_test.php`
- Create: `tests/unit/pick_test.php`

**Interfaces:**
- Consumes: Task 2 の `identify_parse()`、Task 5 の `reco_view_upload()`
- Produces:
  - `identify_parse()` の戻り値に `candidates`(`string[]`。`pr\d{4}` の形だけ、最大3件)
  - `upload_pick(int $uploadId, string $visitorId, string $productId): bool`
  - `upload` に `candidates VARCHAR(32) NULL` と `picked_by_user TINYINT(1) NOT NULL DEFAULT 0`

**背景(実装者へ):** 設計書 §3 の表に、**確度が中くらい(0.4〜0.8)で候補が複数** のときは「**候補を並べて選ばせる。選んでから評価へ**」とある。いまは `identify_branch()` が `'choose'` を返すだけで、**画面には何も無い**。1本に決め打って評価を出すと、外したときに黙って誤った評価と推薦を出すことになる。

**費用は増えない。** 同じ1回の判定で返る項目が増えるだけ。

- [ ] **Step 1: マイグレーションを書いてローカルに当てる**

`db/migrations/011_upload_candidates.sql`:

```sql
-- 確度が中くらいのときに並べる候補(設計書 2026-09-19 §3)。
-- カンマ区切りの ProductID を最大3件。選び直しは picked_by_user で見分ける
-- (モデルが同定したのか、利用者が選んだのかを取り違えないため)。
ALTER TABLE upload ADD COLUMN candidates     VARCHAR(32) NULL;
ALTER TABLE upload ADD COLUMN picked_by_user TINYINT(1)  NOT NULL DEFAULT 0;
```

```bash
mysql --socket=/run/mariadb/mariadb.sock beer < db/migrations/011_upload_candidates.sql
mysql --socket=/run/mariadb/mariadb.sock beer -e "SHOW COLUMNS FROM upload LIKE 'candidates'"
```
Expected: 1行

- [ ] **Step 2: 失敗するテストを書く**

`tests/unit/identify_test.php` に追記:

```php
// --- 候補(設計書 2026-09-19 §3)---
$mkc = function ($cand): array {
    return ['content' => [['type' => 'text', 'text' => json_encode([
        'is_beer' => true, 'brand_text' => 'X', 'brewery_text' => 'Y',
        'matched_product_id' => 'pr0001', 'style_guess' => null,
        'color' => 5, 'clarity' => 2, 'confidence' => 0.6,
        'abv_guess' => null, 'ibu_guess' => null, 'fruity_guess' => null,
        'candidates' => $cand,
    ])]]];
};

eq(identify_parse($mkc(['pr0001','pr0002']))['candidates'], ['pr0001','pr0002'], '候補を受け取る');
eq(identify_parse($mkc([]))['candidates'], [], '候補が無ければ空の配列');
eq(identify_parse($mkc(null))['candidates'], [], 'null でも空の配列(undefined index を出さない)');
eq(identify_parse($mkc(['pr0001','ダメ','pr0002']))['candidates'], ['pr0001','pr0002'],
   '形が違うものは捨てる');
eq(identify_parse($mkc(['pr0001','pr0002','pr0003','pr0004']))['candidates'],
   ['pr0001','pr0002','pr0003'], '最大3件まで');
eq(identify_parse($mkc(['pr0001','pr0001']))['candidates'], ['pr0001'], '同じものは1つにまとめる');
```

`tests/unit/pick_test.php`:

```php
<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/common/nebula/helpers.php';
require_once dirname(__DIR__, 2) . '/common/reco/repo.php';

$vid   = str_repeat('7', 32);
$other = str_repeat('8', 32);
foreach ([$vid, $other] as $v) {
    db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen)
                   VALUES (:v, NOW(), NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()")
        ->execute([':v' => $v]);
}
db()->prepare("DELETE FROM upload WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);

// 実在する銘柄を2つ借りる(このテストは products を書き換えない)
$ids = db()->query("SELECT ProductID FROM products ORDER BY ProductID LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
ok(count($ids) === 2, '検査に使う銘柄が2つある');

$uid = upload_record($vid, ['is_beer' => true, 'matched_product_id' => $ids[0],
    'brand_text' => 'T', 'brewery_text' => 'T', 'style_guess' => null,
    'color' => null, 'clarity' => null, 'confidence' => 0.6,
    'abv_guess' => null, 'ibu_guess' => null, 'fruity_guess' => null,
    'candidates' => $ids, 'model' => 'test'], str_repeat('9', 64), null);

$u = upload_owned_by($uid, $vid);
eq($u['candidates'], implode(',', $ids), '候補が保存される');
eq((int)$u['picked_by_user'], 0, '最初は利用者が選んでいない');

// 候補の中からなら選べる
eq(upload_pick($uid, $vid, $ids[1]), true, '候補の中からは選べる');
$u = upload_owned_by($uid, $vid);
eq($u['product_id'], $ids[1], '選んだ銘柄が入る');
eq((int)$u['picked_by_user'], 1, '利用者が選んだ印が付く');

// 候補に無いものは選べない(URLを書き換えて任意の銘柄にすり替えられないこと)
$outsider = db()->query("SELECT ProductID FROM products WHERE ProductID NOT IN ('" .
            implode("','", $ids) . "') ORDER BY ProductID LIMIT 1")->fetchColumn();
eq(upload_pick($uid, $vid, (string)$outsider), false, '候補に無いものは選べない');
eq(upload_owned_by($uid, $vid)['product_id'], $ids[1], 'はねられたあとも値は変わらない');

// 他人の写真は選び直せない
eq(upload_pick($uid, $other, $ids[0]), false, '他人の写真は選び直せない');
eq(upload_owned_by($uid, $vid)['product_id'], $ids[1], '他人の操作で値は変わらない');

db()->prepare("DELETE FROM upload WHERE visitor_id IN (:a, :b)")->execute([':a' => $vid, ':b' => $other]);
```

- [ ] **Step 3: 落ちることを確かめる**

Run: `php tests/unit/run.php pick`
Expected: `Call to undefined function upload_pick()`

- [ ] **Step 4: `identify.php` に候補を足す**

`$empty` と戻り値に `'candidates' => []` を足し、次の読み取りを入れる:

```php
    // 候補は ProductID の配列。形が違うもの・重複は捨て、**最大3件**まで。
    // 一覧に実在するかどうかは identify_call() 側で確かめる(matched_product_id と同じ扱い)
    $cands = [];
    foreach ((array)($j['candidates'] ?? []) as $c) {
        if (!is_string($c) || !preg_match('/^pr\d{4}$/', $c)) { continue; }
        if (in_array($c, $cands, true)) { continue; }
        $cands[] = $c;
        if (count($cands) >= 3) { break; }
    }
```

`identify_call()` の中、`matched_product_id` の実在検査のすぐ下に、候補の実在検査を足す:

```php
            // 一覧に無いIDは候補からも外す。実在しない銘柄を選ばせない
            if (!empty($r['candidates'])) {
                $known = [];
                foreach ($catalog as $c) { $known[$c['ProductID'] ?? ''] = true; }
                $r['candidates'] = array_values(array_filter($r['candidates'],
                    function ($pid) use ($known) { return isset($known[$pid]); }));
            }
```

`identify_prompt()` の説明に追記する:

```php
         . "確信が持てないときは、当てはまりそうな候補を candidates に"
         . "**一覧のID(pr で始まる6文字)の配列**で最大3件入れてください。"
         . "1本に絞れるときは candidates を空の配列にしてください。";
```

構造化出力のスキーマにも足す(`required` にも):

```php
            'candidates' => ['type' => 'array', 'items' => ['type' => 'string']],
```

`identify_call()` の `catch` の中の「壊れたときの形」にも `'candidates' => []` を足す。

- [ ] **Step 5: `repo.php` に保存と選び直しを足す**

`upload_record()` の INSERT に `candidates` を足す:

```php
        ':candidates' => !empty($r['candidates']) ? implode(',', $r['candidates']) : null,
```

選び直し:

```php
/**
 * 候補の中から1本を選ぶ(設計書 2026-09-19 §3)。
 *
 * **候補に無い銘柄は選べない。** URL を書き換えて任意の銘柄にすり替えられると、
 * 記録が汚れ、蔵へ渡す数字の根拠が崩れる。
 * **他人の写真は選び直せない。**
 */
function upload_pick(int $uploadId, string $visitorId, string $productId): bool
{
    $u = upload_owned_by($uploadId, $visitorId);
    if ($u === null) { return false; }
    $cands = array_filter(explode(',', (string)($u['candidates'] ?? '')));
    if (!in_array($productId, $cands, true)) { return false; }

    db()->prepare(
        "UPDATE upload SET product_id = :p, picked_by_user = 1 WHERE upload_id = :u")
        ->execute([':p' => $productId, ':u' => $uploadId]);
    return true;
}
```

- [ ] **Step 6: `handle.php` で候補を渡す**

`reco_handle_upload()` の戻り値の `compact()` に `'candidates'` を足す。中身は

```php
    // 確度が中くらいで候補が複数あるときだけ並べる(設計書 §3)。
    // 1本に決め打つと、外したときに黙って誤った評価と推薦を出すことになる
    $candidates = [];
    if ($view === 'result' && $result['is_beer'] === true
        && identify_branch($result) === 'choose' && count($result['candidates'] ?? []) >= 2) {
        foreach ($result['candidates'] as $pid) {
            $b = beer_by_id($pid);
            if ($b) { $candidates[] = $b; }
        }
    }
```

`reco_view_upload()`(Task 5)でも、`upload` の行から同じものを組み立てて返す:

```php
    $candidates = [];
    if (empty($u['picked_by_user']) && !empty($u['candidates'])
        && $u['confidence'] !== null && (float)$u['confidence'] >= 0.4 && (float)$u['confidence'] < 0.8) {
        foreach (array_filter(explode(',', (string)$u['candidates'])) as $pid) {
            $b = beer_by_id($pid);
            if ($b) { $candidates[] = $b; }
        }
    }
```

返す配列に `'candidates' => $candidates` を足す(2件未満なら並べない、の判断は画面側で `count() >= 2` を見る)。

- [ ] **Step 7: `try.php` に候補の並びと選んだときの分岐を足す**

GET 分岐(Task 5 で足したもの)の中に、選び直しを入れる:

```php
        if (isset($_GET['pick']) && preg_match('/^pr\d{4}$/', (string)$_GET['pick'])) {
            if (upload_pick($uploadId, $visitorId, (string)$_GET['pick'])) {
                event_record($visitorId, 'pick', (string)$_GET['pick'], $uploadId);
                // 選んだ銘柄で評価と推薦を作り直す
                header('Location: /try.php?u=' . $uploadId);
                exit;
            }
        }
```

**`upload_pick()` が false のときは何も言わずに続けない。** 候補に無いものが来たということは、URL が書き換えられている。そのまま画面を出す(選び直しは起きていないので、表示は正しい)。

表示は、**評価より前**に置く(設計書 §3「選んでから評価へ」):

```php
    <?php if (count($candidates) >= 2): ?>
      <div class="ex-cands">
        <div class="ex-lbl">どれでしょうか</div>
        <p class="ex-sub">読み取りに確信が持てませんでした。選ぶと、その銘柄で評価し直します。</p>
        <?php foreach ($candidates as $c): ?>
          <a class="ex-rec" href="/try.php?u=<?= (int)$uploadId ?>&amp;pick=<?= e($c['ProductID']) ?>">
            <?php list($ccol) = group_meta(style_group($c['FamilyName'] ?? '', $c['StyleName'] ?? '')); ?>
            <span class="ex-dot" style="background:<?= e($ccol) ?>"></span>
            <span class="ex-nm">
              <b><?= e($c['ProductName']) ?></b>
              <span><?= e($c['MakerName'] ?: '') ?><?= !empty($c['StyleName']) ? ' · ' . e($c['StyleName']) : '' ?></span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
```

**初期値 `$candidates = [];` を冒頭の変数初期化に置くこと。**

Task 5 で足した GET 分岐には `$candidates = $v['candidates'] ?? [];` が既に入っている。写真を上げた直後(POST)の経路では `$h['candidates']` から受け取ること。

- [ ] **Step 8: テストを通してコミット**

Run: `php tests/unit/run.php` → 0 fail

```bash
git add db/migrations/011_upload_candidates.sql common/reco/identify.php \
        common/reco/repo.php common/reco/handle.php \
        tests/unit/identify_test.php tests/unit/pick_test.php
git commit -m "確度が中くらいのときに並べる候補を、判定で受け取って保存する

候補に無い銘柄は選べない(URLを書き換えて任意の銘柄にすり替えられないように)。
モデルが同定したのか利用者が選んだのかは picked_by_user で見分ける。

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
git add try.php assets/css/exposure.css
git commit -m "候補を並べて選ばせ、選んだ銘柄で評価し直す

1本に決め打つと、外したときに黙って誤った評価と推薦を出すことになる。

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## 実装後に管理者(オーケストレーター)がやること

実装者の担当ではない。**タスク8まで終わってから**、管理者が行う。

1. **dev にマイグレーション009・010・011を当てる**(`deploy/apply_sql.php` 経由。`--allow-ddl`)。
   **当てずにコードを配ると `/try.php` が丸ごと 500 になる**(007・008 で一度踏みかけている)
2. dev へデプロイし、**利用者に実機で確認してもらう**
3. 確認したい4点: ②解析中の絵が出るか / ③評価の言い回しが自然か /
   ④感想を残して LINE と繋げるか / ⑤「もっと〜」で結果が変わるか
4. **LINE チャネルは作成直後「開発中」で、作成者以外はログインできない。**
   本番に出すときに「公開」へ切り替える(設計書 §9)
5. 本番公開の前に閉じるものは、旧設計 §13-b と新設計 §9 に集約してある

## この計画で作らないもの(設計書 §8)

- **自由記述による絞り込み** — ボタンで足りないと分かってから
- **タイプ(MBTI的なもの)** — 後で真面目に定義する。入れるとしても3軸から規則で決まるものにする
- **色・透明度を主軸にした評価** — 埋まっているのが 52% / 43% しかなく、語れない銘柄が半分ある
- **Messaging API による通知** — 有料。LINEログインだけで足りる
