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
6. **PDOのデフォルトエラーモード — 2026-08-22追加、実測で確定**: `common/sql.php`・`php/sql.php`
   はいずれも `new PDO($db_dsn, $db_user, $db_pass)` のように接続オプション配列を渡していない。
   PHP公式のRFC「Saner PDO defaults」によりPHP 8.0以降、オプション未指定時の
   `PDO::ATTR_ERRMODE` の既定値は **`PDO::ERRMODE_EXCEPTION`**(PHP 7以前の既定だった
   `PDO::ERRMODE_SILENT` ではない)。この環境(PHP 8.5.9)で
   `$dbh->getAttribute(PDO::ATTR_ERRMODE)` を直接確認したところ `2`(=EXCEPTION)であることを
   実測で確認した(`tests/`配下は変更していない、スクラッチパッド上の使い捨てスクリプトによる確認)。
   **これは本台帳が過去に「デフォルトのERRMODE_SILENT下でexecute()が黙ってfalseを返す」等と
   記載していた箇所の前提が不正確だったことを意味する**(該当のTC-POST-THANK-NEW-01/UPDATE-01の
   「仮説B」の記述はコロン欠けバインドが失敗した場合に何が起きるかの説明として書いたものだが、
   実際にはSILENTモードでの「静かな失敗」ではなく、EXCEPTIONモードでの
   「即座にFatal error(未捕捉PDOException)」が正しい帰結だったはずである。結論(仮説A確定・
   コロン欠けは実害なし)自体は実測で覆っていないため訂正不要だが、棄却された仮説Bのメカニズム
   描写は不正確だった点をここに記録する)。
   このデフォルト値は、**存在しないカラムへの参照や壊れたSQLを実行するページ**
   (TC-SEL-STRATHCONA-01: `products`に無い`BREWERY`列を参照)の期待値算出に直接影響する:
   `$dbh->query()` 自体が **`PDOException`を送出**し(`$res`に`false`が入ってから`fetchAll()`で
   `TypeError`になるのではない)、`try/catch`が無いためそのまま **Fatal error: Uncaught
   PDOException** として現れる。実測で `SQLSTATE[42S22]: Column not found: 1054 Unknown column
   'BREWERY' in 'WHERE'` というメッセージ、および `php/sql.php` の `$dbh->query($query);` の行
   (改修1適用後のオフセットで23行目)で例外が投げられることを確認した。

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

### 2.1 `common/sql.php` — 【2026-08-22 訂正】検証者(B)指摘1により9ページを追加調査した結果、
以前「死んでいる分岐」としていた3分岐(`$src_mak=='no'`、`$src_cla=='yes'`、`$src_fru=='yes'`)は
**実際には実ページ経由で到達する**ことが判明した。以下は訂正後の対応表。

| 分岐 | 到達させるページ/ケース | 備考 |
|---|---|---|
| `$src_prd=='no'`(products SELECT スキップ) | TC-CORE-01(直接require) | 実ページでは到達不可(常に不設定 or else側)。**訂正なし、引き続き死分岐** |
| `$src_prd` else(products 全件) | TC-SEL-POSTADDP-01, TC-SEL-POSTEVAL-01, TC-SEL-CHECK-*, TC-CORE-02/03, 新規9ページ全件 | 実ページで到達 |
| `$src_mak=='no'` | **TC-SEL-STYDETAIL-01**(`style/detail/style.php`が明示的に`$src_mak="no"`をセットする)、TC-CORE-01 | ~~実ページでは一切設定されない(死んでいる分岐)~~ **【訂正】生きている分岐**。`style/detail/style.php`がスタイル詳細ページでmaker一覧を使わないため明示的にスキップしている。TC-CORE-01は最小フィクスチャでの単体確認として引き続き有用(§2.4参照) |
| `$src_mak=='yes'`(`$sql_where_mak`使用) | TC-CORE-02 | 実ページでは一切設定されない(死んでいる分岐。9ページ追加調査後も確認先なし) |
| `$src_mak` else(`$sql_where`使用) | TC-SEL-POSTADDP-01, TC-SEL-POSTEVAL-01, TC-SEL-CHECK-*, TC-SEL-BRWMAKERS-01, TC-SEL-BRWMAKER-01, TC-SEL-BEERDETAIL-01, TC-SEL-STYDETAILMAKERS-01(到達前にfatal) | 実ページで到達 |
| `$src_sty=='yes'` | TC-SEL-POSTADDP-01, TC-SEL-POSTEVAL-01, TC-SEL-CHECK-*, TC-SEL-STYSTYLES-01, TC-SEL-STYDETAIL-01 | 実ページで到達 |
| `$src_sty` スキップ | TC-CORE-01, TC-SEL-BRWMAKERS-01, TC-SEL-BRWMAKER-01, TC-SEL-BEERPRODUCTS-01 | 実ページで到達(訂正なし、元から生きていた) |
| `$src_cla=='yes'` | **TC-SEL-BEERDETAIL-01**(`beer/detail/product.php`が明示的に`$src_cla="yes"`をセットする)、TC-CORE-03 | ~~実ページでは一切設定されない(死んでいる分岐)~~ **【訂正】生きている分岐**。`beer/detail/product.php`(商品詳細ページ)が透明度画像・名称表示のために使用。TC-CORE-03は最小フィクスチャでの単体確認として引き続き有用(§2.4参照) |
| `$src_cla` スキップ | TC-SEL-POSTADDP-01 等(未設定のため自動的に該当) | 実ページで到達 |
| `$src_fru=='yes'` | **TC-SEL-BEERDETAIL-01**(同上、`$src_fru="yes"`も同時にセット)、TC-CORE-03 | ~~実ページでは一切設定されない(死んでいる分岐)~~ **【訂正】生きている分岐**。同上 |
| `$src_fru` スキップ | 同上 | 実ページで到達 |

→ 9ページ追加調査の結果、**`common/sql.php`の11分岐のうち「実ページで到達不可能な死分岐」は
`$src_prd=='no'` と `$src_mak=='yes'` の2つのみ**に訂正される(以前は5分岐を死分岐としていたが
誤りだった)。TC-CORE-01〜03 は元々「死分岐を単体で踏むための直接requireラッパー」として設計したが、
今回の訂正により **TC-CORE-01(`$src_mak='no'`)と TC-CORE-03(`$src_cla`/`$src_fru='yes'`)は
実ページ経路が別途存在する分岐の追加ユニットテストという位置づけに変わる**
(実ページ側は複数フィクスチャ行が絡む分、期待値計算が複雑になるため、TC-CORE-01/03の
最小フィクスチャでの単体確認としての価値は引き続き高い。回帰時の切り分けにも有効なため
削除はしない)。**TC-CORE-02(`$src_mak='yes'`)のみが真に死分岐単体テストとして残る**。

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

### 2.4 共有SQL層をrequireする未テストページ9件(検証者(B)指摘1、2026-08-22追加)

検証者(B)から「共有SQL層(`common/sql.php`・`php/sql.php`)をrequireするページのうち9件が
台帳に未収録」との指摘を受け、オーケストレーターの構造調査をもとに実コードを読み、
sandbox(`beer/`ディレクトリのコピーが追加済み)上で実行して確認した。結果は以下の3系統に分かれる:

| ページ | 使用するSQL層 | 分岐上の位置づけ | 追加ケース |
|---|---|---|---|
| `brewery/makers.php` | `common/sql.php` | `$src_mak`else / `$src_sty`スキップ(既存カバー分岐の別ページからの再確認) | TC-SEL-BRWMAKERS-01 |
| `brewery/detail/maker.php` | `common/sql.php` | 同上。`$sql_where`をproducts/maker両方に使い回す(`php/maker.php`と同型のイディオム) | TC-SEL-BRWMAKER-01 |
| `beer/products.php` | `common/sql.php` | 同上 | TC-SEL-BEERPRODUCTS-01 |
| `beer/detail/product.php` | `common/sql.php` | **`$src_cla='yes'`/`$src_fru='yes'`を初めて実ページ経由で踏む(§2.1訂正の根拠ページ)** | TC-SEL-BEERDETAIL-01 |
| `style/styles.php` | `common/sql.php` | `$src_sty='yes'`(既存カバー分岐の別ページからの再確認) | TC-SEL-STYSTYLES-01 |
| `style/detail/makers.php` | `common/sql.php`(到達前にfatal) | **壊れたページ(相対パスバグ)。分岐到達より前に`require()`が失敗** | TC-SEL-STYDETAILMAKERS-01 |
| `style/detail/style.php` | `common/sql.php` | **`$src_mak='no'`を初めて実ページ経由で踏む(§2.1訂正の根拠ページ)** | TC-SEL-STYDETAIL-01 |
| `php/check.php` | `php/sql.php`(**post/check/check.phpはcommon/sql.php**) | 既存カバー分岐の再確認だが、`post/check/check.php`との**分岐差分(common版 vs php版)を直接対比できる唯一のcheck.php系ペア** | TC-SEL-PHPCHECK-NEW-01 |
| `php/Strathcona_Beer_Company.php` | `php/sql.php`(到達前にfatal) | **壊れたページ(存在しないカラムへのSQL参照)。分岐到達より前にPDOExceptionでfatal** | TC-SEL-STRATHCONA-01 |

このうち `beer/detail/product.php` と `style/detail/style.php` の2ページは、
**§2.1で「死んでいる分岐」としていた記述を訂正する直接の根拠**になっている
(常設のGET/POSTページとして実際にこれらの分岐へ到達することが実測で確認できたため)。
残り7ページは主に**既存分岐の別ルートからの再確認**、または**独立した「壊れたページ」の
特性固定**が目的であり、新たな分岐は開拓しないが、「共有SQL層をrequireする全ページを
台帳が把握している」という検証者(B)の懸念には直接応える。

