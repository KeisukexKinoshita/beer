# 写真から似た1本を薦める機能 — 実装計画(2/3: 公開できる状態にする)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 計画1で作った機能を、**利用者が実際に触れる状態**にする。まず dev で触れるようにし、
最後に prod のトップを差し替えて公開する。

**Architecture:** 新しい仕組みは足さない。**塞ぐ → 配る → 画面を整える → 法務 → prod** の順に進む。
タスク2が終わった時点で、あなたのスマホから dev で写真を上げられるようになる。

**Tech Stack:** PHP 8.5 (Apache コンテナ) / nginx (proxy) / MariaDB (RDS) / Claude Sonnet 5

**Spec:** `docs/superpowers/specs/2026-09-16-photo-recommender-design.md`
(特に **§13-b「計画2に持ち越すもの」**が、この計画の入力である)

**前の計画:** `docs/superpowers/plans/2026-09-16-photo-recommender-core.md`(完了)

## 2026-09-19 追記: この計画の範囲が変わった

利用者が dev で実際に使い「**あっけない**」と返ってきたことを受けて、
価値の定義と動線を組み直した(`docs/superpowers/specs/2026-09-19-evaluate-expand-keep-design.md`)。

**この計画のうち、タスク3〜6だけを実施する。**

| タスク | 扱い |
|---|---|
| 1 公開面を塞ぐ | **完了** |
| 2 dev に配る | **完了** |
| **3 AdSense撤去・privacy更新** | **実施する**(新設計と衝突しない) |
| **4 利用者規約** | **実施する**(同上) |
| **5 年齢確認** | **実施する**(同上) |
| **6 特性テストの取り直し** | **実施する。画面を作り変える前にやる意味がある** |
| 7 トップ差し替え | **新設計に吸収**。ここでは作らない |
| 8 候補3件の画面 | **新設計に吸収**(§3「読み取りが確かでないとき」) |
| 9 クリック記録 | **新設計に吸収**(§6「推薦に理由を添える」と一緒に作る) |
| 10 prod へ出す | **新設計の完了後**に行う |

タスク6を7より先に置いた理由は、そのまま生きている。
**画面を作り変える前にゴールデンを取り直さないと、壊れたものごと固定してしまう。**

## Global Constraints

- **DBは `scripts/remote_sql.sh`(beer-data-pipeline スキル内)以外から触らない。**
  このコンテナの `/etc/hosts` は RDS のホスト名を `127.0.0.1` に向けており、
  直接接続すると**ローカルのテストDBを本番と誤認する**。AWS CLI が無いので
  `BEER_EC2_IP=54.168.54.119` を頭に付ける
- **prod に触るのは最後のタスクだけ。** それまでは dev のみ
- **秘密情報をリポジトリとチャットに置かない。** `api_config.local.php` / `db_config.local.php` は
  サーバ内で注入する。中身を読まない・表示しない
- **サーバは seisan3 と同居している。** 共有物(nginx 設定・docker-compose・イメージ)を
  触ったら、**必ず seisan3.com が 200 を返すことを確認する**
- **API を呼ぶのは実機確認のときだけ。** 1枚 約0.8円。呼ぶ前に必ず一声かける
- **`tests/eval/run_eval.php` は引数なしで実行すると15枚ぶん課金する。**
  `--replay` か `--rescore` を使う
- **既存の特性テスト(`tests/runner/`)は 2026-08-22 の凍結写しを検査しており、現在のコードを見ていない。**
  タスク6で再ベースライン化するまで、通っても意味がない
- 単体テストは `php tests/unit/run.php`。現在 **142 pass / 0 fail**。壊さない
- コミットは UI 修正とロジック修正を分ける

## この計画に**含まれない**もの

- **LINE ログイン** — 匿名で2回使えるので公開には要らない。独立した仕組み(OAuth の往復)なので別計画
- PR枠・蔵向けウィジェット・月次レポート — 計画3
- 200銘柄の表記の棚卸し(`pr0151` の「有頂点」誤記を含む)— データの仕事なので `/beer-data-pipeline` で

---

## ファイル構成

| パス | 何をするか |
|---|---|
| `deploy/nginx/drtbeer.conf` | `img/upload/` `vendor/` `*.local.php` を外から遮断(修正) |
| `deploy/deploy.sh` | `img/upload` を apache 書込可に(修正) |
| `deploy/beer-php/Dockerfile` | GD 入りのイメージ(**サーバに GD が無い場合のみ**新規) |
| `common/nebula/head.php` | AdSense タグの削除(修正) |
| `privacy.php` | AdSense の記述を削除し、**Anthropic への送信**を追記(修正) |
| `terms.php` | 利用規約(新規) |
| `common/reco/age.php` | 年齢確認の判定(新規) |
| `agecheck.php` | 年齢確認の画面(新規) |
| `index.php` | **トップを露光案に差し替え**(全面改訂) |
| `common/reco/handle.php` | 候補3件の分岐を足す(修正) |
| `try.php` | 候補から選ぶ画面、推薦クリックの記録(修正) |
| `common/reco/identify.php` | 候補を複数返せるようにする(修正) |
| `tests/runner/` | ゴールデンを現在のコードで取り直す(修正) |
| `db/seeds/015_*.sql` | prod 昇格時の記録用(新規・必要なら) |

---

### Task 1: 公開面を塞ぐ

