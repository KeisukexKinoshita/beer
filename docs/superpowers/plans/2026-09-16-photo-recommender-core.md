# 写真から似た1本を薦める機能 — 実装計画(1/3: 中核)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** dev 環境で「ビールの写真を上げると、似た3本が出る」が通しで動く状態にする。

**Architecture:** 既存の素の PHP + MariaDB にそのまま足す。推薦の中核は **DBに触らない純粋関数**にして固定データだけで単体テストを回す。画像判定は Anthropic の API を呼ぶが、テストでは**記録した応答を再生**して1円も使わない。既存の3テーブル(products / maker / style)と既存ページには一切触らない。

**Tech Stack:** PHP 8.3 / MariaDB (RDS) / Anthropic PHP SDK (`anthropic-ai/sdk`) / Claude Sonnet 5

**Spec:** `docs/superpowers/specs/2026-09-16-photo-recommender-design.md`

## Global Constraints

- **DBは `scripts/remote_sql.sh`(beer-data-pipeline スキル内)以外から触らない。** このコンテナの `/etc/hosts` は RDS のホスト名を `127.0.0.1` に向けており、直接接続すると**テストDBを本番と誤認して書き込む**。AWS CLI が無いときは `BEER_EC2_IP=54.168.54.119` を付ける
- **マイグレーションの適用は `deploy/apply_sql.php`。** DDL は `--allow-ddl` が要る。適用済みファイルは sha256 で照合されるので**1バイトも書き換えない**。直すときは新しい番号を作る
- **秘密情報をリポジトリとチャットに置かない。** APIキーは `api_config.local.php`(gitignore + サーバ内で注入)。`db_config.local.php` と同じ方式
- **モデルは `claude-sonnet-5`。** 変えるときは設計書 §6 の判断からやり直す
- **既存の特性テストハーネスに枠組みを増やさない。** 単体テストは素の PHP で書く(PHPUnit を入れない)
- **`tests/runner/exec_page.php` は `$_FILES` をセットしない。** アップロードのページ全体テストはできないので、**門番と判定は関数の単体テストで担保する**
- **推薦を返す関数と PR枠を返す関数を混ぜない。** 掲載料が順位を動かさないことをコードの形で担保する(PR枠自体は計画3)
- コミットは UI 修正とロジック修正を分ける

---

## ファイル構成

| パス | 責務 |
|---|---|
| `db/migrations/004_recommender.sql` | 5テーブルの追加 |
| `db/migrations/004_recommender_rollback.sql` | その取り消し |
| `common/reco/cascade.php` | **推薦の中核。DBに触らない純粋関数** |
| `common/reco/repo.php` | DBの読み書き(候補の取得・upload/recommendation/event の記録) |
| `common/reco/identify.php` | 画像判定。API呼び出しは差し替え可能にする |
| `common/reco/gate.php` | 門番と上限。**APIを呼ぶ前**の判断だけを持つ |
| `common/reco/visitor.php` | 匿名IDの発行と解決 |
| `common/nebula/helpers.php` | `group_map()` をスタイル色の二段構えに(修正) |
| `assets/css/exposure.css` | 露光案のトークンとコンポーネント |
| `try.php` | 画面(初期 → 判定 → 推薦)。トップの差し替えは計画2 |
| `tests/unit/run.php` | 単体テストの走らせ役(素のPHP) |
| `tests/unit/cascade_test.php` / `gate_test.php` / `identify_test.php` | 単体テスト |
| `tests/fixtures/identify/*.json` | 記録した API 応答(再生用) |
| `tests/eval/run_eval.php` | 15枚の精度検証 |
| `api_config.local.php.example` | APIキーの置き方の見本 |

---

### Task 1: 5テーブルを足す

**Files:**
- Create: `db/migrations/004_recommender.sql`
- Create: `db/migrations/004_recommender_rollback.sql`

**Interfaces:**
- Consumes: なし
- Produces: テーブル `visitor` / `upload` / `unknown_beer` / `recommendation` / `event`

- [ ] **Step 1: マイグレーションを書く**

`db/migrations/004_recommender.sql`:

```sql
-- 写真から似た1本を薦める機能のための5テーブル(設計書 §4)。
-- 既存の products / maker / style には一切触らない。
-- 対象RDSはMySQL 8.4。文字コードは utf8mb4。

CREATE TABLE IF NOT EXISTS visitor (
  visitor_id    CHAR(32)     NOT NULL,            -- ランダム。Cookie に入れる値そのもの
  first_seen    DATETIME     NOT NULL,
  last_seen     DATETIME     NOT NULL,
  line_user_id  VARCHAR(64)  NULL,                -- LINE連携はこの1列を埋めるだけ
  age_confirmed TINYINT(1)   NOT NULL DEFAULT 0,  -- 年齢確認の記憶(計画2で使う)
  PRIMARY KEY (visitor_id),
  UNIQUE KEY uq_visitor_line (line_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS upload (
  upload_id     BIGINT       NOT NULL AUTO_INCREMENT,
  visitor_id    CHAR(32)     NOT NULL,
  created_at    DATETIME     NOT NULL,
  image_path    VARCHAR(255) NULL,                -- ビール以外と判定したものは保存しないので NULL
  image_hash    CHAR(64)     NOT NULL,            -- sha256。同じ写真の再送を API を呼ばずに返す
  is_beer       TINYINT(1)   NOT NULL,
  product_id    CHAR(6)      NULL,                -- 同定できた銘柄。できなければ NULL
  brand_text    VARCHAR(191) NULL,                -- ラベルから読めた銘柄名
  brewery_text  VARCHAR(191) NULL,
  style_guess   CHAR(6)      NULL,                -- style.StyleID
  color         TINYINT      NULL,                -- 1-10
  clarity       TINYINT      NULL,                -- 1-4
  confidence    DECIMAL(4,3) NULL,                -- 0.000-1.000
  model         VARCHAR(40)  NULL,                -- 使ったモデル。後で精度を振り返るため
  PRIMARY KEY (upload_id),
  KEY idx_upload_visitor (visitor_id, created_at),
  KEY idx_upload_hash (image_hash),
  KEY idx_upload_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS unknown_beer (
  unknown_id    BIGINT       NOT NULL AUTO_INCREMENT,
  brand_text    VARCHAR(191) NOT NULL,
  brewery_text  VARCHAR(191) NULL,
  hits          INT          NOT NULL DEFAULT 1,
  last_seen     DATETIME     NOT NULL,
  PRIMARY KEY (unknown_id),
  UNIQUE KEY uq_unknown_name (brand_text, brewery_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recommendation (
  reco_id       BIGINT       NOT NULL AUTO_INCREMENT,
  upload_id     BIGINT       NOT NULL,
  position      TINYINT      NOT NULL,            -- 1,2,3
  product_id    CHAR(6)      NOT NULL,
  stage         TINYINT      NOT NULL,            -- カスケードの何段目で拾ったか
  pr_product_id CHAR(6)      NULL,                -- PR枠に出した銘柄(計画3で使う)
  created_at    DATETIME     NOT NULL,
  PRIMARY KEY (reco_id),
  UNIQUE KEY uq_reco_pos (upload_id, position),
  KEY idx_reco_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event (
  event_id      BIGINT       NOT NULL AUTO_INCREMENT,
  visitor_id    CHAR(32)     NOT NULL,
  created_at    DATETIME     NOT NULL,
  kind          VARCHAR(24)  NOT NULL,            -- reco_view / reco_click / pr_click / confirm_yes / confirm_no
  target        VARCHAR(64)  NULL,                -- ProductID など
  upload_id     BIGINT       NULL,
  PRIMARY KEY (event_id),
  KEY idx_event_visitor (visitor_id, created_at),
  KEY idx_event_kind (kind, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

`db/migrations/004_recommender_rollback.sql`:

```sql
-- 004 の取り消し。作った順の逆で落とす。
DROP TABLE IF EXISTS event;
DROP TABLE IF EXISTS recommendation;
DROP TABLE IF EXISTS unknown_beer;
DROP TABLE IF EXISTS upload;
DROP TABLE IF EXISTS visitor;
```

- [ ] **Step 2: dev に dry-run を当てて、DDL が拒否されないことを確かめる**

Run:
```bash
cd /workspace/tool/beer
BEER_EC2_IP=54.168.54.119 /workspace/.claude/skills/beer-data-pipeline/scripts/remote_sql.sh dev \
  --apply db/migrations/004_recommender.sql --allow-ddl --dry-run
```
Expected: 「DDL と --dry-run は併用できない」旨で**中止する**。MySQL は DDL で暗黙コミットするため runner が組み合わせ自体を禁じている。これが出れば runner は正常。

- [ ] **Step 3: dev に本適用する**

Run:
```bash
BEER_EC2_IP=54.168.54.119 /workspace/.claude/skills/beer-data-pipeline/scripts/remote_sql.sh dev \
  --apply db/migrations/004_recommender.sql --allow-ddl
