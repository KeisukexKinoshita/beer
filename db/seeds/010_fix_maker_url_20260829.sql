-- Darth Beer.com データ修正シード
-- 目的     : リンク切れになっている maker.URL1 を差し替える (2社)
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
-- 生成日時 : 2026-08-29
-- 巻き戻し : 010_fix_maker_url_20260829_rollback.sql
--
-- 背景:
--   31社すべての URL1 に実際にアクセスして確認した結果、2社が 404 だった。
--   URL1 はブリュワリー詳細ページから公式サイトへ出すリンクで、
--   切れていると読み手が行き止まりに当たる。
--
--   mk0013 伊勢角屋麦酒
--     旧: https://www.kadoyahonten.co.jp        -> 404
--     新: https://www.biyagura.jp/              -> 200「【公式】クラフトビール・地ビールの通販 ISEKADO(伊勢角屋麦酒)」
--     ブリュワリー解説の作成時にも同じ問題を検出しており、
--     source_url 側は 007 で既に biyagura.jp を入れてある。URL1 だけ残っていた。
--
--   mk0015 三菱食品
--     旧: https://www.mitsubishi-shokuhin.com/liquor/beer/index.html -> 404
--     新: https://www.mitsubishi-shokuhin.com/                       -> 200「三菱食品株式会社」
--     酒類・ビールの下層ページが無くなっていたため、企業サイトのトップに寄せる。

UPDATE `maker` SET `URL1` = 'https://www.biyagura.jp/'
  WHERE `MakerID` = 'mk0013';
UPDATE `maker` SET `URL1` = 'https://www.mitsubishi-shokuhin.com/'
  WHERE `MakerID` = 'mk0015';