ケース設計の詳細は §3.4、期待値計算の前提(PDOのデフォルトエラーモード)は §0 前提6を参照。

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
- **改修後(2026-08-22, commit 37c70b3/18ff215)の再突合せ**: `tests/golden`比較で差分は
  カテゴリ(a)のみ(`common/sql.php`由来の`Undefined variable`警告ブロック計7件が消失。
  `$src_prd`/`$src_mak`x2/`$src_cla`/`$src_fru`の5件と、`$json_cla`/`$json_fru`の2件)。
  §8参照。fatal error本体・その直前までの出力・DB非該当ケースの結論に変化なし。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`php/sql.php`由来の`$src_prd`x2の
  `Undefined variable`警告2件が消失)。`Undefined array key "comment"`は`product.php`自身の
  警告でありsql.php層とは無関係のため変化なし(改修2の対象外)。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ。上記8件の`Undefined variable`/`Undefined variable`
  ($json_*)警告が全て消失し、他の出力(products/maker各1行、sec2ループ1件)は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`php/sql.php`由来の`$src_prd`x2/`$src_sty`/
  `$src_cla`/`$src_fru`(計5件)+`$json_sty`/`$json_cla`/`$json_fru`(計3件)= **8件**が消失、
  TC-SEL-MAK-01と同型)。`$_GET['comment']`未定義Warning(product.php層とは
  無関係のmakers.php自身の警告)は改修2の対象外のため不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`php/sql.php`由来の`$src_prd`x2/`$src_cla`/
  `$src_fru`(計4件)+`$json_cla`/`$json_fru`(計2件)= **6件**が消失)。`evaluation.php`自身の
  `$keyIndex_mak`/`$product_id`未定義Warningと`Deprecated`群(改修2の対象外、対象は
  `common/sql.php`・`php/sql.php`のみ)は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`php/sql.php`由来の`$src_prd`x2/`$src_sty`/
  `$src_cla`/`$src_fru`(計5件)+`$json_sty`/`$json_cla`/`$json_fru`(計3件)= **8件**が消失、
  TC-SEL-MAK-01と同型)。`product_id`未定義Warning(list_maker.php自身)は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

#### TC-SEL-LSTY-01: `php/list_style.php`

- **目的**: TC-SEL-LMAK-01のstyle版。`$src_sty='yes'` 込みの取得確認。
- **入力**: GET `/php/list_style.php`(パラメータ無し)
- **期待値**:
  - `js_prd`=全3行、`js_sty`=全3行(maker取得も実行されるが`js_mak`はページで未使用。値は全3行)。
  - **PHP8リスク**: `$_GET['product_id']` 未指定と同様の Warning(list_maker.phpと同型)。
  - チェックボックスフォーム: `chk_style` の value に 1/2/3、ラベルに IPA/Pilsner/Stout が3件分描画。
- **実測との突合せ**: `tests/golden/TC-SEL-LSTY-01.html`と完全一致(1/2/3×StyleName、
  `Undefined array key "product_id"` Warning 1件)。
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(list_style.phpは`$src_sty='yes'`を設定しているため
  style分岐自体は実行される。`php/sql.php`由来の`$src_prd`x2/`$src_cla`/`$src_fru`(計4件)+
  `$json_cla`/`$json_fru`(計2件)= **6件**が消失)。`product_id`未定義Warning(list_style.php自身)は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(上記6件の`php/sql.php`由来Warningが全て消失)。
  `product_id`未定義Warning(add_product.php自身)は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`common/sql.php`由来の`$src_prd`/`$src_mak`x2/
  `$src_cla`/`$src_fru`/`$json_cla`/`$json_fru`のWarning計7件が消失)。5フィールド版style script・
  `product_id`未定義Warning(add_product.php自身)は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

#### TC-SEL-POSTEVAL-01: `post/evaluation.php`(`common/sql.php` 経由)

- **目的**: `post/evaluation.php` は `php/evaluation.php` と違い、正しくロジックが実装されている
  (`$_GET['serch']` は評価ページ内で読まれないが、`$sql_where`/`$sql_where_sty`/`$src_sty='yes'` を
  設定したうえで通常表示するだけの静的フォーム)。common/sql.php 版の基本経路確認。
- **入力**: GET `/post/evaluation.php`(パラメータ無し)
- **期待値**: `js_prd`/`js_mak`/`js_sty`=全件(5フィールドstyle)、`js_cla`/`js_fru`=`0`。
  `$product_id` 未定義Warning(hidden ProductIDが空)。TC-SEL-POSTADDP-01と同型。
- **実測との突合せ**: `tests/golden/TC-SEL-POSTEVAL-01.html`と完全一致(5フィールド版styleスクリプト、
  `$product_id`未定義Warningを含めTC-SEL-POSTADDP-01と同型のWarningパターン)。
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`common/sql.php`由来のWarning計7件が消失、
  TC-SEL-POSTADDP-01と同型)。`product_id`未定義Warningは不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`php/sql.php`由来の`$src_sty`/`$src_cla`/`$src_fru`
  + `$json_prd`/`$json_sty`/`$json_cla`/`$json_fru`のWarning計7件が消失)。`js_mak`=Beta Brewing
  1行のみ・`print_r`出力は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`common/sql.php`由来の`$src_prd`/`$src_mak`x2/
  `$src_cla`/`$src_fru`/`$json_cla`/`$json_fru`のWarning計7件が消失)。画面内容・`value_http`・
  `$_FILES`関連Warning群(post/check/check.php自身、改修2の対象外)は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`common/sql.php`由来のWarning計7件が消失、
  TC-SEL-CHECK-NEW-01と同型)。画面内容・`value_http`は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`common/sql.php`由来の`$src_sty`/`$src_cla`/
  `$src_fru`+`$json_prd`/`$json_mak`/`$json_sty`/`$json_cla`/`$json_fru`のWarning計8件が消失、
  `$src_prd`/`$src_mak`はラッパーで明示セット済みのため元々Warningなし)。`js_*=0`の内容は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

#### TC-CORE-02: `$src_mak='yes'`

- **目的**: commonのみに存在する `$sql_where_mak` 使用分岐(実ページでは到達不可)を単体で踏む。
- **入力**: ラッパーが `$src_prd='no'; $src_mak='yes'; $sql_where_mak="MakerID='mk9002'";` をセット。
- **期待値**: `js_mak` = mk9002の1行のみ(`Beta Brewing`)。`js_prd`=`0`(products skip)。
  `js_sty`/`js_cla`/`js_fru`=`0`(未設定)。
- **実測との突合せ**: `tests/golden/TC-CORE-02.html`と完全一致。`js_mak`=mk9002(Beta Brewing)の
  1行のみ、他は`0`。commonのみに存在する`$src_mak=='yes'`分岐(実ページ到達不能)を単体で確認。
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`$src_sty`/`$src_cla`/`$src_fru`(計3件)+`$json_prd`/
  `$json_sty`/`$json_cla`/`$json_fru`(計4件)= **7件**が消失。`$src_mak`は当ケースでラッパーが
  明示的に`'yes'`をセットするためWarning対象外、`$json_mak`もmk9002の1行が定義されるためWarning
  対象外)。`js_mak`=mk9002のみの内容は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: 差分はカテゴリ(a)のみ(`$src_mak`x2/`$src_sty`(計3件)+`$json_prd`/
  `$json_sty`(計2件)= **5件**が消失。`$src_cla`/`$src_fru`はラッパーが明示セット済みのため対象外、
  `$json_cla`/`$json_fru`も全4行が定義されるため対象外)。`js_cla`/`js_fru`全4行・`js_mak`全3行の
  内容は不変。§8参照。
- **状態**: PASS(改修後、差分は記録済みの2種のみ)

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
- **改修後の再突合せ**: `tests/out/TC-POST-THANK-NEW-01.html`/`.db.txt`/`.exit`は
  `tests/golden`と**完全に一致(差分ゼロ)**。`php/thank.php`→`common/sql_POST.php`の経路には
  sql.php由来のUndefined variable警告もrename警告も含まれないため、カテゴリ(a)(b)いずれの
  対象にもならない(改修1のrequire_once追加はこのファイルの行番号を+1シフトさせるが、
  当ケースの出力にはWarning行が元々無いため観測されるHTML上の差は生じない)。§8参照。
- **状態**: PASS(改修後、差分なし)

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
- **改修後の再突合せ**: `tests/out/TC-POST-THANK-UPDATE-01.html`/`.db.txt`/`.exit`は
  `tests/golden`と**完全に一致(差分ゼロ)**。TC-POST-THANK-NEW-01と同じ理由でカテゴリ(a)(b)
  いずれの対象にもならない。§8参照。
- **状態**: PASS(改修後、差分なし)

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
- **改修後の再突合せ**: 差分は**カテゴリ(b)のみ**(このケースはsql.php層を経由しないため
  カテゴリ(a)は非該当)。`common/sql_POST.php` L40の`rename()`失敗Warningが、改修1で同ファイル
  先頭に`require_once dirname(__FILE__).'/../db_config.local.php';`が1行追加された影響で
  **L41にシフト**した(`tests/golden`は`on line 40`、`tests/out`は`on line 41`。それ以外は
  1バイトも違わない)。rename失敗という事象自体・その原因(ディレクトリ深さ依存の固定相対パス)・
  DB副作用が完全一致であることに変化はなく、単なる行番号シフトであるため
  「挙動保存(behavior-preserving)」の範囲内と判断する。§8参照。
- **【2026-08-22 最終確定】修正1(commit db02f69)によりrename()の実装がcwd依存の相対パスから
  `dirname(__FILE__)` 基準の絶対パス(§11.2参照)に変更され、このケースが特性固定していた
  バグ自体が**意図的に修正**された。`bash tests/runner/run_all.sh` 再実行で独立確認
  (`tests/golden`との差分はこのケースの`.html`/`.db.txt`のみ、他25ケースは差分ゼロ):
  - `tests/out/TC-POST-THANK-NEW-02.html` から `Warning: rename(...): No such file or
    directory ...` の2行(Warning本文+空行)が消失。
  - `tests/out/TC-POST-THANK-NEW-02.db.txt` の `== img/product ==` セクションに
    `104.png` が追加された(修正前は空だった)。
  - products/rate_userの内容(104行追加・102のIBU_all=30.00等)・exitコード(0)は完全に不変。
  - **期待値を修正後の挙動に更新する**: `post/check/thank/thank.php`経由でも`php/thank.php`と
    完全に同じ結果になる(rename成功・`img/product/104.png`生成・Warningなし)。
    これにより本ケースの元々の設計意図(「commonとphpのsql_POST.phpがbyte-identicalなら
    同一パラメータで同一結果になるはず」という相互確認)が、**HTML出力面でも真に成立する**
    ようになった(旧仕様不一致は「意図しないバグ」に起因していたため、修正後は台帳の当初の
    期待値どおりに戻った形)。
  - オーケストレーターがこの新しい出力を`tests/golden/`に再ベースラインする予定
    (本メッセージ時点ではまだ`tests/golden/TC-POST-THANK-NEW-02.*`は旧バグ入りの内容のまま
    残っているため、`tests/out`との差分としてここに記録した。再ベースライン後は差分ゼロになる
    見込み)。