計画1のレビューで出た**公開前に必ず塞ぐもの**。まだ誰も外からアクセスできないうちに済ませる。

**Files:**
- Modify: `deploy/nginx/drtbeer.conf`
- Modify: `deploy/deploy.sh:36-38`

**Interfaces:**
- Consumes: なし
- Produces: なし(設定のみ)

- [ ] **Step 1: nginx で3つを遮断する**

`deploy/nginx/drtbeer.conf` の**本番と dev の両方の `server` ブロック**に、
`location / { ... }` より**前に**次を入れる(`^~` は前方一致で、正規表現より優先される)。

```nginx
    # --- 外から触られては困るもの ---
    # いずれも Apache 側の .htaccess では防げない。**AllowOverride None** のため
    # (このファイルの sitemap の節にも同じ理由が書いてある)。
    # nginx で止めるのが唯一の手段。

    # 利用者がアップロードした写真。本人の履歴でのみ見せる約束(設計書 §4)。
    # Apache の mod_autoindex が既定で有効なので、塞がないと
    # ディレクトリ一覧に全利用者の写真が並ぶ。
    location ^~ /img/upload/ { return 404; }

    # composer で入れた第三者のコード。SDK の examples/*.php が29本、
    # 直接リクエストできてしまう。公開面に置く理由がない。
    location ^~ /vendor/ { return 404; }

    # 接続情報と APIキー。PHP ハンドラが落ちたときに平文で配信される。
    # api_config.local.php は**課金に直結する鍵**を持っている。
    location ~ \.local\.php$ { return 404; }
```

- [ ] **Step 2: 書き込み先を用意する**

`deploy/deploy.sh` の該当箇所を次に置き換える。

```bash
# アップロード先ディレクトリを apache(UID 33) 書込可能にする。
# img/upload は写真リコメンド機能の保存先。**ここが root 所有のままだと
# 写真の保存が必ず失敗し、DB には保存したという行だけが残る**(計画1のレビュー I-4)。
sudo mkdir -p "$DEST/img/tmp" "$DEST/img/product" "$DEST/img/upload"
sudo chown -R 33:33 "$DEST/img/tmp" "$DEST/img/product" "$DEST/img/upload"
```

- [ ] **Step 3: nginx の文法を確かめる**

サーバに配る前に、手元で構文だけ見る。

Run:
```bash
cd /workspace/tool/beer
grep -c "location \^~ /img/upload/" deploy/nginx/drtbeer.conf
grep -c "location \^~ /vendor/" deploy/nginx/drtbeer.conf
grep -c "location ~ \\\\.local\\\\.php\\$" deploy/nginx/drtbeer.conf
```
Expected: **どれも 2**(本番と dev の両方に入っている)。1 なら片方に入れ忘れている。

- [ ] **Step 4: コミット**

```bash
git add deploy/nginx/drtbeer.conf deploy/deploy.sh
git commit -m "公開面から img/upload・vendor・*.local.php を遮断し、img/upload を書込可にする"
```

---

### Task 2: dev に配って、実機で1枚通す

**この計画でいちばん大事なタスク。** ここが終われば、利用者が自分のスマホで触れる。

**Files:**
- Create: `deploy/beer-php/Dockerfile`(**GD が無い場合のみ**)
- Modify: `deploy/compose-snippet.yml`(同上)

**Interfaces:**
- Consumes: Task 1 の nginx 設定と deploy.sh
- Produces: dev で動く `https://dev.drtbeer.com/try.php`

- [ ] **Step 1: サーバの状態を3つ確かめる**

**何を直すかは、この結果で決まる。推測で進めない。**

Run(ssh は `scripts/remote_sql.sh` と同じ経路を使う。実行できない場合は利用者に依頼する):
```bash
# (a) GD 拡張が入っているか
sudo docker exec beer-dev php -m | grep -i "^gd$" || echo "GD なし"
# (b) img/upload の所有者
sudo docker exec beer-dev ls -ld /var/www/html/img/upload 2>/dev/null || echo "ディレクトリ無し"
# (c) ディレクトリ一覧が出るか(Task 1 適用前の状態を見ておく)
sudo docker exec beer-dev sh -c 'grep -r "Options" /etc/apache2/apache2.conf | head -3'
```

結果を報告に書く。**(a) の答えで次の Step が変わる。**

- [ ] **Step 2a: GD がある場合 — 何もしない**

Step 3 へ進む。

- [ ] **Step 2b: GD が無い場合 — beer 専用のイメージを作る**

**`seisan3-php` を作り直してはいけない。** seisan3 と共用なので、あちらを壊す危険がある。

`deploy/beer-php/Dockerfile`(新規):

```dockerfile
# beer 専用のイメージ。seisan3-php を土台に GD だけを足す。
#
# GD が要るのは、アップロードされた写真を長辺1568pxに縮めるため
# (common/reco/identify.php の identify_shrink)。縮めないと入力トークンが
# 4倍ほどになり、1枚あたりの費用がそのぶん増える。
#
# **seisan3-php 自体は変更しない。** 共用しているので、あちらを壊さないため。
FROM seisan3-php

RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg-dev libpng-dev libwebp-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd \
 && apt-get purge -y libjpeg-dev libpng-dev libwebp-dev \
 && apt-get autoremove -y && rm -rf /var/lib/apt/lists/*
```

`deploy/compose-snippet.yml` の `beer-prod` / `beer-dev` の `image:` を `beer-php` に変え、
コメントで理由を残す。

