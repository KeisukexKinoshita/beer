# CLAUDE.md — beer (Darth Beer.com)

クラフトビール評価サイト。2022年構築の本番コード(旧EC2 beer_instance から抽出済み)を
ベースに、seisan3-next サーバへのコンテナ同居で再公開するプロジェクト。

## new-site インタビュー決定事項 (2026-08-22)

| 項目 | 決定 |
|---|---|
| サービス名 / slug | beer |
| GitHubリポジトリ | KeisukexKinoshita/beer (private) |
| 旧コード流用 | あり (旧EC2から抽出済み、パスワードは REDACTED_DB_PASSWORD に置換済み) |
| 公開ドメイン | 未定 (フェーズ③の前に決定する) |
| DB | 要。RDS に `beer` / `beer_dev` スキーマ |
| dev環境 | あり (dev.<domain> + Basic認証) |

## インフラの現状 (2026-08-22 時点)

- RDS `beer-rds` (beer-rds.cqpmrauqnmy0.ap-northeast-1.rds.amazonaws.com) は**稼働中**。
  旧コードが参照するエンドポイントそのままで、`beer` スキーマのデータも残存見込み。
  seisan3 と共用の姉妹インフラ。
- デプロイ先: seisan3-next サーバ (i-0985dcea2eb68ed56)。IPは動的、夜間停止あり(JST 1:00〜6:50)。
- 旧インスタンスのAMIアーカイブ: archive-beer-instance-20260821 (ami-0765883319b95fad0)

## コード構成

- 素のPHP (フレームワークなし) + HTML/CSS/JS。Chart.js でレーダーチャート表示。
- DB接続は `common/sql.php` / `common/sql_POST.php` に PDO 直書き
  (他に `php/sql.php`, `php/sql_POST.php` と `bk_html/`, `php/bk/` 内のバックアップコピーにも散在)。
- `bk_html/`, `php/bk/` は当時の手動バックアップ。動作に不要だが履歴として残している。
- `img/` に商品画像 約118MB。

## 進捗 (new-site)

- ① 環境準備: 完了 (2026-08-22)
- ② 開発: **完了** (2026-08-22)。loop-engineering により特性テスト28ケースで挙動固定
  (27 PASS / 1 仕様不一致=既知renameバグの特性固定)。改修は
  「DB接続の db_config.local.php 方式化」+「PHP 8.5対応(sql層の警告解消)」の2件で、
  検証者(beer-test-verifier)の敵対的検証で**承認**済み。詳細は tests/cases.md と docs/loop-scope.md
- ③ 公開: 未着手。deploy/ 一式は準備済み。**公開ドメインの新規取得待ち**
  (seisan3系サブドメインは使わない方針)。RDSの `beer_dev` スキーマ作成も③で実施

## 開発の約束事

- 接続情報は `db_config.local.php` 方式 (gitignore済み + サーバ内で注入)。
  パスワード実値をリポジトリ・チャットに置かない。
- 旧コードのロジックを保つ改修は /loop-engineering (特性テストで挙動固定) を使う。
- UI修正とロジック修正はコミットを分ける。
