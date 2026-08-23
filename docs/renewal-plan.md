# Darth Beer.com リニューアル 計画メモ

仕様書: `darth-beer-renewal-spec.md` (宇宙テーマのUI/UX刷新 + 3D可視化 + データ拡張)

## 進行ルール (仕様§8)

フェーズ0(デザイン案 + 技術スタック/デプロイ提案)の**承認を得るまで実装コードを書かない**。
各フェーズ完了時に動作確認手順を提示する。

- フェーズ0: デザイン案提示 + 技術/デプロイ提案 → 承認 ← **いまここ**
- フェーズ1: 一覧刷新 + 個別Beerページ(グラフ・国旗) + §7の細部修正
- フェーズ2: トップ刷新(3D分布マップ) + Typeページ新設 + 地図
- (完了後) 運用データパイプライン(§6、今回スコープ外)

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