サーバでの手順(報告に手順と出力を残す):
```bash
# イメージを作る
sudo docker build -t beer-php -f /srv/beer/deploy/beer-php/Dockerfile /srv/beer/deploy/beer-php
# GD が入ったか
sudo docker run --rm beer-php php -m | grep -i "^gd$"
# コンテナを入れ替える(beer-dev だけ。prod は最後のタスク)
sudo docker compose -f /srv/seisan3/deploy/docker-compose.yml up -d beer-dev
```

**seisan3 を壊していないことを必ず確認する**:
```bash
curl -s -o /dev/null -w "%{http_code}\n" https://seisan3.com
```
Expected: `200`。違ったら**そこで止めて報告する**。

- [ ] **Step 3: nginx の設定を配る**

```bash
# 手元で作った drtbeer.conf をサーバへ置き、文法を見てから反映する
sudo docker exec seisan3-proxy nginx -t
sudo docker exec seisan3-proxy nginx -s reload
```
`nginx -t` が通らなければ**反映しない**。

- [ ] **Step 4: コードを配る**

```bash
cd /workspace/tool/beer
bash deploy/make_archive.sh /tmp/claude-0/beer-deploy.tar.gz
# サーバへ送って展開(既存の手順どおり)
sudo bash /srv/beer/deploy/deploy.sh dev /tmp/beer-deploy.tar.gz
```

**`api_config.local.php` をサーバに置く。** 配布物には含まれないので、
`db_config.local.php` と同じやり方で注入する。**中身は表示しない。**

- [ ] **Step 5: 遮断が効いているか確かめる**

```bash
for p in /img/upload/ /vendor/ /api_config.local.php /vendor/anthropic-ai/sdk/examples/messages.php; do
  printf "%-56s " "$p"
  curl -s -o /dev/null -w "%{http_code}\n" -u "<dev のBasic認証>" "https://dev.drtbeer.com$p"
done
```
Expected: **すべて 404**。200 が1つでもあれば Task 1 の設定が効いていない。

- [ ] **Step 6: 実機で1枚通す(約0.8円)**

**利用者に一声かけてから**、ブラウザで `https://dev.drtbeer.com/try.php` を開き、
`data/sample/IMG_1016.jpeg`(BrewDog HAZY JANE)を上げる。

見るところ:
- 「HAZY JANE」と出て、似た3本が出るか
- **写真が実際に保存されているか**(`sudo docker exec beer-dev ls -l /var/www/html/img/upload/`)
- `upload` テーブルに行が入っているか(`remote_sql.sh dev --query`)
- **PHP の警告が出ていないか**(`sudo docker logs beer-dev --tail 30`)

**失敗したらここで止めて報告する。** よくある原因は GD 不在と書き込み権限。

- [ ] **Step 7: 記録して、コミット**

`docs/deploy-status.md` に、この時点の状態(GD の有無・イメージ・遮断の確認結果)を追記する。

```bash
git add deploy/ docs/deploy-status.md
git commit -m "dev に写真リコメンド機能を配り、遮断と実機動作を確認"
```

---

### Task 3: AdSense を撤去し、privacy.php を実態に合わせる

**Files:**
- Modify: `common/nebula/head.php:36-44`
- Modify: `privacy.php`

**Interfaces:**
- Consumes: なし
- Produces: なし

- [ ] **Step 1: タグを消す**

`common/nebula/head.php` の AdSense を読み込む `if` ブロックを、コメントごと削除する。
**`ads.txt` は残す**(利用者の決定。アカウントも放置する)。

- [ ] **Step 2: privacy.php から AdSense を消す**

- 外部送信の表から **Google AdSense の行**を削除
- 「3. 広告について」の節を削除

- [ ] **Step 3: Anthropic への送信を足す**

外部送信の表に、次の行を足す。**「等」を使わず、送るものを具体的に書く**
(改正電気通信事業法の外部送信規律)。

```html
      <tr>
        <td>Anthropic PBC<br>（Claude API）</td>
        <td>ビールの写真を送信したとき</td>
        <td>アップロードされた写真そのもの</td>
        <td>写真に写っているビールの銘柄・スタイル・色を読み取るため</td>
      </tr>
```

あわせて、本文に次の主旨を足す(文言は整えてよい):

- 写真は**銘柄の判定のためだけ**に送ること
- **氏名・メールアドレスは送らない**こと(そもそも取得していない)
- 送った写真は**公開面には出ない**こと
- 保存した写真の**削除を求められる**こと(問い合わせフォームから)

- [ ] **Step 4: 手元で表示を確かめる**

```bash
cd /workspace/tool/beer
nohup php -S 127.0.0.1:8080 > /tmp/php-server.log 2>&1 &
sleep 2
curl -s http://127.0.0.1:8080/privacy.php | grep -c "AdSense"
curl -s http://127.0.0.1:8080/privacy.php | grep -c "Anthropic"
curl -s http://127.0.0.1:8080/ | grep -c "adsbygoogle"
```
Expected: 1つ目 **0** / 2つ目 **1以上** / 3つ目 **0**

- [ ] **Step 5: コミット**

```bash
git add common/nebula/head.php privacy.php
git commit -m "AdSense を撤去し、写真の外部送信先として Anthropic を明記する"
```

---

### Task 4: 利用規約を新設する

