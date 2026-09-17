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

---

## 写真リコメンド機能を dev に配布 (2026-09-17, task-2)

### Step 1 の確認結果

| 確認 | 結果 |
|---|---|
| (a) GD 拡張 | **無し**(`beer-dev` = `seisan3-php` そのまま) → Step 2b (beer専用イメージ) へ |
| (b) `img/upload` 所有者 | ディレクトリ無し(デプロイ未実施の段階) |
| (c) Apache `Options` | `Options FollowSymLinks` が有効、`Options Indexes FollowSymLinks` はコメントアウト側にも存在。**mod_autoindex 対策は nginx 側の `return 404` に一本化**(Apache 側の Options 設定に依存しない) |

### Step 2b: `beer-php` イメージを新設

`deploy/beer-php/Dockerfile` を新設(`seisan3-php` を土台に GD だけ追加。`seisan3-php` 自体は無改変)。
`deploy/compose-snippet.yml` の `beer-prod` / `beer-dev` の `image:` を `beer-php` に変更。

**ブリーフ記載の Dockerfile 案には実機で1回踏んだバグがあった**: `apt-get purge` の直後に
`apt-get autoremove -y` を走らせると、`-dev` パッケージの自動インストール依存だった
ランタイム共有ライブラリ(`libpng16` 等)まで「もう要らない」と判定されて一緒に消え、
`gd.so` が `libpng16.so.16: cannot open shared object file` でロード不能になった。
`autoremove` の行を削除して解決(サイズ増は約2.8MB分の `-dev` ヘッダ程度)。

サーバでの実施: `beer-php` をビルド → `gd_info()` で JPEG/PNG/WebP 対応を確認 →
`docker-compose.yml` の **`beer-dev` の `image:` 行(41行目)だけ** `beer-php` に変更
(`beer-prod` はtask-10の担当なので未変更)→ `docker compose up -d beer-dev` で入替。
**毎ステップ後に `https://seisan3.com` が 200 であることを確認済み。**

### Step 3-4: nginx / コードの配布

- `deploy/nginx/drtbeer.conf` をサーバへ配置(`nginx -t` OK → `nginx -s reload`)。reload後も
  `seisan3.com` は 200
- `deploy/deploy.sh` が**サーバ側で旧版のままだった**(task-1 のローカル修正が未反映で、
  `img/upload` の chown が無く、初回デプロイで `img/upload` が root 所有のまま作られた)。
  最新の `deploy.sh` をサーバへ同期し、再デプロイして `img/upload` が UID 33 所有になることを確認
- `api_config.local.php` を `db_config.local.php` と同じ方式(UID33・640)でサーバへ注入
  (中身は表示していない)

### Step 5: 遮断の確認

`/img/upload/` `/vendor/` `/api_config.local.php` `/vendor/anthropic-ai/sdk/examples/messages.php`
の4パスすべてが **404**(`/` は 401 で通常通り)。公開ドメイン経由(`https://dev.drtbeer.com`)でも
同じ結果。**Basic認証の資格情報を持たない状態で確認できた** — `return 404` は nginx の
rewriteフェーズで auth_basic より先に評価されるため、未認証でも該当パスは404になる
(認証情報が無くても遮断の有無自体は検証できる)。

### Step 6: 実機での通し試験

**未実施(BLOCKED)。** Anthropic API を実際に呼ぶ `try.php` への写真アップロード
(`curl -F photo=@...`)は、このセッションの権限で**拒否**された(計画1の `git push` /
API呼び出しと同じ拒否パターン)。1回の失敗後、迂回せずに停止した。
`upload` テーブルは0行、`img/upload/` は空、`beer-dev` のログにも POST の形跡は無く、
課金や中途半端な状態は発生していない。ユーザーに以下のいずれかを依頼する必要がある:
- ブラウザで `https://dev.drtbeer.com/try.php` を開いて実機で1枚上げる(Basic認証は
  dev/prod共通で利用者が把握している前提)
- またはこのセッションに実行許可を与えて再試行させる

その他の確認項目(GD/写真保存/DB行/PHP警告)は、実機試験ができ次第あわせて見ること。
