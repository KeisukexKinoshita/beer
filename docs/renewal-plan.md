# Darth Beer.com リニューアル 計画メモ

仕様書: `darth-beer-renewal-spec.md` (宇宙テーマのUI/UX刷新 + 3D可視化 + データ拡張)

## 進行ルール (仕様§8)

フェーズ0(デザイン案 + 技術スタック/デプロイ提案)の**承認を得るまで実装コードを書かない**。
各フェーズ完了時に動作確認手順を提示する。

- フェーズ0: デザイン案提示 + 技術/デプロイ提案 → **完了(B案Nebula確定)**
- フェーズ1: 一覧刷新 + 個別Beerページ(グラフ・国旗) + §7の細部修正 → **実装完了・dev反映済み(ユーザー確認待ち)**
- フェーズ2: トップ刷新 + Type一覧新設 + Leaflet地図 → **実装完了・dev反映済み(ユーザー確認待ち)**
- (完了後) 運用データパイプライン(§6、今回スコープ外)

## フェーズ1 実装メモ (2026-08-23 dev反映)

- 共通: common/nebula/{helpers,head,header,footer}.php、assets/js/{nebula-bg,galaxy,radar,beerlist}.js、assets/css/nebula.css
- index.php: 新ヘッダー+ヒーロー銀河+最新Beer(日/海外)+人気Brewery(日/海外)。※3D本格化とTypeはフェーズ2
- beer/products.php: 味覚銀河(16:9)+カード+スタイル絞込/並替/検索。全文表示を廃止
- beer/detail/product.php: 画像|概要→スペック(国旗・ブリュワリーリンク)→レーダー+製造地枠。動的title/meta
- brewery/makers.php: 日本/海外カード。 brewery/detail/maker.php: ロゴ枠+製造地枠+取扱ビール一覧
- §7: title/meta動的化・alt適正化・星評価aria-label・ロゴリンク/index.php・loading=lazy
- DB: 001マイグレーション(maker.country_code等 / style.catchcopy)をbeer_devに適用済み
- 注意: 旧characterizationテスト(tests/)はmainの旧コード用。renewalの新ページには適用しない(挙動を意図的に刷新)
- フェーズ2でやる残: 3D top本格化 / Typeマスタ拡充(catchcopy)+Type一覧 / Leaflet地図(要lat/lng投入) / ロゴ素材

## グラフ強化 (2026-08-23、ユーザー要望)

- 3D味覚グラフ: **原点=各軸の平均**にして±方向にプロット(平均からのズレが見える)。**軸は白黒系**(点はスタイル色)
- 座標系(平均・スケール)は常に全ビールで決め、`highlightIds`で特定の銘柄/集合を強調・他を淡色化
- 個別ページにグラフ追加:
  - beer詳細: この銘柄をリング+名前で強調。スタイル名をstyle詳細へリンク
  - brewery詳細: PC=ロゴ|概要|製造地(ロゴと同高)の3カラム→スマホ縦積み。その下に自社ビール強調グラフ
  - style/detail/style.php: Nebula化(Type詳細を前倒し作成)。そのスタイルのビール強調グラフ+一覧
- 注: Type一覧ページと catchcopy/StyleExplain の文言投入はフェーズ2で

## フェーズ2 実装メモ (2026-08-23 dev反映)

- db/migrations/002: maker緯度経度(19社)・styleキャッチコピー(24種)をbeer_devに投入
- Leaflet実地図(assets/js/beermap.js + head条件読込 useMap): CARTO darkタイルで製造地を表示。
  beer詳細・brewery詳細の地図枠を実地図化(緯度経度あれば)。SRIハッシュはunpkg実測値を使用
- style/styles.php: Type一覧(人気順=取扱数)新設。ナビにType追加
- index.php: 人気のTypeセクション追加 → 仕様§3①の構成(ヒーロー/最新Beer/人気Brewery/人気Type)が完成
- StyleExplain(スタイル長文説明)は未投入。ロゴ素材も未設定(枠のみ)
- 残(将来): §6データ更新パイプライン(スコープ外) / 本番beerへの002マイグレーション適用は本番反映時 /
  StyleExplain長文・ロゴ素材の用意 / 販売地(現状は製造地のみ)

## 決定事項 (2026-08-23)

- ブランチ: **renewal** で進める。main/prod(dev公開中)は安定維持、フェーズ完了ごとにmainへマージ
- **デザイン: B案 Nebula に確定 (2026-08-23)**。星雲の色彩・エモーショナル/没入型。
  理由(ユーザー): 明るく滞在時間が伸びやすい・老若男女に届く。
  モック: docs/design-mocks/B_nebula.html / Artifact https://claude.ai/code/artifact/662aa233-f355-46a1-aded-4c0c007037ba
