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

## 別課題として記録するもの

※「変更禁止事項」の『コロン欠けもそのまま保つ』は、2026-08-22のユーザー指示による
クリーンアップで方針変更され、表記統一済み(挙動同一はGM実証済み)。

未解決:
- SQLインジェクション: $sql_where / $maker_id 等がエスケープなしでSQLに連結されている
  (2026-08-22 ユーザー判断で今回は見送り)
- 廃止済みGoogleアナリティクス(UA-)タグ、AdSenseタグの扱い (同上、見送り)
- sql.php / sql_POST.php の重複コピー統合 (見送り推奨のまま)

解決済み (2026-08-22、台帳§11参照):
- ~~バインドパラメータのコロン欠け~~ → クリーンアップで表記統一 (commit 8621067)
- ~~画像renameの相対パスバグ~~ → ファイル基準絶対パス化で修正 (commit db02f69)
- ~~壊れたページ2件~~ → リンク元ゼロの孤児と確認し削除 (commit db02f69):
  style/detail/makers.php, php/Strathcona_Beer_Company.php

## ハーネス方針

- 対象コードを**無改変のまま** tests/sandbox/ にコピー
- DB境界はスタブではなく**ローカルMariaDB**を使う:
  - コンテナ内の MariaDB (インストール済み) に `beer` スキーマを作成し、
    tests/fixtures/*.sql の**合成フィクスチャ**(小さく制御されたデータ)を投入
  - `/etc/hosts` で旧RDSホスト名 (beer-rds.cqpmrauqnmy0.ap-northeast-1.rds.amazonaws.com) を
    127.0.0.1 に向け、ローカルに `admin` ユーザーをコード直書きのプレースホルダパスワードで作成
    → 接続コードに一切触れずに実行できる
  - INSERT/UPDATE系はテスト後にテーブル内容をSELECTして検証し、各ケース前にフィクスチャを再投入
- ランナーは依存ゼロの素のPHPスクリプト。ページをCLI実行してHTML出力を捕捉し、
  期待出力と比較する。ケースごとにプロセス分離
- 台帳: tests/cases.md

## ゴールデンマスター (参照環境)

**PHP 8.5(新環境)基準で構築する** (ユーザー決定 2026-08-22)。
無改変コードを PHP 8.5 で実行した出力を正とし、PHP 8 起因のバグ
(未定義変数の警告混入、fatal error 等)が出た箇所は「バグとして台帳に記録した上で修正」し、
期待値は仕様(=本来意図された出力)側で確定する。旧PHP 7.4環境との突き合わせは行わない。

## 完了条件

- 全ケースがゴールデンマスターと一致(PHP 8.5 + db_config.local.php 方式で)
- beer-test-verifier の敵対的検証で承認