**Files:**
- Create: `terms.php`
- Modify: `common/nebula/footer.php`(リンクを足す)
- Modify: `sitemap.php`(`/terms.php` を足す)

**Interfaces:**
- Consumes: なし
- Produces: `https://drtbeer.com/terms.php`

- [ ] **Step 1: `terms.php` を書く**

骨格は `privacy.php` と同じ形にする。**一人称は「私」**(既存の about.php に合わせる)。

```php
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/helpers.php';
$title = '利用規約';
$desc  = 'Darth Beer.com の利用規約です。写真の扱い、推薦の性質、掲載料を受け取った枠の表示についてお知らせします。';
require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/head.php';
?>
<body>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/header.php'; ?>
<div class="wrap doc">
  <h1>利用規約</h1>
  <p class="lead">最終更新: 2026-09-17</p>

  <h2>1. アップロードされた写真</h2>
  <!-- 保存すること / 銘柄判定のため Anthropic に送ること /
       公開面に出さないこと / 削除の求め方 -->

  <h2>2. 推薦について</h2>
  <!-- 推定であって保証ではないこと -->

  <h2>3. 掲載料を受け取った枠</h2>
  <!-- 「PR」と表示すること / 推薦の順位には影響しないこと -->

  <h2>4. 年齢</h2>
  <h2>5. 禁止事項</h2>
  <h2>6. 免責</h2>
  <h2>7. 準拠法</h2>
</div>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/common/nebula/footer.php'; ?>
```

各節の中身は下表のとおり。**コメントを本文に置き換えてから commit すること。**

必ず入れる項目:

| 節 | 書くこと |
|---|---|
| アップロードされた写真 | 保存すること / **銘柄判定のため Anthropic に送ること** / 公開面に出さないこと / 削除の求め方 |
| 推薦の性質 | **推定であって保証ではない**。味の好みを保証しない |
| PR枠 | **掲載料を受け取った枠であることを表示する**。**推薦順位には影響しない**(計画3で実装するが、約束として先に書く) |
| 禁止事項 | 大量アップロード / ビール以外の投稿 / 他人の権利を侵す画像 |
| 年齢 | 20歳未満の利用をお断りすること |
| 免責 | 掲載情報の正確性を保証しないこと / 利用により生じた損害の免責 |
| 準拠法 | 日本法 |

- [ ] **Step 2: 導線を足す**

`common/nebula/footer.php` の privacy / about の並びに「利用規約」を足す。
`sitemap.php` の URL 一覧に `/terms.php` を足す。

- [ ] **Step 3: 確かめる**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8080/terms.php
curl -s http://127.0.0.1:8080/ | grep -c "terms.php"
curl -s http://127.0.0.1:8080/sitemap.php | grep -c "terms.php"
```
Expected: `200` / `1以上` / `1`

- [ ] **Step 4: コミット**

```bash
git add terms.php common/nebula/footer.php sitemap.php
git commit -m "利用規約を新設し、フッタとサイトマップに載せる"
```

---

### Task 5: 年齢確認

**Files:**
- Create: `common/reco/age.php`
- Create: `agecheck.php`
- Modify: `try.php`(確認前なら `agecheck.php` へ送る)
- Test: `tests/unit/age_test.php`

**Interfaces:**
- Consumes: `visitor_current()` / `db()`
- Produces:
  - `age_confirmed(string $visitorId): bool`
  - `age_confirm(string $visitorId): void` — `visitor.age_confirmed` を 1 にする

- [ ] **Step 1: 失敗するテストを書く**

`tests/unit/age_test.php`:

```php
<?php
$_SERVER['DOCUMENT_ROOT'] = '/workspace/tool/beer';
require_once __DIR__ . '/../../common/reco/age.php';
require_once __DIR__ . '/../../common/reco/repo.php';

$vid = 'age' . str_repeat('0', 29);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen) VALUES (:v, NOW(), NOW())
               ON DUPLICATE KEY UPDATE age_confirmed = 0")->execute([':v' => $vid]);

eq(age_confirmed($vid), false, '初めての訪問者は未確認');
age_confirm($vid);
eq(age_confirmed($vid), true,  '確認したら記録される');
eq(age_confirmed('存在しない訪問者' . str_repeat('0', 16)), false, '未知の訪問者は未確認');

db()->prepare("DELETE FROM visitor WHERE visitor_id = :v")->execute([':v' => $vid]);
```

- [ ] **Step 2: 落ちることを確かめる**

Run: `php tests/unit/run.php age`
Expected: Fatal error — `Failed opening required '.../common/reco/age.php'`

- [ ] **Step 3: 実装する**

`common/reco/age.php`:

```php
<?php
declare(strict_types=1);
/*
 * 年齢確認(設計書 §12)。酒類を扱うので、20歳未満への訴求を避ける。
 *
 * **記憶先はサーバ側**(visitor.age_confirmed)。ブラウザのローカル保存だけにすると
 * Safari では7日で消えて、そのたびに確認画面が出る。
 */

require_once __DIR__ . '/../nebula/helpers.php';

function age_confirmed(string $visitorId): bool
{
    $st = db()->prepare("SELECT age_confirmed FROM visitor WHERE visitor_id = :v");
    $st->execute([':v' => $visitorId]);
    return (int)$st->fetchColumn() === 1;
}

