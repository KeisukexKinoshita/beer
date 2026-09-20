# beer デプロイ手順 (seisan3-nextサーバへのコンテナ同居)

方式は seisan3 と同じ「コンテナ追加 + nginx vhost追記 + Route 53レコード」。
参考実物: seisan3 リポジトリの deploy/ と docs/new-server.md。

## 前提

- サーバ: seisan3-next (i-0985dcea2eb68ed56)。IP動的、夜間停止あり(JST 1:00〜6:50)
- 実行イメージ: `seisan3-php` を再利用 (php:8.5-apache + mysqli/pdo_mysql、display_errors=Off)
- ドメイン: **未定** (seisan3.com のサブドメインは使わない方針。新規取得後に
  nginx/beer.conf の `BEER_DOMAIN` を置換)
- DB: RDS beer-rds の `beer` (本番、旧サイトのデータが残存) / `beer_dev` (dev用に複製)

## サーバ側 初回セットアップ

1. レイアウト作成: `/srv/beer/{deploy,prod/html,dev/html}`。この deploy/ 一式を `/srv/beer/deploy/` へ
2. compose: `/srv/seisan3/deploy/docker-compose.yml` の services に
   `compose-snippet.yml` の beer-prod / beer-dev を追記、proxy の depends_on にも追加
3. nginx: **`nginx/drtbeer.conf` を `/srv/seisan3/deploy/nginx/conf.d/` に配置**
   (BEER_DOMAIN は既に実ドメイン(drtbeer.com)に設定済み。default_server は seisan3 のまま。
   `img/upload/` / `vendor/` / `*.local.php` の遮断が含まれているため、
   これを使わないと公開面が無防備になります。`nginx/beer.conf` は使わないこと)
4. `db_config.local.php` を `/srv/beer/{prod,dev}/html/` 直下に配置
   (実パスワードはサーバ内でのみ記入。**UID 33 (www-data) 所有・640**)
   - prod: `$db_name='beer'` / dev: `$db_name='beer_dev'`
5. `docker compose up -d` → proxy 再読込

## DB (beer_dev の作成)

RDS内で `beer` スキーマを `beer_dev` に複製する (mysqldump | mysql)。
本番 `beer` は旧サイトのデータがそのまま残っているため移行作業は不要。

## デプロイフロー (dev → ユーザー確認 → prod)

`db/` は配布物(tarball)に含まれない(意図通り。サーバ側で直接 SQL を当てる)。
一方 `try.php` は 007(`visitor.flash`)・008(`visitor.csrf_token`)の**両方**が無いと
落ち、`agecheck.php` も 008 が無いと落ちる。**マイグレーションを当てずにコードだけ
デプロイすると、写真機能が丸ごと 500 になる**(dev で実際に踏みかけた)。
コードを展開する前に、必ず対象環境の DB へ未適用の `db/migrations/*.sql` を当てること。

```bash
bash deploy/make_archive.sh            # コンテナ内で tarball 作成
IP=$(aws ec2 describe-instances --instance-ids i-0985dcea2eb68ed56 \
  --query 'Reservations[0].Instances[0].PublicIpAddress' --output text)
scp -i /root/.ssh/warikan_key.pem /tmp/claude-0/beer-deploy.tar.gz ec2-user@$IP:/tmp/
scp -i /root/.ssh/warikan_key.pem db/migrations/*.sql ec2-user@$IP:/tmp/beer-migrations/  # 事前に mkdir しておく

# 未適用の db/migrations/*.sql を番号順に dev へ当てる (apply_sql.php が
# schema_migrations で適用済みを見ているので、二重に流しても安全)。
# ALTER TABLE (DDL) なので --allow-ddl が要る。*_rollback.sql は当てない。
ssh -i /root/.ssh/warikan_key.pem ec2-user@$IP '
for f in $(ls /tmp/beer-migrations/[0-9]*.sql | grep -v _rollback); do
  sudo docker run --rm -v /srv:/srv -v /tmp/beer-migrations:/tmp/beer-migrations seisan3-php \
    php /srv/beer/deploy/apply_sql.php dev "$f" --allow-ddl
done'
ssh -i /root/.ssh/warikan_key.pem ec2-user@$IP 'sudo bash /srv/beer/deploy/deploy.sh dev /tmp/beer-deploy.tar.gz'
# dev URL (Basic認証) でユーザー確認 → OK後:

# prod にも同じ SQL を当ててからコードを展開する
ssh -i /root/.ssh/warikan_key.pem ec2-user@$IP '
for f in $(ls /tmp/beer-migrations/[0-9]*.sql | grep -v _rollback); do
  sudo docker run --rm -v /srv:/srv -v /tmp/beer-migrations:/tmp/beer-migrations seisan3-php \
    php /srv/beer/deploy/apply_sql.php prod "$f" --allow-ddl
done'
ssh -i /root/.ssh/warikan_key.pem ec2-user@$IP 'sudo bash /srv/beer/deploy/deploy.sh prod /tmp/beer-deploy.tar.gz'
```

## DNS / ALB (ドメイン確定後)

- Route 53 でゾーン作成 (新規ドメインならレジストラ登録 or Route 53 Domains で取得)
- A ALIAS → ALB "ALBwarikan"。HTTPS は ACM で証明書発行 → ALB :443 リスナーに SNI 追加
- ALB :80 リスナーに host-header ルール → seisan3-next-tg (HTTP で始める場合)

## 検証チェックリスト

- [ ] dev URL で全ページ応答
- [ ] 007 / 008 が適用済みであること (agecheck.php と try.php が 500 にならないことで確認できる)
- [ ] prod URL 到達 (HTTPS を選んだ場合は証明書有効)
- [ ] seisan3.com に影響なし
- [ ] reboot 後にコンテナ自動復帰
- [ ] 夜間停止の対象であることをユーザーと確認済みにする