- **状態**: PASS(修正済み)。旧「仕様不一致」は解消(§11.2参照。rename成功・104.png生成・
  Warningなしを確認。goldenの再ベースラインはオーケストレーター側で対応予定)

### 3.4 追加ケース(検証者(B)指摘1、2026-08-22追加) — 共有SQL層をrequireする未テストページ9件

**設計方針**:
- 期待値は従来どおり §1 のフィクスチャ + 各ページの実コード(本セクションの根拠として引用する
  行番号・変数名は現在の `tests/sandbox/` = 改修1・改修2適用後のコード)から手計算する。
- **ゴールデンマスターは「改修後コード」を基準として新規に確定する**(sql層の改修1・改修2は
  §8で既に挙動保存を確認済みのため、これらの新規9ページについては改修前コードとの突き合わせは
  行わない。今回が初回のゴールデンマスター取得になる)。
- 各ケースの期待値は、フィクスチャ・コード契約からの手計算に加えて、`tests/runner/exec_page.php`
  (オーケストレーターの既存ハーネスと同一のランナー)を用いて `tests/fixtures/fixtures.sql`
  投入直後の状態で**設計時の一次確認**を行った(このセッション内で `mysql beer <
  tests/fixtures/fixtures.sql` → `php tests/runner/exec_page.php <page> <params.json>` を実行し、
  出力を目視で手計算値と突き合わせた。tests/golden・tests/sandboxはこの確認のために変更していない、
  読み取り専用の実行のみ)。**ただしこれは正式なゴールデンマスター取得ではない**
  (ケースJSON・`tests/golden/`への格納はオーケストレーターの担当)。状態は全件「未実行」のまま
  とし、一次確認で得た値を期待値として記載する。
  **【2026-08-22 追記】オーケストレーターがケースJSON化・ゴールデンマスター取得を完了
  (commit 0a2a7f2)。以下9ケースの「実測との突合せ」欄・状態欄は全て確定済み(§9.3参照)。
  9件全てで一次確認時の期待値と正式なゴールデンマスターが完全一致した(想定外の差分ゼロ)。**
- 対象ページの中で分岐が生きていることが判明したページ(`beer/detail/product.php`,
  `style/detail/style.php`)は §2.1 の訂正と対応させてある。

**必要な追加sandbox/フィクスチャ(オーケストレーターへの依頼事項)**:
- `beer/` ディレクトリのsandboxコピー: `tests/runner/make_sandbox.sh` は**確認時点で既に
  `beer` が対象ディレクトリループに含まれており**(`for d in common css js php post style
  brewery beer bk_html`)、`bash tests/runner/make_sandbox.sh` 実行で `tests/sandbox/beer/
  products.php` ・ `tests/sandbox/beer/detail/product.php` が正しくコピーされることを確認した。
  追加対応は不要(オーケストレーターの当初メッセージでは「未コピー」とのことだったが、
  設計時点では既に反映されていた)。
- `style/detail/`・`brewery/`・`php/check.php`・`php/Strathcona_Beer_Company.php` は
  いずれも既存の `make_sandbox.sh` (`style`/`brewery`/`php` を丸ごとコピー)で既にsandboxに
  含まれていることを確認済み。追加コピー不要。
- ダミーファイルの追加は不要: `brewery/detail/explain/mk9001.html`〜`mk9003.html` は既存の
  index.php向けダミー(`make_sandbox.sh`が生成)をそのまま流用できる。
- 新規テーブル・フィクスチャ行の追加は不要(§1の既存フィクスチャのみで全9ケースをカバーできる)。
- `$_FILES` 未対応というハーネス制約(既存の `TC-SEL-CHECK-NEW-01` 等と同じ)が
  `TC-SEL-PHPCHECK-NEW-01` にも及ぶ。対応は必須ではない(既知の制約として期待値に織り込み済み)。
- 追加のケースJSON(`tests/runner/cases/*.json`)とゴールデンマスター(`tests/golden/`)の作成は
  オーケストレーターの担当。

---

#### TC-SEL-BRWMAKERS-01: `brewery/makers.php`

- **目的**: `common/sql.php`経由のメーカー一覧ページ。ループが `index.php`(count-7の逆走査で
  負インデックスに達する既知バグ)とは異なる **安全な逆順ループ**(`$i = count-1; $i>-1; $i--`)
  であることを確認する。
- **入力**: GET `/brewery/makers.php`(パラメータ無し)
- **期待値**:
  - `$sql_where="''=''"` のみ設定 → products/maker/style/clarity/fruityの取得は
    `common/sql.php`のデフォルト分岐どおり(products全件・maker全件・sty/cla/fru=`0`)。
  - `js_prd`=products全3行、`js_mak`=maker全3行(投入順)、`js_sty`/`js_cla`/`js_fru`=`0`。
  - ループは `for($i = count($mak_MakerID) -1; $i >-1; $i--)`(count=3 → i=2,1,0の3回、
    **負インデックスなし**)。逆順で mk9003(Gamma Brewing), mk9002(Beta Brewing),
    mk9001(Alpha Brewing) の3件の`<li>`が出力される。各`<li>`内で
    `require('detail/explain/'.$mak_MakerID[$i].'.html')` が実行され、対応するダミー
    (`<p>fixture explain mk9003</p>` 等)がそのままインラインで挿入される。クラッシュしない。
  - **PHP8リスク**: 末尾の `$comment = $_GET['comment'];`(brewery/makers.php 76行目)で
    `Undefined array key "comment"` Warning 1件(非致命的、`$comment`は未使用)。
  - 一次確認(`php tests/runner/exec_page.php brewery/makers.php`)で上記を実測し、完全一致を確認
    (exit=0、Warningは`comment`の1件のみ、他のsql.php由来Warningは改修2により発生しない)。
- **ゴールデンマスターとの突合せ(commit 0a2a7f2)**: `tests/golden/TC-SEL-BRWMAKERS-01.html`と
  完全一致(`diff`差分ゼロ)。Warningは`brewery/makers.php`76行目の`comment`未定義1件のみ
  (実測で行番号まで確認)。makerループは逆順3件(mk9003→mk9002→mk9001)+explainダミー
  インライン挿入まで一次確認どおり。exit=0。
- **状態**: PASS(GM固定)

#### TC-SEL-BRWMAKER-01: `brewery/detail/maker.php`

- **目的**: `$_GET['MakerID']`→`$sql_where`を products と maker の両方に使い回すイディオム
  (`php/maker.php`のTC-SEL-MAK-01と同型)の common版。`require('./explain/'.$maker_id.'.html')`
  がsandboxの既存ダミーで解決できることを確認する。
- **入力**: GET `/brewery/detail/maker.php?MakerID=mk9002`
- **期待値**:
  - `$sql_where = "MakerID='mk9002'"`。products側: `WHERE MakerID='mk9002'` → P102のみ1行。
    maker側(`$src_mak`未設定→else→同じ`$sql_where`使用): `WHERE MakerID='mk9002'` → mk9002のみ1行。
  - `js_prd`=[P102の1行(`IBU_all`=フィクスチャ初期値`99.99`、まだthank.php未経由)]、
    `js_mak`=[mk9002の1行]、`js_sty`/`js_cla`/`js_fru`=`0`。
  - `$keyIndex_mak = array_search('mk9002', mak_MakerID) = 0`。
  - 画面: `<h2>Beta Brewing</h2>`、`<p class='p2'>`内に`require('./explain/mk9002.html')`の
    ダミー本文(`<p>fixture explain mk9002</p>`)がそのままインライン挿入、`<p class='p3'>`に
    URL1リンク(`https://beta.example.com`)。
  - sec2 商品ループ(`for($i=0;$i<count($prd_ProductID);$i++)`, count=1, 0始まりの通常ループ):
    P102(Beta Light Pilsner)の`<li>`1件のみ。リンク先は`../../beer/detail/product.php?ProductID=102`。
  - Warningなし(このページは`$_GET['comment']`を読まず、`$src_sty`/`$src_cla`/`$src_fru`も
    改修2のisset初期化によりWarningを出さない)。
  - 一次確認で上記を実測し完全一致を確認(exit=0、Warning 0件)。
- **ゴールデンマスターとの突合せ(commit 0a2a7f2)**: `tests/golden/TC-SEL-BRWMAKER-01.html`と
  完全一致(`diff`差分ゼロ)。h2=Beta Brewing、explainダミーインライン、URL1リンク、sec2ループ
  1件(P102)、Warning 0件、exit=0。
- **状態**: PASS(GM固定)

#### TC-SEL-BEERPRODUCTS-01: `beer/products.php`

- **目的**: `common/sql.php`経由の全ビール一覧ページ。TC-SEL-BRWMAKERS-01と同型の安全な
  逆順ループ(`$i = count-1; $i>-1; $i--`)であることを確認する。
- **入力**: GET `/beer/products.php`(パラメータ無し)
- **期待値**:
  - `$sql_where="''=''"` のみ → products全3行・maker全3行取得、`js_sty`/`js_cla`/`js_fru`=`0`。
  - ループ(count=3、i=2,1,0、負インデックスなし): 逆順で103(Gamma Dark Stout),
    102(Beta Light Pilsner), 101(Alpha Citrus IPA)の3件の`<li>`。`<h2>`見出しは
    `ビール 3件`。クラッシュしない。
  - **PHP8リスク**: 末尾の`$comment = $_GET['comment'];`(90行目)で`Undefined array key
    "comment"` Warning 1件。
  - 一次確認で完全一致を確認(exit=0)。
