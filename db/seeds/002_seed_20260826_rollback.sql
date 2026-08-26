-- Darth Beer.com データ更新パイプライン 巻き戻し
-- 目的     : 候補 28件 の反映 (新規 28件 / 更新 0件)。仕様§6 データ更新パイプライン を取り消す
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260826 / seq=002
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates-ship.json
-- 生成日時 : 2026-08-26 12:04:58
-- 対応する適用SQL : 002_seed_20260826.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- pr0086 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0086';

-- pr0082 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0082';

-- pr0078 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0078';

-- pr0077 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0077';

-- pr0074 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0074';

-- pr0073 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0073';

-- pr0069 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0069';

-- pr0067 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0067';

-- pr0065 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0065';

-- pr0061 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0061';

-- pr0060 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0060';

-- pr0059 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0059';

-- pr0057 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0057';

-- pr0056 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0056';

-- pr0054 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0054';

-- pr0047 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0047';

-- pr0045 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0045';

-- pr0044 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0044';

-- pr0042 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0042';

-- pr0041 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0041';

-- mk0029 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0029';

-- mk0028 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0028';

-- mk0027 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0027';

-- mk0025 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0025';

-- mk0024 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0024';

-- mk0022 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0022';

-- mk0021 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0021';

-- mk0020 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0020';
