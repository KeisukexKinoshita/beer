# loop-engineering スコープ表 — beer 再構築 (2026-08-22)

## 目的

旧EC2から抽出した2022年構築のコードを、**挙動を変えずに**以下の2点だけ改修し、
seisan3-next サーバのコンテナで動かせる状態にする。

1. **DB接続の `db_config.local.php` 方式化** — PDO接続情報(ホスト・DB名・ユーザー・パスワード)の
   直書きを排し、gitignore済みの設定ファイル読込に置き換える(seisan3と同方式)
2. **PHP 8.5 対応** — 旧コード(PHP 7.x時代)が PHP 8.5 で警告・エラーを出す箇所の修正
   (未定義変数、未定義配列キー等)。**出力されるHTMLは変えない**

## 対象ファイル

| 区分 | ファイル |
|---|---|
| DBアクセス層 | common/sql.php, common/sql_POST.php, php/sql.php (※commonと分岐した別版。統合しない), php/sql_POST.php |
| 呼び出しページ | index.php, php/product.php, php/evaluation.php, php/maker.php, php/makers.php, php/list_maker.php, php/list_style.php, php/thank.php, php/add_product.php, php/post_product.php, post/ 配下, brewery/ 配下 |
| 対象外 | bk_html/, php/bk/, js/bk_js (手動バックアップ。改修せず、そのまま残す) |

## 変更禁止事項 (このループの不変条件)

- 各ページのHTML出力(埋め込みの js_prd / js_mak / js_sty / js_cla / js_fru JSON変数を含む)を変えない
- sql_POST系が発行するSQL文・バインドパラメータの内容を変えない
  (既存の誤り — 例: `':Alcohol'` のコロン欠け `'Alcohol'` — も**そのまま保つ**。修正は別課題)
- php/sql.php と common/sql.php の分岐(取得カラムの差)を統合しない
- 検索条件の組み立てロジック($sql_where 等)に触らない

## 別課題として記録するもの (このループでは直さない)

- SQLインジェクション: $sql_where / $maker_id 等がエスケープなしでSQLに連結されている
- バインドパラメータのコロン欠け(sql_POST.php の 'Alcohol', 'Clarity_user')
- 廃止済みGoogleアナリティクス(UA-)タグ、AdSenseタグの扱い
- sql.php / sql_POST.php の重複コピー統合

## ハーネス方針

- 対象コードを**無改変のまま** tests/sandbox/ にコピー
- PDO接続をスタブに差し替え: SELECT系は tests/fixtures/*.json のフィクスチャを返し、
  INSERT/UPDATE系は「発行SQL文とパラメータ列」をログに記録する
- ランナーは依存ゼロの素のPHPスクリプト。ページをCLI実行してHTML出力を捕捉し、
  期待出力と比較する。ケースごとにプロセス分離
- 台帳: tests/cases.md

## ゴールデンマスター (参照環境)

旧本番EC2は終了済みのため、**seisan3-next サーバ上の docker (php:7.4-cli) で
無改変コードを実行した出力を正**とする(旧本番はPHP 7.x世代のため)。
PHP 8.5(改修後の実行環境)との差分のうち「PHP版数由来の警告等」が改修対象、
それ以外の出力差はすべて回帰として扱う。

## 完了条件

- 全ケースがゴールデンマスターと一致(PHP 8.5 + db_config.local.php 方式で)
- beer-test-verifier の敵対的検証で承認
