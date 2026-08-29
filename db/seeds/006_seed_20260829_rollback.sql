-- Darth Beer.com データ更新パイプライン 巻き戻し
-- 目的     : 候補 49件 の反映 (新規 0件 / 更新 49件)。仕様§6 データ更新パイプライン を取り消す
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260829 / seq=006
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates-style-all.json
-- 生成日時 : 2026-08-29 06:56:37
-- 対応する適用SQL : 006_seed_20260829.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- st0102 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0102';

-- st0101 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0101';

-- st0100 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0100';

-- st0098 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0098';

-- st0097 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0097';

-- st0096 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0096';

-- st0095 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0095';

-- st0094 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '', `catchcopy` = NULL WHERE `StyleID` = 'st0094';

-- st0093 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0093';

-- st0092 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0092';

-- st0091 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0091';

-- st0089 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0089';

-- st0088 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0088';

-- st0087 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0087';

-- st0085 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0085';

-- st0084 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0084';

-- st0073 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0073';

-- st0070 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0070';

-- st0069 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0069';

-- st0068 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0068';

-- st0067 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0067';

-- st0066 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0066';

-- st0065 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0065';

-- st0056 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0056';

-- st0055 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0055';

-- st0052 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0052';

-- st0051 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0051';

-- st0049 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0049';

-- st0048 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0048';

-- st0041 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0041';

-- st0040 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0040';

-- st0028 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0028';

-- st0021 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0021';

-- st0018 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0018';

-- st0017 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL WHERE `StyleID` = 'st0017';

-- st0016 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL WHERE `StyleID` = 'st0016';

-- st0013 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0013';

-- st0012 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0012';

-- st0011 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0011';

-- st0010 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0010';

-- st0009 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0009';

-- st0008 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0008';

-- st0007 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0007';

-- st0005 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0005';

-- st0004 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL WHERE `StyleID` = 'st0004';

-- st0003 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0003';

-- st0002 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL WHERE `StyleID` = 'st0002';

-- st0001 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL WHERE `StyleID` = 'st0001';

-- st0000 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL WHERE `StyleID` = 'st0000';
