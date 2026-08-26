-- Darth Beer.com データ更新パイプライン 巻き戻し
-- 目的     : 候補 3件 の反映 (新規 0件 / 更新 3件)。仕様§6 データ更新パイプライン を取り消す
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260826 / seq=001
--            候補ファイル = /tmp/claude-0/-workspace-tool-beer/134d20e7-dc4a-4fbd-8230-110d626e9918/scratchpad/smoke/candidates.json
-- 生成日時 : 2026-08-26 05:52:33
-- 対応する適用SQL : 001_seed_20260826.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- st0090 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0090';

-- st0086 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0086';

-- st0083 を変更前の値に戻す
UPDATE `style` SET `StyleExplain` = '' WHERE `StyleID` = 'st0083';
