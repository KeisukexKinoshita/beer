-- 未登録ビールの評価(設計書 2026-09-19 §3)のために、モデルが推定した値を残す。
-- 既存の color / clarity と同じ扱いで、範囲外は入れない(identify_parse で捨てる)。
ALTER TABLE upload ADD COLUMN abv_guess    DECIMAL(4,2) NULL;
ALTER TABLE upload ADD COLUMN ibu_guess    SMALLINT     NULL;
ALTER TABLE upload ADD COLUMN fruity_guess TINYINT      NULL;
