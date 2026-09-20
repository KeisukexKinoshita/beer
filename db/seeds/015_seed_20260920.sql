-- Darth Beer.com データ更新パイプライン シード
-- 目的     : 候補 200件 の反映 (新規 0件 / 更新 200件)。仕様§6 データ更新パイプライン
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260920 / seq=015
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates.json
-- 生成日時 : 2026-09-20 11:57:33
-- 巻き戻し : 015_seed_20260920_rollback.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- pr0000 (MARS)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0000';

-- pr0001 (KABUKI)
--   変更前: Fruity='4.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 9, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0001';

-- pr0002 (Big Sexy Funk)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0002';

-- pr0003 (SATURN)
--   変更前: Fruity='1.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0003';

-- pr0004 (TITAN)
--   変更前: Fruity='2.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0004';

-- pr0005 (HEAD BANGER)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0005';

-- pr0006 (PUNK IPA)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0006';

-- pr0007 (インドの青鬼)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0007';

-- pr0008 (よなよなエール)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0008';

-- pr0009 (水曜日のネコ)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0009';

-- pr0010 (毬花 -Marihana-)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0010';

-- pr0011 (瑠璃 -Ruri-)
--   変更前: Fruity='1.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0011';

-- pr0012 (スプリングバレー 豊潤 496)
--   変更前: Fruity='1.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0012';

-- pr0013 (HAZY JANE)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0013';

-- pr0014 (BURST IPA)
--   変更前: Fruity='2.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0014';

-- pr0015 (ヒステリック IPA)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0015';

-- pr0016 (KASUMI)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0016';

-- pr0017 (LAYER CAKE)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0017';

-- pr0018 (Starwatcher)
--   変更前: Fruity='2.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0018';

-- pr0019 (Contract Burn)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0019';

-- pr0020 (TrES-2b -North)
--   変更前: Fruity='2.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0020';

-- pr0021 (ヘイジーIPA)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0021';

-- pr0022 (STONE HAZY IPA)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0022';

-- pr0023 (ガツんとIPA)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0023';

-- pr0024 (ジューシーIPA)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0024';

-- pr0025 (ゆずふわIPA)
--   変更前: Fruity='3.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0025';

-- pr0026 (ホワイトエール)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0026';

-- pr0027 (雷電カンヌキIPA)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0027';

-- pr0028 (つまぶるIPA)
--   変更前: Fruity='2.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0028';

-- pr0029 (Neko Nihiki)
--   変更前: Fruity='2.000', estimated_fields=NULL
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0029';

-- pr0030 (東京ブラック)
--   変更前: Fruity='2.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0030';

-- pr0031 (裏通りのドンダバダ)
--   変更前: Fruity='1.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0031';

-- pr0032 (僕ビール君ビール)
--   変更前: Fruity='2.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0032';

-- pr0033 (正気のサタン)
--   変更前: Fruity='2.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0033';

-- pr0034 (伽羅-Kyara-)
--   変更前: Fruity='1.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0034';

-- pr0035 (白-Shiro-)
--   変更前: Fruity='2.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0035';

-- pr0036 (漆黒-Shikkoku-)
--   変更前: Fruity='3.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0036';

-- pr0037 (紅赤-Beniaka-)
--   変更前: Fruity='2.000', estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0037';

-- pr0038 (シュバルツ)
--   変更前: Fruity=NULL, estimated_fields='IBU_all,Color'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0038';

-- pr0039 (金色堂ゴールデンエール)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0039';

-- pr0040 (ケラ・ピルカ)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0040';

-- pr0041 (ボヘミアン ピルスナー)
--   変更前: Fruity='1.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0041';

-- pr0042 (Angry Boy Brown Ale)
--   変更前: Fruity='1.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0042';

-- pr0043 (PILS)
--   変更前: Fruity=NULL, estimated_fields=NULL
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0043';

-- pr0044 (Ichii Senshin (一意専心))
--   変更前: Fruity='3.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0044';

-- pr0045 (Stout)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0045';

-- pr0046 (アンバースワンエール)
--   変更前: Fruity='2.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0046';

-- pr0047 (太陽のラガー)
--   変更前: Fruity='1.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0047';

-- pr0048 (KIRISHIMA BEER STOUT （スタウト）)
--   変更前: Fruity=NULL, estimated_fields=NULL
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0048';

-- pr0050 (デュンケル瓶)
--   変更前: Fruity=NULL, estimated_fields='IBU_all,Color'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0050';

