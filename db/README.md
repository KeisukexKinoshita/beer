# DB の運用 (マイグレーション / シード)

## 環境

| 環境 | DB名 | 接続設定 |
|---|---|---|
| dev | `beer_dev` | `/srv/beer/dev/html/db_config.local.php` (サーバ内のみ) |
| prod | `beer` | `/srv/beer/prod/html/db_config.local.php` (サーバ内のみ) |

RDS `beer-rds` は **VPC内からのみ到達可能**(`PubliclyAccessible: false`)。
開発コンテナからは直接届かないので、**DB操作はすべて EC2 経由**で行う。

> **開発コンテナの罠**: コンテナの `/etc/hosts` は RDS のホスト名を `127.0.0.1`
> (特性テスト用のローカルMariaDB)へ向けている。コンテナ内から RDS のホスト名で
> 接続すると**テスト用DBに繋がる**。本物を触るときは必ず下記の runner を使うこと。

## ファイルの種類と採番

```
db/migrations/NNN_snake_case.sql   スキーマ変更 (DDL) と、それに伴う初期データ
db/seeds/NNN_seed_YYYYMMDD.sql     データ投入 (DML のみ)。パイプラインが生成する
```

- `NNN` は3桁の連番。**適用済みのファイルは中身を書き換えない**(runner が sha256 で検知して拒否する)。
  修正が要るときは新しい番号のファイルを作る
- 冒頭コメントに「目的 / 対象DB / 冪等性の扱い」を書く
- 対象は MySQL 8。`ADD COLUMN IF NOT EXISTS` は使えないため、
  既存列の重複エラーは runner が「既存のためスキップ」として処理する

## 適用のしかた

runner: `deploy/apply_sql.php` (サーバの `/srv/beer/deploy/` に配置)

```bash
# 手元 → サーバへ SQL を送る
scp -i /root/.ssh/warikan_key.pem db/migrations/00X_xxx.sql ec2-user@$IP:/tmp/
ssh -i /root/.ssh/warikan_key.pem ec2-user@$IP 'sudo cp /tmp/00X_xxx.sql /srv/beer/sql/'

# dev に適用 (マイグレーション = DDL を含むので --allow-ddl)
sudo docker run --rm -v /srv:/srv seisan3-php \
  php /srv/beer/deploy/apply_sql.php dev /srv/beer/sql/00X_xxx.sql --allow-ddl

# シード (DML のみ) は先に dry-run で件数を確認できる
sudo docker run --rm -v /srv:/srv seisan3-php \
  php /srv/beer/deploy/apply_sql.php dev /srv/beer/sql/00X_seed.sql --dry-run

# 確認できたら prod (自動でバックアップを取る)
sudo docker run --rm -v /srv:/srv seisan3-php \
  php /srv/beer/deploy/apply_sql.php prod /srv/beer/sql/00X_xxx.sql --allow-ddl
```

### runner の安全装置

| 装置 | 内容 |
|---|---|
| 接続先の表示 | 環境・DB名・ホスト・主要テーブル件数を毎回表示してから実行する |
| charset 検査 | DSN に `charset=utf8mb4` が無ければ中止(絵文字や一部漢字が壊れるため) |
| 適用履歴 | `schema_migrations` に filename と sha256 を記録。二重適用を防ぎ、内容改変を検知する |
| DML 限定 | 既定では DDL を拒否。マイグレーションのみ `--allow-ddl` を明示する |
| 危険文の禁止 | `DROP DATABASE` / `TRUNCATE` / `GRANT` / `SET GLOBAL` を含むと中止 |
| **DDL + dry-run 禁止** | MySQL は DDL で暗黙コミットするため巻き戻せない。「試すつもりが本適用」になる事故が実際に起きたので、組み合わせ自体を禁止した |
| prod バックアップ | prod 適用前に products / maker / style を `/srv/beer/backup/` へ丸ごと保存 |
| トランザクション | DML のみの場合は全文をトランザクションで囲む(失敗時は巻き戻す) |

`--mark-applied` は SQL を実行せずに「適用済み」として記録する。
schema_migrations を作る前に手作業で当てた分を後から登録するためのもの。

## ID の採番規則

すべて `char(6)` の固定書式。**件数ではなく `MAX(ID)` から次を決める**(欠番があるため)。

| テーブル | 書式 | 例 |
|---|---|---|
| products | `prNNNN` | pr0040 → 次は pr0041 |
| maker | `mkNNNN` | mk0018 → 次は mk0019 |
| style | `stNNNN` | st0102 → 次は st0103 (欠番あり。97行だが最大は st0102) |

既存コード (`common/sql_POST.php`) は `SELECT MAX(ProductID)` の結果に
**PHP の文字列インクリメント (`$id++`)** を適用している(`pr0038` → `pr0039`)。
新しく採番するコードもこの結果と一致させること。

> 注意: PHP の文字列インクリメントは `pr9999` の次を `ps0000` にする。
> 4桁が埋まる前に採番規則を見直すこと。

## 現在のスキーマ (2026-08-26)

`products` / `maker` / `style` の3テーブルが主。詳細は `docs/renewal-plan.md` を参照。
文字セットは全て `utf8mb4 / utf8mb4_0900_ai_ci`。

適用済みマイグレーション:

| ファイル | 内容 |
|---|---|
| 001_renewal_columns.sql | maker に国コード・緯度経度・ロゴパス、style にキャッチコピー |
| 002_geo_and_catchcopy.sql | 19社の緯度経度、24スタイルのキャッチコピーを投入 |
| 003_content_rights.sql | 仕様9-6の出典URL・画像権利区分、公式URL、推定項目フラグ |

dev / prod とも上記3本を適用済み。