```
Expected: 5テーブルが作られ、`schema_migrations` に `004_recommender.sql` が記録される。

- [ ] **Step 4: 作られたことを確かめる**

Run:
```bash
BEER_EC2_IP=54.168.54.119 /workspace/.claude/skills/beer-data-pipeline/scripts/remote_sql.sh dev \
  --query "SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES
           WHERE TABLE_SCHEMA='beer_dev'
             AND TABLE_NAME IN ('visitor','upload','unknown_beer','recommendation','event')
           ORDER BY TABLE_NAME"
```
Expected: 5行返り、`TABLE_COLLATION` がすべて `utf8mb4_` で始まる。

- [ ] **Step 5: コミット**

```bash
git add db/migrations/004_recommender.sql db/migrations/004_recommender_rollback.sql
git commit -m "リコメンド機能の5テーブルを追加(migration 004)"
```

---

### Task 2: スタイル色を二段構えにする

既存の9箇所が `list($gcol, $glabel) = group_meta($g)` を使っている。**その形を変えずに**、返る色を減彩に切り替え、強調色は別の関数で取れるようにする。

**Files:**
- Modify: `common/nebula/helpers.php:95-118`
- Test: `tests/unit/group_test.php`

**Interfaces:**
- Consumes: なし
- Produces:
  - `group_map(): array` — `['ipa' => ['#86adbd', '#5fd0ff', 'IPA系'], ...]`(減彩, 強調, ラベル)
  - `group_meta(string $g): array` — `[減彩色, ラベル]`。**既存の呼び出し9箇所はそのまま動く**
  - `group_hi(string $g): string` — 強調色
  - `group_map_js(): array` — `['ipa' => ['c'=>'#86adbd', 'h'=>'#5fd0ff', 'l'=>'IPA系'], ...]`

- [ ] **Step 1: 失敗するテストを書く**

`tests/unit/group_test.php`:

```php
<?php
require_once __DIR__ . '/../../common/nebula/helpers.php';

$m = group_map();
eq(count($m['ipa']), 3, 'group_map は 減彩・強調・ラベル の3値を持つ');
eq($m['ipa'][0], '#86adbd', 'ipa の通常色は減彩');
eq($m['ipa'][1], '#5fd0ff', 'ipa の強調色は現行値');
eq($m['ipa'][2], 'IPA系',   'ipa のラベル');

list($col, $label) = group_meta('stout');
eq($col,   '#a396b8', 'group_meta は減彩色を返す(既存9箇所の呼び出しを壊さない)');
eq($label, 'Stout / 黒', 'group_meta のラベルは従来どおり');

eq(group_hi('stout'), '#b98cff', 'group_hi は強調色を返す');
eq(group_hi('存在しないキー'), group_hi('other'), '未知のキーは other に落ちる');

$js = group_map_js();
eq($js['wheat']['c'], '#aeb890', 'JS へ渡す c は減彩色');
eq($js['wheat']['h'], '#c8e86a', 'JS へ渡す h は強調色');
eq($js['wheat']['l'], '小麦 / Weizen', 'JS へ渡す l はラベル');
eq(count($js), 6, 'グループは6つ');
```

- [ ] **Step 2: テストの走らせ役を作る**

`tests/unit/run.php`:

```php
<?php
declare(strict_types=1);
/*
 * 素のPHPの単体テスト走らせ役。
 * PHPUnit を入れないのは、既存の特性テストハーネス(tests/runner/)に
 * 枠組みを増やさないという方針による(設計書 §10)。
 *
 * 使い方:
 *   php tests/unit/run.php            すべて
 *   php tests/unit/run.php cascade    ファイル名に cascade を含むものだけ
 */

$T = ['pass' => 0, 'fail' => 0];

function eq($actual, $expected, string $name): void {
    global $T;
    if ($actual === $expected) { $T['pass']++; echo "  ok   $name\n"; return; }
    $T['fail']++;
    echo "  FAIL $name\n";
    echo "       期待: " . json_encode($expected, JSON_UNESCAPED_UNICODE) . "\n";
    echo "       実際: " . json_encode($actual,   JSON_UNESCAPED_UNICODE) . "\n";
}

function ok(bool $cond, string $name): void { eq($cond, true, $name); }

$filter = $argv[1] ?? '';
foreach (glob(__DIR__ . '/*_test.php') as $f) {
    if ($filter !== '' && strpos(basename($f), $filter) === false) continue;
    echo basename($f) . "\n";
    require $f;
    echo "\n";
}
echo "{$T['pass']} pass / {$T['fail']} fail\n";
exit($T['fail'] === 0 ? 0 : 1);
```

- [ ] **Step 3: 落ちることを確かめる**

Run: `php tests/unit/run.php group`
Expected: FAIL。`group_map は 減彩・強調・ラベル の3値を持つ` で `期待: 3 / 実際: 2`。

- [ ] **Step 4: helpers.php を直す**

`common/nebula/helpers.php` の `group_map()` から `group_map_js()` までを次に置き換える:

```php
/**
 * スタイルグループの定義。**色とラベルの単一の出所はここ。**
 *
 * 値は [通常色, 強調色, ラベル] の3つ組。
 * - 通常色(減彩): 一覧・推薦リスト・凡例など、色が並ぶところ全部
 * - 強調色(現行値): 次の3か所だけ。同定された1本のハロー /
 *   一覧で絞り込み中・選択中 / スタイル詳細ページのそのページ自身のスタイル
 *
 * 黒地に現行のネオンを並べると駄菓子的に見えるため二段にした(設計書 §11)。
 * 足すときはここだけを直す。表示順は凡例・チップの並び順になる。
 */
function group_map() {
    static $m = [
        'ipa'   => ['#86adbd', '#5fd0ff', 'IPA系'],
        'stout' => ['#a396b8', '#b98cff', 'Stout / 黒'],
        'sour'  => ['#bf96a8', '#ff6fb0', 'Sour'],
        'pale'  => ['#c0b088', '#ffd06b', 'Pale / Amber'],
        'wheat' => ['#aeb890', '#c8e86a', '小麦 / Weizen'],
        'other' => ['#88b8ab', '#5cf0c2', 'Lager / その他'],
    ];
    return $m;
}

/** グループ→ [通常色, ラベル]。**既存の呼び出し9箇所はこの形に依存している** */
function group_meta($g) {
    $m = group_map();
    $v = $m[$g] ?? $m['other'];
    return [$v[0], $v[2]];
}

/** グループ→ 強調色 */
function group_hi($g) {
    $m = group_map();
    $v = $m[$g] ?? $m['other'];
    return $v[1];
}

/** JS へ渡す形 {key: {c: 通常色, h: 強調色, l: ラベル}} */
function group_map_js() {
    $out = [];
    foreach (group_map() as $k => $v) { $out[$k] = ['c' => $v[0], 'h' => $v[1], 'l' => $v[2]]; }
    return $out;
}
```

- [ ] **Step 5: テストが通ることを確かめる**

Run: `php tests/unit/run.php group`
Expected: `13 pass / 0 fail`

- [ ] **Step 6: 既存ページが壊れていないことを確かめる**

Run: `bash tests/runner/run_all.sh`
Expected: 既存と同じ結果(27 PASS / 1 既知の仕様不一致)。色の値は変わるがゴールデンは HTML 構造を見ているため、差分が出た場合は**色の16進数だけ**であることを目視で確かめてからゴールデンを更新する。

- [ ] **Step 7: コミット**

```bash
git add common/nebula/helpers.php tests/unit/run.php tests/unit/group_test.php
git commit -m "スタイル色を通常(減彩)と強調の二段構えにする"
```

---

### Task 3: 推薦カスケード(純粋関数)

**Files:**
- Create: `common/reco/cascade.php`
- Test: `tests/unit/cascade_test.php`

**Interfaces:**
- Consumes: `style_group(string $family, string $name): string`(helpers.php)、`is_unmeasured($v): bool`(helpers.php)
- Produces:
  - `reco_pick(array $seed, array $pool, int $limit = 3): array` — 各要素 `['ProductID' => string, 'stage' => int, 'row' => array]`
  - `reco_sort_key(array $row, array $seed): array` — `[別蔵フラグ(0が別蔵), 度数差, ProductID]`

- [ ] **Step 1: 失敗するテストを書く**

`tests/unit/cascade_test.php`:

```php
<?php
require_once __DIR__ . '/../../common/reco/cascade.php';

/*
 * テスト用のヘルパー。run.php は全テストファイルを同じスコープに require するので、
 * 名前は reco_ で始めて衝突を避ける。
 */