-- pr0051 (Rising Sun Pale Ale)
--   変更前: Fruity='2.000', estimated_fields='Color,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0051';

-- pr0052 (Freshly Squeezed)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0052';

-- pr0053 (Osmunda)
--   変更前: Fruity='3.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0053';

-- pr0054 (銀河高原ビール 小麦のビール)
--   変更前: Fruity='3.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0054';

-- pr0055 (クラシック)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0055';

-- pr0056 (Lupulin Nectar)
--   変更前: Fruity='3.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0056';

-- pr0057 (SPRING VALLEY BREWERY JAPANエール 香)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0057';

-- pr0059 (ヒメホワイト（缶）)
--   変更前: Fruity='3.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0059';

-- pr0060 (エスプレッソスタウト)
--   変更前: Fruity='1.000', estimated_fields='Color,Fruity'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0060';

-- pr0061 (ゴールデンエール)
--   変更前: Fruity='2.000', estimated_fields='Color,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0061';

-- pr0062 (ペールエール)
--   変更前: Fruity=NULL, estimated_fields='Color'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0062';

-- pr0063 (Pineapple Party)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0063';

-- pr0064 (道後ビール スタウト(漱石ビール))
--   変更前: Fruity=NULL, estimated_fields='Color'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0064';

-- pr0065 (富良野大麦)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0065';

-- pr0066 (横浜ラガー)
--   変更前: Fruity='2.000', estimated_fields='Fruity'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0066';

-- pr0067 (Suruga Bay Imperial IPA)
--   変更前: Fruity='2.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0067';

-- pr0068 (RAUCH)
--   変更前: Fruity=NULL, estimated_fields=NULL
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0068';

-- pr0069 (New Ichigo Ichie)
--   変更前: Fruity='3.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0069';

-- pr0070 (W-IPA)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0070';

-- pr0071 (スワンレイクIPA)
--   変更前: Fruity='2.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0071';

-- pr0072 (森閑のペールエール)
--   変更前: Fruity='2.000', estimated_fields='Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0072';

-- pr0073 (KIRISHIMA BEER PILSNER)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0073';

-- pr0074 (道後ビール ケルシュ(坊っちゃんビール))
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0074';

-- pr0075 (ヴァイツェン瓶)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0075';

-- pr0077 (Harmonic Dreams)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0077';

-- pr0078 (Magnolia)
--   変更前: Fruity='3.000', estimated_fields='Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0078';

-- pr0079 (Chill Pils)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0079';

-- pr0080 (マイボック)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0080';

-- pr0081 (ホッピング シャチ)
--   変更前: Fruity='2.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0081';

-- pr0082 (SPRING VALLEY BREWERY シルクエール 白)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'IBU_all,Clarity,Fruity' WHERE `ProductID` = 'pr0082';

-- pr0083 (Anemone)
--   変更前: Fruity='2.000', estimated_fields='Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0083';

-- pr0084 (ペールエール（缶）)
--   変更前: Fruity='2.000', estimated_fields='Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0084';

-- pr0085 (アンバーエール)
--   変更前: Fruity=NULL, estimated_fields='Color'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0085';

-- pr0086 (アンバーエール)
--   変更前: Fruity='1.000', estimated_fields='Color,Fruity'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0086';

-- pr0087 (HEFE WEIZEN)
--   変更前: Fruity='3.000', estimated_fields='Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0087';

-- pr0088 (宇宙IPA)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0088';

-- pr0089 (宇宙GOLD)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0089';

-- pr0090 (宇宙SENSEI)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0090';

-- pr0091 (宇宙LAND)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0091';

-- pr0092 (宇宙DRAGON)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0092';

-- pr0093 (宇宙MASTER)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0093';

-- pr0094 (宇宙MONK)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0094';

-- pr0095 (宇宙SHAKE)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0095';

-- pr0096 (宇宙MONSTER)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0096';

-- pr0097 (宇宙ALE)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0097';

-- pr0098 (宇宙LAGER)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0098';

-- pr0099 (宇宙PILS (WEST COAST PILSNER))
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0099';

-- pr0100 (WHITE HOLE)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0100';

-- pr0101 (BLACK HOLE)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0101';

-- pr0102 (DARK MATTER IPA)
--   変更前: Fruity='2.000', estimated_fields='Clarity,Color,Fruity,IBU_all'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0102';

-- pr0103 (MILKY WAY)
--   変更前: Fruity='2.000', estimated_fields='Clarity,Fruity,IBU_all'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Clarity,Fruity' WHERE `ProductID` = 'pr0103';

