-- Darth Beer.com データ更新パイプライン 巻き戻し
-- 目的     : 候補 200件 の反映 (新規 0件 / 更新 200件)。仕様§6 データ更新パイプライン を取り消す
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260920 / seq=015
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates.json
-- 生成日時 : 2026-09-20 11:57:33
-- 対応する適用SQL : 015_seed_20260920.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- pr0202 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = NULL WHERE `ProductID` = 'pr0202';

-- pr0201 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0201';

-- pr0200 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0200';

-- pr0199 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0199';

-- pr0198 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0198';

-- pr0197 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'Color' WHERE `ProductID` = 'pr0197';

-- pr0196 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0196';

-- pr0195 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0195';

-- pr0194 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0194';

-- pr0193 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0193';

-- pr0192 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0192';

-- pr0191 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0191';

-- pr0190 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0190';

-- pr0189 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0189';

-- pr0188 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0188';

-- pr0187 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0187';

-- pr0186 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0186';

-- pr0185 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0185';

-- pr0184 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0184';

-- pr0183 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0183';

-- pr0182 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0182';

-- pr0181 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0181';

-- pr0180 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0180';

-- pr0179 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0179';

-- pr0178 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all,Color' WHERE `ProductID` = 'pr0178';

-- pr0177 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0177';

-- pr0176 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0176';

-- pr0175 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0175';

-- pr0174 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0174';

-- pr0173 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0173';

-- pr0172 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0172';

-- pr0171 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0171';

-- pr0170 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0170';

-- pr0169 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0169';

-- pr0168 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Clarity,Fruity' WHERE `ProductID` = 'pr0168';

-- pr0167 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0167';

-- pr0166 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0166';

-- pr0165 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0165';

-- pr0164 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0164';

-- pr0163 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 4.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0163';

-- pr0162 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 4.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0162';

-- pr0161 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0161';

-- pr0160 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0160';

-- pr0159 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0159';

-- pr0158 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0158';

-- pr0157 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0157';

-- pr0156 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0156';

-- pr0155 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0155';

-- pr0154 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0154';

-- pr0153 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0153';

-- pr0152 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = NULL WHERE `ProductID` = 'pr0152';

-- pr0151 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0151';

-- pr0150 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0150';

-- pr0149 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = NULL WHERE `ProductID` = 'pr0149';

-- pr0148 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0148';

-- pr0147 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = NULL WHERE `ProductID` = 'pr0147';

-- pr0146 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = NULL WHERE `ProductID` = 'pr0146';

-- pr0145 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = NULL WHERE `ProductID` = 'pr0145';

-- pr0144 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = NULL WHERE `ProductID` = 'pr0144';

-- pr0143 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'Color' WHERE `ProductID` = 'pr0143';

-- pr0142 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = NULL WHERE `ProductID` = 'pr0142';

-- pr0141 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0141';

-- pr0140 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0140';

-- pr0139 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0139';

-- pr0138 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0138';

-- pr0137 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0137';

-- pr0136 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0136';

-- pr0135 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0135';

-- pr0134 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0134';

-- pr0133 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all,Color' WHERE `ProductID` = 'pr0133';

-- pr0132 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0132';

-- pr0131 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0131';

-- pr0130 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0130';

-- pr0129 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0129';

-- pr0128 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0128';

-- pr0127 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0127';

-- pr0126 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0126';

-- pr0125 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0125';

-- pr0124 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0124';

-- pr0123 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0123';

-- pr0122 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0122';

-- pr0121 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0121';

-- pr0120 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0120';

-- pr0119 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all,Color' WHERE `ProductID` = 'pr0119';

-- pr0118 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all,Color' WHERE `ProductID` = 'pr0118';

-- pr0117 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0117';

-- pr0116 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0116';

-- pr0115 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0115';

-- pr0114 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0114';

-- pr0113 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0113';

-- pr0112 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0112';

-- pr0111 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0111';

-- pr0110 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0110';

-- pr0109 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0109';

-- pr0108 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0108';

-- pr0107 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0107';

-- pr0106 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0106';

-- pr0105 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0105';