- **ゴールデンマスターとの突合せ(commit 0a2a7f2)**: `tests/golden/TC-SEL-BEERPRODUCTS-01.html`と
  完全一致(`diff`差分ゼロ)。Warningは90行目の`comment`未定義1件のみ、逆順3件(103→102→101)、exit=0。
- **状態**: PASS(GM固定)

#### TC-SEL-BEERDETAIL-01: `beer/detail/product.php`(**§2.1訂正の直接根拠**)

- **目的**: `common/sql.php`の`$src_cla='yes'`/`$src_fru='yes'`分岐が実ページ経由で到達することを
  確認する(旧台帳で「死んでいる分岐」としていた記述の訂正根拠)。`php/product.php`
  (TC-SEL-PRD-01)のcommon版に相当。
- **入力**: GET `/beer/detail/product.php?ProductID=102`
- **期待値**:
  - `$sql_where`/`$sql_where_sty`/`$src_sty='yes'`/`$sql_where_cla="''=''"`/
    `$sql_where_fru="''=''"`/`$src_cla='yes'`/`$src_fru='yes'` を全て明示設定 →
    `common/sql.php`のproducts全件・maker(`$src_mak`未設定→else→`$sql_where`使用)全件・
    style全件・**clarity全4行・fruity全4行**を取得。
  - `js_prd`=products全3行、`js_mak`=maker全3行、`js_sty`=style全3行、
    **`js_cla`=clarity全4行、`js_fru`=fruity全4行**(この2つが§2.1で「死分岐」としていた
    `common/sql.php`のcla/fru='yes'側であり、本ケースで生きていることを実測確認した)。
  - `$keyIndex_prd = array_search('102', prd_ProductID) = 1`。
    `$keyIndex_mak = array_search($prd_MakerID[1]='mk9002', mak_MakerID) = 1`
    (**`$_GET['MakerID']`ではなく`prd_MakerID`経由で導出する点が`php/product.php`との相違**)。
    `$keyIndex_sty = array_search('2', sty_StyleID) = 1`(Pilsner)。
    `$keyIndex_cla = array_search(round('2')=2, cla_ClarityValue) = 1`(Clear)。
    `$keyIndex_fru = array_search(round('1')=1, fru_FruityValue) = 0`(Not Fruity)。
  - 画面表示(日本語UI、`php/product.php`と表示文言が異なる版): `<h1> Beta Light Pilsner </h1>`
    (メーカー名は併記しない)、`ブリュワリー : `+リンク`Beta Brewing`、
    `スタイル : Pilsner`、色=Color2.png、透明度=Clarity2.png+`Clear`、`IBU : 99.99`
    (フィクスチャ初期値、thank.php未経由)、フルティーさ=Fruity1.png+`Not Fruity`、
    `アルコール度 : 4.80`、点数=`3.50`。評価リンクは
    `../../post/evaluation.php?product_id=102`。
  - **PHP8リスク**: 末尾の`$comment = $_GET['comment'];`(129行目)で`Undefined array key
    "comment"` Warning 1件。
  - 一次確認(`php tests/runner/exec_page.php beer/detail/product.php`、
    `{"get":{"ProductID":"102"}}`)で上記全て実測し完全一致を確認(exit=0、
    `js_cla`/`js_fru`が`0`ではなく4行のJSONであることを直接確認した)。
- **ゴールデンマスターとの突合せ(commit 0a2a7f2)**: `tests/golden/TC-SEL-BEERDETAIL-01.html`と
  完全一致(`diff`差分ゼロ)。**`js_cla`/`js_fru`がclarity/fruity全4行のJSONであることを
  正式なゴールデンマスターでも確認**(§2.1訂正の根拠が正式に裏付けられた)。表示値
  (STYLE=Pilsner、COLOR=Color2.png、CLARITY=Clarity2.png/Clear、IBU=99.99、
  FRUITY=Fruity1.png/Not Fruity、ALCOHOL=4.80、点数=3.50)・Warning(`comment`未定義1件、
  129行目)とも一致。exit=0。
- **状態**: PASS(GM固定)

#### TC-SEL-STYSTYLES-01: `style/styles.php`

- **目的**: `common/sql.php`の`$src_sty='yes'`経由でのスタイル一覧。安全な逆順ループの再確認。
- **入力**: GET `/style/styles.php`(パラメータ無し)
- **期待値**:
  - `$sql_where="''=''"`/`$sql_where_sty="''=''"`/`$src_sty='yes'` → products全3行・maker全3行・
    style全3行取得、`js_cla`/`js_fru`=`0`。
  - ループ(count=3、i=2,1,0、負インデックスなし): 逆順でStyleID=3(Stout), 2(Pilsner),
    1(IPA)の3件の`<li>`。`<h2>`見出しは`Styles 3件`。クラッシュしない。
  - **PHP8リスク**: `$comment`未定義Warning 1件(92行目)。
  - 一次確認で完全一致を確認(exit=0)。
- **ゴールデンマスターとの突合せ(commit 0a2a7f2)**: `tests/golden/TC-SEL-STYSTYLES-01.html`と
  完全一致(`diff`差分ゼロ)。Warningは92行目の`comment`未定義1件のみ、逆順3件
  (StyleID=3→2→1)、exit=0。
- **状態**: PASS(GM固定)

#### TC-SEL-STYDETAILMAKERS-01: `style/detail/makers.php`(**壊れたページ、fatal特性固定**)

- **目的**: `style/detail/makers.php`は`brewery/makers.php`からのコピー&ペーストと見られるが、
  ディレクトリ階層(`style/detail/`は2階層)に対して相対パスの階層数(`../common/sql.php`は
  1階層分)が合っておらず、`common/sql.php`に到達できない。この**壊れたページの特性を固定**する。
- **入力**: GET `/style/detail/makers.php`(パラメータ無し)
- **期待値**:
  - `require('../common/sql.php')`(12行目)は実行時カレントディレクトリ
    (=`tests/sandbox/style/detail/`)から見て`tests/sandbox/style/common/sql.php`を指すが、
    このパスは存在しない(`common/`はリポジトリ直下=`tests/sandbox/common/`のみに存在する。
    `style/detail/`から`common/`に到達するには`../../common/sql.php`が必要、
    `style/detail/style.php`はそのとおり`../../common/sql.php`を使っており対照的)。
  - よって出力は `<!DOCTYPE html>`〜`<link rel="stylesheet" href="../style.css">` までの
    `<head>`前半部分のみが生成された時点で、
    `Warning: require(../common/sql.php): Failed to open stream: No such file or directory
    in .../tests/sandbox/style/detail/makers.php on line 12` に続き
    `Fatal error: Uncaught Error: Failed opening required '../common/sql.php'
    (include_path='.:/usr/share/pear:/usr/share/php') in
    .../tests/sandbox/style/detail/makers.php:12` + スタックトレースで**即座に打ち切られる**
    (`</head>`以降は一切出力されない。exit=255)。
  - 一次確認で上記メッセージ・exit=255を完全一致で確認済み(§0前提6のとおり、こちらは
    PDO関連ではなく単純な`require()`失敗のため、PHPのバージョンやPDOのERRMODE設定に関わらず
    再現する)。
  - この不具合は**本番でも`style/detail/makers.php`にアクセスすると常にFatal errorになる**
    ことを意味する(実運用上の問題として、loop-scope.mdの「別課題として記録するもの」への
    追加候補としてオーケストレーターへ申し送る)。
- **ゴールデンマスターとの突合せ(commit 0a2a7f2)**: `tests/golden/TC-SEL-STYDETAILMAKERS-01.html`
  と完全一致(`diff`差分ゼロ)。予測どおり`<head>`前半(`<link rel="stylesheet"
  href="../style.css">`まで)だけを出力して`require(../common/sql.php): Failed to open stream:
  No such file or directory`のWarning、続けて`Fatal error: Uncaught Error: Failed opening
  required '../common/sql.php'`(12行目)+スタックトレースで打ち切り。exit=255
  (オーケストレーター提示のexitコードとも一致)。
- **【2026-08-22 ページ削除に伴うケース撤去】修正2(commit db02f69)により、この壊れたページ
  `style/detail/makers.php` 自体がリポジトリから削除された(§11.3参照。「別課題として記録する」
  ではなく「孤児ページを削除する」という判断がなされた)。対応するケースJSON
  (`tests/runner/cases/TC-SEL-STYDETAILMAKERS-01.json`)と`tests/golden/
  TC-SEL-STYDETAILMAKERS-01.*`も削除済み。`bash tests/runner/run_all.sh`で本ケースはもはや
  実行されない(対象ページが存在しないため)。
- **状態**: 削除(ページごと撤去。commit db02f69。上記の期待値・PASS実績は削除前の最終確認結果
  として記録を残す)

#### TC-SEL-STYDETAIL-01: `style/detail/style.php`(**§2.1訂正の直接根拠**)

- **目的**: `common/sql.php`の`$src_mak='no'`分岐が実ページ経由で到達することを確認する
  (旧台帳で「死んでいる分岐」としていた記述の訂正根拠)。
- **入力**: GET `/style/detail/style.php?StyleID=2`
- **期待値**:
  - `$style_id='2'`。`$sql_where = "StyleID='2'"`、`$sql_where_sty = "StyleID='2'"`、
    **`$src_mak = "no"`**、`$src_sty = "yes"` を設定して `../../common/sql.php`
    (2階層上、正しいパス)をrequire。
  - products側: `WHERE StyleID='2'` → P102のみ1行。style側(`$src_sty='yes'`→
    `$sql_where_sty`使用): `WHERE StyleID='2'` → StyleID=2(Pilsner)のみ1行。
    **maker側: `$src_mak=='no'` → SELECTを実行せず、`$json_mak`は未代入のまま
    → `js_mak = 0`**(§2.1で「死んでいる」としていた分岐が、まさにこの`0`という形で
    可視化される)。
  - `js_prd`=[P102の1行]、`js_sty`=[StyleID=2の1行]、`js_mak`=`0`、`js_cla`/`js_fru`=`0`。
  - `$keyIndex_sty = array_search('2', sty_StyleID) = 0`。
  - 画面: `<h2>Pilsner</h2>`、`<p class='p2'>Crisp, light lager style. </p>`
    (mak_MakerNameは一切参照しないページのため`$src_mak='no'`にしても表示上の欠落は生じない
    ——このページ自体がmaker一覧を使わない設計であることの裏付け)。
    sec2商品ループ(0始まり、count=1、負インデックスなし):
    P102(Beta Light Pilsner)の`<li>`1件、リンク先`../../beer/detail/product.php?ProductID=102`。
  - Warningなし(`$_GET['comment']`読み取りが無いページ、改修2によりsql.php由来Warningも0件)。
  - 一次確認で上記を実測し完全一致を確認(exit=0、`js_mak`が`0`であることを直接確認した)。