-- pr0104 (BIG BANG)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0104';

-- pr0105 (MOON)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0105';

-- pr0106 (SUN)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0106';

-- pr0107 (SIRIUS)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0107';

-- pr0108 (PLEIADES)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0108';

-- pr0109 (ANTARES)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0109';

-- pr0110 (ALDEBARAN)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0110';

-- pr0111 (ARCTURUS)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0111';

-- pr0112 (ALTAIR)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0112';

-- pr0113 (ANDROMEDA)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0113';

-- pr0114 (PLUTO)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0114';

-- pr0115 (CORE)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0115';

-- pr0116 (INFINITY)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0116';

-- pr0117 (MAGNETAR)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0117';

-- pr0118 (アルト)
--   変更前: Fruity=NULL, estimated_fields='IBU_all,Color'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0118';

-- pr0119 (チョコレートスタウト)
--   変更前: Fruity=NULL, estimated_fields='IBU_all,Color'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0119';

-- pr0120 (2026 チョコレートスタウトヴィンテージ)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Fruity'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0120';

-- pr0121 (コーヒースタウト)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0121';

-- pr0122 (ドライチョコレートスタウト)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0122';

-- pr0123 (姫とリオの乾杯ビール)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0123';

-- pr0124 (イーハトーブの海)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0124';

-- pr0125 (かぼすラードラー)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 9, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0125';

-- pr0126 (レモンラードラー)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 9, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0126';

-- pr0127 (クールシップウィーンラガー)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0127';

-- pr0128 (ミルクチョコレートスタウト)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0128';

-- pr0129 (ザ・デイ Nクラシック)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0129';

-- pr0130 (三鉄プレミアムラガー2026)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0130';

-- pr0131 (ザ・デイ TGピルスナー)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0131';

-- pr0132 (つなぐビール)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0132';

-- pr0133 (つなぐビール 琥珀色のラガー)
--   変更前: Fruity=NULL, estimated_fields='IBU_all,Color'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0133';

-- pr0134 (つなぐビール ソラチエース)
--   変更前: Fruity='1.000', estimated_fields='Fruity'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0134';

-- pr0135 (ウルズス)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0135';

-- pr0136 (山葡萄ラードラー)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 9, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0136';

-- pr0137 (茜音-Akane-)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0137';

-- pr0138 (青碧-AO-)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0138';

-- pr0139 (Fresh Hop（フレッシュホップ）)
--   変更前: Fruity='3.000', estimated_fields='Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0139';

-- pr0140 (采葉-Iroha-)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0140';

-- pr0141 (白舞-Shirogamau-)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0141';

-- pr0142 (バレルフカミダス)
--   変更前: Fruity=NULL, estimated_fields=NULL
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0142';

-- pr0143 (軽井沢ビール クラフトザウルス ブラックIPA)
--   変更前: Fruity=NULL, estimated_fields='Color'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0143';

-- pr0144 (軽井沢ビール クラフトザウルス ブリュットIPA)
--   変更前: Fruity=NULL, estimated_fields=NULL
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0144';

-- pr0145 (軽井沢ビール クラフトザウルス ペールエール)
--   変更前: Fruity=NULL, estimated_fields=NULL
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0145';

-- pr0146 (軽井沢高原ビール オーガニック)
--   変更前: Fruity=NULL, estimated_fields=NULL
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0146';

-- pr0147 (軽井沢高原ビール 2026年限定)
--   変更前: Fruity=NULL, estimated_fields=NULL
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0147';

-- pr0148 (眠れるししし)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0148';

-- pr0149 (山の上ニューイ)
--   変更前: Fruity=NULL, estimated_fields=NULL
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0149';

-- pr0150 (そらとしば Play Ball! Ale)
--   変更前: Fruity='1.000', estimated_fields='Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0150';

-- pr0151 (有頂点エイリアンズ)
--   変更前: Fruity='2.000', estimated_fields='Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'Color,Clarity,Fruity' WHERE `ProductID` = 'pr0151';

-- pr0152 (軽井沢高原ビール ワイルドフォレスト)
--   変更前: Fruity=NULL, estimated_fields=NULL
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0152';

-- pr0153 (前略 好みなんて 聞いてないぜ SORRY 其ノ二)
--   変更前: Fruity='2.000', estimated_fields='Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0153';

-- pr0154 (前略 好みなんて 聞いてないぜ SORRY 其ノ四)
--   変更前: Fruity='3.000', estimated_fields='Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0154';

