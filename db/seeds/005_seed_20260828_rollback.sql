-- Darth Beer.com データ更新パイプライン 巻き戻し
-- 目的     : 候補 42件 の反映 (新規 42件 / 更新 0件)。仕様§6 データ更新パイプライン を取り消す
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260828 / seq=005
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates-3b-all.json
-- 生成日時 : 2026-08-28 04:51:35
-- 対応する適用SQL : 005_seed_20260828.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- pr0154 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0154';

-- pr0153 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0153';

-- pr0152 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0152';

-- pr0151 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0151';

-- pr0150 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0150';

-- pr0149 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0149';

-- pr0148 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0148';

-- pr0147 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0147';

-- pr0146 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0146';

-- pr0145 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0145';

-- pr0144 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0144';

-- pr0143 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0143';

-- pr0142 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0142';

-- pr0141 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0141';

-- pr0140 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0140';

-- pr0139 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0139';

-- pr0138 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0138';

-- pr0137 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0137';

-- pr0136 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0136';

-- pr0135 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0135';

-- pr0134 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0134';

-- pr0133 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0133';

-- pr0132 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0132';

-- pr0131 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0131';

-- pr0130 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0130';

-- pr0129 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0129';

-- pr0128 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0128';

-- pr0127 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0127';

-- pr0126 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0126';

-- pr0125 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0125';

-- pr0124 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0124';

-- pr0123 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0123';

-- pr0122 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0122';

-- pr0121 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0121';

-- pr0120 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0120';

-- pr0119 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0119';

-- pr0118 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0118';

-- st0107 の INSERT を取り消す
DELETE FROM `style` WHERE `StyleID` = 'st0107';

-- st0106 の INSERT を取り消す
DELETE FROM `style` WHERE `StyleID` = 'st0106';

-- st0105 の INSERT を取り消す
DELETE FROM `style` WHERE `StyleID` = 'st0105';

-- st0104 の INSERT を取り消す
DELETE FROM `style` WHERE `StyleID` = 'st0104';

-- st0103 の INSERT を取り消す
DELETE FROM `style` WHERE `StyleID` = 'st0103';