- **ゴールデンマスターとの突合せ(commit 0a2a7f2)**: `tests/golden/TC-SEL-STYDETAIL-01.html`と
  完全一致(`diff`差分ゼロ)。**`js_mak`が`0`(SELECT自体スキップ)であることを正式な
  ゴールデンマスターでも確認**(§2.1訂正の`$src_mak='no'`が生きているという主張の直接裏付け)。
  h2=Pilsner、p2=style explain、sec2ループ1件(P102)、Warning 0件、exit=0。
- **状態**: PASS(GM固定)

#### TC-SEL-PHPCHECK-NEW-01: `php/check.php`(`New_Update=new`)(**common版との分岐差分の直接対比**)

- **目的**: `post/check/check.php`(TC-SEL-CHECK-NEW-01、`common/sql.php`使用)の`php/`版コピーで
  ある`php/check.php`が**`php/sql.php`(分岐差分のある別版)を使用する**ことを確認する。
  ロジック本体(`$value`配列の構築、check画面の表示分岐)は`diff`でパス関連の差分以外
  完全に同一であることを確認済み(相対パス・`require('sql.php')`のみが差分)。
- **入力**: POST `/php/check.php`(TC-SEL-CHECK-NEW-01と完全に同一のPOSTパラメータ)
  ```json
  {"ProductID":"", "chk_MakerID":"mk9002", "chk_productname":"Delta Wheat",
   "chk_StyleID":"2", "chk_alcohol":"5.10", "chk_ibu":"25.00", "chk_color":"5",
   "chk_clarity":{"value":"3","explain":"Slight Haze"},
   "chk_fruity":{"value":"2","explain":"Bit Fruity"},
   "chk_favorite":"4", "UserID":"us0001", "New_Update":"new",
   "comment":"First tasting.", "ProductExplain":"A new wheat beer."}
  ```
- **期待値**:
  - **画面の可視内容(Brewery/BeerName/Alcohol/IBU/Color/Clarity/Fruity/Favorite/Comment/Photo)
    および`value_http`の内容は TC-SEL-CHECK-NEW-01 と完全に同一**
    (`$sql_where="''=''"`で全maker/全productsが対象になるため、`common/sql.php`版と
    `php/sql.php`版で選択される行集合が一致する。`php/sql.php`のmaker分岐条件`$src_prd=='no'`は
    `$src_prd`未設定のため`else`側に落ち、`common/sql.php`の`$src_mak`未設定→`else`側と
    同じ`$sql_where`を使うため結果が一致する)。
  - `value_http` = `userid=us0001&productid=&makerid=mk9002&productname=Delta+Wheat&styleid=2&alcohol=5.10&ibu=25.00&color=5&clarity=3&fruity=2&favorite=4&new_update=new&productexplain=A+new+wheat+beer.&comment=First+tasting.`
    (TC-SEL-CHECK-NEW-01と1バイトも違わない)。
  - **差分は`<script>`ブロックのみ**: `php/sql.php`版のためstyle抽出が3フィールド版
    (`var sty_StyleID=[], sty_StyleName=[], sty_StyleIBU=[];`、TC-SEL-ADDP-01と同型)になり、
    `common/sql.php`版(5フィールド、TC-SEL-CHECK-NEW-01)とはここでのみ相違する。
  - **PHP8リスク**: `$_FILES['upimg']`未設定によるWarning群(`Undefined array key "upimg"`×2 +
    `Trying to access array offset on null`×2 + `move_uploaded_file()`の`Deprecated`)は
    TC-SEL-CHECK-NEW-01と同一パターン(行番号も25/26行目で共通、ファイル名のみ`php/check.php`
    に変わる)。
  - 一次確認で上記全てを実測し完全一致を確認(exit=0、可視内容・`value_http`は
    TC-SEL-CHECK-NEW-01と同一、`<script>`ブロックのみ3フィールド版であることを確認)。
- **ゴールデンマスターとの突合せ(commit 0a2a7f2)**: `tests/golden/TC-SEL-PHPCHECK-NEW-01.html`と
  完全一致(`diff`差分ゼロ)。`value_http`は`tests/golden/TC-SEL-CHECK-NEW-01.html`のものと
  1バイトも違わず同一であることを`diff`で確認。可視内容(Brewery/BeerName/Alcohol/IBU/
  Color/Clarity/Fruity/Comment/Photo)も同一。`<script>`ブロックはphp/sql.php版の3フィールド
  (`var sty_StyleID=[], sty_StyleName=[], sty_StyleIBU=[];`)。$_FILES関連Warning5件
  (25/26行目、`php/check.php`自身のファイル名で)もTC-SEL-CHECK-NEW-01と同一パターン。exit=0。
- **状態**: PASS(GM固定)

#### TC-SEL-STRATHCONA-01: `php/Strathcona_Beer_Company.php`(**壊れたページ、fatal特性固定**)

- **目的**: `products`テーブルに存在しない`BREWERY`列をWHERE句に使うSQLが実行され、
  PDOの例外により**壊れたページとしてfatalする特性を固定**する。§0前提6(PDOのデフォルト
  ERRMODE_EXCEPTION)を直接裏付けるケース。
- **入力**: GET `/php/Strathcona_Beer_Company.php`(パラメータ無し)
- **期待値**:
  - `$sql_where = "BREWERY='Strathcona Beer Company'"` を設定して `require('sql.php')`
    (`php/sql.php`)。
  - `php/sql.php`内、`$src_prd`は改修2のisset初期化により`''`(空文字列)がデフォルトされ
    `'no'`ではないため`else`分岐へ進み、`$query = "SELECT * FROM products WHERE
    BREWERY='Strathcona Beer Company'"`を`$dbh->query($query)`で実行。
  - `products`テーブルに`BREWERY`列は存在しない(§1のDDL参照)ため、MariaDBは
    `Unknown column 'BREWERY' in 'WHERE'`(SQLSTATE 42S22)エラーを返す。
  - §0前提6のとおり、この環境のPDOデフォルト`ATTR_ERRMODE`は`EXCEPTION`のため、
    `$dbh->query()`の呼び出し自体が**`PDOException`を送出**し、`try/catch`が無いため
    **Fatal error: Uncaught PDOException**として現れる
    (`fetchAll()`呼び出し時点での`Call to a member function fetchAll() on bool`という
    **TypeErrorにはならない**点に注意。これはSILENTモードを前提にした場合の誤った予測である)。
  - 出力は `<html>`〜`<div class='chart'>` の開始タグまでの静的HTML部分のみが生成され、続けて
    `Fatal error: Uncaught PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown
    column 'BREWERY' in 'WHERE' in .../tests/sandbox/php/sql.php:23` + スタックトレース
    (`#0 .../php/sql.php(23): PDO->query()` / `#1 .../php/Strathcona_Beer_Company.php(22):
    require('...')` / `#2 {main}`)で打ち切られる。exit=255。
  - 一次確認でメッセージ・行番号(`php/sql.php:23`)・exit=255を完全一致で確認済み
    (§0前提6に記載の実測結果と同一)。
  - この不具合は**本番でも`php/Strathcona_Beer_Company.php`にアクセスすると常にFatal error
    になる**ことを意味する(元々コメントアウトされていない生きたページとして残っているが、
    実質「壊れて使えないページ」である。loop-scope.mdの「別課題として記録するもの」への
    追加候補としてオーケストレーターへ申し送る)。
- **ゴールデンマスターとの突合せ(commit 0a2a7f2)**: `tests/golden/TC-SEL-STRATHCONA-01.html`と
  完全一致(`diff`差分ゼロ)。**§0前提6で予測した`PDOException`(orchestratorの当初想定=
  fetchAll()でのTypeErrorではない)が正式なゴールデンマスターでも確認された**:
  `Fatal error: Uncaught PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column
  'BREWERY' in 'WHERE' in .../php/sql.php:23`、スタックトレース1行目が
  `#0 .../php/sql.php(23): PDO->query()`であることも一致。出力は`<div class='chart'>`の
  開始タグまでで打ち切り。exit=255(オーケストレーター提示のexitコードとも一致)。
- **【2026-08-22 ページ削除に伴うケース撤去】修正2(commit db02f69)により、この壊れたページ
  `php/Strathcona_Beer_Company.php` 自体がリポジトリから削除された(§11.3参照)。対応する
  ケースJSON(`tests/runner/cases/TC-SEL-STRATHCONA-01.json`)と`tests/golden/
  TC-SEL-STRATHCONA-01.*`も削除済み。`bash tests/runner/run_all.sh`で本ケースはもはや
  実行されない(対象ページが存在しないため)。
- **状態**: 削除(ページごと撤去。commit db02f69。上記の期待値・PASS実績は削除前の最終確認結果
  として記録を残す)

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