function age_confirm(string $visitorId): void
{
    $st = db()->prepare("UPDATE visitor SET age_confirmed = 1 WHERE visitor_id = :v");
    $st->execute([':v' => $visitorId]);
}
```

- [ ] **Step 4: 画面を作る**

`agecheck.php` — 露光案の見た目(`assets/css/exposure.css` を使う)で、1画面。

- 「20歳以上ですか」
- **はい** → `age_confirm()` して、`?next=` で渡された安全なパス(先頭が `/` で `//` でないもの)へ戻す
- **いいえ** → 説明だけの画面。**「戻る」ボタンを出さない**

`try.php` の先頭(`visitor_current()` の直後)に:

```php
// 酒類を扱うので、写真の受付より前に年齢を確認する(設計書 §12)
if (!age_confirmed($visitorId)) {
    header('Location: /agecheck.php?next=/try.php');
    exit;
}
```

- [ ] **Step 5: 通ることを確かめる**

Run: `php tests/unit/run.php age`
Expected: `3 pass / 0 fail`

Run: `php tests/unit/run.php` → **145 pass / 0 fail**(142 + 3)。
**実際の数が違ったらそのまま報告する。**

- [ ] **Step 6: 画面の往復を確かめる**

```bash
curl -s -o /dev/null -w "%{http_code} -> %{redirect_url}\n" http://127.0.0.1:8080/try.php
```
Expected: `302 -> /agecheck.php?next=/try.php`

`next` に外部URLを入れても飛ばされないこと(`?next=https://example.com`)も確かめる。

- [ ] **Step 7: コミット**

```bash
git add common/reco/age.php agecheck.php try.php tests/unit/age_test.php
git commit -m "年齢確認を追加し、記憶先をサーバ側に置く"
```

---

### Task 6: 特性テストのゴールデンを取り直す

**トップを差し替える前に**やる。いまのゴールデンは 2026-08-22 の凍結写しで、
**現在のコードを1行も検査していない**。この状態でトップを差し替えると、
既存ページの回帰を拾う手段が目視だけになる。

**Files:**
- Modify: `tests/runner/run_all.sh`(`make_sandbox.sh` を呼ぶ)
- Modify: `tests/golden/*.html`(取り直し)
- Modify: `tests/cases.md`(経緯を追記)

**Interfaces:**
- Consumes: なし
- Produces: 現在のコードを検査するゴールデン

- [ ] **Step 1: sandbox が更新されない原因を直す**

`tests/runner/run_all.sh` の先頭で `make_sandbox.sh` を呼ぶ。

```bash
#!/bin/bash
# 全ケース実行。使い方: bash tests/runner/run_all.sh
#
# **sandbox を毎回作り直す。** 以前は作り直していなかったため、2026-08-22 の
# 凍結写しを検査し続けており、リニューアル後のコードを1行も見ていなかった。
set -eu
cd "$(dirname "$0")/../.."
bash tests/runner/make_sandbox.sh
for f in tests/runner/cases/*.json; do
  bash tests/runner/run_case.sh "$f"
done
```

- [ ] **Step 2: 現在のコードで走らせ、差分を**目で見る**

```bash
bash tests/runner/start_db.sh
bash tests/runner/run_all.sh
bash tests/runner/compare_golden.sh
```

**ほぼ全ケースで差分が出るはず**(旧サイトと現行サイトは別物なので)。

**ここが肝心**: 差分をそのまま受け入れない。**ケースごとに `tests/out/` を開いて、
いまのページとして正しいかを目で確かめてから**ゴールデンにする。
「現在の挙動を凍結する」だけでは、いま壊れているものも一緒に凍結してしまう。

見るところ(各ページ):
- PHP の Warning / Notice / Fatal が混ざっていないか
- スタイル色が**減彩のもの**になっているか(`#86adbd` など)
- 絞り込みチップが**日本語のラベル**になっているか(`#5fd0ff` のような色コードでないか)

- [ ] **Step 3: ゴールデンを更新する**

確認したものだけを `tests/golden/` へ写す。**確認していないものは写さない。**

- [ ] **Step 4: 2回続けて緑になることを確かめる**

```bash
bash tests/runner/run_all.sh && bash tests/runner/compare_golden.sh
bash tests/runner/run_all.sh && bash tests/runner/compare_golden.sh
```
Expected: 2回とも差分なし。1回目と2回目で違うなら、出力に時刻など**揺れるもの**が混ざっている。

- [ ] **Step 5: 経緯を残す**

`tests/cases.md` に、**なぜ取り直したか**(sandbox が凍結写しだったこと、
`make_sandbox.sh` が呼ばれていなかったこと)を追記する。

- [ ] **Step 6: コミット**

```bash
git add tests/
git commit -m "特性テストの sandbox を毎回作り直し、ゴールデンを現在のコードで取り直す"
```

---

### Task 7: トップを差し替える

**Files:**
- Modify: `index.php`(全面改訂)
- Modify: `try.php`(トップに統合するぶんを整理)
- Modify: `common/nebula/header.php`(タブの整理)

**Interfaces:**
- Consumes: `reco_handle_upload()` / `age_confirmed()` / `group_map()`
- Produces: `https://drtbeer.com/` が写真の受付から始まる

- [ ] **Step 1: 何を残し、何を捨てるかを決める**

**捨てるもの**(設計書 §11):
- 「宇宙は、味わいでできている。」を含む見出し(`index.php:51`)
- グラデーション文字(`.grad`)
- 紫の地(`--bg: #0b0820`)

