-- 012_style_meta_20260830.sql の巻き戻し
-- 旧値は beer-data-work/current/style.json (2026-08-30 の prod スナップショット) による。

-- (1) st0025 の名前を戻す
UPDATE `style` SET `StyleName` = 'German-Style Hefeweizen' WHERE `StyleID` = 'st0025';

-- (2) 総称スタイルの基準IBU を戻す
UPDATE `style` SET `IBU` = 22.500 WHERE `StyleID` = 'st0080';
UPDATE `style` SET `IBU` = 50.500 WHERE `StyleID` = 'st0082';