/** テスト用の1行。all_beers() の行から、カスケードが使う列だけを抜いた形 */
function reco_t_row(string $pid, string $style, string $family, string $name, string $maker, $abv): array {
    return ['ProductID' => $pid, 'StyleID' => $style, 'FamilyName' => $family,
            'StyleName' => $name, 'MakerID' => $maker, 'Alcohol' => $abv];
}

/** 結果から ProductID の並びだけを取り出す */
function reco_t_ids(array $picked): array {
    return array_map(function ($p) { return $p['ProductID']; }, $picked);
}
/** 結果から stage の並びだけを取り出す */
function reco_t_stages(array $picked): array {
    return array_map(function ($p) { return $p['stage']; }, $picked);
}

// --- 1. 同じ StyleID に十分な候補がある ---
$seed = reco_t_row('pr0001', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0);
$pool = [
    reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk02', 6.2),
    reco_t_row('pr0003', 'st0010', 'IPA', 'American IPA', 'mk03', 5.8),
    reco_t_row('pr0004', 'st0010', 'IPA', 'American IPA', 'mk04', 7.5),
    reco_t_row('pr0005', 'st0020', 'IPA', 'Hazy IPA',     'mk05', 6.0),
];
$got = reco_pick($seed, $pool);
eq(reco_t_stages($got), [1, 1, 1], '同StyleIDで3件そろえば全部が段階1');
eq(reco_t_ids($got), ['pr0003', 'pr0002', 'pr0004'], '度数が近い順(0.2→0.2→1.5)。同差は新しいIDが先');

// --- 2. 同StyleIDに1件しかない(実データで23銘柄が該当) ---
$seed = reco_t_row('pr0001', 'st0030', 'Wheat Beers', 'Wheat Wine', 'mk01', 9.0);
$pool = [
    reco_t_row('pr0002', 'st0030', 'Wheat Beers', 'Wheat Wine', 'mk02', 9.2),
    reco_t_row('pr0003', 'st0031', 'Wheat Beers', 'Weizenbock', 'mk03', 8.5),
    reco_t_row('pr0004', 'st0040', 'Weizen',      'Hefeweizen', 'mk04', 5.0),
];
$got = reco_pick($seed, $pool);
eq(reco_t_stages($got), [1, 2, 3], '段階1で尽きたら2、そこでも尽きたら3へ広がる');
eq(reco_t_ids($got), ['pr0002', 'pr0003', 'pr0004'], '段階順に拾う');

// --- 3. 同じ系統に誰もいない。表示グループでも足りず度数で埋める ---
$seed = reco_t_row('pr0001', 'st0050', 'Strong Ales', 'Barley Wine', 'mk01', 10.0);
$pool = [
    reco_t_row('pr0002', 'st0060', 'Lager', 'Pilsner', 'mk02', 5.0),
    reco_t_row('pr0003', 'st0061', 'Lager', 'Helles',  'mk03', 9.5),
];
$got = reco_pick($seed, $pool);
eq(count($got), 2, '候補が3件に満たなくても落ちない');
eq(reco_t_ids($got), ['pr0003', 'pr0002'], '最後の手段は度数が近い順');

// --- 4. 別の蔵が最優先 ---
$seed = reco_t_row('pr0001', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0);
$pool = [
    reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0),  // 同じ蔵・度数ぴったり
    reco_t_row('pr0003', 'st0010', 'IPA', 'American IPA', 'mk02', 8.0),  // 別の蔵・度数は遠い
];
$got = reco_pick($seed, $pool);
eq(reco_t_ids($got), ['pr0003', 'pr0002'], '度数がどれだけ近くても、別の蔵が先に来る');

// --- 5. 度数が未計測のものは後ろ ---
$seed = reco_t_row('pr0001', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0);
$pool = [
    reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk02', null),
    reco_t_row('pr0003', 'st0010', 'IPA', 'American IPA', 'mk03', 9.0),
];
$got = reco_pick($seed, $pool);
eq(reco_t_ids($got), ['pr0003', 'pr0002'], '度数NULLは度数差を比べられないので後ろ');

// --- 6. 基準の1本そのものは出さない ---
$seed = reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0);
$pool = [
    reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0),
    reco_t_row('pr0003', 'st0010', 'IPA', 'American IPA', 'mk02', 6.1),
];
$got = reco_pick($seed, $pool);
eq(reco_t_ids($got), ['pr0003'], '自分自身は候補から外れる');

// --- 7. DBに無い銘柄(StyleID も ProductID も無い)でも動く ---
$seed = ['ProductID' => null, 'StyleID' => null, 'FamilyName' => 'IPA',
         'StyleName' => 'Hazy IPA', 'MakerID' => null, 'Alcohol' => 6.5];
$pool = [
    reco_t_row('pr0002', 'st0010', 'IPA',   'American IPA', 'mk02', 6.4),
    reco_t_row('pr0003', 'st0060', 'Lager', 'Pilsner',      'mk03', 5.0),
];
$got = reco_pick($seed, $pool);
eq(reco_t_stages($got), [2, 4], 'StyleID が無いので段階1は空振りし、系統から始まる');
eq(reco_t_ids($got), ['pr0002', 'pr0003'], '系統で1件、残りは度数で埋める');

// --- 8. 同じ銘柄を2回出さない ---
$seed = reco_t_row('pr0001', 'st0010', 'IPA', 'American IPA', 'mk01', 6.0);
$pool = [reco_t_row('pr0002', 'st0010', 'IPA', 'American IPA', 'mk02', 6.0)];
$got = reco_pick($seed, $pool);
eq(reco_t_ids($got), ['pr0002'], '段階1で拾ったものが段階2以降で再登場しない');
```

- [ ] **Step 2: 落ちることを確かめる**

Run: `php tests/unit/run.php cascade`
Expected: Fatal error — `Failed opening required '.../common/reco/cascade.php'`

- [ ] **Step 3: カスケードを実装する**

`common/reco/cascade.php`:

```php
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
```

- [ ] **Step 4: テストが通ることを確かめる**

Run: `php tests/unit/run.php cascade`
Expected: `12 pass / 0 fail`

- [ ] **Step 5: 実データで4段すべてに到達することを確かめる**

`tests/unit/cascade_real_check.php` を作らず、その場で確かめる:

```bash
BEER_EC2_IP=54.168.54.119 /workspace/.claude/skills/beer-data-pipeline/scripts/remote_sql.sh prod \
  --query "SELECT p.StyleID, COUNT(*) n FROM products p GROUP BY p.StyleID HAVING n = 1" \
  | grep -c StyleID
```
Expected: 23。設計書 §5 の「同スタイルに仲間がいない23件」と一致すること。ずれていたらデータが動いているので、**設計書 §5 の数字を更新してから**先へ進む。

- [ ] **Step 6: コミット**

```bash
git add common/reco/cascade.php tests/unit/cascade_test.php
git commit -m "推薦の4段カスケードを純粋関数として実装"
```

---

### Task 4: 門番と上限

**APIを呼ぶ前**に弾く判断だけを持つ。DBも時計も外から渡すので、単体テストで境界を全部見られる。

**Files:**
- Create: `common/reco/gate.php`
- Test: `tests/unit/gate_test.php`

**Interfaces:**
- Consumes: なし
- Produces:
  - `gate_check(array $ctx): array` — `['ok' => bool, 'reason' => string]`。`reason` は `ok` / `disabled` / `bad_type` / `too_large` / `burst` / `visitor_daily` / `global_daily` / `global_monthly` / `cached`
  - `GATE_LIMITS` 定数配列 — `['visitor_daily' => 20, 'global_daily' => 200, 'global_monthly' => 4000, 'burst_seconds' => 20, 'max_bytes' => 10485760]`

- [ ] **Step 1: 失敗するテストを書く**

`tests/unit/gate_test.php`:

```php
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
```

- [ ] **Step 2: 落ちることを確かめる**

Run: `php tests/unit/run.php gate`
Expected: Fatal error — `Failed opening required '.../common/reco/gate.php'`

- [ ] **Step 3: 門番を実装する**

`common/reco/gate.php`:

```php
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
```

- [ ] **Step 4: テストが通ることを確かめる**

Run: `php tests/unit/run.php gate`
Expected: `20 pass / 0 fail`

- [ ] **Step 5: コミット**

```bash
git add common/reco/gate.php tests/unit/gate_test.php
git commit -m "APIを呼ぶ前の門番と上限を実装"
```

---

### Task 5: 画像判定(記録と再生)

API を呼ぶ部分を差し替え可能にして、テストでは**記録した応答を再生**する。1円も使わずに `is_beer=false`・確度中・確度低・API失敗の4系統を通せる。

**Files:**
- Create: `common/reco/identify.php`
- Create: `api_config.local.php.example`
- Create: `tests/fixtures/identify/high.json` / `low.json` / `not_beer.json` / `error.json`
- Test: `tests/unit/identify_test.php`
- Modify: `.gitignore`(`api_config.local.php` を足す)

**Interfaces:**
- Consumes: なし
- Produces:
  - `identify_parse(array $response): array` — API の生の応答 → `['is_beer','brand_text','brewery_text','matched_product_id','style_guess','color','clarity','confidence']`
  - `identify_branch(array $result): string` — `'confirm'`(0.8以上) / `'choose'`(0.4以上) / `'unknown'`(0.4未満) / `'not_beer'`
  - `identify_call(string $imagePath, array $catalog, ?callable $transport = null): array` — `$transport` を渡すと API の代わりにそれを呼ぶ
  - `identify_prompt(array $catalog): string` — キャッシュに載せる固定部分

- [ ] **Step 1: 失敗するテストを書く**

`tests/unit/identify_test.php`:

```php
<?php
require_once __DIR__ . '/../../common/reco/identify.php';