> **【最新の正式な件数は §11.4 を参照】** 本セクション以下(§6〜§10)は各ラウンド時点での
> 件数・結果を歴史的記録として残しているため、19件→28件→**26件**(最終)と段階的に増減した
> 記述が混在する。現時点の正しい総ケース数・結果内訳は §11.4 の「最終ケース数集計」を参照。

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
- **【2026-08-22追記】検証者(B)指摘1により9件を追加設計**(§2.4・§3.4参照)。
  総テストケース数は **28件**になった。新規9件のうち `brewery/makers.php`・
  `beer/products.php`・`style/styles.php` の3件は既存分岐(`$src_sty`スキップ、`$src_mak`else 等)
  を別ページから再確認するもの、`brewery/detail/maker.php` は `php/maker.php`
  (TC-SEL-MAK-01)のcommon版、`beer/detail/product.php`・`style/detail/style.php` の2件は
  §2.1 で「死分岐」としていた `$src_cla`/`$src_fru='yes'` および `$src_mak='no'` が実は
  生きていたことを示す訂正の根拠、`php/check.php` は `post/check/check.php` との共有ロジックの
  `php/sql.php`版対比、`style/detail/makers.php`・`php/Strathcona_Beer_Company.php` の2件は
  独立した「壊れたページ」の特性固定(前者はディレクトリ階層に対する相対パス誤り、後者は
  存在しない列へのSQL参照によるPDOException)。
  **【2026-08-22 さらに追記】オーケストレーターがケースJSON化・ゴールデンマスター取得を完了
  (commit 0a2a7f2)。9件全件を正式なゴールデンマスターと突合せ、全件PASS(GM固定)。
  一次確認時の期待値との差分は0件(想定外の差分なし)。詳細は§9.3。**

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

---

## 8. 改修後の期待値変更の記録(2026-08-22、commit 37c70b3 / 18ff215 適用後)

「無言の期待値変更禁止」の原則に基づき、改修適用後に `tests/golden/`(改修前基準)と
`tests/out/`(改修後の実行結果)の間に生じた差分を、**改修ごとに・理由と挙動保存の根拠を添えて**
ここに記録する。以降このセクションが「改修後の正」の期待値注記となる。

**再現・独立検証の方法**: `bash tests/runner/make_sandbox.sh && bash tests/runner/start_db.sh &&
bash tests/runner/run_all.sh` の後、以下を実行して機械的に全差分を分類した(本セクションの数値は
この検証で独立に算出したもの):
```bash
# (a) sql.php由来のUndefined variable警告の総数(golden側)
for f in tests/golden/*.html; do grep "Undefined variable" "$f" | grep -c "/sql\.php on line"; done \
  | paste -sd+ | bc   # → 106

# (b) 全19ケースの exit / db.txt / params.json が完全一致することの確認
for f in tests/golden/*.{exit,db.txt,params.json}; do
  b=$(basename "$f"); diff -q "$f" "tests/out/$b" || echo "DIFF: $b"
done   # → 出力なし(差分ゼロ)

# html差分が下記2種のみで説明しきれることの確認(golden側からWarning行を機械的に除去し、
# 既知のrename行番号シフトを適用したうえでoutと完全一致するかを検証)
# → 19ファイル全てで「UNEXPLAINED DIFF」ゼロ件を確認
```

### 8.1 改修1(commit 37c70b3、DB接続の db_config.local.php 方式化)による差分

- **変更内容**: `common/sql.php` / `common/sql_POST.php` / `php/sql.php` / `php/sql_POST.php`
  の4ファイル先頭に `require_once dirname(__FILE__).'/../db_config.local.php';` を1行追加し、
  PDO接続の第1〜3引数を直書き文字列から `$db_dsn, $db_user, $db_pass` 参照に置換。
- **観測された差分**: この改修**単独**では出力差分はカテゴリ(b)(行番号シフト)のみに現れる。
  具体的には `common/sql_POST.php` L40 の `rename()` 失敗Warning(TC-POST-THANK-NEW-02でのみ
  観測)が **L41 に+1シフト**した。他の警告行はいずれも改修2で本文ごと消去されるため、
  改修1単独由来の行番号シフトが直接観測できるのはこの1箇所のみ。
- **挙動保存の根拠**: 追加された行は `require_once` の1行のみで、それ自体は標準出力に何も
  書き出さない(設定変数の読み込みのみ)。よって以降の全行番号が機械的に+1シフトするだけであり、
  接続先DSN/ユーザー/パスワードの実値は環境変数化された後も従来と同一の値を指す設計
  (`db_config.local.php` はgitignore対象、`db_config.local.php.example` に実装のひな形をコミット)。
  SQL文・バインドパラメータ・クエリ結果には一切影響しないため、DB状態(`.db.txt`)・exitコード
  (`.exit`)が全19ケースで完全一致していることと合わせて、「接続方法を変えただけで挙動は変えていない」
  というこの改修の意図どおりであることを確認した。
- **該当ケース**: TC-POST-THANK-NEW-02(行番号のみ40→41に変更して期待値を確定)。

### 8.2 改修2(commit 18ff215、$src_* の isset初期化 / $json_* の empty()ガード)による差分

- **変更内容**: `common/sql.php` と `php/sql.php` に、各 `$src_*` 変数の未設定時 `Undefined variable`
  警告を防ぐ `isset()` ベースの初期化と、各 `$json_*` 変数の未設定時警告を防ぐ `empty()` ガードを追加。
- **観測された差分**: **sql.php由来の`Undefined variable`警告ブロックが完全に消失**した。
  独立集計で **106個**(golden側の全19ファイル合計、内訳は `$src_prd`/`$src_mak`/`$src_sty`/
  `$src_cla`/`$src_fru` の未定義警告と、対応する `$json_prd`/`$json_mak`/`$json_sty`/`$json_cla`/
  `$json_fru` の未定義警告の合算)を確認し、オーケストレーターの機械検証結果(106個)と一致した。
  警告が挿入されていた箇所は単に消え、`let js_X = 0;` のように前後の空行・改行を含めて
  1行に収束する形になる(値そのもの`0`や実データJSONの内容は一切変わらない)。
- **挙動保存の根拠**:
  1. **`js_prd`/`js_mak`/`js_sty`/`js_cla`/`js_fru` の値は全19ケースで完全に不変**
     (警告テキストが除去された結果、周辺の空行の入り方が変わるのみで、JSON内容・`0`判定の
     どちらの分岐を通るかは改修前と完全に同一)。
  2. **sql.php層以外が発する `Undefined variable`/`Undefined array key`/`Deprecated` 警告
     (5件: `$product_id`×3箇所, `$keyIndex_mak`×1, `php/evaluation.php`のDeprecated群など)は
     一切変化していない**(改修2の対象は`common/sql.php`と`php/sql.php`のみであり、各ページ
     ファイル自身が発する警告は対象外のため)。これは独立検証(`grep`による全体の
     `Undefined variable`件数: golden 111件 → out 5件、差分106件が過不足なくsql.php由来の
     ものと一致)で確認済み。
  3. **DB状態・exitコードは全19ケースで完全一致**(TC-SEL-IDX-01のfatal error/exit=255を含む)。
     警告メッセージの消失は「出力されるHTML」の一部が変わったことを意味するため
     `docs/loop-scope.md`の不変条件(「各ページのHTML出力を変えない」)に照らすと**厳密には
     出力文字列の変更**にあたるが、これは対象コードのPHP8対応(未定義変数警告の除去)という
     ループの目的そのもの(CLAUDE.md/loop-scope.mdが明示的に許容する「PHP8起因の警告混入」の
     解消)であり、**実データ・分岐結果・副作用に変化がないことをもって「挙動保存」と判定する**。
     これは改修前ラウンドの台帳(§4 PHP8リスク まとめ)で「Warning/Deprecationの文言自体も
     ゴールデンマスターの一部」としつつ「PHP8.5移行時にこれらの警告を握りつぶす/修正するなら、
     それは別課題または明示的な仕様変更として扱う必要がある」と明記していた想定どおりの変更である。
- **該当ケース**: TC-SEL-IDX-01, TC-SEL-PRD-01, TC-SEL-MAK-01, TC-SEL-MAKERS-01, TC-SEL-EVAL-01,
  TC-SEL-LMAK-01, TC-SEL-LSTY-01, TC-SEL-ADDP-01, TC-SEL-POSTADDP-01, TC-SEL-POSTEVAL-01,
  TC-SEL-POSTPRD-01, TC-SEL-CHECK-NEW-01, TC-SEL-CHECK-UPDATE-01, TC-CORE-01, TC-CORE-02,
  TC-CORE-03(計16ケース、消失した警告件数はケースごとに§3内の「改修後の再突合せ」欄を参照)。

### 8.3 差分の分類まとめ(独立検証結果)

| カテゴリ | 内容 | 該当ケース数 | 件数 |
|---|---|---|---|
| (a) sql.php由来 `Undefined variable` 警告の消失 | 改修2 | 16件 | 106個(独立集計で一致) |
| (b) sql_POST.php由来警告の行番号シフト(L40→L41) | 改修1 | 1件(TC-POST-THANK-NEW-02) | 1箇所 |
| 差分なし | — | 2件(TC-POST-THANK-NEW-01, TC-POST-THANK-UPDATE-01) | 0 |
| **想定外の差分** | — | **0件** | **0** |

19ケース全件で、観測された差分は上記(a)(b)の2種のみで過不足なく説明できることを、
`tests/golden`から機械的に(a)の警告行と(b)の既知の行番号シフトを取り除いたテキストが
`tests/out`と完全一致するという独立の再構成検証で確認した(想定外の差分は0件)。

---

## 9. 改修後ラウンド 実行結果まとめ(2026-08-22)

**再現コマンド**:
```bash
cd /workspace/tool/beer
bash tests/runner/make_sandbox.sh   # 改修後コードでsandbox再構築
bash tests/runner/start_db.sh       # MariaDB起動(コンテナ再起動後は毎回必要)
bash tests/runner/run_all.sh        # 全19ケース実行 → tests/out/ に出力
# tests/golden/ (改修前基準) との差分を確認 → カテゴリ(a)(b)のみであることを目視・機械的に確認
```

### 9.1 結果サマリ表(改修後)

