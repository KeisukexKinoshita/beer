-- 年齢確認の「はい」が、外部サイトからの POST だけで成立してしまう問題を塞ぐ。
-- 画面を表示したときに合言葉を発行し、その合言葉を持つ POST だけを受け付ける。
-- あわせて、いつ確認したかを残す(酒類を扱うゲートなので、記録があるほうがよい)。
ALTER TABLE visitor ADD COLUMN csrf_token CHAR(64) NULL;
ALTER TABLE visitor ADD COLUMN age_confirmed_at DATETIME NULL;
