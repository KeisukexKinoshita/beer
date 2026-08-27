-- Darth Beer.com データ更新パイプライン 巻き戻し
-- 目的     : 候補 31件 の反映 (新規 31件 / 更新 0件)。仕様§6 データ更新パイプライン を取り消す
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260827 / seq=003
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates3-ship.json
-- 生成日時 : 2026-08-27 00:10:51
-- 対応する適用SQL : 003_seed_20260827.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- pr0087 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0087';

-- pr0085 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0085';

-- pr0084 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0084';

-- pr0083 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0083';

-- pr0081 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0081';

-- pr0080 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0080';

-- pr0079 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0079';

-- pr0075 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0075';

-- pr0072 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0072';

-- pr0071 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0071';

-- pr0070 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0070';

-- pr0068 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0068';

-- pr0066 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0066';

-- pr0064 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0064';

-- pr0063 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0063';

-- pr0062 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0062';

-- pr0055 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0055';

-- pr0053 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0053';

-- pr0052 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0052';

-- pr0051 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0051';

-- pr0050 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0050';

-- pr0048 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0048';

-- pr0046 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0046';

-- pr0043 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0043';

-- pr0040 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0040';

-- pr0039 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0039';

-- pr0038 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0038';

-- mk0030 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0030';

-- mk0026 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0026';

-- mk0023 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0023';

-- mk0019 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0019';
