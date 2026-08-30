-- Darth Beer.com データ更新パイプライン 巻き戻し
-- 目的     : 候補 43件 の反映 (新規 0件 / 更新 43件)。仕様§6 データ更新パイプライン を取り消す
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260830 / seq=011
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates-style2-all.json
-- 生成日時 : 2026-08-30 04:24:56
-- 対応する適用SQL : 011_seed_20260830.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- st0099 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '', `catchcopy` = NULL WHERE `StyleID` = 'st0099';

-- st0082 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0082';

-- st0080 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0080';

-- st0078 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0078';

-- st0077 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0077';

-- st0076 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0076';

-- st0074 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0074';

-- st0072 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0072';

-- st0064 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0064';

-- st0063 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0063';

-- st0062 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0062';

-- st0061 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0061';

-- st0058 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0058';

-- st0057 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0057';

-- st0054 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0054';

-- st0053 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0053';

-- st0050 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0050';

-- st0047 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0047';

-- st0046 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0046';

-- st0045 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0045';

-- st0044 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0044';

-- st0043 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0043';

-- st0042 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0042';

-- st0039 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0039';

-- st0038 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0038';

-- st0037 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0037';

-- st0036 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0036';

-- st0035 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0035';

-- st0034 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0034';

-- st0033 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0033';

-- st0032 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0032';

-- st0031 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0031';

-- st0030 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0030';

-- st0029 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0029';

-- st0027 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0027';

-- st0026 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0026';

-- st0024 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0024';

-- st0023 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0023';

-- st0022 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0022';

-- st0020 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0020';

-- st0015 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0015';

-- st0014 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0014';

-- st0006 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = NULL, `catchcopy` = NULL WHERE `StyleID` = 'st0006';