| ケースID | 結果 | 差分カテゴリ |
|---|---|---|
| TC-SEL-IDX-01 | PASS(改修後) | (a) 7個消失 |
| TC-SEL-PRD-01 | PASS(改修後) | (a) 2個消失 |
| TC-SEL-MAK-01 | PASS(改修後) | (a) 8個消失 |
| TC-SEL-MAKERS-01 | PASS(改修後) | (a) 8個消失 |
| TC-SEL-EVAL-01 | PASS(改修後) | (a) 6個消失 |
| TC-SEL-LMAK-01 | PASS(改修後) | (a) 8個消失 |
| TC-SEL-LSTY-01 | PASS(改修後) | (a) 6個消失 |
| TC-SEL-ADDP-01 | PASS(改修後) | (a) 6個消失 |
| TC-SEL-POSTADDP-01 | PASS(改修後) | (a) 7個消失 |
| TC-SEL-POSTEVAL-01 | PASS(改修後) | (a) 7個消失 |
| TC-SEL-POSTPRD-01 | PASS(改修後) | (a) 7個消失 |
| TC-SEL-CHECK-NEW-01 | PASS(改修後) | (a) 7個消失 |
| TC-SEL-CHECK-UPDATE-01 | PASS(改修後) | (a) 7個消失 |
| TC-CORE-01 | PASS(改修後) | (a) 8個消失 |
| TC-CORE-02 | PASS(改修後) | (a) 7個消失 |
| TC-CORE-03 | PASS(改修後) | (a) 5個消失 |
| TC-POST-THANK-NEW-01 | PASS(改修後) | 差分なし |
| TC-POST-THANK-UPDATE-01 | PASS(改修後) | 差分なし |
| TC-POST-THANK-NEW-02 | 仕様不一致(改修後もDB副作用はPASS相当) | (b) 行番号シフトのみ |

上表(a)列の件数は `grep`による独立の機械集計値(各ケースの`tests/golden/*.html`から
`Undefined variable ... /sql.php on line`に一致する行数を数えたもの)であり、目視で書き起こした
台帳本文中の警告変数名リストとも突き合わせ済み。合計は
7+2+8+8+6+8+6+6+7+7+7+7+7+8+7+5 = **106個**となり、§8.3・オーケストレーターの機械検証結果
(106個)と完全に一致することを確認した。

### 9.2 結果集計

- PASS(改修後) 18件 / 仕様不一致 1件(TC-POST-THANK-NEW-02、行番号シフトのみ・事象と原因に
  変化なし) / FAIL 0件。
- **想定外の差分: 0件**。オーケストレーターの機械検証(sql.php由来Undefined variable警告106個
  消失)と完全に一致する独立検証結果を得た。DB状態(`.db.txt`)・exitコード(`.exit`)・
  params(`.params.json`)は全19ケースで改修前後完全一致。
- 改修1・改修2とも、§8に記載した根拠により「挙動保存」と判定する。追加の懸念事項は無い。

### 9.3 最終ラウンド: 新規9ケースのゴールデンマスター突合せ(2026-08-22、commit 0a2a7f2)

**再現コマンド**:
```bash
cd /workspace/tool/beer
bash tests/runner/start_db.sh   # MariaDB起動(コンテナ再起動後は毎回必要)
bash tests/runner/run_all.sh    # 全28ケース実行 → tests/out/ に出力
diff -rq tests/out tests/golden # 差分ゼロを確認(全87ファイル: html/exit/params.json + sql_POST系のdb.txt)
```
実行結果: `tests/out/` と `tests/golden/` は **全28ケース・全87ファイルでbyte-identical**
(`diff -rq`差分ゼロ)。既存19ケースも回帰なし(オーケストレーターの報告どおり)。

**新規9ケースの結果表**(§3.4の期待値との突合せ。全件、警告の文言・行番号・exitコードまで
一致を確認した):

| ケースID | 結果 | 突合せの要点 |
|---|---|---|
| TC-SEL-BRWMAKERS-01 | PASS(GM固定) | `comment`未定義Warning1件(L76)のみ、exit=0。一致 |
| TC-SEL-BRWMAKER-01 | PASS(GM固定) | Warning0件、exit=0。一致 |
| TC-SEL-BEERPRODUCTS-01 | PASS(GM固定) | `comment`未定義Warning1件(L90)のみ、exit=0。一致 |
| TC-SEL-BEERDETAIL-01 | PASS(GM固定) | `js_cla`/`js_fru`が4行のJSON(`0`ではない)であることを正式に確認。§2.1訂正の裏付け完了 |
| TC-SEL-STYSTYLES-01 | PASS(GM固定) | `comment`未定義Warning1件(L92)のみ、exit=0。一致 |
| TC-SEL-STYDETAILMAKERS-01 | PASS(GM固定) | `require()`失敗Fatal(L12)、exit=255。メッセージ文言まで一致 |
| TC-SEL-STYDETAIL-01 | PASS(GM固定) | `js_mak`が`0`であることを正式に確認。§2.1訂正の裏付け完了 |
| TC-SEL-PHPCHECK-NEW-01 | PASS(GM固定) | `value_http`がTC-SEL-CHECK-NEW-01と`diff`差分ゼロで同一。styleスクリプト3フィールド版を確認 |
| TC-SEL-STRATHCONA-01 | PASS(GM固定) | **`PDOException`(§0前提6の予測どおり)を正式に確認**。`php/sql.php:23`、exit=255 |

**期待値とのズレ: 0件**。設計時の一次確認(`tests/runner/exec_page.php`による読み取り専用実行)で
得た期待値が、オーケストレーターによる正式なケースJSON化・ゴールデンマスター取得後も
1バイトの差分もなく再現された。特に以下2点は§0/§2.1で行った訂正・予測の正しさを正式な
ゴールデンマスターで裏付けるものである:
- `beer/detail/product.php`・`style/detail/style.php` により、`common/sql.php`の
  `$src_cla`/`$src_fru='yes'`・`$src_mak='no'`が実ページから到達可能な生きた分岐であること
  (§2.1訂正)。
- `php/Strathcona_Beer_Company.php` により、この環境のPDOデフォルト`ATTR_ERRMODE`が
  `EXCEPTION`であること、およびオーケストレーターの当初予測(`query()`が`false`を返し
  `fetchAll()`でTypeErrorになる)が誤りで、実際は`query()`自体が`PDOException`を送出すること
  (§0前提6)。

**最終結果集計(28ケース)**: PASS(GM固定/改修後) **27件** / 仕様不一致 **1件**
(TC-POST-THANK-NEW-02、DB副作用は完全一致・HTML出力のrename失敗Warningの行番号シフトのみ、
事象と原因に変化なし) / FAIL **0件**。

---

## 10. 検証者(B)指摘1への対応まとめ(2026-08-22)

### 10.1 追加ケース一覧

| ID | ページ | 入力の要点 | 期待値の要点 |
|---|---|---|---|
| TC-SEL-BRWMAKERS-01 | `brewery/makers.php` | GETパラメータ無し | maker全3行、安全な逆順ループ(負インデックスなし)、`comment`未定義Warning1件 |
| TC-SEL-BRWMAKER-01 | `brewery/detail/maker.php` | `MakerID=mk9002` | products/maker各1行(Beta)、explainダミーインライン、sec2ループ1件、Warning0件 |
| TC-SEL-BEERPRODUCTS-01 | `beer/products.php` | GETパラメータ無し | products全3行、安全な逆順ループ、`comment`未定義Warning1件 |
| TC-SEL-BEERDETAIL-01 | `beer/detail/product.php` | `ProductID=102` | **`$src_cla`/`$src_fru='yes'`が生きている証拠**。`js_cla`/`js_fru`が4行のJSON(`0`ではない) |
| TC-SEL-STYSTYLES-01 | `style/styles.php` | GETパラメータ無し | style全3行、安全な逆順ループ、`comment`未定義Warning1件 |
| TC-SEL-STYDETAILMAKERS-01 | `style/detail/makers.php` | GETパラメータ無し | **壊れたページ**。`require('../common/sql.php')`が相対パス誤りでFatal、exit=255 |
| TC-SEL-STYDETAIL-01 | `style/detail/style.php` | `StyleID=2` | **`$src_mak='no'`が生きている証拠**。`js_mak`が`0`(SELECT自体スキップ) |
| TC-SEL-PHPCHECK-NEW-01 | `php/check.php` | TC-SEL-CHECK-NEW-01と同一POST | 可視内容・`value_http`は完全同一、`<script>`のみ3フィールド版(php/sql.php版) |
| TC-SEL-STRATHCONA-01 | `php/Strathcona_Beer_Company.php` | GETパラメータ無し | **壊れたページ**。存在しない`BREWERY`列への参照でPDOException、exit=255。**orchestratorの想定(query()がfalseに→fetchAllでFatal)は誤りで、実際はquery()自体がPDOExceptionを送出することを実測で確認・訂正** |

全9件、`tests/runner/exec_page.php`(オーケストレーターの既存ハーネス)を用いた一次確認で
期待値どおりの出力(メッセージ文言・行番号・exitコードまで)を得た。
**【2026-08-22 最終確定】オーケストレーターがケースJSON化・ゴールデンマスター取得を完了
(commit 0a2a7f2)。正式な`tests/golden/`との突合せでも9件全てPASS(GM固定)、期待値との
ズレは0件だった(詳細は§9.3)。**

### 10.2 台帳訂正箇所

1. **§2.1(`common/sql.php`のカバレッジ対応表)**: `$src_mak=='no'`・`$src_cla=='yes'`・
   `$src_fru=='yes'` の3分岐を「死んでいる分岐」としていた記述を訂正。実際に死んでいるのは
   `$src_prd=='no'` と `$src_mak=='yes'` の2分岐のみ。
2. **TC-CORE-01/TC-CORE-03の位置づけ**: 「死分岐の単体テスト」から「実ページ経路が別途存在する
   分岐の、最小フィクスチャでの追加ユニットテスト」に更新(§2.1内に記載)。削除はせず、
   最小フィクスチャでの切り分け用途として維持する方針とした。TC-CORE-02のみが真の死分岐単体
   テストとして残る。
3. **§0に前提6を追加**: PDOのデフォルト`ATTR_ERRMODE`が(接続時にオプション指定が無い場合)
   PHP 8.0以降`EXCEPTION`であることを実測で確定。過去の記述(TC-POST-THANK-NEW-01/UPDATE-01の
   棄却済み「仮説B」)にあった「デフォルトのERRMODE_SILENT下で...」という説明が不正確だった点を
   記録(結論自体=仮説A確定は変わらない)。
