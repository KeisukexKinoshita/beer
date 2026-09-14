-- Darth Beer.com データ更新パイプライン 巻き戻し
-- 目的     : 候補 57件 の反映 (新規 57件 / 更新 0件)。仕様§6 データ更新パイプライン を取り消す
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260909 / seq=014
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates-20260909-all.json
-- 生成日時 : 2026-09-14 23:53:44
-- 対応する適用SQL : 014_seed_20260909.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- pr0202 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0202';

-- pr0201 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0201';

-- pr0200 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0200';

-- pr0199 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0199';

-- pr0198 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0198';

-- pr0197 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0197';

-- pr0196 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0196';

-- pr0195 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0195';

-- pr0194 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0194';

-- pr0193 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0193';

-- pr0192 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0192';

-- pr0191 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0191';

-- pr0190 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0190';

-- pr0189 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0189';

-- pr0188 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0188';

-- pr0187 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0187';

-- pr0186 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0186';

-- pr0185 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0185';

-- pr0184 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0184';

-- pr0183 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0183';

-- pr0182 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0182';

-- pr0181 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0181';

-- pr0180 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0180';

-- pr0179 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0179';

-- pr0178 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0178';

-- pr0177 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0177';

-- pr0176 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0176';

-- pr0175 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0175';

-- pr0174 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0174';

-- pr0173 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0173';

-- pr0172 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0172';

-- pr0171 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0171';

-- pr0170 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0170';

-- pr0169 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0169';

-- pr0168 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0168';

-- pr0167 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0167';

-- pr0166 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0166';

-- pr0165 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0165';

-- pr0164 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0164';

-- pr0163 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0163';

-- pr0162 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0162';

-- pr0161 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0161';

-- pr0160 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0160';

-- pr0159 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0159';

-- pr0158 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0158';

-- pr0157 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0157';

-- pr0156 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0156';

-- pr0155 の INSERT を取り消す
DELETE FROM `products` WHERE `ProductID` = 'pr0155';

-- mk0039 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0039';

-- mk0038 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0038';

-- mk0037 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0037';

-- mk0036 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0036';

-- mk0035 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0035';

-- mk0034 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0034';

-- mk0033 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0033';

-- mk0032 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0032';

-- mk0031 の INSERT を取り消す
DELETE FROM `maker` WHERE `MakerID` = 'mk0031';