$fx = function (string $name): array {
    return json_decode(file_get_contents(__DIR__ . '/../fixtures/identify/' . $name . '.json'), true);
};

// --- 応答の読み取り ---
$r = identify_parse($fx('high'));
eq($r['is_beer'], true,                'ビールだと判定できている');
eq($r['matched_product_id'], 'pr0013', 'DBの銘柄に結びついている');
eq($r['confidence'], 0.92,             '確度が取れている');

$r = identify_parse($fx('not_beer'));
eq($r['is_beer'], false,               'ビール以外を見分けている');
eq($r['matched_product_id'], null,     'ビール以外は銘柄に結びつけない');

// --- 分岐(設計書 §6) ---
eq(identify_branch(['is_beer' => true,  'confidence' => 0.92]), 'confirm',  '0.8以上は1件を確認');
eq(identify_branch(['is_beer' => true,  'confidence' => 0.80]), 'confirm',  '境界の0.8は確認側');
eq(identify_branch(['is_beer' => true,  'confidence' => 0.79]), 'choose',   '0.8未満は候補から選ばせる');
eq(identify_branch(['is_beer' => true,  'confidence' => 0.40]), 'choose',   '境界の0.4は選ばせる側');
eq(identify_branch(['is_beer' => true,  'confidence' => 0.39]), 'unknown',  '0.4未満は未該当として扱う');
eq(identify_branch(['is_beer' => false, 'confidence' => 0.99]), 'not_beer', '確度が高くてもビール以外はビール以外');

// --- 差し替えた経路で1周する ---
$calls = 0;
$transport = function (string $path, string $prompt) use ($fx, &$calls): array {
    $calls++;
    return $fx('high');
};
$catalog = [['ProductID' => 'pr0013', 'ProductName' => 'HAZY JANE', 'MakerName' => 'BREWDOG']];
$r = identify_call('/dev/null', $catalog, $transport);
eq($calls, 1,                          'transport がちょうど1回呼ばれる');
eq($r['matched_product_id'], 'pr0013', '差し替えた経路でも結果が取れる');

// --- API が失敗したとき ---
$boom = function (string $path, string $prompt): array { throw new RuntimeException('500'); };
$r = identify_call('/dev/null', $catalog, $boom);
eq($r['error'], true,                  '例外を投げずにエラーとして返す');
eq($r['is_beer'], null,                'エラー時は判定を作らない');

// --- プロンプトの固定部分 ---
$p = identify_prompt($catalog);
ok(strpos($p, 'pr0013') !== false,     '銘柄一覧がプロンプトに入っている');
ok(strpos($p, 'HAZY JANE') !== false,  '銘柄名も入っている');
eq(identify_prompt($catalog), $p,      '同じ入力なら同じ文字列(キャッシュが効く条件)');
```

- [ ] **Step 2: 再生用の記録を4つ置く**

`tests/fixtures/identify/high.json`:

```json
{
  "id": "msg_test_high",
  "model": "claude-sonnet-5",
  "stop_reason": "end_turn",
  "content": [
    {
      "type": "text",
      "text": "{\"is_beer\":true,\"brand_text\":\"HAZY JANE\",\"brewery_text\":\"BREWDOG\",\"matched_product_id\":\"pr0013\",\"style_guess\":\"st0007\",\"color\":4,\"clarity\":3,\"confidence\":0.92}"
    }
  ]
}
```

`tests/fixtures/identify/low.json`:

```json
{
  "id": "msg_test_low",
  "model": "claude-sonnet-5",
  "stop_reason": "end_turn",
  "content": [
    {
      "type": "text",
      "text": "{\"is_beer\":true,\"brand_text\":\"青天の霹靂\",\"brewery_text\":\"OIRASE BEER\",\"matched_product_id\":null,\"style_guess\":\"st0045\",\"color\":3,\"clarity\":2,\"confidence\":0.35}"
    }
  ]
}
```

`tests/fixtures/identify/not_beer.json`:

```json
{
  "id": "msg_test_notbeer",
  "model": "claude-sonnet-5",
  "stop_reason": "end_turn",
  "content": [
    {
      "type": "text",
      "text": "{\"is_beer\":false,\"brand_text\":\"Red Bull\",\"brewery_text\":null,\"matched_product_id\":null,\"style_guess\":null,\"color\":null,\"clarity\":null,\"confidence\":0.97}"
    }
  ]
}
```

`tests/fixtures/identify/error.json`:

```json
{
  "type": "error",
  "error": { "type": "api_error", "message": "Internal server error" }
}
```

- [ ] **Step 3: 落ちることを確かめる**

Run: `php tests/unit/run.php identify`
Expected: Fatal error — `Failed opening required '.../common/reco/identify.php'`

- [ ] **Step 4: 判定を実装する**

`common/reco/identify.php`:

```php
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
```

- [ ] **Step 5: テストが通ることを確かめる**

Run: `php tests/unit/run.php identify`
Expected: `16 pass / 0 fail`

- [ ] **Step 6: APIキーの置き方の見本を作る**

`api_config.local.php.example`:

```php
<?php
/*
 * Anthropic の APIキー。db_config.local.php と同じ方式で、
 * **実値はリポジトリに置かない**。サーバ内で注入する。
 * コピーして api_config.local.php を作り、キーを入れる。
 */
return [
    'anthropic_api_key' => 'sk-ant-xxxxxxxxxxxxxxxxxxxx',
    'reco_enabled'      => true,   // false にすると機能全体が止まる(設計書 §9)
    'daily_cap'         => 200,    // dev では 30 にする
];
```

`.gitignore` に1行足す:

```
api_config.local.php
```

- [ ] **Step 7: コミット**

```bash
git add common/reco/identify.php tests/unit/identify_test.php tests/fixtures/identify/ \
        api_config.local.php.example .gitignore