-- pr0155 (クリア)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0155';

-- pr0156 (プレミアム・クリア)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0156';

-- pr0157 (ダーク)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0157';

-- pr0158 (プレミアム・ダーク)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0158';

-- pr0159 (黒ビール（ブラック）)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0159';

-- pr0160 (白ビール（ヴァイス）)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0160';

-- pr0161 (赤ビール（アルト）)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0161';

-- pr0162 (軽井沢エール＜エクセラン＞)
--   変更前: Fruity='4.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0162';

-- pr0163 (清涼飛泉プレミアム)
--   変更前: Fruity='4.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0163';

-- pr0164 (Dear Family 星のふる夜に〈クリア〉)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0164';

-- pr0165 (軽井沢 香りのクラフト 柚子)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0165';

-- pr0166 (いい日旅立ち〈ヴァイス〉)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0166';

-- pr0167 (流氷ドラフト)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0167';

-- pr0168 (知床ドラフト)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Clarity,Fruity' WHERE `ProductID` = 'pr0168';

-- pr0169 (桜桃の雫)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 9, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0169';

-- pr0170 (ABASHIRIプレミアムビール)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0170';

-- pr0171 (ABASHIRI White Ale)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0171';

-- pr0172 (ABASHIRI Golden Ale)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0172';

-- pr0173 (ABASHIRI Artisan Ale)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0173';

-- pr0174 (監極の黒)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0174';

-- pr0175 (遠野麦酒ZUMONA WEIZEN)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0175';

-- pr0176 (遠野麦酒ZUMONA HAZY IPA)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0176';

-- pr0177 (遠野麦酒ZUMONA GOLDEN PILSNER)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0177';

-- pr0178 (遠野麦酒ZUMONA ALTBIER)
--   変更前: Fruity=NULL, estimated_fields='IBU_all,Color'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0178';

-- pr0179 (遠野麦酒ZUMONA GOLDEN ALE)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0179';

-- pr0180 (仙人秘水BEER IPA)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0180';

-- pr0181 (伊豆の国ビール ピルスナー)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0181';

-- pr0182 (伊豆の国ビール ヴァイツェン)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0182';

-- pr0183 (伊豆の国ビール スタウト)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0183';

-- pr0184 (7 Hop 7 Grain DDH IPA)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0184';

-- pr0185 (All Good IPA)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 6, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0185';

-- pr0186 (Cascadian Tides Stout)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0186';

-- pr0187 (Five Fruit Gose)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 9, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0187';

-- pr0188 (Highway To Helles Lager)
--   変更前: Fruity='1.000', estimated_fields='IBU_all,Color,Fruity'
UPDATE `products` SET `Fruity` = 1, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0188';

-- pr0189 (King For A Day NEIPA)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0189';

-- pr0190 (Lust For Life DDH IPA)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0190';

-- pr0191 (Lust For Life IPA)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 7, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0191';

-- pr0192 (ゴーヤーDRY)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0192';

-- pr0193 (青い空と海のビール)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0193';

-- pr0194 (シークヮーサーホワイトエール)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 8, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0194';

-- pr0195 (星空のポーター)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 2, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0195';

-- pr0196 (ユキノチカラ 白ビール)
--   変更前: Fruity='3.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0196';

-- pr0197 (FLYING IPA)
--   変更前: Fruity=NULL, estimated_fields='Color'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'Color,Fruity' WHERE `ProductID` = 'pr0197';

-- pr0198 (NINJA WHITE)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Clarity,Fruity'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Color,Clarity,Fruity' WHERE `ProductID` = 'pr0198';

-- pr0199 (LUCKY DOG)
--   変更前: Fruity=NULL, estimated_fields='IBU_all'
UPDATE `products` SET `Fruity` = 4, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0199';

-- pr0200 (LUCKY CAT)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Fruity' WHERE `ProductID` = 'pr0200';

-- pr0201 (悪魔のビール レッドセッションIPA)
--   変更前: Fruity='2.000', estimated_fields='IBU_all,Color,Fruity'
UPDATE `products` SET `Fruity` = 5, `estimated_fields` = 'IBU_all,Color,Fruity' WHERE `ProductID` = 'pr0201';

-- pr0202 (CAPTAIN CROW エクストラペールエール)
--   変更前: Fruity=NULL, estimated_fields=NULL
UPDATE `products` SET `Fruity` = 3, `estimated_fields` = 'Fruity' WHERE `ProductID` = 'pr0202';
