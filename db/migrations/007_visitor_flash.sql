-- 確認のあとに出すお礼を、URL ではなく訪問者の行で持つ。
-- $_GET で渡していたため、URL を打つだけで「記録しました」が出てしまい、
-- 実際には何も記録していないのに記録したと伝えていた(レビュー指摘 C-1/C-2)。
-- 対象: dev = beer_dev / 本番昇格時 = beer
ALTER TABLE visitor ADD COLUMN flash VARCHAR(24) NULL;
