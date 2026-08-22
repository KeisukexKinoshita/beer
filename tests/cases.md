# beer 特性テスト ケース台帳

対象: `common/sql.php` / `common/sql_POST.php` / `php/sql.php`(commonと分岐した別版)/
`php/sql_POST.php` と、これらを require する各ページ。
`bk_html/`, `php/bk/` は対象外(参照もしていない)。

本台帳の期待値はすべて **仕様(CLAUDE.md・docs/loop-scope.md・対象コードの意図された契約)側から
計算したもの**であり、実装を実行して得た値をそのまま転記したものではない。
実行結果との不一致は「仕様と実装のズレ」として実行報告側で扱う。

ハーネス前提(`tests/runner/exec_page.php` を実読して確認済み):
- `php exec_page.php <sandbox内の相対パス> [params.json]` で1ページ1プロセス実行。
  `params.json` の `get`/`post` がそのまま `$_GET`/`$_POST`/`$_REQUEST` になる。
  **`$_FILES` は一切セットされない**(アップロードは未対応)。
- `error_reporting(E_ALL)` + `display_errors=1` なので、Warning/Deprecation/Fatal error は
  すべて標準出力(HTML)にインラインで混入する。Fatal error 発生時点でそれ以降の出力は生成されない
  (PHPはストリーミング出力のため、それまでに書き出し済みの部分は残る)。
- `tests/runner/make_sandbox.sh` により `brewery/detail/explain/mk9000.html`〜`mk9003.html` が
  ダミーとして用意済み。→ 合成フィクスチャの `maker.MakerID` は **`mk9001`/`mk9002`/`mk9003`**
  (`mk9000` は予備)を用いる。これは index.php:120 の
  `require('brewery/detail/explain/'.$mak_MakerID[$i].'.html')` に対応させるための必須制約
  (オーケストレーター指示済み)。
- `tests/sandbox/img/tmp/`, `tests/sandbox/img/product/` は空ディレクトリとして用意済み
  (img/ 本体はコピーされない)。`sql_POST.php` の `rename()` を通す場合は当該ケースの実行前に
  `image_name` と同名のダミーファイルを `tests/sandbox/img/tmp/` に置く必要がある(後述)。

---

## 0. 前提・仮定(期待値計算の基礎) — 【2026-08-22 実行・ゴールデンマスター確認により確定済み】

以下はコード上明示されていなかったため当初は仮定として置いていたが、`tests/golden/` (無改変コード+
PHP8.5の実行結果、フィクスチャは `tests/fixtures/fixtures.sql`)を実読して全て確定させた。
再現コマンド: `bash tests/runner/start_db.sh && bash tests/runner/run_all.sh`(`tests/out/` が
`tests/golden/` と全19ケースでbyte-identicalになることを確認済み)。

