-- 確度が中くらいのときに並べる候補(設計書 2026-09-19 §3)。
-- カンマ区切りの ProductID を最大3件。選び直しは picked_by_user で見分ける
-- (モデルが同定したのか、利用者が選んだのかを取り違えないため)。
ALTER TABLE upload ADD COLUMN candidates     VARCHAR(32) NULL;
ALTER TABLE upload ADD COLUMN picked_by_user TINYINT(1)  NOT NULL DEFAULT 0;
