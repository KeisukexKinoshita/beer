-- API は課金されたのに upload に行が残らない経路があり、そこでは日次・月次・バーストの
-- どの上限も効かなかった(最終レビュー C-2)。失敗も1行として残し、**課金の回数を数える**。
-- 対象: dev = beer_dev / 本番昇格時 = beer
ALTER TABLE upload ADD COLUMN status VARCHAR(16) NOT NULL DEFAULT 'ok';
-- 既存行はすべて成功したもの
UPDATE upload SET status = 'ok' WHERE status = '';