**残すもの**:
- 3Dの銀河(Flavour Galaxy)は**「位置づけの地図」として別タブに残す**(設計書 §3)。
  トップからは外す
- 「最新のBeer」「人気のBrewery」も別タブへ

**新しいトップ** = いまの `try.php` の内容。

- [ ] **Step 2: `index.php` を書き換える**

`try.php` の中身を `index.php` に移し、`try.php` は `index.php` へ 301 で寄せる
(dev で共有したURLが死なないように)。

タブは `common/nebula/header.php` に集約する:

```php
<nav class="ex-tabs">
  <a href="/"                       class="<?= $navActive === 'find'    ? 'on' : '' ?>">さがす</a>
  <a href="/beer/products.php"      class="<?= $navActive === 'beer'    ? 'on' : '' ?>">銘柄</a>
  <a href="/brewery/makers.php"     class="<?= $navActive === 'brewery' ? 'on' : '' ?>">蔵</a>
  <a href="/style/styles.php"       class="<?= $navActive === 'style'   ? 'on' : '' ?>">スタイル</a>
</nav>
```

- [ ] **Step 3: 消えた銘柄で 500 にならないようにする**

`common/reco/handle.php` が `beer_by_id($result['matched_product_id'])` の戻り値を
そのまま `reco_pick()` に渡している。**棚卸しで銘柄を消したあとに同じ写真が再送されると
`null` が渡って `TypeError` で 500 になる**(計画1のレビュー M-2)。公開ページなので塞ぐ。

```php
    $seed = null;
    if ($result['matched_product_id']) {
        $seed = beer_by_id($result['matched_product_id']);
        // 棚卸しで消えた銘柄を指している記録が再生されることがある。
        // 500 を返すより、読み取れた特徴だけで薦めるほうがよい。
        if (!$seed) { $result['matched_product_id'] = null; }
    }
    if (!$seed) {
        $seed = ['ProductID' => null, 'StyleID' => $result['style_guess'],
                 'FamilyName' => '', 'StyleName' => '', 'MakerID' => null, 'Alcohol' => null];
        // …既存のスタイル解決はそのまま
    }
```

`tests/unit/handle_test.php` に検査を足す:

```php
// --- 記録が消えた銘柄を指していても 500 にしない ---
// 実在しない pr9999 を持つ upload 行を直接作り、同じ画像ハッシュで再送する
// (identify_call の一覧照合を通らない経路なので、DB に直接入れる)
$vid = 'gon' . str_repeat('0', 29);
$hash = str_repeat('e', 64);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen) VALUES (:v, NOW(), NOW())
               ON DUPLICATE KEY UPDATE last_seen = NOW()")->execute([':v' => $vid]);
db()->prepare("INSERT INTO upload (visitor_id, created_at, image_hash, is_beer, product_id, status)
               VALUES (:v, NOW(), :h, 1, 'pr9999', 'ok')")->execute([':v' => $vid, ':h' => $hash]);
$r = reco_handle_upload($vid, ['error' => UPLOAD_ERR_OK,
    'tmp_name' => __DIR__ . '/../../data/sample/IMG_1016.jpeg', 'size' => 1024], null);
eq($r['view'], 'result', '消えた銘柄を指す記録でも画面は出る(500 にしない)');
db()->prepare("DELETE FROM upload WHERE visitor_id = :v")->execute([':v' => $vid]);
db()->prepare("DELETE FROM visitor WHERE visitor_id = :v")->execute([':v' => $vid]);
```

- [ ] **Step 4: 既存ページが壊れていないことを確かめる**

```bash
bash tests/runner/run_all.sh && bash tests/runner/compare_golden.sh
```
**Task 6 でゴールデンを取り直してあるので、ここで初めて意味のある検査になる。**
差分が出たら、**そのページを開いて理由を確かめる**。

- [ ] **Step 5: 手元で全ページを開く**

```bash
for p in / /beer/products.php /brewery/makers.php /style/styles.php /privacy.php /terms.php /about.php; do
  printf "%-28s " "$p"
  curl -s -o /tmp/p.html -w "%{http_code} " "http://127.0.0.1:8080$p"
  grep -ciE "warning|notice|fatal error" /tmp/p.html | tr '\n' ' '
  echo "(警告の件数)"
done
```
Expected: すべて `200` かつ 警告 `0`。

- [ ] **Step 6: コミット(2つに分ける)**

```bash
git add common/nebula/header.php
git commit -m "タブをヘッダに集約する"
git add index.php try.php
git commit -m "トップを写真の読み取りから始まる画面に差し替える"
```

---

### Task 8: 確度が中くらいのときに候補から選ばせる

設計書 §6 が定めているのに、計画1で落としていたもの。

**Files:**
- Modify: `common/reco/identify.php`(候補を複数返せるようにする)
- Modify: `common/reco/handle.php`
- Modify: `index.php`(選ぶ画面)
- Test: `tests/unit/identify_test.php` / `handle_test.php`

**Interfaces:**
- Produces: `identify_parse()` の戻り値に `candidates` を足す
  — `[['product_id' => 'pr0013', 'confidence' => 0.6], ...]`(最大3件、無ければ空配列)

- [ ] **Step 1: 構造化出力に候補を足す**

`identify_transport_anthropic()` のスキーマに、次を足す。
**`minimum` / `maximum` は API が拒否するので使わない**(計画1で判明済み)。