4. **§2.4・§3.4を新設**: 9ページの構造整理表と9件のケース本体。
5. **§6・末尾に追記**: 総テストケース数を19件→28件に更新。§9.3で新規9件のゴールデンマスター
   突合せ結果(全件PASS)を確定。

### 10.3 私(テストエンジニア)が用意すべきもの/オーケストレーターへの依頼事項

- **オーケストレーターに依頼したもの(完了済み、2026-08-22 commit 0a2a7f2)**:
  1. ~~`tests/runner/cases/TC-SEL-BRWMAKERS-01.json` 等、新規9件分のケースJSON作成~~
     → **完了**。全9件のケースJSONが作成され、`bash tests/runner/run_all.sh`で全28ケースが
     実行可能になっている。
  2. ~~上記9件の `tests/golden/` への正式なゴールデンマスター格納~~ → **完了**。§9.3のとおり
     全9件を突合せ、期待値とのズレ0件でPASS(GM固定)確定。
  3. **未対応・引き続き依頼事項**: `style/detail/makers.php` と
     `php/Strathcona_Beer_Company.php` の2つの「壊れたページ」を`docs/loop-scope.md`の
     「別課題として記録するもの」に追加するかどうかの判断(このループでは直さない前提だが、
     少なくとも台帳上は特性として記録済み)。
- **sandbox・フィクスチャの追加対応は不要と判断**: 確認時点で `tests/runner/make_sandbox.sh` は
  既に `beer` ディレクトリを含んでおり(オーケストレーターの当初メッセージでは「未コピー」との
  ことだったが設計時点で既に反映されていた)、`style/detail/`・`brewery/detail/explain/`の
  ダミーファイルも含め、9件全てが既存のsandbox構成・既存フィクスチャ(§1)だけで動作することを
  一次確認で検証済み。新規テーブル行・新規ダミーファイルの追加は不要。

---

## 11. クリーンアップ+公開前修正の記録(2026-08-22、commit 8621067 / db02f69)

ユーザー指示によりオーケストレーターが適用した3件の変更(共有SQL層のクリーンアップ1件+
意図的な挙動修正2件)を記録する。独立確認は `bash tests/runner/make_sandbox.sh && bash
tests/runner/run_all.sh` を実行し、`tests/golden/`(この時点でのオーケストレーター提供版)との
差分を検分する形で行った。

### 11.1 クリーンアップ(commit 8621067、挙動保存)

**対象**: `common/sql.php`・`common/sql_POST.php`・`php/sql.php`・`php/sql_POST.php` の4ファイル。

**変更内容**(現物を確認して裏付けた):
1. **死コード除去**: 各SELECT/INSERT/UPDATEブロックの直前で毎回 `new PDO(...)` していたのを、
   ファイル冒頭で1回だけ生成した `$dbh` を使い回す形に統合(§0前提6のPDO接続文字列自体は不変。
   接続オプション未指定なのでERRMODE=EXCEPTIONのデフォルトも変わらない)。
2. **PDO接続の1回集約**: 上記1と同じ変更の別表現。`sql.php`は10行目、`sql_POST.php`は5行目に
   `$dbh = new PDO($db_dsn, $db_user, $db_pass);` が1箇所だけ存在することを確認した。
3. **`fetchALL`→`fetchAll`の表記統一**: PHPのメソッド名は大文字小文字を区別しないため
   (`fetchALL`も`fetchAll`も同一メソッドを指す)、**純粋に表記上の変更で機能に影響しない**。
   `common/sql.php`・`php/sql.php`の全箇所(products/maker/style/clarity/fruityの5箇所)と
   `sql_POST.php`の全箇所(MAX(ProductID)/style IBU/MAX(Rate_userID)の3箇所)で確認した。
4. **バインドキーのコロン表記統一**: `common/sql_POST.php`(`php/sql_POST.php`も同一)の
   `'Alcohol' => ...` → `':Alcohol' => ...`、`'Clarity_user' => ...` → `':Clarity_user' => ...`
   に修正され、他の全キーと同じ`:`プレフィックス形式に統一された(27行目・61行目で確認)。
   §0前提3で確定させたとおり、コロン有無はPDOのemulated prepareが正規化して吸収するため
   **元々実害の無いバインドだった**(TC-POST-THANK-NEW-01のDB実測でAlcohol/Clarity_userとも
   正しく反映されることを確認済み)。したがってこの表記統一は**挙動を変えない**
  (`array(':ProductID' => ..., ':Alcohol' => ...)` のように単にキー名の見た目が揃っただけ)。

**確認結果**: `bash tests/runner/run_all.sh` の実行で、**26ケース中25ケース
(TC-POST-THANK-NEW-02を除く全て)が`tests/golden/`と`diff`差分ゼロ**であることを確認した。
これは「HTML本文不変・行番号シフトのみ」というオーケストレーターの説明と整合する
(改修1でも同様のパターンだったため、今回の`require_once`行数に変化が無いクリーンアップでは
行番号シフトすら発生せず、全ケースが完全一致した)。**クリーンアップは挙動保存として確認できた**。

### 11.2 修正1: `rename()`のファイル基準絶対パス化(commit db02f69、意図的な挙動変更)

**変更内容**(`common/sql_POST.php` 39〜42行目、現物確認済み):
```php
// 呼び出し元ページの階層に依存しないよう、このファイル基準の絶対パスで移動する
// (旧実装は cwd 依存の '../img/...' で、post/check/thank/ 経由の投稿では常に失敗していた)
$img_root = dirname(__FILE__).'/../img';
rename($img_root.'/tmp/'.$image_name_http, $img_root.'/product/'.$image_rename);
```
旧実装の `rename('../img/tmp/'.., '../img/product/'..)` は実行時カレントディレクトリ
(=エントリページの設置ディレクトリ)に依存する相対パスだったため、`php/thank.php`
(サイトルートから1階層)経由では成功する一方、`post/check/thank/thank.php`
(サイトルートから3階層、**実際の投稿導線そのもの**)経由では常に失敗していた
(TC-POST-THANK-NEW-02で特性固定済みのバグ、§3.3参照)。新実装は`dirname(__FILE__)`
(=`common/`ディレクトリ)基準の絶対パスを使うため、エントリページの階層に関わらず常に
正しい`img/tmp/`・`img/product/`を指す。

**これは挙動保存ではなく、意図的なバグ修正である**(検証者(B)の指摘や台帳の分析で発見した
「実運用で新規投稿の画像が反映されない」という実害のあるバグの是正)。

**独立確認結果**(`tests/out` vs `tests/golden`。※`tests/golden`は本メッセージ時点でまだ旧版
=修正前の値のままのため、この差分自体が「修正が正しく効いている」ことの証拠になる):
- `TC-POST-THANK-NEW-02.html`: `Warning: rename(../img/tmp/upload_test.png,
  ../img/product/104.png): No such file or directory in .../common/sql_POST.php on line 41`
  という行(Warning本文+空行の計2行)が**消失**。
- `TC-POST-THANK-NEW-02.db.txt`: `== img/product ==` セクションに **`104.png`が追加**
  (修正前は空だった)。
- products/rate_userテーブルの内容(ProductID=104行・102のIBU_all=30.00・Rate_userID=3行等)・
  exitコード(0)は完全に不変。
- 他の25ケース(sql_POST系の`TC-POST-THANK-NEW-01`・`TC-POST-THANK-UPDATE-01`を含む)は
  この変更の影響を受けず、全て`tests/golden`と差分ゼロ。

**§3.3のTC-POST-THANK-NEW-02の状態を「仕様不一致」→「PASS(修正済み)」に更新した**
(期待値は「rename成功・`img/product/104.png`生成・Warningなし」に修正済み)。
オーケストレーターがこの新しい出力を正式な`tests/golden/`として再ベースラインする予定。

### 11.3 修正2: 孤児ページ2件の削除(commit db02f69、意図的な挙動変更)

`style/detail/makers.php`(TC-SEL-STYDETAILMAKERS-01が特性固定していた、相対パスの階層誤りで
`require()`が常にFatalするページ)と `php/Strathcona_Beer_Company.php`
(TC-SEL-STRATHCONA-01が特性固定していた、存在しない`BREWERY`列参照でPDOExceptionが常に
発生するページ)の2ファイルがリポジトリから削除された。いずれも「アクセスすると必ずFatal
errorになるだけの孤児ページ」であり、§10.3で台帳から申し送った「`docs/loop-scope.md`の
別課題として記録するか」という論点に対し、**「記録して残す」ではなく「削除する」という判断が
下された**形になる。

**台帳側の対応**:
- 対応するケースJSON(`tests/runner/cases/TC-SEL-STYDETAILMAKERS-01.json`・
  `TC-SEL-STRATHCONA-01.json`)と`tests/golden/`配下の該当ファイルは削除済み
  (`bash tests/runner/run_all.sh`実行時に自動的に対象外となることを確認した)。
- §3.4の該当2ケースは**削除**したことを明記しつつ本文は削除前の最終確認結果として残した
  (「削除(ページごと撤去)」、§3.4参照)。これにより「なぜこの2ケースが台帳に無いのか」を
  将来の読者が追跡できるようにする。
- §2.4の表・§10.1の一覧は歴史的記録として残し、削除の事実はここ(§11.3)と各ケース本文に
  集約して記載する(表自体の書き換えは行わない。書き換えると「元々存在しなかった」かのように
  読めてしまうため)。

### 11.4 最終ケース数集計

- **削除前**: 28件(PASS(GM固定/改修後) 27件 + 仕様不一致 1件)
- **削除後**: **26件**(TC-SEL-STYDETAILMAKERS-01・TC-SEL-STRATHCONA-01の2件を撤去)
  - PASS(GM固定/改修後) **25件**
  - PASS(修正済み) **1件**(TC-POST-THANK-NEW-02、旧仕様不一致から復帰)
  - 削除 **2件**(ケース数の集計には含めない。台帳上は記録として残す)
  - FAIL **0件**
- `bash tests/runner/run_all.sh` は26ケースを実行し(`tests/runner/cases/*.json`が26ファイル)、
  独立確認の結果、想定外の差分は**0件**(TC-POST-THANK-NEW-02の差分は修正1で意図されたとおりの
  変化であり、想定内)。
