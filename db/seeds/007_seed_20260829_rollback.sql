-- Darth Beer.com データ更新パイプライン 巻き戻し
-- 目的     : 候補 19件 の反映 (新規 0件 / 更新 19件)。仕様§6 データ更新パイプライン を取り消す
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260829 / seq=007
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates-maker-all.json
-- 生成日時 : 2026-08-29 14:50:32
-- 対応する適用SQL : 007_seed_20260829.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- mk0018 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = '...', `source_url` = NULL WHERE `MakerID` = 'mk0018';

-- mk0017 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = '...', `source_url` = NULL WHERE `MakerID` = 'mk0017';

-- mk0016 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = '...', `source_url` = NULL WHERE `MakerID` = 'mk0016';

-- mk0015 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = '...', `source_url` = NULL WHERE `MakerID` = 'mk0015';

-- mk0014 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = '...', `source_url` = NULL WHERE `MakerID` = 'mk0014';

-- mk0013 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = '...', `source_url` = NULL WHERE `MakerID` = 'mk0013';

-- mk0012 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'REVISION is...', `source_url` = NULL WHERE `MakerID` = 'mk0012';

-- mk0011 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = '...', `source_url` = NULL WHERE `MakerID` = 'mk0011';

-- mk0010 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'キリンは...', `source_url` = NULL WHERE `MakerID` = 'mk0010';

-- mk0009 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'Y.MARKET BREWING is…', `source_url` = NULL WHERE `MakerID` = 'mk0009';

-- mk0008 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'ベアレン is…', `source_url` = NULL WHERE `MakerID` = 'mk0008';

-- mk0007 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'COEDO BREWERY is…', `source_url` = NULL WHERE `MakerID` = 'mk0007';

-- mk0006 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'ヤッホーブルーイング is…', `source_url` = NULL WHERE `MakerID` = 'mk0006';

-- mk0005 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'Mikkeller is…', `source_url` = NULL WHERE `MakerID` = 'mk0005';

-- mk0004 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'VERTERE is…', `source_url` = NULL WHERE `MakerID` = 'mk0004';

-- mk0003 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'BREWDOG is…', `source_url` = NULL WHERE `MakerID` = 'mk0003';

-- mk0002 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'West Coast Brewing is…', `source_url` = NULL WHERE `MakerID` = 'mk0002';

-- mk0001 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'NYのデザイナーが手掛ける、目を引くパッケージが特徴。', `source_url` = NULL WHERE `MakerID` = 'mk0001';

-- mk0000 を変更前の値に戻す
UPDATE `maker` SET `MakerExplain` = 'UCHU BREWING is...', `source_url` = NULL WHERE `MakerID` = 'mk0000';