1. **PDOの型 — 確定**: `tests/fixtures/fixtures.sql` のDDL(INT / DECIMAL / VARCHAR / TEXT)に対し、
   実測では **INT型カラムのみPHPネイティブint(JSON上は非クオート数値)、それ以外
   (VARCHAR/DECIMAL/TEXT)は全てPHP文字列(JSON上はダブルクオート付き文字列)** として返る
   ことを確認した(例: `TC-CORE-03`の`js_cla`=`[{"ClarityID":1,"ClarityValue":1,...}]`(数値)、
   `TC-SEL-PRD-01`の`js_prd`=`[{"ProductID":101,...,"Alcohol":"5.50",...}]`(ProductIDのみ数値、
   Alcohol等DECIMAL/VARCHARは文字列)。pdo_mysql(mysqlnd)がDECIMALを精度保持のため文字列で返し、
   INTはネイティブ型に変換するという一般的な実装挙動と整合する。
   本台帳の以降の記載はこの規則に従う(INTカラム=ProductID/Rate_userID/ClarityID/ClarityValue/
   FruityID/FruityValueのみ非クオート、他は全て文字列)。
2. **行順序 — 確定**: 実測で全ケースとも `js_prd`/`js_mak`/`js_sty`/`js_cla`/`js_fru` は
   フィクスチャのINSERT順(101,102,103 / mk9001,mk9002,mk9003 / 1,2,3 / …)のとおりに返っている
   ことを確認した(このMariaDB環境・データ量では投入順=返却順)。
3. **バインドパラメータのコロン欠け — 確定(仮説A)**: `TC-POST-THANK-NEW-01`のDB実測で
   `products`にProductID=104行がAlcohol=5.10で正しく追加され、`rate_user`にRate_userID=3行が
   Clarity_user=3で正しく追加されていることを確認した。**コロン欠け(`'Alcohol'`,
   `'Clarity_user'`)は実害なく正しくバインドされる**(仮説A確定、仮説Bは棄却)。
   PDOの emulated prepare が先頭コロンの有無を正規化して照合するという理解で正しかった。
4. **`UPDATE products SET IBU_all=(select IBU_Style) where IBU=0.000` — 確定**: `TC-POST-THANK-NEW-01`
   のDB実測で ProductID=102 の `IBU_all` が投入時の `99.99` から **`30.00`**(=自身の`IBU_Style`)に
   書き換わっていることを確認した。**FROM無し相関サブクエリは同一行の自己代入として実行される**
   ことが確定した(MariaDBはこの構文をエラーにしない)。`TC-POST-THANK-UPDATE-01`
   (`new_update=update`)では102行は`99.99`のまま不変であることも確認済み(=このUPDATE文自体は
   `new`分岐の中でのみ実行される、という理解も正しかった)。
5. **`array_search` の緩い比較**: PHP8でも `array_search($needle, $haystack)` はデフォルト非strict
   (`==`)。文字列同士の比較になる場面(GETパラメータ由来の文字列 vs フィクスチャの文字列)は
   通常の文字列一致として扱う。実測でも矛盾なし。

---

## 1. 合成フィクスチャ設計

### 1.1 `maker`(3行)

| MakerID | MakerName | MakerExplain | URL1 |
|---|---|---|---|
| mk9001 | Alpha Brewing | Craft brewery Alpha, known for citrusy IPA. | https://alpha.example.com |
| mk9002 | Beta Brewing | Craft brewery Beta, focuses on lagers. | https://beta.example.com |
| mk9003 | Gamma Brewing | Craft brewery Gamma, dark beer specialist. | https://gamma.example.com |

投入順はこの表の順(MakerID昇順と一致)。`mk9001`〜`mk9003` は `tests/sandbox/brewery/detail/explain/`
に対応するダミーhtmlが用意済み(`mk9000.html` も予備で存在するが本フィクスチャでは未使用)。

### 1.2 `style`(3行)

| StyleID | FamilyName | StyleName | IBU | StyleExplain |
|---|---|---|---|---|
| 1 | Ale | IPA | 45.00 | Hoppy and bitter ale style. |
| 2 | Lager | Pilsner | 30.00 | Crisp, light lager style. |
| 3 | Ale | Stout | 35.00 | Dark, roasty ale style. |

### 1.3 `clarity`(4行) / `fruity`(4行)

`clarity`:

| ClarityID | ClarityValue | ClarityName |
|---|---|---|
| 1 | 1 | Brilliant |
| 2 | 2 | Clear |
| 3 | 3 | Slight Haze |
| 4 | 4 | Hazy |

`fruity`:

| FruityID | FruityValue | FruityName |
|---|---|---|
| 1 | 1 | Not Fruity |
| 2 | 2 | Bit Fruity |
| 3 | 3 | Fruity Beer |
| 4 | 4 | Almost Smoothie |

(値は各ページの `evaluation.php`/`add_product.php` のチェックボックス選択肢1〜4と一致させている)

### 1.4 `products`(3行)

| ProductID | MakerID | ProductName | StyleID | Alcohol | IBU_all | IBU | Color | Clarity | Fruity | Favorite | ProductExplain | IBU_Style | Comment |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 101 | mk9001 | Alpha Citrus IPA | 1 | 5.50 | 42.00 | 42.00 | 3 | 1 | 2 | 4.20 | A citrusy IPA from Alpha Brewing. | 45.00 | (空) |
| 102 | mk9002 | Beta Light Pilsner | 2 | 4.80 | **99.99** | **0.000** | 2 | 2 | 1 | 3.50 | A crisp pilsner from Beta Brewing. | 30.00 | (空) |
| 103 | mk9003 | Gamma Dark Stout | 3 | 6.20 | 35.00 | 35.00 | 9 | 4 | 1 | 4.80 | A roasty stout from Gamma Brewing. | 35.00 | (空) |

ProductID 102 は意図的に `IBU=0.000` かつ `IBU_all=99.99`(= `IBU_Style`=30.00 とわざと不一致)にしてある。
`sql_POST.php` の `UPDATE products SET IBU_all=(select IBU_Style) where IBU=0.000` が
**新規投稿1件だけでなくテーブル全体に波及する**ことを検出するための仕掛け(前提4参照)。
101/103 は `IBU<>0.000` なので対象外(不変であることの対照群)。

想定DDL列順序(`SELECT *` の列順、およびINSERT文の列順と一致させること):
`ProductID, MakerID, ProductName, StyleID, Alcohol, IBU_all, IBU, Color, Clarity, Fruity, Favorite, ProductExplain, IBU_Style, Comment`

### 1.5 `rate_user`(2行)

| Rate_userID | ProductID | UserID | Color_user | Clarity_user | Fruity_user | Favorite_user | New_Update | Comment |
|---|---|---|---|---|---|---|---|---|
| 1 | 101 | us0001 | 3 | 1 | 2 | 4 | new | Great IPA! |
| 2 | 102 | us0002 | 2 | 2 | 1 | 3 | update | Nice pilsner. |

`MAX(ProductID)=103` → 次回 `new` 投稿の採番期待値 = **104**。
`MAX(Rate_userID)=2` → 次回投稿の採番期待値 = **3**。

列順序(INSERT文と一致): `Rate_userID, ProductID, UserID, Color_user, Clarity_user, Fruity_user, Favorite_user, New_Update, Comment`

---

## 2. カバレッジ対応表(分岐 × 到達経路)

### 2.1 `common/sql.php`

| 分岐 | 到達させるページ/ケース | 備考 |
|---|---|---|
| `$src_prd=='no'`(products SELECT スキップ) | TC-CORE-01(直接require) | 実ページでは到達不可(常に不設定 or else側) |
| `$src_prd` else(products 全件) | TC-SEL-POSTADDP-01, TC-SEL-POSTEVAL-01, TC-SEL-CHECK-*, TC-CORE-02/03 | 実ページで到達 |
| `$src_mak=='no'` | TC-CORE-01 | **実ページでは一切設定されない(死んでいる分岐)** |
| `$src_mak=='yes'`(`$sql_where_mak`使用) | TC-CORE-02 | **実ページでは一切設定されない(死んでいる分岐)** |
| `$src_mak` else(`$sql_where`使用) | TC-SEL-POSTADDP-01, TC-SEL-POSTEVAL-01, TC-SEL-CHECK-* | 実ページで到達 |
| `$src_sty=='yes'` | TC-SEL-POSTADDP-01, TC-SEL-POSTEVAL-01, TC-SEL-CHECK-* | 実ページで到達(common使用ページは全て `sty='yes'` を設定) |
| `$src_sty` スキップ | TC-CORE-01 | **実ページでは一切到達しない(死んでいる分岐)** |
| `$src_cla=='yes'` | TC-CORE-03 | **実ページでは一切設定されない(死んでいる分岐)** |
| `$src_cla` スキップ | TC-SEL-POSTADDP-01 等(未設定のため自動的に該当) | 実ページで到達 |
| `$src_fru=='yes'` | TC-CORE-03 | **実ページでは一切設定されない(死んでいる分岐)** |
| `$src_fru` スキップ | 同上 | 実ページで到達 |

→ `$src_mak`(3分岐すべて)・`$src_sty`のスキップ側・`$src_cla`/`$src_fru`の'yes'側は、
現行の対象ページ群からは**到達不能な死分岐**。TC-CORE-01〜03 は `common/sql.php` を
直接 require する薄いラッパー(ハーネス側で用意。対象コード自体は無改変)で変数を手動セットし、
分岐単体を到達させる合成ユニットテスト。

### 2.2 `php/sql.php`(commonとの差分は2箇所のみ、diff確認済み)

差分1: maker取得の条件が `$src_mak` ではなく **`$src_prd=='no'`** になっている
(`elseif` も無いため maker SELECT は必ず実行され、スキップする経路が存在しない)。
差分2: style の array_column/JS抽出から `FamilyName` と `StyleExplain` が欠落
(`sty_FamilyName`, `sty_StyleExplain` が無い。`js_sty` 自体=SELECT *の生JSONは両版で同一)。

| 分岐 | 到達させるページ/ケース |
|---|---|
| `$src_prd=='no'` → maker は `$sql_where_mak` 使用、products SELECT スキップ | TC-SEL-POSTPRD-01(**php/sql.php固有の分岐、commonの`$src_mak`とは無関係に発火する点が最大の相違**) |
| `$src_prd` else → products全件、maker は `$sql_where` 使用 | TC-SEL-PRD-01, TC-SEL-MAK-01, TC-SEL-MAKERS-01, TC-SEL-EVAL-01, TC-SEL-LMAK-01, TC-SEL-LSTY-01, TC-SEL-ADDP-01 |
| `$src_sty=='yes'` | TC-SEL-PRD-01, TC-SEL-EVAL-01, TC-SEL-ADDP-01 |
| `$src_sty` スキップ | TC-SEL-MAK-01, TC-SEL-MAKERS-01, TC-SEL-LMAK-01, TC-SEL-POSTPRD-01 |
| `$src_cla=='yes'` / `$src_fru=='yes'` | TC-SEL-PRD-01 のみ(product.php が唯一cla/fruを'yes'にする) |
| `$src_cla`/`$src_fru` スキップ | それ以外全ページ |

→ `php/sql.php` は対象ページの組み合わせだけで**全分岐を実ページ経由でカバー可能**
(直接require不要)。

### 2.3 `sql_POST.php`(common/php で byte-identical。`diff` で確認済み)

| 分岐 | 到達させるケース |
|---|---|
| `new_update=='new'`(products INSERT + IBU_all UPDATE + 画像rename) | TC-POST-THANK-NEW-01, TC-POST-THANK-NEW-02 |
| `new_update!='new'`(rate_userのみINSERT) | TC-POST-THANK-UPDATE-01 |

`php/sql_POST.php` と `common/sql_POST.php` は同一ファイルのため、両者を呼ぶページ
(`php/thank.php` と `post/check/thank/thank.php`)は同一パラメータなら同一結果になるはず
(TC-POST-THANK-NEW-01 と NEW-02 で相互確認)。

---

## 3. テストケース

各ケースの「状態」は初期値として `未実行` を記載。

### 3.1 SELECT系ページ

#### TC-SEL-IDX-01: `index.php`(トップページ)

- **目的**: `common/sql.php` の実運用時デフォルト分岐(index.php は `$sql_where`/`$sql_where_sty`/`$src_sty` のみ設定)の確認。フィクスチャ行数(3件)が index.php の想定行数(7件以上)を下回る場合の実挙動(未定義変数・配列添字・fatal error)を特性として固定する。
- **入力**: GET `/index.php`(パラメータ無し)。使用フィクスチャ: 全テーブル。
- **期待値**:
  1. `<head>` 内埋め込み変数:
     - `js_prd` = products全3行のJSON(投入順 101,102,103。前提1により全フィールド文字列)
     - `js_mak` = maker全3行のJSON(投入順 mk9001,mk9002,mk9003)
     - `js_sty` = style全3行のJSON(投入順 1,2,3)
     - `js_cla` = `0`(`$src_cla` 未設定 → `$json_cla` 未定義 → `if($json_cla)` は falsy → `echo '0'`)
     - `js_fru` = `0`(同上、`$src_fru` 未設定)
  2. body: 「新着のクラフトビール」ループ (`for($i=count-1;$i>count-7;$i--)`, count=3 → i=2,1,0,-1,-2,-3)
     - i=2,1,0: 有効。逆順で 103(Gamma Dark Stout), 102(Beta Light Pilsner), 101(Alpha Citrus IPA) の `<li>` が出力される。
     - i=-1,-2,-3: **PHP8リスク**。負インデックスは配列に存在しないため `Undefined array key` Warning
       ×3種(ProductID/ProductName/ProductExplain)×3イテレーション。`echo` は null→空文字列になるため
       `<li>` 自体は空データで出力される(クラッシュはしない)。
  3. 「新着のブリュワリー」ループ(同じ `count-7` パターン、makerで count=3)
     - i=2,1,0: 有効。逆順で mk9003(Gamma Brewing), mk9002(Beta Brewing), mk9001(Alpha Brewing) の `<li>`。
     - **i=-1 到達時に FATAL ERROR**: `require('brewery/detail/explain/'.$mak_MakerID[-1].'.html')` で
       `$mak_MakerID[-1]` は未定義(null→空文字列)となり `require('brewery/detail/explain/.html')` を実行、
       対応するファイルは存在しない(`mk9000`〜`mk9003`しか用意されていない)ため
       `require` は **Fatal error(Uncaught Error / Failed opening required)** で処理が停止する。
     - よって最終出力は「ブリュワリー」有効3件の `<li>` まで出力された時点で **打ち切り**
       (styleセクション以降、`</body></html>` 含め一切出力されない)。
  4. **PHP8リスク**: `common/sql.php` 内で index.php が未設定の変数を参照する箇所全て
     (`$src_prd` L11, `$src_mak` L42・L44 の2回, `$src_cla` L86, `$src_fru` L100, さらに
     `$json_cla`/`$json_fru` 参照時)で `Undefined variable` Warning、計7件程度が出力に混入する。
     この実測(オーケストレーターのスモーク実行で確認済み)と一致するはず。
  5. **要ゴールデンマスター確認**: (a) fatal error 発生時の正確なメッセージ文言/フォーマット(PHP 8.5の
     `display_errors` 出力形式)、(b) fatal error 直前までの出力が確実にflushされているか
     (出力バッファリングの有無)。
- **実測との突合せ**: `tests/golden/TC-SEL-IDX-01.html`(exit=255)で完全一致を確認。
  fatal error文言は `Warning: require(brewery/detail/explain/.html): Failed to open stream:
  No such file or directory` に続き `Fatal error: Uncaught Error: Failed opening required
  'brewery/detail/explain/.html' (include_path='.:/usr/share/pear:/usr/share/php') in
  .../index.php:120` + スタックトレースという形で出力される。出力バッファリングは無く、
  fatal直前までの全出力(head内`<script>`、products一覧6件(有効3+空3)、maker一覧の有効3件
  +4件目の途中(`<p class='MakerExplain'>`の開始まで))がそのまま標準出力にflushされている。
  `common/sql.php`側のUndefined variable Warningは`$src_prd`/`$src_mak`×2/`$src_cla`/`$src_fru`
  の計5件、`$json_cla`/`$json_fru`のUndefined variable Warning計2件、合計7件で一致。
  §5の要確認事項5(fatal errorの正確な形)を確定。
- **状態**: PASS(GM固定)

#### TC-SEL-PRD-01: `php/product.php`(php/sql.php 経由、全分岐 yes)

- **目的**: `php/sql.php` の `$src_sty`/`$src_cla`/`$src_fru` 全て `'yes'` の経路と、`array_search` による
  4つの `$keyIndex_*` 計算を検証する。
- **入力**: GET `/php/product.php?product_id=102&maker_id=mk9002`
- **期待値**:
  - `js_prd`=全3行, `js_mak`=全3行(`$src_prd`未設定→else→`$sql_where`使用), `js_sty`=全3行,
    `js_cla`=全4行, `js_fru`=全4行。
  - `$keyIndex_prd = array_search('102', prd_ProductID) = 1`
  - `$keyIndex_mak = array_search('mk9002', mak_MakerID) = 1`
  - `$keyIndex_sty = array_search($prd_StyleID[1]='2', sty_StyleID) = 1`(Pilsner)
  - `$keyIndex_cla = array_search(round('2'),cla_ClarityValue) = array_search(2,...) = 1`(Clear)
  - `$keyIndex_fru = array_search(round('1'),fru_FruityValue) = array_search(1,...) = 0`(Not Fruity)
  - 画面表示: `img src=../img/product/102.png`、見出し `Beta Light Pilsner (Beta Brewing)`、
    STYLE: `Pilsner`、COLOR: `round(2)=2`→`Color2.png`、CLARITY: `Clarity2.png`+`Clear`、
    IBU: `99.99`(=`prd_IBU`は列名`IBU_all`をマップしたもの。フィクスチャの初期値のまま。
    まだ `thank.php` を経由していないため99.99のまま = **thank.php実行後の値30.00とは別物**である点に注意)、
    FRUITY: `Fruity1.png`+`Not Fruity`、ALCOHOL: `4.80`、POPULARITY星: `reviewNum=3.50`。
  - **PHP8リスク**: 末尾の `$comment = $_GET['comment'];` で GETに `comment` が無いため
    `Undefined array key "comment"` Warning(非致命的、`$comment`はnullのまま未使用)。
- **実測との突合せ**: `tests/golden/TC-SEL-PRD-01.html`と完全一致。keyIndex計算・
  STYLE=Pilsner・COLOR=Color2.png・CLARITY=Clarity2.png/Clear・IBU=99.99(前提1の型規則で文字列)・
  FRUITY=Fruity1.png/Not Fruity・ALCOHOL=4.80・reviewNum=3.50、全て予測どおり。
  Warningは `$src_prd`(php/sql.php L11・L42の2件、product.phpとmaker取得の両方が`$src_prd`
  未設定を参照する差分分岐由来)+ `Undefined array key "comment"`(1件)の計3件。
- **状態**: PASS(GM固定)

#### TC-SEL-MAK-01: `php/maker.php`

- **目的**: `$sql_where` を products と maker の両方で使い回す実装(`MakerID='...'`条件が両テーブルに
  たまたま存在するカラム名であることに依存)の確認。`php/sql.php` の `$src_sty`/`$src_cla`/`$src_fru`
  スキップ経路も同時にカバー。
- **入力**: GET `/php/maker.php?MakerID=mk9001`
- **期待値**:
  - `$sql_where = "MakerID='mk9001'"`。products側: `WHERE MakerID='mk9001'` → P101のみ1行。
    maker側(`$src_prd`未設定→else→同じ`$sql_where`使用): `WHERE MakerID='mk9001'` → mk9001のみ1行。
  - `js_prd`=[P101の1行], `js_mak`=[mk9001の1行], `js_sty`=`0`, `js_cla`=`0`, `js_fru`=`0`。
  - `$keyIndex_mak = array_search('mk9001', mak_MakerID) = 0`
  - 画面: 見出し `Alpha Brewing`(nl2brは改行が無いため無変化)、`MakerExplain`本文、URL1リンク。
  - sec2 商品ループ(`for($i=0;$i<count($prd_ProductID);$i++)`, count=1, **0始まりの通常ループなので
    index.phpのような負インデックス問題は起きない**): P101 の `<li>` 1件のみ。
  - **PHP8リスク**: `$src_sty`/`$src_cla`/`$src_fru` 未設定 → `Undefined variable` Warning ×3、
    `$json_sty`/`$json_cla`/`$json_fru` 未定義参照 → Warning ×3、`$src_prd` 未設定(php/sql.php内2箇所参照)
    → Warning ×2。計8件程度。
- **実測との突合せ**: `tests/golden/TC-SEL-MAK-01.html`と完全一致。products/maker各1行、
  sec2ループ1件(P101)、Warning実測は `$src_prd`(L11・L42) x2 + `$src_sty` + `$src_cla` +
  `$src_fru` + `$json_sty`/`$json_cla`/`$json_fru` x3 = **8件で確定**(予測どおり)。
- **状態**: PASS(GM固定)

#### TC-SEL-MAKERS-01: `php/makers.php`

- **目的**: `$sql_where="''=''"` のみ設定した場合の maker 全件表示、通常の0始まりループでの
  健全なページング(index.phpのバグの対照ケース)。
- **入力**: GET `/php/makers.php`
- **期待値**:
  - `js_prd`=全3行(products SELECTは`$src_prd`未設定→実行される。ページ内では未使用だが出力には含まれる)。
  - `js_mak`=全3行。`js_sty`/`js_cla`/`js_fru`=`0`。
  - 見出し `Breweries 3件`。
  - ループ `for($i=0;$i<count($mak_MakerID);$i++)`(正順、0〜2): mk9001,mk9002,mk9003 の順で
    3件の `<li>` が出力される(クラッシュなし)。
- **実測との突合せ**: `tests/golden/TC-SEL-MAKERS-01.html`と概ね一致。「Breweries 3件」+
  3件のmakerリスト(1,2,3の正順)は予測どおり。**追加の発見**: `php/makers.php`末尾にも
  `$comment = $_GET['comment'];` があり(未読取)、`Undefined array key "comment"` Warning が
  1件追加で発生する(当初の期待値には未記載だったため本項で補完。挙動の予測自体に誤りは無い)。
- **状態**: PASS(GM固定、期待値に`comment`未定義Warningを追記して確定)

#### TC-SEL-EVAL-01: `php/evaluation.php`

- **目的**: **既存バグの特性固定**。このファイルは `$_GET['product_id']` を一切読まず、
  `$product_id` も `$keyIndex_mak` も未定義のまま使用している(grep で確認済み: `$_GET`, `keyIndex_mak`,
  `product_id` の3箇所しかヒットせず、代入は無い)。
- **入力**: GET `/php/evaluation.php`(任意のGETパラメータを付けても無視される。パラメータ無しで実行)
- **期待値**:
  - `$sql_where`/`$sql_where_sty`/`$src_sty='yes'` 設定済みのため `js_prd`=全3行、`js_sty`=全3行。
    maker(`$src_prd`未設定→else→`$sql_where`使用)`js_mak`=全3行。`js_cla`/`js_fru`=`0`。
  - **PHP8リスク(重要)**:
    - `$product_id` 未定義 → `Undefined variable $product_id` Warning。hidden input
      `<input type="hidden" name="ProductID" value="">`(空文字列)として出力される。
    - `$keyIndex_mak` 未定義 → `Undefined variable $keyIndex_mak` Warning。続けて
      `$mak_MakerExplain[$keyIndex_mak]` は `null` を配列添字に使うため `""` にキャスト→
      `$mak_MakerExplain[""]` は存在しない添字 → `Undefined array key ""` Warning → 値は `null`。
    - `nl2br(null)` → PHP 8.1以降の **Deprecated: nl2br(): Passing null to parameter #1 ($string)
      of type string is deprecated**。結果自体は空文字列になり、`<p class='p1'>` の中身は空。
    - いずれも非致命的(fatalにはならない)。チェックボックス群(Color/Clarity/Fruity/Favorite/Comment)は
      静的HTMLのため正常に描画される。
- **実測との突合せ**: `tests/golden/TC-SEL-EVAL-01.html`と一致。**追加の発見**: `Undefined array
  key ""` Warningの直前に `Deprecated: Using null as an array offset is deprecated, use an
  empty string instead` (PHP8.1+の仕様)が出力されることを確認(当初の期待値には未記載のため
  補完)。`nl2br(null)`のDeprecated、`$product_id`未定義Warningも予測どおり発生。
- **状態**: PASS(GM固定、Deprecated文言を追記して確定)

#### TC-SEL-LMAK-01: `php/list_maker.php`

- **目的**: `$_GET['product_id']` 未指定時の未定義キー警告と、以降未使用の `$keyIndex`/`$rate` 変数の
  無害な計算を確認。
- **入力**: GET `/php/list_maker.php`(パラメータ無し)
- **期待値**:
  - `$sql_where="''=''"` のみ→ products全3行・maker全3行取得(`js_sty`/`js_cla`/`js_fru`=`0`)。
  - **PHP8リスク**: `$_GET['product_id']` 未指定 → `Undefined array key "product_id"` Warning、
    `$product_id=null`。`array_search(null, $prd_ProductID)` は `false`(loose比較で一致する行が無いため)。
    `$keyIndex`/`$rate` は以降未参照のため実害なし。
  - チェックボックスフォーム: `chk_maker` の value に mk9001/mk9002/mk9003、ラベルに各MakerNameが
    3件分描画される。クラッシュなし。
- **実測との突合せ**: `tests/golden/TC-SEL-LMAK-01.html`と完全一致(mk9001〜mk9003×MakerName、
  `Undefined array key "product_id"` Warning 1件)。
- **状態**: PASS(GM固定)

#### TC-SEL-LSTY-01: `php/list_style.php`

- **目的**: TC-SEL-LMAK-01のstyle版。`$src_sty='yes'` 込みの取得確認。
- **入力**: GET `/php/list_style.php`(パラメータ無し)
- **期待値**:
  - `js_prd`=全3行、`js_sty`=全3行(maker取得も実行されるが`js_mak`はページで未使用。値は全3行)。
  - **PHP8リスク**: `$_GET['product_id']` 未指定と同様の Warning(list_maker.phpと同型)。
  - チェックボックスフォーム: `chk_style` の value に 1/2/3、ラベルに IPA/Pilsner/Stout が3件分描画。
- **実測との突合せ**: `tests/golden/TC-SEL-LSTY-01.html`と完全一致(1/2/3×StyleName、
  `Undefined array key "product_id"` Warning 1件)。
- **状態**: PASS(GM固定)

#### TC-SEL-ADDP-01: `php/add_product.php`

- **目的**: 新規投稿フォーム描画(`php/sql.php` 経由)。プルダウンの選択肢がフィクスチャと一致するか。
- **入力**: GET `/php/add_product.php`(パラメータ無し)
- **期待値**:
  - `js_prd`/`js_mak`/`js_sty`=全件、`js_cla`/`js_fru`=`0`。
  - `<select name='chk_MakerID'>` の `<option>` に mk9001/mk9002/mk9003 × MakerName、
    `<select name='chk_StyleID'>` に 1/2/3 × StyleName。
  - **PHP8リスク**: `$product_id` 未定義変数 → Warning、hidden `ProductID` の value は空文字列。
- **実測との突合せ**: `tests/golden/TC-SEL-ADDP-01.html`と完全一致(`<option value=mk9001 >`〜
  `mk9003`、`value=1`〜`3`、`$product_id`未定義Warning、`$src_prd`x2/`$src_cla`/`$src_fru`/
  `$json_cla`/`$json_fru`のWarning計6件)。
- **状態**: PASS(GM固定)

#### TC-SEL-POSTADDP-01: `post/add_product.php`(`common/sql.php` 経由)

- **目的**: `common/sql.php` を実ページから呼ぶ経路の確認(TC-SEL-ADDP-01のcommon版)。
  `$src_mak`/`$src_prd` の実運用デフォルト(else側)を踏襲することを再確認する。
- **入力**: GET `/post/add_product.php`(パラメータ無し)
- **期待値**: TC-SEL-ADDP-01 と同じ `js_prd`/`js_mak`/`js_sty` 内容(全件)、`js_cla`/`js_fru`=`0`。
  ただし埋め込み `<script>` の **style 抽出部が5フィールド版**
  (`sty_StyleID, sty_FamilyName, sty_StyleName, sty_StyleIBU, sty_StyleExplain`)になる点が
  `php/sql.php` 版(3フィールド: `sty_StyleID, sty_StyleName, sty_StyleIBU`)との**必須の差分**。
  この差分自体をここで固定する(統合してはいけない、というスコープの不変条件の直接的な検証)。
  `$product_id` 未定義によるWarningも同様に発生。
- **実測との突合せ**: `tests/golden/TC-SEL-POSTADDP-01.html`と完全一致。style抽出の5フィールド版
  (`var sty_StyleID=[], sty_FamilyName=[], sty_StyleName=[], sty_StyleIBU=[], sty_StyleExplain=[];`)
  を実測で確認、`php/sql.php`版(3フィールド、TC-SEL-ADDP-01)との差分をそのまま確定させた。
- **状態**: PASS(GM固定)

#### TC-SEL-POSTEVAL-01: `post/evaluation.php`(`common/sql.php` 経由)

- **目的**: `post/evaluation.php` は `php/evaluation.php` と違い、正しくロジックが実装されている
  (`$_GET['serch']` は評価ページ内で読まれないが、`$sql_where`/`$sql_where_sty`/`$src_sty='yes'` を
  設定したうえで通常表示するだけの静的フォーム)。common/sql.php 版の基本経路確認。
- **入力**: GET `/post/evaluation.php`(パラメータ無し)
- **期待値**: `js_prd`/`js_mak`/`js_sty`=全件(5フィールドstyle)、`js_cla`/`js_fru`=`0`。
  `$product_id` 未定義Warning(hidden ProductIDが空)。TC-SEL-POSTADDP-01と同型。
- **実測との突合せ**: `tests/golden/TC-SEL-POSTEVAL-01.html`と完全一致(5フィールド版styleスクリプト、
  `$product_id`未定義Warningを含めTC-SEL-POSTADDP-01と同型のWarningパターン)。
- **状態**: PASS(GM固定)

#### TC-SEL-POSTPRD-01: `php/post_product.php`(**php/sql.php の分岐差分の直接検証**)

- **目的**: `php/sql.php` 固有の分岐(`if($src_prd=='no')` が products だけでなく maker の
  WHERE句選択にも作用する)を実ページ経由で踏む、本台帳で最も重要なケースの一つ。
- **入力**: GET `/php/post_product.php?serch=Beta`
- **期待値**:
  - `$src_prd='no'` が設定される → products SELECT は**スキップ**(`js_prd` は `$json_prd` 未定義
    → `0`)。
  - `$sql_where_mak = "MakerName like'%Beta%'"`(空白無し連結。MySQLの字句解析はキーワードと
    引用文字列の間の空白を必須としないため、`LIKE'%Beta%'` は正しく `LIKE '%Beta%'` と同義に解釈される
    と予測。**要ゴールデンマスター確認**)。
  - `php/sql.php` の maker 分岐は `$src_prd=='no'` が true のため `$sql_where_mak` を使用する側に入る
    (= commonなら `$src_mak` で判定するところを `$src_prd` で判定してしまう分岐差分そのもの)。
    → `WHERE MakerName like '%Beta%'` = mk9002(Beta Brewing)のみ1行ヒット。
  - `js_mak` = mk9002の1行。`js_sty`/`js_cla`/`js_fru`=`0`(いずれも未設定)。
  - ページ本体: `for` ループで `print_r($mak_MakerName[$i])` を1回実行 → 出力に生の文字列
    `Beta Brewing` が(`<pre>`等の装飾無しで)そのまま挿入される。
  - **PHP8リスク**: `$src_sty`/`$src_cla`/`$src_fru` 未設定によるWarning群、`$json_prd`未定義に
    よるWarning(products側は明示的にスキップされているため、`if($src_prd=='no'){ // not execute }`
    のコメントブロックのみで `$json_prd` 自体が代入されない)。
- **実測との突合せ**: `tests/golden/TC-SEL-POSTPRD-01.html`と完全一致。本台帳で最も重要な
  分岐差分検証ケースが確定: `js_prd`=`0`、`js_mak`=mk9002(Beta Brewing)の1行のみ、
  ページ末尾に `print_r($mak_MakerName[0])` の生出力 `Beta Brewing` を確認。
  `"MakerName like"."'%".$serch."%'"`(空白無し連結)がMariaDBで構文エラーにならないことも
  実測で確認(§5 要確認事項6を確定)。
- **状態**: PASS(GM固定)

#### TC-SEL-CHECK-NEW-01: `post/check/check.php`(`New_Update=new`)

- **目的**: `common/sql.php` 経由のcheck画面。`$keyIndex_prd` が見つからない(空文字列)場合に
  `false`→配列添字`0`にキャストされる挙動と、`$keyIndex_sty` が計算されるが実際には
  画面に一切使われない(死んだ計算)ことを確認する。
- **入力**: POST `/post/check/check.php`
  ```json
  {"ProductID":"", "chk_MakerID":"mk9002", "chk_productname":"Delta Wheat",
   "chk_StyleID":"2", "chk_alcohol":"5.10", "chk_ibu":"25.00", "chk_color":"5",
   "chk_clarity":{"value":"3","explain":"Slight Haze"},
   "chk_fruity":{"value":"2","explain":"Bit Fruity"},
   "chk_favorite":"4", "UserID":"us0001", "New_Update":"new",
   "comment":"First tasting.", "ProductExplain":"A new wheat beer."}
  ```
  (`$_FILES['upimg']` はハーネス未対応のため送出しない)
- **期待値**:
  - `$product_id = ''`。`$keyIndex_prd = array_search('', prd_ProductID) = false`。
  - `$keyIndex_mak = array_search('mk9002', mak_MakerID) = 1`。
  - `$keyIndex_sty = array_search($prd_StyleID[(int)false=0]='1', sty_StyleID) = 0`
    (フィクスチャのindex0が存在するため警告は出ない。**ただしこの値は check.php のどの分岐でも
    画面出力に使われない=無害なデッドコードである**ことを確認するのが本ケースの副次目的)。
  - 画面(`new_update=='new'`分岐): Brewery=`Beta Brewing`、BeerName=`Delta Wheat`、
    Alcohol=`5.10 %`、IBU=`25.00 IBU`、Color=`Color5.png`、Clarity=`Clarity3.png`+`Slight Haze`
    (`$_POST['chk_clarity']['explain']`をそのまま使用)、Fruity=`Fruity2.png`+`Bit Fruity`、
    Favorite=`Favorite4.png`、Comment=`First tasting.`、Photo=`Not Selected`
    (`$img_name`は`$_FILES`未設定のため空)。
  - **PHP8リスク**: `$_FILES['upimg']['name']` 未設定 → `Undefined array key "upimg"` Warning
    ×2箇所(name取得時・move_uploaded_file時)、`move_uploaded_file()` 自体も失敗Warning
    (ハーネスが`$_FILES`を用意しない限り常に発生する既知の制約。バグではなくハーネス制約として記録)。
  - 出力される `value_http`(次のthank.phpケースの入力になる):
    `userid=us0001&productid=&makerid=mk9002&productname=Delta+Wheat&styleid=2&alcohol=5.10&ibu=25.00&color=5&clarity=3&fruity=2&favorite=4&new_update=new&productexplain=A+new+wheat+beer.&comment=First+tasting.`
    (`http_build_query`のキー順は`$value`への代入順:
    userid,productid,makerid,productname,styleid,alcohol,ibu,color,clarity,fruity,favorite,new_update,productexplain,comment)
- **実測との突合せ**: `tests/golden/TC-SEL-CHECK-NEW-01.html`と完全一致。画面内容・`value_http`の
  文字列(キー順含む)ともに予測どおり。**Warning文言を精緻化**: `$_FILES['upimg']`未設定により
  `Undefined array key "upimg"`(check.php L25, L26の2箇所)に加えて、それぞれ直後に
  `Warning: Trying to access array offset on null`(2件)、さらに
  `Deprecated: move_uploaded_file(): Passing null to parameter #1 ($from) of type string is
  deprecated`(1件)が発生する。ハーネスが`$_FILES`未対応であることに起因する既知の制約
  (対象コードのバグではない)として確定。
- **状態**: PASS(GM固定、Warning文言を確定)

#### TC-SEL-CHECK-UPDATE-01: `post/check/check.php`(`New_Update=update`)

- **目的**: 既存商品への評価追加の画面確認。`$keyIndex_prd` が有効に見つかるケース。
- **入力**: POST `/post/check/check.php`
  ```json
  {"ProductID":"101", "chk_MakerID":"mk9001", "chk_color":"7",
   "chk_clarity":{"value":"4","explain":"Hazy"},
   "chk_fruity":{"value":"3","explain":"Fruity Beer"},
   "chk_favorite":"2", "UserID":"us0003", "New_Update":"update",
   "comment":"Updated take."}
  ```
- **期待値**:
  - `$keyIndex_prd = array_search('101', prd_ProductID) = 0`。`$keyIndex_mak = array_search('mk9001', mak_MakerID) = 0`。
  - 画面(`update`分岐): Brewery=`Alpha Brewing`、BeerName=`$prd_ProductName[0]`=`Alpha Citrus IPA`
    (**productsテーブルの既存値**。ユーザー入力のproductnameは無い=updateフォームにその項目が無いため妥当)、
    Color=`Color7.png`、Clarity=`Clarity4.png`+`Hazy`、Fruity=`Fruity3.png`+`Fruity Beer`、
    Favorite=`Favorite2.png`、Comment=`Updated take.`
  - `value_http`: `userid=us0003&productid=101&makerid=mk9001&color=7&clarity=4&fruity=3&favorite=2&new_update=update&comment=Updated+take.`
    (updateパスでは`productname`/`styleid`/`alcohol`/`ibu`/`productexplain`はそもそも `$value` に
    代入されないため`http_build_query`に現れない)。
- **実測との突合せ**: `tests/golden/TC-SEL-CHECK-UPDATE-01.html`と完全一致(画面内容・`value_http`
  とも予測どおり。$_FILES関連の処理が無い分岐のためファイル関連Warningは発生しない)。
- **状態**: PASS(GM固定)

### 3.2 `common/sql.php` 死分岐の直接カバレッジ(ハーネス側ラッパー使用)

以下3ケースは、対象コード(`common/sql.php`)を無改変のまま require するだけの薄いラッパーを
ハーネス側に用意して実行する(ラッパー自体はハーネスの実装物であり対象コードではない)。

#### TC-CORE-01: `$src_prd='no'` かつ `$src_mak='no'`

- **目的**: commonのみに存在する「products/makerともにSELECTスキップ」分岐と、
  `$src_sty` 未設定(スキップ)分岐を同時に踏む。
- **入力**: ラッパーが `$src_prd='no'; $src_mak='no';` のみセットして `common/sql.php` を require。
- **期待値**: `$json_prd`/`$json_mak`/`$json_sty`/`$json_cla`/`$json_fru` すべて未定義
  → `js_prd`〜`js_fru` すべて `0`。SELECTクエリは1本も発行されない。
  **PHP8リスク**: `$src_cla`/`$src_fru` 未定義Warning、`$json_*` 未定義Warning ×5。
- **実測との突合せ**: `tests/golden/TC-CORE-01.html`と完全一致。`js_prd`/`js_mak`/`js_sty`/`js_cla`/
  `js_fru`すべて`0`、Warning実測は`$src_sty`+`$src_cla`+`$src_fru`+`$json_prd`/`$json_mak`/
  `$json_sty`/`$json_cla`/`$json_fru`の計8件(`$src_prd`/`$src_mak`はラッパーで明示セット済みの
  ためWarningなし、予測どおり)。
- **状態**: PASS(GM固定)

#### TC-CORE-02: `$src_mak='yes'`

- **目的**: commonのみに存在する `$sql_where_mak` 使用分岐(実ページでは到達不可)を単体で踏む。
- **入力**: ラッパーが `$src_prd='no'; $src_mak='yes'; $sql_where_mak="MakerID='mk9002'";` をセット。
- **期待値**: `js_mak` = mk9002の1行のみ(`Beta Brewing`)。`js_prd`=`0`(products skip)。
  `js_sty`/`js_cla`/`js_fru`=`0`(未設定)。
- **実測との突合せ**: `tests/golden/TC-CORE-02.html`と完全一致。`js_mak`=mk9002(Beta Brewing)の
  1行のみ、他は`0`。commonのみに存在する`$src_mak=='yes'`分岐(実ページ到達不能)を単体で確認。
- **状態**: PASS(GM固定)

#### TC-CORE-03: `$src_cla='yes'` かつ `$src_fru='yes'`

- **目的**: commonのみで到達不能な clarity/fruity の 'yes' 分岐(実ページでは product.php が
  `php/sql.php` 側でしか 'yes' にしないため、common側は死んでいる)を単体で踏む。
- **入力**: ラッパーが `$src_prd='no'; $sql_where_cla="''=''"; $sql_where_fru="''=''"; $src_cla='yes'; $src_fru='yes';` をセット
  (`$src_mak` は未設定 → else 分岐、`$sql_where` も未設定なので合わせて `$sql_where="''=''"`もセットする)。
- **期待値**: `js_cla`=clarity全4行、`js_fru`=fruity全4行。`js_prd`=`0`、`js_mak`=maker全3行
  (`$sql_where="''=''"`のため)、`js_sty`=`0`(未設定)。
- **実測との突合せ**: `tests/golden/TC-CORE-03.html`と完全一致。`js_cla`=
  `[{"ClarityID":1,"ClarityValue":1,...},...]`(前提1のとおりINT列は非クオート数値)、
  `js_fru`も同様に全4行、`js_mak`=maker全3行、`js_prd`/`js_sty`=`0`。commonのみに存在する
  `$src_cla`/`$src_fru`='yes'分岐(実ページ到達不能)を単体で確認。
- **状態**: PASS(GM固定)

### 3.3 sql_POST系(INSERT/UPDATE の副作用検証)

いずれも「実行後のテーブル内容」を `SELECT` して検証する。各ケース実行前にフィクスチャ
(1.1〜1.5)を再投入すること。

#### TC-POST-THANK-NEW-01: `php/thank.php`(新規投稿, `new_update=new`)

- **目的**: `sql_POST.php` の `new` 分岐フル経路(採番・style参照・INSERT・グローバルUPDATE・
  画像rename)と、コロン欠けバインドの実挙動を検証する。
- **事前準備**: `tests/sandbox/img/tmp/upload_test.png` に空のダミーファイルを置く
  (置かない場合は rename が失敗しWarningが混入するが、DB検証には影響しない)。
- **入力**: POST `/php/thank.php`
  ```json
  {"value_http": "userid=us0001&productid=&makerid=mk9002&productname=Delta+Wheat&styleid=2&alcohol=5.10&ibu=25.00&color=5&clarity=3&fruity=2&favorite=4&new_update=new&productexplain=A+new+wheat+beer.&comment=First+tasting.",
   "image_name": "upload_test.png"}
  ```
  (TC-SEL-CHECK-NEW-01 の出力 `value_http` をそのまま利用)
- **期待値(仮説A: コロン欠けは実害なし。前提3参照)**:
  - `MAX(ProductID)=103` → `NEXT_ProductID=104`。
  - `SELECT IBU FROM style WHERE StyleID='2'` → `30.00` → `$IBU_Style=30.00`。
  - `products` に1行追加:
    `ProductID=104, MakerID='mk9002', ProductName='Delta Wheat', StyleID=2, Alcohol=5.10, IBU_all=25.00, IBU=25.00, Color=5, Clarity=3, Fruity=2, Favorite=4, ProductExplain='A new wheat beer.', IBU_Style=30.00, Comment='First tasting.'`
  - `UPDATE products SET IBU_all=(select IBU_Style) where IBU=0.000` が発火:
    - ProductID=102(`IBU=0.000`)の `IBU_all` が `99.99` → **`30.00`** に変わる(自身の`IBU_Style`)。
    - ProductID=101, 103, 104 は `IBU<>0.000` のため無変化。
  - `../img/tmp/upload_test.png` → `../img/product/104.png` に rename される。
  - `MAX(Rate_userID)=2` → `NEXT_Rate_userID=3`。
  - `rate_user` に1行追加:
    `Rate_userID=3, ProductID=104(=更新後の$value['productid']), UserID='us0001', Color_user=5, Clarity_user=3, Fruity_user=2, Favorite_user=4, New_Update='new', Comment='First tasting.'`
- **期待値(仮説B: コロン欠けでbindが失敗する場合。代替仮説)**:
  - `products` へのINSERTが `execute()` レベルで失敗し(戻り値`false`、例外は上がらない)、
    ProductID=104 の行自体が **作成されない**。後続の `UPDATE ... WHERE IBU=0.000` は
    `Alcohol`列を参照しないため独立して成功し得るが、104行が無いため対象は102のみ(同じ結果)。
    `rate_user` のINSERTも同様にコロン欠け(`Clarity_user`)により失敗し、行が追加されない。
  - この場合 `thank.php` の画面自体は(`$res`の戻り値を見ていないため)通常どおり
    「Thanks for submitting!」を表示し、**エラーは一切表面化しない**という点が重要。
- **要ゴールデンマスター確認**: 仮説A/Bどちらが実際の挙動か。products/rate_userを実際にSELECTして
  ProductID=104 / Rate_userID=3 の行の有無で判定できる。
- **実測との突合せ**: `tests/golden/TC-POST-THANK-NEW-01.db.txt`で **仮説A(コロン欠け実害なし)を
  確定**。実測結果:
  - `products`: 104行が `MakerID=mk9002, ProductName=Delta Wheat, StyleID=2, Alcohol=5.10,
    IBU_all=25.00, IBU=25.00, Color=5, Clarity=3, Fruity=2, Favorite=4.00,
    ProductExplain=A new wheat beer., IBU_Style=30.00, Comment=First tasting.` で正しく追加
    (`Alcohol`のコロン欠けも実害なし)。
  - `products` 102行の `IBU_all` が `99.99` → **`30.00`** に変化(前提4のグローバルUPDATE確定)。
    101/103/104行のIBU_allは無変化。
  - `rate_user`: 3行目が `ProductID=104, UserID=us0001, Color_user=5, Clarity_user=3,
    Fruity_user=2, Favorite_user=4, New_Update=new, Comment=First tasting.` で正しく追加
    (`Clarity_user`のコロン欠けも実害なし)。
  - `img/product/104.png` が作成されている(rename成功、Warningなし)。
  仮説Bは棄却。§0前提3・前提4、§5要確認事項3・4を確定。
- **状態**: PASS(GM固定)

#### TC-POST-THANK-UPDATE-01: `php/thank.php`(追加評価, `new_update=update`)

- **目的**: `new_update!='new'` の場合に products 側が一切変更されない(グローバルUPDATEも
  発火しない)ことを確認する対照ケース。
- **入力**: POST `/php/thank.php`
  ```json
  {"value_http": "userid=us0003&productid=101&makerid=mk9001&color=7&clarity=4&fruity=3&favorite=2&new_update=update&comment=Updated+take.",
   "image_name": ""}
  ```
  (TC-SEL-CHECK-UPDATE-01 の出力 `value_http` をそのまま利用)
- **期待値**:
  - `products` テーブルは**一切変更されない**(101/102/103とも初期値のまま。TC-POST-THANK-NEW-01と
    異なり `IBU_all=0.000` の102行に対するグローバルUPDATEも実行されない = このパスには
    そもそも到達しない、が特性上の要点)。
  - `MAX(Rate_userID)` はフィクスチャ時点の状態に依存する(このケース単独実行なら`2`→`NEXT=3`。
    TC-POST-THANK-NEW-01の後に連続実行するなら`3`→`NEXT=4`。**各ケース前にフィクスチャを
    再投入する**運用を前提とするため、本ケース単独では`NEXT_Rate_userID=3`とする)。
  - `rate_user` に1行追加: `Rate_userID=3, ProductID=101, UserID='us0003', Color_user=7,
    Clarity_user=4(コロン欠け、前提3参照), Fruity_user=3, Favorite_user=2, New_Update='update',
    Comment='Updated take.'`
  - rename() は呼ばれない(`new`分岐の外)。
- **実測との突合せ**: `tests/golden/TC-POST-THANK-UPDATE-01.db.txt`と完全一致。`products`は
  101/102/103とも完全に不変(102の`IBU_all`も`99.99`のまま=`new`分岐外ではグローバルUPDATEが
  一切発火しないことを確認)。`rate_user`に3行目が
  `ProductID=101, UserID=us0003, Color_user=7, Clarity_user=4(コロン欠けも実害なし), Fruity_user=3,
  Favorite_user=2, New_Update=update, Comment=Updated take.` で正しく追加。rename()は呼ばれず
  `img/product/`は空のまま。仮説A(コロン欠け実害なし)を再確認。
- **状態**: PASS(GM固定)

#### TC-POST-THANK-NEW-02: `post/check/thank/thank.php`(TC-POST-THANK-NEW-01と同一パラメータ)

- **目的**: `php/sql_POST.php` と `common/sql_POST.php` が byte-identical であることのDBレベルでの
  相互確認(実行主体のパスが違うだけで結果は一致するはず)。
- **入力**: TC-POST-THANK-NEW-01 と同一の `value_http`/`image_name`(フィクスチャは再投入した状態から
  開始)。POST `/post/check/thank/thank.php`。
- **期待値**: TC-POST-THANK-NEW-01 と**完全に同一のDB結果**(ProductID=104行、102のIBU_all=30.00、
  Rate_userID=3行)。
  画面のHTML差分としては、相対パスの違いのみ許容する
  (例: 「Back to Home」リンクが `../../../index.php` になる、CSSの相対パスが異なる、
  `common/header.php` の絶対相対位置が異なる 等)。DBに関わらない純粋な相対パス差以外の
  差分が出た場合は仕様不一致として報告する。
- **実測との突合せ(仕様不一致)**: `tests/golden/TC-POST-THANK-NEW-02.db.txt` は
  TC-POST-THANK-NEW-01 と **DBレベルでは完全に同一**(products 104行、102のIBU_all=30.00、
  rate_user 3行)であり、`common/sql_POST.php`と`php/sql_POST.php`がbyte-identicalであることの
  相互確認としては当初予測どおり成立した。
  しかし **HTML出力面で当初予測(「相対パスの違いのみ」)が誤りだった**ことが判明した:
  `tests/golden/TC-POST-THANK-NEW-02.html` にのみ
  `Warning: rename(../img/tmp/upload_test.png,../img/product/104.png): No such file or
  directory in .../common/sql_POST.php on line 40` が出力される(TC-POST-THANK-NEW-01には無い)。
  **原因**: `sql_POST.php`の`rename('../img/tmp/'.$image_name_http, '../img/product/'.$image_rename)`
  は「サイトルートから1階層上」を前提にした固定相対パスであり、実行時カレントディレクトリ
  (=エントリページの設置ディレクトリ)に依存する。`php/thank.php`(`php/`、サイトルートから
  1階層)から呼ばれる場合は`../img/tmp/`が正しくサイトルート直下の`img/tmp/`を指すが、
  `post/check/thank/thank.php`(`post/check/thank/`、3階層)から呼ばれる場合は`../img/tmp/`が
  `post/check/img/tmp/`を指してしまい存在しない。**これはテスト環境固有の問題ではなく、
  本番でも`post/check/thank/thank.php`経由(実際のUI導線そのもの)の新規投稿では画像リネームが
  常に失敗する、という実在のバグである**(DBへのINSERTは成功するため症状は「画像だけ反映されない」
  という気づきにくい形で現れる)。ゴールデンマスター方針(無改変コードの実挙動を正とする)に従い、
  この追加Warningを期待値に組み込んで確定する。DB副作用は完全一致のためsql_POST.php自体の同一性は
  維持されているが、「HTML差分は相対パスのみ」という当初の予測は誤りだったため、本ケースは
  「期待値と実装のズレ」ではなく「**台帳側の当初予測の誤り**」として仕様不一致に分類し、
  期待値を上記の実挙動に修正する。オーケストレーターへの報告事項として最終報告に含める。
- **状態**: 仕様不一致(期待値をGM側に修正・確定。DB副作用はPASS相当、HTML出力に
  ディレクトリ深さ依存のrename失敗Warningが追加で発生する点を新規に記録)

---

## 4. PHP8リスク まとめ(横断)

| 箇所 | 内容 | 影響 |
|---|---|---|
| `common/sql.php` / `php/sql.php` 全体 | 呼び出し元ページが `$src_prd`/`$src_mak`/`$src_sty`/`$src_cla`/`$src_fru`/`$sql_where_*` を
  設定しない場合、比較式評価のたびに `Undefined variable` Warning | 非致命的。ほぼ全SELECT系ページで複数件発生(TC個別に記載) |
| `common/sql.php` / `php/sql.php` の `$json_*` | 対応する `$src_*` がスキップ分岐だと `$json_*` が代入されず、`<script>`内の`if($json_*)`参照で `Undefined variable` Warning | 非致命的。`js_*=0`側に落ちる |
| `index.php` L66・L101 のバックワードループ (`count-1` 〜 `count-7`) | フィクスチャ行数(3)が想定行数(7以上)を下回ると負インデックス参照 → `Undefined array key` Warning | products側は非致命的(空データ表示)。**maker側は`require()`にそのまま使われるため FATAL ERROR に発展**(TC-SEL-IDX-01) |
| `php/evaluation.php` | `$product_id`・`$keyIndex_mak` が未定義のまま使用 | `Undefined variable` ×2、`Undefined array key ""` ×1、`nl2br(null)` の **Deprecated**(PHP8.1+) |
| `php/add_product.php` / `post/add_product.php` | `$product_id` 未定義のままhidden inputへecho | `Undefined variable` Warning |
| `php/list_maker.php` / `php/list_style.php` | `$_GET['product_id']` 未指定 | `Undefined array key "product_id"` Warning |
| `php/product.php` | `$_GET['comment']` 未指定 | `Undefined array key "comment"` Warning |
| `post/check/check.php` | `$_FILES['upimg']` がハーネス未対応で常に未設定 | `Undefined array key "upimg"` Warning ×2 + `Trying to access array offset on null` ×2 + `move_uploaded_file()` の `Deprecated`(ハーネス制約) |
| `common/sql_POST.php` の `rename('../img/tmp/'.., '../img/product/'..)` (**PHP8リスクというよりロジックバグ、実測で新規発見**) | 相対パスがサイトルートから1階層上固定。エントリページの設置階層に依存するため、`php/thank.php`(1階層)からは成功するが `post/check/thank/thank.php`(3階層)からは失敗する | `Warning: rename(...): No such file or directory`(非致命的・DBには影響しないが、実運用の投稿画面(`post/check/thank/thank.php`)経由では**新規投稿の画像が常にリネームされない**という実害のあるバグ。TC-POST-THANK-NEW-02で実測確認。別課題として記録推奨) |

いずれも「出力されるHTMLを変えない」というスコープ上の不変条件においては、
**Warning/Deprecationの文言自体もHTML出力に混入する文字列としてゴールデンマスターの一部**である
(PHP8.5移行時にこれらの警告を握りつぶす/修正するなら、それは「別課題」または明示的な仕様変更として
扱う必要がある)。

---

## 5. 要ゴールデンマスター確認 一覧 —【全項目 実測により確定済み】

1. **PDOの型変換**(前提1) — **確定**: INT型カラム(ProductID/Rate_userID/ClarityID/ClarityValue/
   FruityID/FruityValue)のみPHPネイティブint(JSON非クオート数値)、それ以外
   (VARCHAR/DECIMAL/TEXT)は全て文字列(JSONダブルクオート付き)。`TC-CORE-03`/`TC-SEL-PRD-01`等で確認。
2. **SELECTの行順序**(前提2) — **確定**: 全ケースでフィクスチャのINSERT順どおりに返却されることを確認
   (このMariaDB環境・データ量では投入順=返却順)。
3. **バインドパラメータのコロン欠けの実害**(前提3・最重要) — **確定(仮説A)**:
   `'Alcohol'`/`'Clarity_user'`(コロン無し)は `:Alcohol`/`:Clarity_user` プレースホルダに
   **正しくバインドされる**(実害なし)。`TC-POST-THANK-NEW-01`のDB実測(products 104行の
   Alcohol=5.10、rate_user 3行目のClarity_user=3)で確定。仮説B(バインド失敗・無INSERT)は棄却。
4. **`UPDATE ... SET col=(select col2)`(FROM無し相関サブクエリ)の実行可否**(前提4) — **確定**:
   MariaDBはこの構文を自己代入(`SET IBU_all = IBU_Style`相当)として正しく実行する。
   `TC-POST-THANK-NEW-01`実測(102の`IBU_all`が`99.99`→`30.00`)で確定。
5. **TC-SEL-IDX-01**: index.php の maker ループが負インデックスに達した際の fatal error — **確定**:
   `Warning: require(brewery/detail/explain/.html): Failed to open stream: No such file or
   directory` に続き `Fatal error: Uncaught Error: Failed opening required
   'brewery/detail/explain/.html' ...` + スタックトレース。出力バッファリングは無く、fatal直前
   までの出力(head内script・products一覧・maker一覧有効3件+4件目途中)はそのままflushされる。
6. **TC-SEL-POSTPRD-01**: `"MakerName like"."'%".$serch."%'"` のように `LIKE` とクオートの間に
   空白が無い連結 — **確定**: MariaDBで構文エラーにならず正しく `LIKE '%Beta%'` として解釈される
   (実測でmk9002=Beta Brewingが正しく1件ヒット)。

**実測により新規発見した追加の要確認事項(解消済み)**:
7. `TC-POST-THANK-NEW-02`実測により、`common/sql_POST.php`の`rename()`が相対パス
   (`../img/tmp/`)固定であるため、エントリページの設置階層(post/check/thank/ = 3階層)次第で
   リネームに失敗するという**実運用に影響するバグ**を新規発見(§4参照)。DB副作用には影響しないが、
   本番の投稿導線(`post/check/thank/thank.php`)経由では新規投稿の画像が反映されない。

---

## 6. カバレッジ集計・実行結果

- 総テストケース数: **19件**(`tests/runner/cases/*.json` 実ファイル数と一致。
  台帳初版時点の「17件」は集計ミスであり本改訂で訂正)。
  内訳: SELECT系13件(IDX-01, PRD-01, MAK-01, MAKERS-01, EVAL-01, LMAK-01, LSTY-01, ADDP-01,
  POSTADDP-01, POSTEVAL-01, POSTPRD-01, CHECK-NEW-01, CHECK-UPDATE-01)+
  common死分岐直接テスト3件(CORE-01/02/03)+ sql_POST系3件(THANK-NEW-01, THANK-UPDATE-01,
  THANK-NEW-02)。
- `common/sql.php`: `$src_prd`(2分岐)/`$src_mak`(3分岐)/`$src_sty`(2分岐)/`$src_cla`(2分岐)/`$src_fru`(2分岐)
  の全11分岐を実ページ4件(TC-SEL-POSTADDP-01, TC-SEL-POSTEVAL-01, TC-SEL-CHECK-NEW-01,
  TC-SEL-CHECK-UPDATE-01)+ 直接require 3件(TC-CORE-01/02/03)でカバー。全件実行しPASS。
- `php/sql.php`: 差分2箇所(maker分岐条件・style抽出フィールド数)を含む全分岐を実ページ8件で
  カバー(直接requireは不要)。特に **TC-SEL-POSTPRD-01** が両ファイルの分岐差分そのものを
  最も直接的に検証するケースであり、実測でも分岐差分どおりの結果を確認(PASS)。
- `sql_POST.php`(common/php同一): `new`/`update` の2分岐を3ケースでカバー
  (`new`側2ケースで common版・php版の相互確認込み)。DB副作用は3ケースとも予測どおり
  (PASS/PASS/仕様不一致=HTML側のrenameパスバグを新規発見)。
- **結果集計**: PASS(GM固定) 18件 / 仕様不一致 1件(TC-POST-THANK-NEW-02、DB副作用はPASS相当・
  HTML出力面の当初予測の誤りを修正) / FAIL 0件。

---

## 7. 実行結果まとめ

**再現コマンド**:
```bash
cd /workspace/tool/beer
bash tests/runner/start_db.sh   # MariaDB起動(コンテナ再起動後は毎回必要)
bash tests/runner/run_all.sh    # 全19ケース実行 → tests/out/ に出力
diff -rq tests/out tests/golden # 差分ゼロを確認(再現性の確認)
```
実行結果: `tests/out/` と `tests/golden/` は全19ケース(html/exit/params.json/db.txt)で
byte-identical(`diff -rq`差分ゼロ)。再現性を確認済み。

### 7.1 結果サマリ表

| ケースID | 結果 | 一言 |
|---|---|---|
| TC-SEL-IDX-01 | PASS(GM固定) | 負インデックス→maker側でFATAL、予測どおり(exit=255) |
| TC-SEL-PRD-01 | PASS(GM固定) | keyIndex計算・表示値すべて予測どおり |
| TC-SEL-MAK-01 | PASS(GM固定) | products/maker各1行、Warning8件確定 |
| TC-SEL-MAKERS-01 | PASS(GM固定、期待値補完) | `comment`未定義Warningを追加確認 |
| TC-SEL-EVAL-01 | PASS(GM固定、期待値補完) | null配列オフセットのDeprecatedを追加確認 |
| TC-SEL-LMAK-01 | PASS(GM固定) | 予測どおり |
| TC-SEL-LSTY-01 | PASS(GM固定) | 予測どおり |
| TC-SEL-ADDP-01 | PASS(GM固定) | 予測どおり |
| TC-SEL-POSTADDP-01 | PASS(GM固定) | 5フィールド版style scriptを確認(php/sql.php版との差分確定) |
| TC-SEL-POSTEVAL-01 | PASS(GM固定) | 予測どおり |
| TC-SEL-POSTPRD-01 | PASS(GM固定) | 分岐差分ケース、LIKE空白無し構文も問題なし |
| TC-SEL-CHECK-NEW-01 | PASS(GM固定、Warning文言確定) | value_http完全一致、$_FILES関連Warning詳細化 |
| TC-SEL-CHECK-UPDATE-01 | PASS(GM固定) | 予測どおり |
| TC-CORE-01 | PASS(GM固定) | commonの`$src_mak='no'`等の死分岐を確認 |
| TC-CORE-02 | PASS(GM固定) | commonの`$src_mak='yes'`死分岐を確認 |
| TC-CORE-03 | PASS(GM固定) | commonの`$src_cla`/`$src_fru`='yes'死分岐を確認 |
| TC-POST-THANK-NEW-01 | PASS(GM固定) | コロン欠け実害なし・グローバルUPDATE発火、両方確定 |
| TC-POST-THANK-UPDATE-01 | PASS(GM固定) | products完全不変、rate_userのみ追加 |
| TC-POST-THANK-NEW-02 | **仕様不一致**(期待値修正) | DB結果は同一だがrename()がディレクトリ深さで失敗する実バグを新規発見 |

### 7.2 仕様不一致の詳細

**TC-POST-THANK-NEW-02**(`post/check/thank/thank.php`、`new_update=new`):
- **入力**: TC-POST-THANK-NEW-01と同一の `value_http`/`image_name=upload_test.png`。
- **仕様上の期待値(台帳初版)**: 「`common/sql_POST.php`と`php/sql_POST.php`はbyte-identicalなので、
  TC-POST-THANK-NEW-01とDBレベルで完全に同一の結果になる。HTML差分は相対パス(index.phpリンク等)
  のみ許容する」。
- **実際の値**: DBレベルの結果はTC-POST-THANK-NEW-01と完全一致(products 104行、102のIBU_all=
  30.00、rate_user 3行)。しかしHTML出力に `Warning: rename(../img/tmp/upload_test.png,
  ../img/product/104.png): No such file or directory in .../common/sql_POST.php on line 40`
  が追加で出力される(TC-POST-THANK-NEW-01には無い)。
- **差の分析**: `sql_POST.php`の`rename('../img/tmp/'.., '../img/product/'..)`は実行時カレント
  ディレクトリに依存する固定相対パス。`php/thank.php`(サイトルートから1階層)からは
  `../img/tmp/`が正しくルート直下`img/tmp/`を指すが、`post/check/thank/thank.php`(3階層)からは
  `post/check/img/tmp/`を指してしまい存在しない。**同一コード(sql_POST.php)が、呼び出し元ページの
  ディレクトリ階層によって異なる副作用(画像リネームの成否)を持つ**という、テスト設計時には
  想定していなかった実挙動。台帳の「HTML差分は相対パスのみ」という予測が不正確だったための
  仕様不一致であり、実装のバグではなく**台帳(期待値)側の誤りとして訂正**した
  (§0前提・各ケースの実測との突合せ欄に反映済み)。DBへの影響はないため実害は「画像が反映されない」
  というUI上の不具合にとどまるが、`post/check/thank/thank.php`が実際の投稿導線であることを踏まえると
  実運用上のバグとして扱う価値があり、オーケストレーターへの申し送り事項とする
  (loop-scope.mdの「別課題として記録するもの」への追加を提案)。
