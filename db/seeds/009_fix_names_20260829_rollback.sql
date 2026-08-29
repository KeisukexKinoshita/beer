-- Darth Beer.com データ修正シード
-- 目的     : 009 の取り消し
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
-- 生成日時 : 2026-08-29
-- 巻き戻し : 009_fix_names_20260829_rollback.sql
--
-- 背景と、なぜ表示が変わらないか:
--
--   1) 前後の空白 (2件)
--      style.StyleName 'New England IPA ' (末尾空白) と
--      products.ProductName ' HAZY JANE' (先頭空白)。
--      **末尾の空白のせいで重複検査(GROUP BY StyleName)をすり抜けていた** ——
--      st0002 と st0019 は実質同じスタイル名なのに別物として数えられていた。
--
--   2) FamilyName の欠落 (2件)
--      st0002 (New England IPA) と st0001 (IMPERIAL SMOOTHIE SOUR ALE) が NULL。
--      helpers.php の style_group() は FamilyName が空でも StyleName から
--      ipa / sour と判定できているため、**銀河ビューの色は変わらない**
--      (php で実際に呼んで確認済み)。データの整合だけが改善する。
--
--   3) 重複スタイル st0019
--      st0002 が銘柄3件・解説259字・キャッチコピーを持つ実体で、
--      st0019 は銘柄0件・解説なしの空。**削除はしない**(IDを消すと
--      監査記録が別の対象を指す恐れがあるため、規律どおり欠番も残さない方針)。
--      代わりに、将来この空のIDへ誤って分類されないよう StyleName に注記を付ける。

UPDATE `style`    SET `StyleName`   = 'New England IPA ' WHERE `StyleID`   = 'st0002';
UPDATE `products` SET `ProductName` = ' HAZY JANE'       WHERE `ProductID` = 'pr0013';
UPDATE `style` SET `FamilyName` = NULL WHERE `StyleID` = 'st0002';
UPDATE `style` SET `FamilyName` = NULL WHERE `StyleID` = 'st0001';
UPDATE `style` SET `StyleName` = 'New England IPA' WHERE `StyleID` = 'st0019';
