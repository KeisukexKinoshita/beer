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
3. nginx: `nginx/beer.conf` を `/srv/seisan3/deploy/nginx/conf.d/` に配置
   (BEER_DOMAIN を実ドメインに置換。default_server は seisan3 のまま)
4. `db_config.local.php` を `/srv/beer/{prod,dev}/html/` 直下に配置
   (実パスワードはサーバ内でのみ記入。**UID 33 (www-data) 所有・640**)
   - prod: `$db_name='beer'` / dev: `$db_name='beer_dev'`
5. `docker compose up -d` → proxy 再読込

## DB (beer_dev の作成)

RDS内で `beer` スキーマを `beer_dev` に複製する (mysqldump | mysql)。
本番 `beer` は旧サイトのデータがそのまま残っているため移行作業は不要。

## デプロイフロー (dev → ユーザー確認 → prod)

```bash
bash deploy/make_archive.sh            # コンテナ内で tarball 作成
IP=$(aws ec2 describe-instances --instance-ids i-0985dcea2eb68ed56 \
  --query 'Reservations[0].Instances[0].PublicIpAddress' --output text)
scp -i /root/.ssh/warikan_key.pem /tmp/claude-0/beer-deploy.tar.gz ec2-user@$IP:/tmp/
ssh -i /root/.ssh/warikan_key.pem ec2-user@$IP 'sudo bash /srv/beer/deploy/deploy.sh dev /tmp/beer-deploy.tar.gz'
# dev URL (Basic認証) でユーザー確認 → OK後:
ssh -i /root/.ssh/warikan_key.pem ec2-user@$IP 'sudo bash /srv/beer/deploy/deploy.sh prod /tmp/beer-deploy.tar.gz'
```

## DNS / ALB (ドメイン確定後)

- Route 53 でゾーン作成 (新規ドメインならレジストラ登録 or Route 53 Domains で取得)
- A ALIAS → ALB "ALBwarikan"。HTTPS は ACM で証明書発行 → ALB :443 リスナーに SNI 追加
- ALB :80 リスナーに host-header ルール → seisan3-next-tg (HTTP で始める場合)

## 検証チェックリスト

- [ ] dev URL で全ページ応答 + 投稿フルフロー動作
- [ ] prod URL 到達 (HTTPS を選んだ場合は証明書有効)
- [ ] seisan3.com に影響なし
- [ ] reboot 後にコンテナ自動復帰
- [ ] 夜間停止の対象であることをユーザーと確認済みにする
