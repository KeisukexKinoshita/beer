# Nebula デザインシステム (B案 確定)

リニューアル全ページはこの規約に従う。視覚の正は `assets/css/nebula.css`。

## 原則

1. **色・余白・角丸・影は直値で書かず、必ずCSS変数(`--mag` 等)を使う**。
   新しい色が要るときはまずトークンを追加してから使う。
2. **フォント**: 見出し=`var(--f-disp)` (M PLUS 1) / 本文=`var(--f-body)` (Noto Sans JP)。
   数値の並ぶ箇所は `font-variant-numeric:tabular-nums`。
3. **モバイルファースト**。ブレークポイントは 900px / 560px。3Dグラフはスマホで
   GPU負荷が過大にならないよう点数・エフェクトを抑え、`prefers-reduced-motion` で自動回転を止める。
4. **アクセシビリティ**: ダーク背景でのコントラストを確保。フォーカスリングを消さない。
   星評価は視覚(★)と数値(4.0)を併記し、`aria-label` で読み上げ可能にする(§7対応)。

## トークン早見 (詳細は nebula.css)

| 用途 | 変数 | 値 |
|---|---|---|
| 背景 / パネル | `--bg` / `--panel` | #0b0820 / #17123a |
| 文字 / ミュート | `--ink` / `--muted` | #f3ecff / #ac9fd6 |
| アクセント3色 | `--mag` / `--vio` / `--teal` | #ff5ca8 / #a06bff / #4be0c8 |
| グラデ | `--grad` | mag→vio→teal |
| スタイル別 | `--st-ipa/stout/sour/pale/other` | 青/紫/桃/黄/緑 |

## コンポーネント (nebula.css にクラス定義)

- レイアウト: `.wrap` `.site-header/.nav/.brand` `.site-footer`
- 見出し: `.eyebrow` `.sec-h` `.sec-top` `.subhead` `.more` `section.blk`
- ボタン: `.btn.pri` / `.btn.ghost`
- カード: `.grid-b/.bcard` (Beer) / `.grid-br/.brcard` (Brewery)
- Type一覧: `.types/.trow`
- 詳細: `.spec .r`(スペック行, `.k/.v/.bar`) `.panel`
- 可視化枠: `.galaxy-shell` `canvas.radar` `.map`
- 背景演出: `#stars`(canvas) + `.neb`

## スタイル別カラーの割当 (銀河・カード・凡例で共通)

IPA系=青 / Stout・黒=紫 / Sour=桃 / Pale・Amber=黄 / Low-alc他=緑。
`style.FamilyName` からこの5群へマッピングする関数をPHP/JS共通で1箇所に持つ。

## 参照

- 承認モック: `docs/design-mocks/B_nebula.html`
- 公開Artifact: https://claude.ai/code/artifact/662aa233-f355-46a1-aded-4c0c007037ba
