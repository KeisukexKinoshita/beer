# beer デプロイ状況 (2026-08-22)

## 完了済み (dev環境構築)

- サーバレイアウト: `/srv/beer/{deploy,prod/html,dev/html}` 作成済み
- コンテナ: `beer-prod` / `beer-dev` (seisan3-phpイメージ, restart:always) を
  `/srv/seisan3/deploy/docker-compose.yml` に追記して起動済み
  (バックアップ: `docker-compose.yml.bak-beer`。proxyのdepends_onは既存サービス無停止を
  優先して触っていない — nginx reload方式で反映)
- nginx vhost: `/srv/seisan3/deploy/nginx/conf.d/beer.conf`。
  **仮ホスト名 `beer.local` / `dev.beer.local`** (ドメイン確定後に置換して reload)
- dev デプロイ: `deploy.sh dev` でコード配置済み (1062ファイル)。全ページ群 200 を確認
- DB: `beer_dev` スキーマ作成済み (本番 `beer` の9テーブルを複製。products 38行/maker 19行/
  style 97行など本番データ生存確認)。`db_config.local.php` は prod=beer / dev=beer_dev で
  各htmlルートに配置 (UID33, 640)。セットアップは `/srv/beer/deploy/beer_db_setup.php`
  (サーバ内実行専用、認証情報はseisan3の設定から取得しチャット非経由)
- ALB: :80 リスナーに priority 20 で host-header `dev.beer.local` / `beer.local` →
  seisan3-next-tg のルールを追加済み (既存 seisan3 ルールに影響なし)
- 既存サービスへの影響: seisan3.com は全工程を通じて 200 応答を確認 (コンテナ無停止)

## dev の確認方法 (ドメイン取得まで)

MacのhostsにALBのIPを追加してブラウザで開く (Basic認証は seisan3 dev と共通):

```
# /etc/hosts に追記 (IPはALBの動的IPのため変わったら引き直す)
52.68.166.171 dev.beer.local
```

→ http://dev.beer.local/ (ALB IPの再取得: `dig ALBwarikan-2114989163.ap-northeast-1.elb.amazonaws.com`)

## 残作業 (ドメイン確定後)

- [ ] Route 53 ゾーン作成 + A ALIAS → ALBwarikan
- [ ] ACM証明書発行 → ALB :443 リスナーに SNI追加
- [ ] `beer.conf` の仮ホスト名を実ドメインに置換 + nginx reload
- [ ] ALB :80 ルールの host-header を実ドメインに更新 (dev用) 
- [ ] prod昇格: ユーザーのdev確認OK後 `deploy.sh prod`
- [ ] 再起動テスト (夜間停止→自動復帰で毎日実質検証されるが、明示確認を1回)
- [ ] 夜間自動停止の対象である旨をユーザーと合意

## 既知の注意点

- 投稿画像renameバグ (post/check/thank経由で画像未反映) ほか別課題は docs/loop-scope.md 参照
- サーバは夜間停止 (JST 1:00〜6:50)。beerも一緒に停止する

---

## 公開完了 (2026-09-02) —— https://drtbeer.com

**フェーズ③ 公開が完了した。** 構成は「seisan3-next サーバへのコンテナ同居」。

| 項目 | 内容 |
|---|---|
| 本番 | `https://drtbeer.com` → ALBwarikan → seisan3-next-tg → nginx(proxy) → `beer-prod` |
| dev | `https://dev.drtbeer.com`(Basic認証)→ 同上 → `beer-dev` |
| www | `https://www.drtbeer.com` → **301 で apex へ**(正規URLを1本にする) |
| HTTP | `http://drtbeer.com` → **301 で HTTPS へ**(ALB のリダイレクトルール) |

### 証明書

**旧証明書は 2024-02-12 に期限切れだった**(2023年発行・`drtbeer.com` のみ・更新も不可)。
新規に取り直した。

- ARN: `arn:aws:acm:ap-northeast-1:127146709373:certificate/3f2171d3-0651-47f3-9414-ce676b0f91e0`
- 対象: `drtbeer.com` / `dev.drtbeer.com` / `www.drtbeer.com`
- 期限: 2027-03-18(**ALB に載っているので自動更新される**)
- **apex の検証用CNAMEは旧証明書のものと同値だったため、そのまま流用できた。**
  追加したのは dev と www の2件だけ

### 変更した AWS リソース

すべて**追加のみ**。既存の seisan3 の設定は書き換えていない。

| リソース | 変更 |
|---|---|
| Route 53 `drtbeer.com` ゾーン | A レコード3件を UPSERT。**変更前は削除済みの ALB `beeralb-546652632` を指しており、ドメインはどこにも繋がっていなかった**(2022年の旧beer環境の残骸) |
| ALB :443 リスナー | 証明書を **SNI で追加**。デフォルト証明書(seisan3)は変更していない |
| ALB :443 ルール | 優先度25 に drtbeer 系3ホスト → seisan3-next-tg |
| ALB :80 ルール | 優先度26 に drtbeer 系3ホスト → **HTTPS へ 301** |
| nginx | `drtbeer.conf` を新設(実体は `/srv/seisan3/deploy/nginx/conf.d/`。コンテナ内は読み取り専用マウント) |

`beer.conf`(`beer.local` / `dev.beer.local`)は内部確認用としてそのまま残してある。

### 閉じたもの

`beer-dev-public.conf` を撤去した(`/srv/beer/beer-dev-public.conf.retired-20260902` に退避)。
ALB のホスト名で **dev サイトが認証なしに閲覧できる**一時経路で、公開ドメインが
できたので不要になった。残すと dev の中身が公開され、本番と同内容の重複にもなる。

### 作業中に確認したこと

nginx の reload 直後と ALB の証明書追加直後に、**毎回 `https://seisan3.com` が 200 を
返すことを確認**した。共用の ALB を触るときはこれを省かない。
