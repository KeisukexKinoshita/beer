-- Darth Beer.com データ修正シード
-- 目的     : 008 の取り消し(IBU を 0 に戻し、estimated_fields を空に戻す)
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと。
-- 生成日時 : 2026-08-29
-- 巻き戻し : 008_fix_ibu_zero_20260829_rollback.sql
--
-- 背景:
--   対象15件はいずれも pr0003〜pr0037 の旧データ。旧投稿フォーム
--   (common/sql_POST.php)が IBU=0 を「実測値なし」の目印に使い、
--   そのとき IBU_all にスタイルの基準値(IBU_Style)を流し込む作りだった。
--
--   結果、この15件は次の状態になっていた:
--     - IBU(実測値の欄)が 0 = 「苦味ゼロのビール」という存在しない事実
--     - IBU_all が IBU_Style と完全に一致 = 中身はスタイルの基準値、つまり推定値
--     - estimated_fields が空 = 推定したことが記録されていない
--
--   つまり推定値を実測値の顔で表示していた。仕様の「黙って推定しない」に反する。
--
-- この修正の効果:
--   1. IBU = NULL   -- サイトは IBU 列を表示していないので見た目は変わらない。
--                      監査役が 0 を「基準値との乖離」と誤読する事故を防ぐ
--   2. estimated_fields = 'IBU_all' -- 詳細ページに「推定」バッジが出るようになる。
--                      15銘柄の見た目が変わる(ユーザー承認済み)
--
-- 残る課題(このシードでは直さない):
--   旧投稿フォームが同じ問題を作り続ける。IBU を空で投稿すると IBU=0 の行ができ、
--   estimated_fields は空のまま IBU_all にスタイル基準値が入る。しかもその UPDATE は
--   ProductID を指定せず全行に走る。コードの修正が別途必要。

-- pr0003
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0003' AND `IBU` IS NULL;

-- pr0004
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0004' AND `IBU` IS NULL;

-- pr0012
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0012' AND `IBU` IS NULL;

-- pr0014
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0014' AND `IBU` IS NULL;

-- pr0018
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0018' AND `IBU` IS NULL;

-- pr0020
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0020' AND `IBU` IS NULL;

-- pr0028
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0028' AND `IBU` IS NULL;

-- pr0030
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0030' AND `IBU` IS NULL;

-- pr0031
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0031' AND `IBU` IS NULL;

-- pr0032
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0032' AND `IBU` IS NULL;

-- pr0033
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0033' AND `IBU` IS NULL;

-- pr0034
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0034' AND `IBU` IS NULL;

-- pr0035
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0035' AND `IBU` IS NULL;

-- pr0036
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0036' AND `IBU` IS NULL;

-- pr0037
UPDATE `products` SET `IBU` = 0.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0037' AND `IBU` IS NULL;