- 技術スタック: **PHP維持 + JS可視化層** に確定(B案モックがまさにこの構成。EC2+ALBで軽く動く)
- 販売地域データ: **当面は製造地(ブリュワリー所在地)のみ**を地図表示。販売地は後回し
- 3Dの主役: 全ビール3D分布マップ(軸: アルコール度 × IBU × フルーティーさ)

## B案 Nebula デザイントークン (確定)

- フォント: 表示=M PLUS 1 (700/800) / 本文=Noto Sans JP
- 背景: #0b0820 (深インディゴ) / パネル #17123a
- 文字: #f3ecff / ミュート #ac9fd6
- アクセント: マゼンタ #ff5ca8 → バイオレット #a06bff → ティール #4be0c8 のグラデーション
- スタイル別カラー(銀河/カード): IPA #5fd0ff / Stout #b98cff / Sour #ff6fb0 / Pale #ffd06b / Other #5cf0c2
- 詳細は assets/css/nebula.css (design-system) を正とする

## 現状DB調査結果 (beer_dev、2026-08-23)

現行テーブル(char(6)のID体系: pr0000/mk0000/st0000):
- `products` 38行: ProductID, MakerID, ProductName, StyleID, Alcohol, IBU_all, IBU,
  Color, Clarity, Fruity, Favorite(評価点), ProductExplain, IBU_Style, Comment
- `maker` 19行: MakerID, MakerName, MakerExplain, URL1 ← **国コード/緯度経度/ロゴパス なし**
- `style` 97行: StyleID, FamilyName(大分類), StyleName(具体名), IBU, StyleExplain(ほぼ空)
  → **実質Typeマスタとして使える**。Typeページ用に説明文+キャッチコピー列を足すだけでよい
- 未使用の残骸: `taste`(10), `style_test`(74) ← 触らない
- `user` 2行: UserPassword が平文 ← 投稿刷新時の課題(今回スコープ外)

数値レンジ(3D軸設計の実データ):
- Alcohol 0.7〜11.0 / IBU 11〜74.56 / Fruity 1〜4 / Color 1〜10
- ビール38件・使用スタイル約15種 → 「38個の天体が浮かぶ銀河」を軽量に描ける

## スキーマ追加計画 (フェーズ1/2で実施、承認後)

- `maker`: country_code(国旗用) / latitude / longitude / logo_path
- `style`: catchcopy(キャッチコピー) + StyleExplain の内容拡充
- `products`: 販売地域は当面追加しない(製造地=makerの座標を使う)

## 技術スタック比較 (提案)

| 観点 | PHP維持+JS可視化層(推奨) | フロント全面刷新 |
|---|---|---|
| 3D/地図との相性 | Three.js/Leafletを該当ページに差込 ◎ | ◎だが全面作り直し |
| 段階移行 | ページ単位で差替、既存が動いたまま ◎ | 全移行until公開 △ |
| EC2+ALB運用 | 現行PHP-Apacheコンテナのまま ◎ | Node/ビルド/SSR追加 △ |
| 個人保守 | 素PHP+軽量JS ◎ | 依存/ビルド保守 △ |

## デプロイ (提案)

③公開フェーズで構築済みの「git → tarアーカイブ → scp → deploy.sh 展開」を正式手順化。
GitHub Actions化は運用が固まってから検討。


## 仕様9章対応 / プレースホルダー確定 (2026-08-26)

- 仕様書の最新版は **docs/darth-beer-renewal-spec.md**(9章「コンテンツの権利方針」あり)。
  リポジトリ直下の同名ファイルは旧版なので参照しないこと
- **商品写真を全廃**(仕様9-1)。既存39枚も表示停止し、`make_archive.sh` の対象からも除外。
  dev の `img/product/` は削除済み
- プレースホルダーは **案C「円柱グラス + 液中の淡い星雲と漂う星」** に確定(ユーザー選択)。
  実装は `assets/js/beerglass.js`(canvas描画・画像ファイルを作らない)と
  `helpers.php` の `beer_glass_tag()`。適用先は index / beer一覧 / beer詳細 / brewery詳細 / style詳細
- データ対応: 液の色=Color / 泡の厚み=Clarity / 漂う星の数=IBU / 奥の光=Alcohol / 星の色づき=スタイル群
- 検証: dev で38銘柄すべてが描画されることを確認済み

### 次にやること(未着手)
1. マイグレーション003: products に source_url / image_rights / official_url / estimated_fields、
   maker に source_url / logo_rights (仕様9-6)
2. データ更新スキル `beer-data-pipeline` の構築(計画は /root/.claude/plans/ にあり)
   - 収集役に原文を保存させない(仕様9-4)。監査は出典URLの再訪で行う
   - Open Brewery DB を一次ソースに(仕様9-5)。日本は10件のみなので公式サイト併用
   - robots.txt / 利用規約の尊重、1サイト数秒に1回 (仕様9-3)