```php
            'candidates' => ['type' => 'array', 'items' => [
                'type' => 'object', 'additionalProperties' => false,
                'required' => ['product_id', 'confidence'],
                'properties' => [
                    'product_id' => ['type' => 'string'],
                    'confidence' => ['type' => 'number'],
                ]]],
```

プロンプトに1文足す:

```
確度が高くないときは、候補を最大3件 candidates に入れてください。
product_id は一覧のID(pr で始まる6文字)です。
```

- [ ] **Step 2: 受け取り側を直す**

`identify_parse()` で、`candidates` の各要素に**`matched_product_id` と同じ2段の検査**をかける
(形式 `/^pr\d{4}$/`、その後 `identify_call()` で一覧との照合)。
**検査を通らないものは捨てる。**

- [ ] **Step 3: テストを足す**

`tests/unit/identify_test.php`:

```php
// --- 候補にも同じ検査をかける ---
$withCands = function (array $cands): array {
    return ['content' => [['type' => 'text', 'text' => json_encode(
        ['is_beer' => true, 'candidates' => $cands])]]];
};
eq(count(identify_parse($withCands([
    ['product_id' => 'pr0013', 'confidence' => 0.6],
    ['product_id' => 'HAZY JANE', 'confidence' => 0.5],
    ['product_id' => 'st0007', 'confidence' => 0.4],
]))['candidates']), 1, '形式が正しい候補だけが残る');
eq(identify_parse($withCands([]))['candidates'], [], '候補が無ければ空配列');
```

- [ ] **Step 4: 画面を作る**

`identify_branch()` が `'choose'` を返し、**かつ `candidates` が2件以上あるとき**だけ、
1件を断定せずに**候補を並べて選ばせる**。選んだものを `confirm` と同じ経路で記録する。

候補が1件以下なら、**いまの挙動のまま**(1件表示+確認)。

- [ ] **Step 5: 確かめる**

Run: `php tests/unit/run.php`
Expected: 増えた件数。**実際の数を報告する。**

CLI のハーネスで `'choose'` かつ候補3件の応答を再生し、**選ぶ画面が出ること**を確かめる。

- [ ] **Step 6: コミット**

```bash
git add common/reco/identify.php common/reco/handle.php index.php tests/unit/
git commit -m "確度が中くらいのとき、候補から選ばせる画面を追加"
```

---

### Task 9: 推薦のクリックを記録する

これが無いと、**蔵向け月次レポート(計画3)のクリック数の根拠が取れない**。

**Files:**
- Create: `go.php`(クリックを記録してから飛ばす)
- Modify: `index.php`(推薦リンクを `go.php` 経由に)
- Test: `tests/unit/handle_test.php`

**Interfaces:**
- Consumes: `event_record()`
- Produces: `event` に `reco_click` が入る

- [ ] **Step 1: `go.php` を作る**

```php
<?php
/*
 * 推薦のクリックを記録してから、銘柄ページへ送る。
 *
 * 素の <a> にすると、何が押されたかが残らない。蔵への月次レポート(設計書 §7)の
 * クリック率は、この記録が根拠になる。
 *
 * **飛ばし先は自サイトの銘柄ページに限る。** 任意のURLへ飛ばせるようにすると、
 * 誰かがこのサイトの名前で外部の危ないページへ誘導できてしまう。
 */
require_once __DIR__ . '/common/reco/visitor.php';
require_once __DIR__ . '/common/reco/repo.php';

$pid = $_GET['p'] ?? '';
if (!preg_match('/^pr\d{4}$/', $pid)) { header('Location: /', true, 302); exit; }

$visitorId = visitor_current();
$uploadId  = isset($_GET['u']) && ctype_digit((string)$_GET['u']) ? (int)$_GET['u'] : null;
$kind      = (($_GET['k'] ?? '') === 'pr') ? 'pr_click' : 'reco_click';
event_record($visitorId, $kind, $pid, $uploadId);

header('Location: /beer/detail/product.php?ProductID=' . urlencode($pid), true, 302);
```

- [ ] **Step 2: 推薦リンクを差し替える**

`index.php` の推薦リストの `href` を
`/go.php?p=<ProductID>&u=<uploadId>` にする。

- [ ] **Step 3: テストを足す**

飛ばし先の判定は `go.php` の中に埋めず、**検査できる関数に出す**。
`common/reco/repo.php` の末尾に:

```php
/**
 * クリックの飛ばし先を決める。**自サイトの銘柄ページ以外へは飛ばさない。**
 * 任意のURLへ飛ばせると、このサイトの名前で外部の危ないページへ誘導できてしまう。
 */
function reco_click_target(?string $productId): string
{
    if (!is_string($productId) || !preg_match('/^pr\d{4}$/', $productId)) { return '/'; }
    return '/beer/detail/product.php?ProductID=' . urlencode($productId);
}
```

`tests/unit/handle_test.php` の末尾に:

```php
// --- クリックの飛ばし先は自サイトの銘柄ページに限る ---
eq(reco_click_target('pr0013'), '/beer/detail/product.php?ProductID=pr0013', '正しい銘柄IDは銘柄ページへ');
eq(reco_click_target('https://example.com'), '/', '外部URLへは飛ばさない');
eq(reco_click_target('../../etc/passwd'),    '/', '相対パスへも飛ばさない');
eq(reco_click_target('st0007'),              '/', 'スタイルIDでは飛ばさない');
eq(reco_click_target(null),                  '/', '指定が無ければトップへ');

// --- クリックが記録される ---
$vid = 'clk' . str_repeat('0', 29);
db()->prepare("INSERT INTO visitor (visitor_id, first_seen, last_seen) VALUES (:v, NOW(), NOW())
               ON DUPLICATE KEY UPDATE last_seen = NOW()")->execute([':v' => $vid]);
$before = (int)db()->query("SELECT COUNT(*) FROM event WHERE kind='reco_click'")->fetchColumn();
event_record($vid, 'reco_click', 'pr0013', null);
$after  = (int)db()->query("SELECT COUNT(*) FROM event WHERE kind='reco_click'")->fetchColumn();
eq($after - $before, 1, 'reco_click が1件記録される');
db()->prepare("DELETE FROM event WHERE visitor_id = :v")->execute([':v' => $vid]);
db()->prepare("DELETE FROM visitor WHERE visitor_id = :v")->execute([':v' => $vid]);
```

`go.php` は `reco_click_target()` を呼ぶだけにする。

- [ ] **Step 4: 実際に押して確かめる**

```bash
cd /workspace/tool/beer
curl -s -o /dev/null -w "%{http_code} -> %{redirect_url}\n" "http://127.0.0.1:8080/go.php?p=pr0013&u=1"
curl -s -o /dev/null -w "%{http_code} -> %{redirect_url}\n" "http://127.0.0.1:8080/go.php?p=../../etc/passwd"
curl -s -o /dev/null -w "%{http_code} -> %{redirect_url}\n" "http://127.0.0.1:8080/go.php?p=https://example.com"
```
Expected: 1つ目は `302 -> /beer/detail/product.php?ProductID=pr0013`、
**2つ目と3つ目は `302 -> /`**(飛ばさない)。

`event` テーブルに `reco_click` が入っていることを確かめ、**後始末する**。

- [ ] **Step 5: コミット**

```bash
git add go.php index.php tests/unit/handle_test.php
git commit -m "推薦のクリックを記録する(蔵向けレポートの根拠)"
```

---

### Task 10: prod へ出す

**この計画の最後。ここで初めて公開される。**

**Files:**
- Modify: `docs/deploy-status.md`

**Interfaces:**
- Consumes: ここまでの全部
- Produces: `https://drtbeer.com/` が新しいトップになる

- [ ] **Step 1: dev で最終確認する**

**利用者に見てもらう。**(承認ゲート)

- 年齢確認 → トップ → 写真を上げる → 似た3本 → 銘柄ページ、の往復
- 銘柄・蔵・スタイルの各タブ
- privacy / terms / about
- `img/upload/` `vendor/` `*.local.php` がすべて 404

- [ ] **Step 2: prod の DB に migration を当てる**

**dev と同じ順で、同じファイルを当てる。**

```bash
cd /workspace/tool/beer
for m in 004_recommender 005_unknown_beer_key 006_upload_status; do
  BEER_EC2_IP=54.168.54.119 /workspace/.claude/skills/beer-data-pipeline/scripts/remote_sql.sh \
    prod --apply db/migrations/$m.sql --allow-ddl
done
```

当てたあと、**dev と prod で `schema_migrations` が一致すること**を確かめる。

- [ ] **Step 3: prod のコンテナを整える**

GD が要る場合は `beer-prod` も `beer-php` に入れ替える。
**入れ替えたら seisan3.com の 200 を必ず確認する。**

- [ ] **Step 4: コードを配る**

```bash
bash deploy/make_archive.sh /tmp/claude-0/beer-deploy.tar.gz
# サーバへ送って
sudo bash /srv/beer/deploy/deploy.sh prod /tmp/beer-deploy.tar.gz
```

`api_config.local.php` を prod 側にも置く(**dev とは別のキーにするかは利用者の判断**)。

- [ ] **Step 5: 公開されたことを確かめる**

```bash
for p in / /beer/products.php /privacy.php /terms.php /agecheck.php; do
  printf "%-28s " "$p"; curl -s -o /dev/null -w "%{http_code}\n" "https://drtbeer.com$p"
done
for p in /img/upload/ /vendor/ /api_config.local.php; do
  printf "%-28s " "$p"; curl -s -o /dev/null -w "%{http_code}\n" "https://drtbeer.com$p"
done
curl -s -o /dev/null -w "seisan3: %{http_code}\n" https://seisan3.com
```
Expected: 上の5つが `200`、下の3つが `404`、seisan3 が `200`。

- [ ] **Step 6: 記録して、コミット**

`docs/deploy-status.md` に、公開日・当てた migration・イメージ・確認結果を追記する。

```bash
git add docs/deploy-status.md
git commit -m "写真リコメンド機能を prod に公開"
```

---

## この計画に**含まれないもの**(計画3・別計画)

| | どこで |
|---|---|
| LINE ログイン | 別計画(匿名で使えるので公開には不要) |
| PR枠・蔵向けウィジェット・月次レポート | 計画3 |
| 1日の上限を JST に揃える | 運用の判断が要る。計画3か運用で |
| dev 側の月次上限を別に絞る | 同上 |
| 確認POSTの所有者検査 / CSRF | 計画3(蔵向けレポートの数字の信頼性に関わる) |
| 200銘柄の表記の棚卸し(`pr0151` の誤記を含む) | `/beer-data-pipeline` の仕事 |