git commit -m "画像判定を実装(APIは差し替え可能。テストは記録した応答を再生する)"
```

---

### Task 6: 本物のAPIにつなぐ

**Files:**
- Create: `composer.json`
- Modify: `common/reco/identify.php`(`identify_transport_anthropic` の中身)
- Modify: `deploy/make_archive.sh`(`vendor/` と `common/reco/` を配る)

**Interfaces:**
- Consumes: `identify_prompt()` / `identify_parse()`(Task 5)
- Produces: `identify_transport_anthropic(string $imagePath, string $prompt): array` — 本物の応答を返す
- Produces: `identify_shrink(string $src, string $dst): void` — 長辺 1568px に縮める

- [ ] **Step 1: SDK を入れる**

```bash
cd /workspace/tool/beer
composer require anthropic-ai/sdk
```
Expected: `composer.json` と `composer.lock`、`vendor/` ができる。

- [ ] **Step 2: 構造化出力のキー名を SDK の例で確かめる**

設計書 §6 は「構造化出力 `output_config.format` で項目を固定する」と決めている。
PHP SDK の**入れ子のキー名は camelCase とスネークが混在する**ので、推測で書かない。

Run:
```bash
php -r 'require "vendor/autoload.php"; $r = new ReflectionMethod(\Anthropic\Services\MessagesService::class, "create"); foreach ($r->getParameters() as $p) { echo $p->getName(), "\n"; }'
```
Expected: `outputConfig` に相当する引数名が分かる。見つからなければ
`vendor/anthropic-ai/sdk/examples/` を grep して、**実際に使われている綴りをそのまま写す**。

見つかったキー名を次の Step のコードの `outputConfig:` の位置に当てはめる。
**見つからなかった場合は構造化出力を使わず**、プロンプトで JSON を要求する形にして、
`identify_parse()` が壊れた応答を弾く作りに頼る(`identify_parse` はすでにそう書いてある)。
どちらにしたかを `docs/data-log/` に1行残す。

- [ ] **Step 3: transport を実装する**

`common/reco/identify.php` の `identify_transport_anthropic` を差し替える:

```php
/** 本物のAPI。SDK の呼び出し方は php/claude-api の作法に従う */
function identify_transport_anthropic(string $imagePath, string $prompt): array
{
    require_once __DIR__ . '/../../vendor/autoload.php';
    $cfg = require __DIR__ . '/../../api_config.local.php';

    $client = new \Anthropic\Client(apiKey: $cfg['anthropic_api_key']);

    $shrunk = sys_get_temp_dir() . '/reco_' . bin2hex(random_bytes(8)) . '.jpg';
    identify_shrink($imagePath, $shrunk);
    $b64 = base64_encode(file_get_contents($shrunk));
    @unlink($shrunk);

    // 返させる項目は構造化出力で固定する(設計書 §6)。
    // キー名は Step 2 で確かめた綴りに合わせること。
    $schema = ['type' => 'object', 'additionalProperties' => false,
        'required' => ['is_beer','brand_text','brewery_text','matched_product_id',
                       'style_guess','color','clarity','confidence'],
        'properties' => [
            'is_beer'            => ['type' => 'boolean'],
            'brand_text'         => ['type' => ['string','null']],
            'brewery_text'       => ['type' => ['string','null']],
            'matched_product_id' => ['type' => ['string','null']],
            'style_guess'        => ['type' => ['string','null']],
            'color'              => ['type' => ['integer','null'], 'minimum' => 1, 'maximum' => 10],
            'clarity'            => ['type' => ['integer','null'], 'minimum' => 1, 'maximum' => 4],
            'confidence'         => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
        ]];

    $message = $client->messages->create(
        model: IDENTIFY_MODEL,
        maxTokens: 1024,
        outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $schema]],
        system: [
            // 銘柄一覧は毎回同じなのでキャッシュに載せる。呼ぶたびに送り直さない
            ['type' => 'text', 'text' => $prompt, 'cacheControl' => ['type' => 'ephemeral']],
        ],
        messages: [[
            'role' => 'user',
            'content' => [
                ['type' => 'image', 'source' => [
                    'type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $b64]],
                ['type' => 'text', 'text' => 'この写真のビールを判定してください。'],
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

/** 長辺を 1568px に縮める。大きい写真をそのまま送ると入力トークンが無駄に増える */
function identify_shrink(string $src, string $dst): void
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
    imagejpeg($im, $dst, 82);
    imagedestroy($im);
}
```

- [ ] **Step 4: 実際に1枚だけ判定して、応答を記録に取り直す**

**ここで初めて課金が発生する。1枚 約0.8円。**

```bash
cd /workspace/tool/beer
cp api_config.local.php.example api_config.local.php
# エディタで api_config.local.php にキーを入れる(キーは画面にもチャットにも出さない)

php -r '
require "common/reco/identify.php";
$catalog = [["ProductID"=>"pr0013","ProductName"=>"HAZY JANE","MakerName"=>"BREWDOG"]];
$r = identify_transport_anthropic("data/sample/IMG_1016.jpeg", identify_prompt($catalog));
file_put_contents("tests/fixtures/identify/high.json", json_encode($r, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo json_encode(identify_parse($r), JSON_UNESCAPED_UNICODE), "\n";
'
```
Expected: `{"is_beer":true,...,"matched_product_id":"pr0013",...}`。**手で書いた記録が本物に置き換わる。**

- [ ] **Step 5: 記録を置き換えてもテストが通ることを確かめる**

Run: `php tests/unit/run.php identify`
Expected: `16 pass / 0 fail`。もし `confidence` の期待値 `0.92` で落ちたら、**テスト側を実測値に合わせる**(記録は本物が正)。

- [ ] **Step 6: 配布物に含める**

`deploy/make_archive.sh` の対象一覧に `common/reco` と `vendor` を足す。`api_config.local.php` は**配らない**(サーバ内で注入する)。

- [ ] **Step 7: コミット**

```bash
git add composer.json composer.lock common/reco/identify.php \
        tests/fixtures/identify/high.json deploy/make_archive.sh
git commit -m "画像判定を本物のAPIにつなぐ(Sonnet 5・銘柄一覧はキャッシュ)"
```

> `vendor/` を追跡するかは、`make_archive.sh` が `git archive` を使う以上**追跡が必要**。
> `.gitignore` に `vendor/` があれば外す。

---

### Task 7: DBの読み書き

**Files:**
- Create: `common/reco/repo.php`
- Create: `common/reco/visitor.php`

**Interfaces:**
- Consumes: `db()`(helpers.php)、`GATE_LIMITS`(gate.php)
- Produces:
  - `visitor_current(): string` — Cookie から匿名IDを取り、無ければ発行して `visitor` に入れる
  - `reco_catalog(): array` — 判定プロンプト用の `[['ProductID','ProductName','MakerName'], ...]`
  - `reco_pool(): array` — カスケード用の全銘柄。`all_beers()` をそのまま使う
  - `gate_context(string $visitorId, string $mime, int $bytes, string $hash): array` — `gate_check()` に渡す配列を DB から作る
  - `upload_record(string $visitorId, array $result, string $hash, ?string $imagePath): int` — `upload` に1行入れて `upload_id` を返す
  - `upload_by_hash(string $hash): ?array` — 同じ画像の前回の結果
  - `unknown_bump(string $brand, ?string $brewery): void` — `unknown_beer` の回数を増やす
  - `reco_record(int $uploadId, array $picked): void` — `recommendation` に3行入れる
  - `event_record(string $visitorId, string $kind, ?string $target, ?int $uploadId): void`

- [ ] **Step 1: 訪問者IDを実装する**

`common/reco/visitor.php`:

```php
<?php
declare(strict_types=1);
/*
 * 匿名の訪問者ID(設計書 §4)。氏名もメールも取らない。
 * LINE 連携は visitor.line_user_id を埋めるだけで、過去の記録がそのまま繋がる。
 */

require_once __DIR__ . '/../nebula/helpers.php';

const VISITOR_COOKIE = 'db_vid';
const VISITOR_TTL    = 31536000;   // 1年

function visitor_current(): string
{
    $id = $_COOKIE[VISITOR_COOKIE] ?? '';
    if (!preg_match('/^[0-9a-f]{32}$/', $id)) {
        $id = bin2hex(random_bytes(16));
    }
    setcookie(VISITOR_COOKIE, $id, [
        'expires'  => time() + VISITOR_TTL,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $now = date('Y-m-d H:i:s');
    $st = db()->prepare(
        "INSERT INTO visitor (visitor_id, first_seen, last_seen)
         VALUES (:id, :now, :now)
         ON DUPLICATE KEY UPDATE last_seen = :now2");
    $st->execute([':id' => $id, ':now' => $now, ':now2' => $now]);

    return $id;
}
```

- [ ] **Step 2: 読み書きを実装する**

`common/reco/repo.php`:

```php
<?php
declare(strict_types=1);
/*
 * リコメンド機能のDB入出力。SQLはここに閉じる。
 * カスケード(cascade.php)と門番(gate.php)はDBを知らないまま単体テストできる。
 */

require_once __DIR__ . '/../nebula/helpers.php';
require_once __DIR__ . '/gate.php';

/** 判定プロンプトに載せる銘柄一覧。**並びが変わるとキャッシュが外れるので ProductID 順に固定する** */
function reco_catalog(): array
{
    return db()->query(
        "SELECT p.ProductID, p.ProductName, m.MakerName
         FROM products p LEFT JOIN maker m ON m.MakerID = p.MakerID
         ORDER BY p.ProductID")->fetchAll();
}

/** カスケードに渡す候補。all_beers() の行をそのまま使う */
function reco_pool(): array { return all_beers(); }

/** gate_check() に渡す配列を DB から作る */
function gate_context(string $visitorId, string $mime, int $bytes, string $hash): array
{
    $cfgPath = __DIR__ . '/../../api_config.local.php';
    $cfg = is_file($cfgPath) ? require $cfgPath : ['reco_enabled' => false];

    $one = function (string $sql, array $p) {
        $st = db()->prepare($sql); $st->execute($p);
        return (int)$st->fetchColumn();
    };

    $lastAt = db()->prepare(
        "SELECT created_at FROM upload WHERE visitor_id = :v ORDER BY upload_id DESC LIMIT 1");
    $lastAt->execute([':v' => $visitorId]);
    $last = $lastAt->fetchColumn();

    $streak = db()->prepare(
        "SELECT is_beer FROM upload WHERE visitor_id = :v ORDER BY upload_id DESC LIMIT 5");
    $streak->execute([':v' => $visitorId]);
    $recent = $streak->fetchAll(PDO::FETCH_COLUMN);
    $notBeer = 0;
    foreach ($recent as $b) { if ((int)$b === 0) { $notBeer++; } else { break; } }

    return [
        'enabled'         => !empty($cfg['reco_enabled']),
        'daily_cap'       => (int)($cfg['daily_cap'] ?? GATE_LIMITS['global_daily']),
        'mime'            => $mime,
        'bytes'           => $bytes,
        'seconds_since'   => $last ? (time() - strtotime((string)$last)) : PHP_INT_MAX,
        'visitor_today'   => $one("SELECT COUNT(*) FROM upload WHERE visitor_id = :v AND DATE(created_at) = CURDATE()", [':v' => $visitorId]),
        'global_today'    => $one("SELECT COUNT(*) FROM upload WHERE DATE(created_at) = CURDATE()", []),
        'global_month'    => $one("SELECT COUNT(*) FROM upload WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())", []),
        'hash_seen'       => upload_by_hash($hash) !== null,
        'not_beer_streak' => $notBeer,
    ];
}

function upload_by_hash(string $hash): ?array
{
    $st = db()->prepare("SELECT * FROM upload WHERE image_hash = :h ORDER BY upload_id DESC LIMIT 1");
    $st->execute([':h' => $hash]);
    $row = $st->fetch();
    return $row ?: null;
}

function upload_record(string $visitorId, array $r, string $hash, ?string $imagePath): int
{
    $st = db()->prepare(
        "INSERT INTO upload
           (visitor_id, created_at, image_path, image_hash, is_beer, product_id,
            brand_text, brewery_text, style_guess, color, clarity, confidence, model)
         VALUES
           (:v, :now, :path, :hash, :isbeer, :pid, :brand, :brewery, :style, :color, :clarity, :conf, :model)");
    $st->execute([
        ':v' => $visitorId, ':now' => date('Y-m-d H:i:s'),
        ':path' => $imagePath, ':hash' => $hash,
        ':isbeer' => ($r['is_beer'] === true) ? 1 : 0,
        ':pid' => $r['matched_product_id'] ?: null,
        ':brand' => $r['brand_text'] ?: null, ':brewery' => $r['brewery_text'] ?: null,
        ':style' => $r['style_guess'] ?: null,
        ':color' => $r['color'], ':clarity' => $r['clarity'],
        ':conf' => $r['confidence'], ':model' => $r['model'] ?? null,
    ]);
    return (int)db()->lastInsertId();
}

/** 読めたがDBに無い銘柄。**これが次のデータ投入バッチの優先リストになる**(設計書 §7) */
function unknown_bump(string $brand, ?string $brewery): void
{
    $st = db()->prepare(
        "INSERT INTO unknown_beer (brand_text, brewery_text, hits, last_seen)
         VALUES (:b, :w, 1, :now)
         ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = :now2");
    $st->execute([':b' => $brand, ':w' => $brewery, ':now' => date('Y-m-d H:i:s'), ':now2' => date('Y-m-d H:i:s')]);
}

function reco_record(int $uploadId, array $picked): void
{
    $st = db()->prepare(
        "INSERT INTO recommendation (upload_id, position, product_id, stage, created_at)
         VALUES (:u, :pos, :pid, :stage, :now)");
    $pos = 0;
    foreach ($picked as $p) {
        $pos++;
        $st->execute([':u' => $uploadId, ':pos' => $pos,
                      ':pid' => $p['ProductID'], ':stage' => $p['stage'],
                      ':now' => date('Y-m-d H:i:s')]);
    }
}

function event_record(string $visitorId, string $kind, ?string $target, ?int $uploadId): void
{
    $st = db()->prepare(
        "INSERT INTO event (visitor_id, created_at, kind, target, upload_id)
         VALUES (:v, :now, :k, :t, :u)");
    $st->execute([':v' => $visitorId, ':now' => date('Y-m-d H:i:s'),
                  ':k' => $kind, ':t' => $target, ':u' => $uploadId]);
}
```

- [ ] **Step 3: dev の実DBで1周させる**

```bash
cd /workspace/tool/beer
php -r '
$_COOKIE = [];
require "common/reco/visitor.php";
require "common/reco/repo.php";
require "common/reco/cascade.php";
$v = visitor_current();
$r = ["is_beer"=>true,"matched_product_id"=>"pr0013","brand_text"=>"HAZY JANE",
      "brewery_text"=>"BREWDOG","style_guess"=>null,"color"=>4,"clarity"=>3,
      "confidence"=>0.92,"model"=>"claude-sonnet-5"];
$u = upload_record($v, $r, str_repeat("a",64), null);
$pool = reco_pool();
$seed = beer_by_id("pr0013");
$picked = reco_pick($seed, $pool);
reco_record($u, $picked);
foreach ($picked as $p) { echo $p["stage"], " ", $p["ProductID"], " ", $p["row"]["ProductName"], "\n"; }
'
```
Expected: 3行出る。**ここで初めて「HAZY JANE に似た3本」が実データで見える。** 同じ蔵(BREWDOG)の銘柄が先頭に来ていたら並べ替えの不具合。

- [ ] **Step 4: 入ったことを確かめて、消す**

```bash
BEER_EC2_IP=54.168.54.119 /workspace/.claude/skills/beer-data-pipeline/scripts/remote_sql.sh dev \
  --query "SELECT u.upload_id, u.product_id, r.position, r.product_id AS reco, r.stage
           FROM upload u JOIN recommendation r ON r.upload_id = u.upload_id
           ORDER BY u.upload_id DESC, r.position LIMIT 5"
```
Expected: 3行。確認したら試し打ちを消す:
```bash
BEER_EC2_IP=54.168.54.119 /workspace/.claude/skills/beer-data-pipeline/scripts/remote_sql.sh dev \
  --query "DELETE FROM recommendation; DELETE FROM upload; DELETE FROM visitor"
```

- [ ] **Step 5: コミット**

```bash
git add common/reco/repo.php common/reco/visitor.php
git commit -m "リコメンド機能のDB入出力と匿名訪問者IDを実装"
```

---

### Task 8: 画面(縦切り)

`index.php` は触らない。`/try.php` として1枚で作る。トップの差し替えは計画2。

**Files:**
- Create: `try.php`
- Create: `assets/css/exposure.css`
- Reference: `docs/design-mocks/D_exposure.html`(色・余白・組みはここから取る)

**Interfaces:**
- Consumes: `visitor_current()` / `gate_context()` / `gate_check()` / `gate_message()` / `identify_call()` / `identify_branch()` / `reco_catalog()` / `reco_pool()` / `reco_pick()` / `upload_record()` / `upload_by_hash()` / `unknown_bump()` / `reco_record()` / `event_record()` / `group_meta()` / `group_hi()` / `style_group()`
- Produces: 画面のみ。他タスクが依存する関数はない

- [ ] **Step 1: 露光案のCSSを書く**

`assets/css/exposure.css`。**スタイル色はここに直値で書かない。** `try.php` が
`group_map()` から `:root` にインラインで流し込む(色の出所が1か所という既存の性質を壊さないため)。

```css
@charset "UTF-8";
/* 露光案(設計書 §11)。地は純黒。写真が主役。色はスタイル分類だけが持つ。
   参照モック: docs/design-mocks/D_exposure.html */

:root{
  --bg:#000; --ink:#fff; --dim:#9d9d9d; --faint:#6a6a6a;
  --line:rgba(255,255,255,.1); --line2:rgba(255,255,255,.07);
  --f-ja:"Zen Kaku Gothic New",-apple-system,BlinkMacSystemFont,
         "Hiragino Kaku Gothic ProN","Noto Sans JP",sans-serif;
  --f-mo:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,monospace;
  --maxw:560px;
  padding-top:env(safe-area-inset-top,0px); padding-bottom:env(safe-area-inset-bottom,0px);
}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--ink);font-family:var(--f-ja);line-height:1.8;
     -webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
img{max-width:100%;display:block}

.ex-wrap{max-width:var(--maxw);margin:0 auto;padding:0 18px}
.ex-bar{display:flex;align-items:center;justify-content:space-between;padding:16px 18px 6px;
        max-width:var(--maxw);margin:0 auto}
.ex-mark{font-size:11px;letter-spacing:.3em;font-weight:400}
.ex-tabs{display:flex;max-width:var(--maxw);margin:0 auto;padding:0 18px 10px}
.ex-tabs a{padding:9px 0;margin-right:18px;font-size:12.5px;color:var(--faint);
           border-bottom:2px solid transparent}
.ex-tabs a.on{color:var(--ink);border-bottom-color:var(--ink)}
.ex-lbl{font-family:var(--f-mo);font-size:10px;letter-spacing:.2em;text-transform:uppercase;
        color:var(--faint);margin-bottom:9px}

/* 受け取り画面 */
.ex-intake{display:grid;place-items:center;gap:26px;padding:54px 18px 0;text-align:center}
.ex-ring{width:190px;height:190px;border-radius:50%;border:1px solid rgba(255,255,255,.18);
         display:grid;place-items:center;position:relative;
         background:radial-gradient(60% 60% at 50% 50%,rgba(255,255,255,.05),transparent 72%)}
.ex-ring::after{content:"";position:absolute;inset:-22px;border-radius:50%;
                border:1px solid rgba(255,255,255,.06)}
.ex-ring span{font-size:30px;color:#c9c9c9;line-height:1}
.ex-intake h1{font-size:16px;font-weight:500;letter-spacing:.02em}
.ex-intake p{font-size:12.5px;color:var(--dim);max-width:24ch}
.ex-acts{display:flex;flex-direction:column;gap:9px;width:100%;max-width:250px}
.ex-btn{font:inherit;font-size:14px;padding:12px;border-radius:3px;cursor:pointer;
        background:var(--ink);color:#000;border:1px solid var(--ink);width:100%}
.ex-btn.ghost{background:transparent;color:var(--ink);border-color:rgba(255,255,255,.2)}
.ex-meta{font-family:var(--f-mo);font-size:10.5px;color:#4f4f4f;letter-spacing:.12em}

/* 結果画面。写真は画面幅いっぱい。背後のハローだけが強調色 */
.ex-shot{aspect-ratio:1/1;display:grid;place-items:center;position:relative;overflow:hidden;
         background:radial-gradient(70% 70% at 50% 42%,#26262a,#000 74%);margin-bottom:16px}
.ex-shot img{max-height:100%;width:auto;position:relative;z-index:1}
.ex-halo{position:absolute;width:56%;padding-top:56%;border-radius:50%;filter:blur(48px);opacity:.34}
.ex-hit{display:flex;align-items:baseline;gap:8px}
.ex-hit b{font-size:23px;font-weight:700;letter-spacing:.01em}
.ex-conf{font-family:var(--f-mo);font-size:11px;color:var(--faint);font-variant-numeric:tabular-nums}
.ex-sub{font-size:12.5px;color:var(--dim);margin-bottom:14px}
.ex-ask{display:flex;gap:9px;margin-bottom:26px}
.ex-ask .ex-btn{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.16);color:var(--ink)}
.ex-ask .ex-btn.yes{background:var(--ink);color:#000;border-color:var(--ink)}

/* 推薦リスト。左は● 。色は通常(減彩) */
.ex-rec{display:flex;gap:12px;padding:12px 0;align-items:center;border-bottom:1px solid var(--line2)}
.ex-dot{width:8px;height:8px;border-radius:50%;flex:0 0 auto}
.ex-nm{flex:1;min-width:0}
.ex-nm b{display:block;font-size:14.5px;font-weight:500;
         white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ex-nm span{font-size:11.5px;color:var(--faint)}
.ex-abv{font-family:var(--f-mo);font-size:12px;color:var(--dim);font-variant-numeric:tabular-nums}

.ex-msg{padding:40px 0;text-align:center;color:var(--dim);font-size:14px}
@media (max-width:400px){ .ex-ring{width:160px;height:160px} }
```

- [ ] **Step 2: 画面を書く**

`try.php` の構成:

```php
<?php
require_once __DIR__ . '/common/nebula/helpers.php';
require_once __DIR__ . '/common/reco/visitor.php';
require_once __DIR__ . '/common/reco/repo.php';
require_once __DIR__ . '/common/reco/gate.php';
require_once __DIR__ . '/common/reco/identify.php';
require_once __DIR__ . '/common/reco/cascade.php';

$visitorId = visitor_current();
$view = 'intake';   // intake / result / error
$msg  = '';
$result = $picked = $seed = [];
$uploadId = 0;
$imageWebPath = null;   // 表示部が参照する。保存しなかったときは null のまま

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $f = $_FILES['photo'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $view = 'error'; $msg = '写真を受け取れませんでした。';
    } else {
        $hash = hash_file('sha256', $f['tmp_name']);
        $mime = mime_content_type($f['tmp_name']);
        $g = gate_check(gate_context($visitorId, $mime, (int)$f['size'], $hash));

        if (!$g['ok'] && $g['reason'] === 'cached') {
            // 同じ写真。APIを呼ばずに前回の結果を使う
            $prev = upload_by_hash($hash);
            $imageWebPath = $prev['image_path'] ? '/' . $prev['image_path'] : null;
            $result = ['is_beer' => (bool)$prev['is_beer'], 'matched_product_id' => $prev['product_id'],
                       'brand_text' => $prev['brand_text'], 'brewery_text' => $prev['brewery_text'],
                       'style_guess' => $prev['style_guess'], 'color' => $prev['color'],
                       'clarity' => $prev['clarity'], 'confidence' => (float)$prev['confidence']];
            $uploadId = (int)$prev['upload_id'];
            $view = 'result';
        } elseif (!$g['ok']) {
            $view = 'error'; $msg = gate_message($g['reason']);
        } else {
            $result = identify_call($f['tmp_name'], reco_catalog());
            if (!empty($result['error'])) {
                $view = 'error'; $msg = 'いま混み合っています。しばらくしてからお試しください。';
            } else {
                // ビール以外は画像を保存しない(設計書 §9)
                $path = null;
                if ($result['is_beer'] === true) {
                    $path = 'img/upload/' . $hash . '.jpg';
                    identify_shrink($f['tmp_name'], __DIR__ . '/' . $path);
                    $imageWebPath = '/' . $path;
                }
                $uploadId = upload_record($visitorId, $result, $hash, $path);
                if ($result['is_beer'] === true && !$result['matched_product_id'] && $result['brand_text']) {
                    unknown_bump($result['brand_text'], $result['brewery_text']);
                }
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
            reco_record($uploadId, $picked);
            event_record($visitorId, 'reco_view', $result['matched_product_id'], $uploadId);
        }
    }
}
```

同じファイルの後半、表示部分:

```php
<?php
$seedGroup = ($view === 'result' && !empty($seed))
    ? style_group($seed['FamilyName'] ?? '', $seed['StyleName'] ?? '') : 'other';
?><!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>ビールをさがす | Darth Beer.com</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;500;700&family=JetBrains+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="/assets/css/exposure.css">
<?php /* スタイル色の出所は group_map() 一つ。CSS に直値を書かず、ここで流し込む */ ?>
<style>:root{
<?php foreach (group_map() as $k => $v): ?>
  --g-<?= e($k) ?>:<?= e($v[0]) ?>; --h-<?= e($k) ?>:<?= e($v[1]) ?>;
<?php endforeach; ?>
}</style>
</head>
<body>

<div class="ex-bar"><div class="ex-mark">DARTH BEER</div></div>
<nav class="ex-tabs">
  <a class="on" href="/try.php">さがす</a>
  <a href="/beer/products.php">銘柄</a>
  <a href="/brewery/makers.php">蔵</a>
  <a href="/style/styles.php">スタイル</a>
</nav>

<?php if ($view === 'intake' || $view === 'error'): ?>
  <?php if ($view === 'error'): ?>
    <div class="ex-wrap"><p class="ex-msg"><?= e($msg) ?></p></div>
  <?php endif; ?>
  <form class="ex-intake" method="post" enctype="multipart/form-data">
    <div class="ex-ring"><span>◎</span></div>
    <h1>飲んだビールの写真から</h1>
    <p>ラベルが写っていれば、似た一本を探します</p>
    <div class="ex-acts">
      <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
             capture="environment" hidden onchange="this.form.submit()">
      <button class="ex-btn" type="button" onclick="document.getElementById('photo').click()">写真をえらぶ</button>
      <a class="ex-btn ghost" href="/beer/products.php">名前でさがす</a>
    </div>
    <div class="ex-meta"><?= count(reco_pool()) ?> BEERS</div>
  </form>

<?php elseif ($result['is_beer'] !== true): ?>
  <div class="ex-wrap">
    <p class="ex-msg">ビールの写真に見えませんでした。<br>ラベルが写るように撮ってみてください。</p>
    <div class="ex-acts"><a class="ex-btn" href="/try.php">もう一度</a></div>
  </div>

<?php else: ?>
  <div class="ex-shot">
    <?php /* 強調色を使ってよい3か所のひとつ。同定された1本の背後のハロー(設計書 §11) */ ?>
    <span class="ex-halo" style="background:var(--h-<?= e($seedGroup) ?>)"></span>
    <?php if (!empty($imageWebPath)): ?><img src="<?= e($imageWebPath) ?>" alt=""><?php endif; ?>
  </div>
  <div class="ex-wrap">
    <div class="ex-hit">
      <b><?= e($result['matched_product_id'] ? $seed['ProductName'] : ($result['brand_text'] ?: '銘柄がわかりませんでした')) ?></b>
      <?php if ($result['confidence'] !== null): ?>
        <span class="ex-conf"><?= number_format((float)$result['confidence'], 2) ?></span>
      <?php endif; ?>
    </div>
    <div class="ex-sub">
      <?= e($result['brewery_text'] ?: ($seed['MakerName'] ?? '')) ?>
      <?php if (!empty($seed['StyleName'])): ?> · <?= e($seed['StyleName']) ?><?php endif; ?>
    </div>

    <?php if (identify_branch($result) === 'confirm'): ?>
      <form class="ex-ask" method="post" action="/try.php">
        <input type="hidden" name="upload_id" value="<?= (int)$uploadId ?>">
        <button class="ex-btn yes" name="confirm" value="yes">これで合っている</button>
        <button class="ex-btn"     name="confirm" value="no">ちがう</button>
      </form>
    <?php else: ?>
      <p class="ex-sub">この銘柄はまだ登録されていません。読み取れた特徴から探しました。</p>
    <?php endif; ?>

    <div class="ex-lbl">Similar</div>
    <?php foreach ($picked as $p): $r = $p['row'];
          list($col) = group_meta(style_group($r['FamilyName'] ?? '', $r['StyleName'] ?? '')); ?>
      <a class="ex-rec" href="/beer/detail/product.php?ProductID=<?= e($r['ProductID']) ?>">
        <?php /* 並ぶところは通常色(減彩)。ここで強調色を使わない */ ?>
        <span class="ex-dot" style="background:<?= e($col) ?>"></span>
        <span class="ex-nm">
          <b><?= e($r['ProductName']) ?></b>
          <span><?= e($r['MakerName'] ?: '') ?></span>
        </span>
        <span class="ex-abv"><?= fmt_unit($r['Alcohol'], '%') ?></span>
      </a>
    <?php endforeach; ?>

    <?php if (!$picked): ?><p class="ex-msg">似た銘柄が見つかりませんでした。</p><?php endif; ?>
    <div class="ex-acts" style="margin:26px auto 40px"><a class="ex-btn ghost" href="/try.php">もう一枚</a></div>
  </div>
<?php endif; ?>

</body>
</html>
```

`$imageWebPath` は、保存した画像の公開パスではなく **`img/upload/` を直接指す**。
計画2で nginx がこのディレクトリを外から見えなくするまでの暫定で、
dev(Basic認証あり)でしか動かさない。

**確認の答え(`confirm`)を受け取る分岐**をファイル冒頭の POST 処理の先頭に足す:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    event_record($visitorId,
        $_POST['confirm'] === 'yes' ? 'confirm_yes' : 'confirm_no',
        null, (int)($_POST['upload_id'] ?? 0));
    header('Location: /try.php');
    exit;
}
```

- [ ] **Step 3: 保存先を用意する**

```bash
mkdir -p /workspace/tool/beer/img/upload
printf 'Deny from all\n' > /workspace/tool/beer/img/upload/.htaccess
```
アップロード画像は公開面に出さない(設計書 §9)。nginx の設定は計画2で入れる。

- [ ] **Step 4: dev で実際に1枚上げる**

dev に配って、ブラウザから `data/sample/IMG_1016.jpeg` を上げる。
Expected: 「HAZY JANE」と出て、その下に似た3本が出る。**BREWDOG の別銘柄が先頭に来ていないこと**(別の蔵が優先される規則)。

- [ ] **Step 5: 既存ページが壊れていないことを確かめる**

Run: `bash tests/runner/run_all.sh`
Expected: 既存と同じ結果。`try.php` は既存ページを require しないので影響は無いはずだが、`helpers.php` を触っているので念のため。

- [ ] **Step 6: コミット(2つに分ける)**

```bash
git add assets/css/exposure.css
git commit -m "露光案のスタイルシートを追加"
git add try.php img/upload/.htaccess
git commit -m "写真から似た1本を薦める画面を /try.php として追加"
```

---

### Task 9: 15枚での精度検証

**Files:**
- Create: `tests/eval/run_eval.php`
- Reference: `data/sample/expected.json`(正解)、`data/sample/*.jpeg`(gitignore)

**Interfaces:**
- Consumes: `identify_call()` / `reco_catalog()`
- Produces: 実行結果を `tests/eval/result-YYYYMMDD.json` に書き、標準出力に表を出す

- [ ] **Step 1: 検証を書く**

`tests/eval/run_eval.php`:

```php
<?php
declare(strict_types=1);
/*
 * 画像判定の同定精度を測る(設計書 §10)。
 *
 * **実行すると課金される。** 15枚 × 約0.8円 = 約12円。
 * モデルやプロンプトを変えたときに回す。日常のテスト(tests/unit)からは呼ばない。
 *
 * 使い方: php tests/eval/run_eval.php
 */

require_once __DIR__ . '/../../common/reco/identify.php';
require_once __DIR__ . '/../../common/reco/repo.php';

$spec = json_decode(file_get_contents(__DIR__ . '/../../data/sample/expected.json'), true);
$catalog = reco_catalog();

$rows = [];
$hitBeer = $hitProduct = $total = $beerTotal = 0;

foreach ($spec['cases'] as $c) {
    $path = __DIR__ . '/../../data/sample/' . $c['file'];
    if (!is_file($path)) { echo "見つかりません: {$c['file']}\n"; continue; }

    $r = identify_call($path, $catalog);
    $total++;

    $beerOk = ($r['is_beer'] === $c['is_beer']);
    if ($beerOk) { $hitBeer++; }

    $prodOk = null;
    if ($c['is_beer']) {
        $beerTotal++;
        // 期待する ProductID があればIDで、無ければ読み取った銘柄名で見る
        if (isset($c['expected_product_id'])) {
            $prodOk = ($r['matched_product_id'] === $c['expected_product_id']);
        } else {
            $prodOk = ($r['matched_product_id'] === null)
                   && $r['brand_text'] !== null
                   && mb_strpos($c['product'], mb_substr((string)$r['brand_text'], 0, 4)) !== false;
        }
        if ($prodOk) { $hitProduct++; }
    }

    $rows[] = ['file' => $c['file'], 'expect_beer' => $c['is_beer'], 'got_beer' => $r['is_beer'],
               'expect_product' => $c['product'], 'got_brand' => $r['brand_text'],
               'got_pid' => $r['matched_product_id'], 'conf' => $r['confidence'],
               'beer_ok' => $beerOk, 'product_ok' => $prodOk];

    printf("%-16s beer:%s  銘柄:%s  確度:%s  %s\n",
        $c['file'],
        $beerOk ? 'ok  ' : 'NG  ',
        $prodOk === null ? '—   ' : ($prodOk ? 'ok  ' : 'NG  '),
        $r['confidence'] === null ? '  — ' : number_format((float)$r['confidence'], 2),
        $prodOk === false ? "(期待 {$c['product']} / 実際 {$r['brand_text']})" : '');
    sleep(1);
}

printf("\nビール判定 %d/%d   銘柄同定 %d/%d\n", $hitBeer, $total, $hitProduct, $beerTotal);

$out = __DIR__ . '/result-' . date('Ymd') . '.json';
file_put_contents($out, json_encode(
    ['model' => IDENTIFY_MODEL, 'ran_at' => date('c'),
     'is_beer' => "$hitBeer/$total", 'product' => "$hitProduct/$beerTotal", 'rows' => $rows],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "結果: $out\n";
```

- [ ] **Step 2: 実行する**

**約12円かかる。** 実行前に利用者に一声かける。

Run: `php tests/eval/run_eval.php`
Expected: 15行出て、最後に `ビール判定 n/15   銘柄同定 n/13` が出る。

見るところ:
- **`IMG_1189`(檸檬堂)と `IMG_4818`(Red Bull)で `is_beer=false` が出るか。** ここが落ちると門番が効かない
- **`IMG_4817`(缶の裏面)。** 表ラベル無しで読めるか
- **`IMG_2441`(先頭文字が隠れた EQUANIMITY)と `IMG_1455`(左端が切れた有頂天エイリアンズ)**
- `IMG_1455` は DB側が「有頂**点**」と誤記されているので、正しく読めるほど `matched_product_id` が null になる可能性がある。**その場合は判定ではなくデータ側の不具合**

- [ ] **Step 3: 結果を記録する**

`docs/data-log/` に日付のファイルを作り、`model` / `is_beer` / `product` の3つと、外した銘柄の一覧を残す。次にモデルを変えたときの比較対象になる。

- [ ] **Step 4: コミット**

```bash
git add tests/eval/run_eval.php tests/eval/result-*.json docs/data-log/
git commit -m "15枚の正解データで同定精度を測る検証を追加"
```

---

## この計画に**含まれないもの**(計画2・3)

| | どこで |
|---|---|
| トップ(`index.php`)の差し替え、タブの整理 | 計画2 |
| 年齢確認、LINEログイン | 計画2 |
| AdSense の撤去、`privacy.php` の更新、利用規約の新設 | 計画2 |
| `img/upload/` を公開しない nginx 設定 | 計画2 |
| PR枠、蔵向けウィジェット、月次レポート | 計画3 |
| prod への適用 | 計画2の最後(dev で通してから) |
