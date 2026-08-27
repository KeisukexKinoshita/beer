-- Darth Beer.com データ更新パイプライン 巻き戻し
-- 目的     : 候補 30件 の反映 (新規 30件 / 更新 0件)。仕様§6 データ更新パイプライン を取り消す
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260827 / seq=004
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates-uchu.json
-- 生成日時 : 2026-08-27 10:33:53
-- 対応する適用SQL : 004_seed_20260827.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- pr0117 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0117';

-- pr0116 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0116';

-- pr0115 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0115';

-- pr0114 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0114';

-- pr0113 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0113';

-- pr0112 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0112';

-- pr0111 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0111';

-- pr0110 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0110';

-- pr0109 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0109';

-- pr0108 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0108';

-- pr0107 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0107';

-- pr0106 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0106';

-- pr0105 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0105';

-- pr0104 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0104';

-- pr0103 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0103';

-- pr0102 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0102';

-- pr0101 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0101';

-- pr0100 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0100';

-- pr0099 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0099';

-- pr0098 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0098';

-- pr0097 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0097';

-- pr0096 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0096';

-- pr0095 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0095';

-- pr0094 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0094';

-- pr0093 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0093';

-- pr0092 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0092';

-- pr0091 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0091';

-- pr0090 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0090';

-- pr0089 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0089';

-- pr0088 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0088';
