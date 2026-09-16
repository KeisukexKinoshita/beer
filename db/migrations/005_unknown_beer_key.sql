-- 004 で作った unknown_beer の一意制約が、brewery_text が NULL のとき効かない問題を直す。
-- MySQL は UNIQUE 索引の NULL を互いに別物として扱うため、
-- ON DUPLICATE KEY UPDATE hits = hits + 1 が発火せず、同じ銘柄の行が増え続けてしまう。
-- 「読めなかった」は NULL ではなく空文字で表す。
ALTER TABLE unknown_beer MODIFY brewery_text VARCHAR(191) NOT NULL DEFAULT '';