-- pr0104 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0104';

-- pr0103 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Clarity,Fruity,IBU_all' WHERE `ProductID` = 'pr0103';

-- pr0102 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Clarity,Color,Fruity,IBU_all' WHERE `ProductID` = 'pr0102';

-- pr0101 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0101';

-- pr0100 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0100';

-- pr0099 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0099';

-- pr0098 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0098';

-- pr0097 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0097';

-- pr0096 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0096';

-- pr0095 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0095';

-- pr0094 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0094';

-- pr0093 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0093';

-- pr0092 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0092';

-- pr0091 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0091';

-- pr0090 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0090';

-- pr0089 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0089';

-- pr0088 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0088';

-- pr0087 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0087';

-- pr0086 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0086';

-- pr0085 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'Color' WHERE `ProductID` = 'pr0085';

-- pr0084 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0084';

-- pr0083 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0083';

-- pr0082 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Clarity,Fruity' WHERE `ProductID` = 'pr0082';

-- pr0081 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0081';

-- pr0080 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0080';

-- pr0079 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0079';

-- pr0078 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0078';

-- pr0077 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0077';

-- pr0075 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0075';

-- pr0074 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0074';

-- pr0073 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0073';

-- pr0072 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0072';

-- pr0071 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0071';

-- pr0070 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0070';

-- pr0069 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0069';

-- pr0068 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = NULL WHERE `ProductID` = 'pr0068';

-- pr0067 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0067';

-- pr0066 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0066';

-- pr0065 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0065';

-- pr0064 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'Color' WHERE `ProductID` = 'pr0064';

-- pr0063 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0063';

-- pr0062 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'Color' WHERE `ProductID` = 'pr0062';

-- pr0061 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0061';

-- pr0060 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0060';

-- pr0059 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0059';

-- pr0057 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0057';

-- pr0056 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0056';

-- pr0055 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0055';

-- pr0054 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0054';

-- pr0053 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0053';

-- pr0052 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0052';

-- pr0051 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0051';

-- pr0050 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all,Color' WHERE `ProductID` = 'pr0050';

-- pr0048 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = NULL WHERE `ProductID` = 'pr0048';

-- pr0047 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0047';

-- pr0046 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0046';

-- pr0045 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0045';

-- pr0044 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0044';

-- pr0043 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = NULL WHERE `ProductID` = 'pr0043';

-- pr0042 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0042';

-- pr0041 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0041';

-- pr0040 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0040';

-- pr0039 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0039';

-- pr0038 を変更前の値に戻す
UPDATE `products` SET `Fruity` = NULL, `estimated_fields` = 'IBU_all,Color' WHERE `ProductID` = 'pr0038';

-- pr0037 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0037';

-- pr0036 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0036';

-- pr0035 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0035';

-- pr0034 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0034';

-- pr0033 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0033';

-- pr0032 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0032';

-- pr0031 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0031';

-- pr0030 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0030';

-- pr0029 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0029';

-- pr0028 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0028';

-- pr0027 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0027';

-- pr0026 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0026';

-- pr0025 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 3.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0025';

-- pr0024 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0024';

-- pr0023 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0023';

-- pr0022 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0022';

-- pr0021 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0021';

-- pr0020 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0020';

-- pr0019 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0019';

-- pr0018 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0018';

-- pr0017 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0017';

-- pr0016 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0016';

-- pr0015 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0015';

-- pr0014 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0014';

-- pr0013 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0013';

-- pr0012 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0012';

-- pr0011 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0011';

-- pr0010 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0010';

-- pr0009 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0009';

-- pr0008 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0008';

-- pr0007 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0007';

-- pr0006 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0006';

-- pr0005 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0005';

-- pr0004 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0004';

-- pr0003 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 1.000, `estimated_fields` = 'IBU_all' WHERE `ProductID` = 'pr0003';

-- pr0002 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0002';

-- pr0001 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 4.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0001';

-- pr0000 を変更前の値に戻す
UPDATE `products` SET `Fruity` = 2.000, `estimated_fields` = NULL WHERE `ProductID` = 'pr0000';
