-- Darth Beer.com データ更新パイプライン シード
-- 目的     : 候補 30件 の反映 (新規 30件 / 更新 0件)。仕様§6 データ更新パイプライン
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260827 / seq=004
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates-uchu.json
-- 生成日時 : 2026-08-27 10:33:53
-- 巻き戻し : 004_seed_20260827_rollback.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- pr0088: 宇宙IPA (uchu-space-ipa)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0088', 'mk0000', '宇宙IPA', 'st0000', 7, 20, NULL, NULL, NULL, 2, NULL, 'うちゅうブルーイングの中でも看板格に位置づけられる一本。小麦とオーツ麦を麦芽に重ね、二段仕込みのドライホップでアロマを厚く積み上げる造りを採る。ブルーベリーやマンゴー、マンダリンを思わせる香りが広がり、苦味はその奥で全体を支える役どころに回っている。蔵の顔とも呼べる、丁寧に組み上げられた一杯。', 20, NULL, 'https://uchubrew.shop-pro.jp/?pid=131277904', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=131277904', 'IBU_all,Fruity');

-- pr0089: 宇宙GOLD (uchu-gold)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0089', 'mk0000', '宇宙GOLD', 'st0000', 7.0, 20.0, NULL, NULL, NULL, 2, NULL, '麦芽にオーツ麦とライ麦を合わせ、ホップはシトラとリワカに、市販前の試験段階にあったニュージーランドの新品種「NZH-106」を加えたダブルドライホップのIPA。原材料の配合比はフィボナッチ数列と黄金比を基準に設計されている。グラスからはパッションフルーツとグレープフルーツ、マンダリン、白ぶどうを思わせる香りが立つ。', 20.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=137781747', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=137781747', 'IBU_all,Fruity');

-- pr0090: 宇宙SENSEI (uchu-sensei)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0090', 'mk0000', '宇宙SENSEI', 'st0004', 5.5, 40.0, NULL, NULL, NULL, 2, NULL, '「うちゅう先生」の名を冠した一本。オーツ麦を配合した麦芽にシトラ、リワカ、クリオシトラ、クリオクラッシュ、クリオシムコーを重ねて仕込まれている。グレープフルーツやパイナップル、マンダリン、ライムを思わせる果実の風味が層になって広がり、ホップの存在感をまっすぐに伝える味わいに仕上がっている。', 40.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=139040110', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=139040110', 'IBU_all,Fruity');

-- pr0091: 宇宙LAND (uchu-land)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0091', 'mk0000', '宇宙LAND', 'st0094', 8.0, 60.0, NULL, NULL, NULL, 2, NULL, '小麦とオーツ麦を麦芽に重ねた仕込みに、シトラとネルソンソーヴィン、その低温加工版であるクライオホップを合わせたダブルIPA。かなり厚みのある副原料構成が土台を作り、そこにオレンジやパイナップル、パッションフルーツ、マンゴーを思わせる香りが幾重にも重なる。公式のIBUは非公開だが、ホップの層を重ねた造りらしい存在感のある一杯。', 60.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=141779259', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=141779259', 'IBU_all,Fruity');

-- pr0092: 宇宙DRAGON (uchu-space-dragon)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0092', 'mk0000', '宇宙DRAGON', 'st0004', 5.5, 40.0, NULL, NULL, NULL, 2, NULL, '麦芽にオーツ麦を加えたペールエール系の一本。ドライホップにはシトラ、ドルシータ、クライオモザイクを重ね、オレンジやグレープフルーツ、パッションフルーツ、ライムを思わせる味わいが幾重にも広がる。ホップの存在感がしっかりと立つ造りになっている。', 40.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=144613885', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=144613885', 'IBU_all,Fruity');

-- pr0093: 宇宙MASTER (uchu-master)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0093', 'mk0000', '宇宙MASTER', 'st0094', 8.5, 60.0, NULL, NULL, NULL, 2, NULL, '看板銘柄「宇宙IPA」の増量版として設計されたダブルIPA。シトラ、モザイク、ネルソンソーヴィンに、粉末状に加工したクライオホップのモザイクとモツエカを重ね、ホップの層を厚くしている。グラスからはシトラス、パイナップル、マンゴーを思わせる香りが広がる、飲みごたえのある一本。', 60.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=144760260', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=144760260', 'IBU_all,Fruity');

-- pr0094: 宇宙MONK (uchu-monk)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0094', 'mk0000', '宇宙MONK', 'st0094', 8.5, 60.0, NULL, NULL, NULL, 2, NULL, '名前は「オショー」に由来する一本で、「宇宙SHAKE」を増幅させた位置づけの造り。麦芽にパイナップル果汁、ココナッツ、ココナッツミルク、バニラを加え、ホップはシトラとモザイクを重ねている。クリーミーでトロピカルな口当たりに、ココナッツとパイナップルの甘みが溶け込む味わい。', 60.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=145283184', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=145283184', 'IBU_all,Fruity');

-- pr0095: 宇宙SHAKE (uchu-shake)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0095', 'mk0000', '宇宙SHAKE', 'st0094', 8.0, 60.0, NULL, NULL, NULL, 2, NULL, '乳糖とバニラビーンズを使い、ミルクシェイクを思わせる副原料構成に仕立てたダブルIPA。ホップはシトラ、モザイク、ネルソンソーヴィンを重ね、オレンジやグレープフルーツ、ストロベリー、マンゴーの香りが甘やかに広がる。IBUは公式に公開されていない一本。', 60.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=141357566', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=141357566', 'IBU_all,Fruity');

-- pr0096: 宇宙MONSTER (uchu-space-monster)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0096', 'mk0000', '宇宙MONSTER', 'st0000', 7, 20.0, NULL, NULL, NULL, 2, NULL, 'バングラデシュ産のライチ蜂蜜を使ったIPA。麦芽とオーツ麦にはちみつと乳糖を合わせ、ホップはストラータ、シトラ、ネルソンソーヴィンを重ねている。オレンジやストロベリー、メロン、ライチを思わせる甘やかな味わいから、さっぱりとすっきりした後口へつながる。甘みは果実ではなく蜂蜜由来のもの。', 20.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=149862662', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=149862662', 'IBU_all,Fruity');

-- pr0097: 宇宙ALE (uchu-ale)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0097', 'mk0000', '宇宙ALE', 'st0084', 7.0, 50.0, NULL, 5, 2, 2, NULL, '麦芽とホップ、糖類だけを原材料としたシンプルな配合のウエストコースト系IPA。ダブルドライホップ仕込みで、グラスからはグレープフルーツやブルーベリーを思わせる爽やかな香りに、マンゴーのような甘い果実の気配が重なる。輪郭のはっきりした苦味とキレのある飲み口が持ち味の一本。', 50.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=176849401', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=176849401', 'IBU_all,Color,Clarity,Fruity');

-- pr0098: 宇宙LAGER (uchu-lager)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0098', 'mk0000', '宇宙LAGER', 'st0068', 5.0, 21.5, NULL, NULL, NULL, NULL, NULL, 'オリジナルデコクションタンクを使い、仕込みの二回目にデコクション製法を採用したラガー。原料は麦芽とホップだけのシンプルな構成で、キレとコクのある味わいに仕上がっている。', 21.5, NULL, 'https://uchubrew.shop-pro.jp/?pid=190325132', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=190325132', 'IBU_all');

-- pr0099: 宇宙PILS (WEST COAST PILSNER) (uchu-pils)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0099', 'mk0000', '宇宙PILS (WEST COAST PILSNER)', 'st0088', 5.0, 35.0, NULL, NULL, NULL, 2, NULL, '麦芽とホップのみで仕込む、アメリカ西海岸生まれのピルスナースタイル。オレンジやブルーベリー、マンゴー、レモンを思わせる香りが立ち、ラガーらしい骨格にホップの果実香を重ねた造り。公式のIBUは非公開。ワールドビアカップ2026のウエストコーストピルスナー部門では世界3位となっている。', 35.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=185279898', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=185279898', 'IBU_all,Fruity');

-- pr0100: WHITE HOLE (uchu-white-hole)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0100', 'mk0000', 'WHITE HOLE', 'st0094', 8.5, 60, NULL, NULL, NULL, 2, NULL, 'シトラ、モザイク、ネルソンソーヴィン、ネクタロン、クライオシトラと五種のホップを重ねたダブルIPA。トロピカルフルーツやパイナップル、パッションフルーツ、マンゴー、マンダリンを思わせる香りが幾重にも折り重なる。ホップの層を厚く積み上げた、飲みごたえのある一本に仕上がっている。', 60, NULL, 'https://uchubrew.shop-pro.jp/?pid=133622811', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=133622811', 'IBU_all,Fruity');

-- pr0101: BLACK HOLE (uchu-black-hole)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0101', 'mk0000', 'BLACK HOLE', 'st0018', 8.0, 82.5, NULL, NULL, NULL, 2, NULL, '麦芽とホップ、糖類だけで仕込まれたインペリアルIPA。リワカにクライオ加工のクラッシュとシムコーを重ね、グラスからはグレープフルーツやパイナップルの果実香に、ダンクと呼ばれる青々しく樹脂を思わせる香りが絡み合う。度数の高さに見合う、骨太な飲みごたえを狙った一本。', 82.5, NULL, 'https://uchubrew.shop-pro.jp/?pid=136431279', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=136431279', 'IBU_all,Fruity');

-- pr0102: DARK MATTER IPA (uchu-dark-matter-ipa)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0102', 'mk0000', 'DARK MATTER IPA', 'st0090', 7.0, 50.0, NULL, 5, 4, 2, NULL, '小麦とオーツ麦を配合した麦芽に、シトラ、ネルソンソーヴィン、ピーチャリン、ラカウ、そして凝縮ホップのクリオシトラを重ねて仕込まれたIPA。グラスに注ぐと濁りを帯びた液体から、シトラス、トロピカル、パイナップル、ピーチ、マンゴーを思わせる香りが立ちのぼる。', 50.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=134258451', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=134258451', 'Clarity,Color,Fruity,IBU_all');

-- pr0103: MILKY WAY (uchu-milky-way)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0103', 'mk0000', 'MILKY WAY', 'st0000', 7.0, 20.0, NULL, NULL, 4, 2, NULL, 'オーツ麦と乳糖を麦芽に重ねて仕込んだ、麦芽使用率50%以上の発泡酒に分類されるIPAである。シトラとネルソンソーヴィンという二種のホップを使い、見た目はヘイジーに仕上がっている。香りには白ぶどうやメロン、ピーチ、オレンジが立つ。公式のIBU値は非公開となっている。', 20.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=134502932', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=134502932', 'Clarity,Fruity,IBU_all');

-- pr0104: BIG BANG (uchu-big-bang)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0104', 'mk0000', 'BIG BANG', 'st0094', 8, 60, NULL, NULL, NULL, 2, NULL, 'シトラ一種のホップに絞り込み、三段仕込みのドライホップでその個性を深く掘り下げたダブルIPA。麦芽にオーツ麦と糖類を重ねた構成に、オレンジやグレープフルーツ、パイナップル、ピーチ、マンダリンを思わせる香りと味わいが幾重にも重なり合う。ひとつのホップの表情をとことん突き詰めた一本に仕上がっている。', 60, NULL, 'https://uchubrew.shop-pro.jp/?pid=164986766', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=164986766', 'IBU_all,Fruity');

-- pr0105: MOON (uchu-moon)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0105', 'mk0000', 'MOON', 'st0084', 7.0, 50.0, NULL, 5, 2, 2, NULL, 'モザイク、シムコー、クラッシュに、クライオ加工のコロンバスを重ねたウエストコースト系のIPA。ホップを重ねがけするDDH製法で仕込まれており、グラスからはパッションフルーツやパイナップル、グレープフルーツ、オレンジの香りが広がる。IBU値は公式には示されていない。', 50.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=145456809', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=145456809', 'IBU_all,Color,Clarity,Fruity');

-- pr0106: SUN (uchu-sun)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0106', 'mk0000', 'SUN', 'st0084', 7.0, 50.0, NULL, 5, 2, 2, NULL, '小麦を含む麦芽にシトラ、クラッシュ、そして凝縮ホップのクリオシトラとクリオコロンバスを重ねて仕込まれた一本。オレンジやグレープフルーツ、パイナップル、レモンを思わせる柑橘とフルーツの風味が層になり、ホップの存在感をまっすぐに描き出す味わいに仕上がっている。', 50.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=145597688', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=145597688', 'IBU_all,Color,Clarity,Fruity');

-- pr0107: SIRIUS (uchu-sirius)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0107', 'mk0000', 'SIRIUS', 'st0093', 6.5, 50.0, NULL, 4, 4, 2, NULL, '小麦を麦芽に重ね、シトラ、エクリプス、モザイク、ラカウ、シムコーとその低温加工版を含む七種のホップを使用したヘイジーIPA。使用ホップはアメリカ、ニュージーランド、オーストラリアの産地にまたがる。シトラスやトロピカル、メロンを思わせる香りが広がり、苦味も存在感を残す。公式のIBUは非公開。', 50.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=143316085', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=143316085', 'IBU_all,Color,Clarity,Fruity');

-- pr0108: PLEIADES (uchu-pleiades)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0108', 'mk0000', 'PLEIADES', 'st0000', 7, 20, NULL, NULL, NULL, 2, NULL, 'モザイクとクライオモザイクのホップを重ねた、二段仕込みのドライホップによるIPA。オーツ麦と小麦を麦芽に加えたなめらかな土台の上に、シトラスやパイナップル、ブルーベリー、マンゴーを思わせる味わいが広がり、苦味が全体の輪郭を締めくくっている。', 20, NULL, 'https://uchubrew.shop-pro.jp/?pid=145090886', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=145090886', 'IBU_all,Fruity');

-- pr0109: ANTARES (uchu-antares)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0109', 'mk0000', 'ANTARES', 'st0085', 10.0, 70.0, NULL, NULL, NULL, 2, NULL, 'シトラとリワカに、クライオ加工のシトラとネルソンソーヴィンを重ねたトリプルドライホップ仕込みのトリプルIPA。オレンジ、グレープフルーツ、パイナップル、マンゴー、メロンと、次々に果実の香りが移り変わる複雑な香り立ちが持ち味。度数の高さに見合う飲みごたえのある一本。', 70.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=149062364', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=149062364', 'IBU_all,Fruity');

-- pr0110: ALDEBARAN (uchu-aldebaran)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0110', 'mk0000', 'ALDEBARAN', 'st0085', 10.0, 70.0, NULL, NULL, NULL, 2, NULL, 'モザイクと凝縮ホップのクリオモザイクを用いたトリプルドライホップで仕込まれ、オレンジやパイナップル、マンゴー、マーマレードを思わせる濃密な果実の風味が幾重にも重なる味わいに仕上がっている。', 70.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=137999065', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=137999065', 'IBU_all,Fruity');

-- pr0111: ARCTURUS (uchu-arcturus)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0111', 'mk0000', 'ARCTURUS', 'st0090', 7.0, 50.0, NULL, 4, 2, 2, NULL, 'シムコーとギャラクシーのホップに、トロピカルでフルーティーな特徴を持つ酵母を合わせて仕込んだIPA。グレープフルーツやタンジェリン、パイナップル、ライムを思わせる香りが酵母由来の果実感と重なり合い、苦味もしっかり感じられる造り。公式のIBUは非公開。', 50.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=143695777', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=143695777', 'IBU_all,Color,Clarity,Fruity');

-- pr0112: ALTAIR (uchu-altair)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0112', 'mk0000', 'ALTAIR', 'st0085', 10, 70, NULL, NULL, NULL, 2, NULL, 'モツエカという一種類のホップのみを用いて仕込んだ、三段仕込みのドライホップによるトリプルIPA。原材料は麦芽と糖類とホップの三つに絞られたシンプルな構成で、トロピカルフルーツやパイナップル、ピーチ、メロン、ライムを思わせる風味が幾重にも層をなしている。高いアルコール度数に見合う、ホップの厚みを持たせた一本。', 70, NULL, 'https://uchubrew.shop-pro.jp/?pid=178562551', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=178562551', 'IBU_all,Fruity');

-- pr0113: ANDROMEDA (uchu-andromeda)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0113', 'mk0000', 'ANDROMEDA', 'st0004', 5.5, 40.0, NULL, NULL, NULL, 2, NULL, '麦芽にオーツ麦とゆず果汁を加えて仕込んだペールエール。シトラ、エクリプス、モザイク、ラカウ、シムコーに、クライオ加工のシトラとシムコーを重ねたホップ使いで、ゆずの香りにパイナップルやトロピカルフルーツを思わせる香りが重なる一本。公式のIBU値は非公開。', 40.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=175346684', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=175346684', 'IBU_all,Fruity');

-- pr0114: PLUTO (uchu-pluto)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0114', 'mk0000', 'PLUTO', 'st0094', 8.0, 60.0, NULL, NULL, NULL, 2, NULL, 'モザイクとネルソンソーヴィンに、クライオ加工のコロンバスとネルソンソーヴィンを重ねて仕込んだダブルIPA。「マスタープルート」の名を冠した一本で、レモンやライチ、マンゴー、パッションフルーツ、パイナップルを思わせる果実の風味が幾重にも広がる。公式のIBU値は非公開だが、果実感の強い味わいに仕上がっている。', 60.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=150970318', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=150970318', 'IBU_all,Fruity');

-- pr0115: CORE (uchu-core)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0115', 'mk0000', 'CORE', 'st0094', 8.0, 60.0, NULL, NULL, NULL, 2, NULL, '既存レシピをホップ増量で改訂したダブルIPA。シトラ、クラッシュ、ネクタロン、そしてクライオ加工したネクタロンとネルソンソーヴィンを重ねたホップ構成が特徴。オレンジやグレープフルーツ、パイナップル、マンゴーを思わせる香りが幾重にも広がる、ホップの存在感を前面に出した一本。公式のIBUは非公開。', 60.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=150970354', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=150970354', 'IBU_all,Fruity');

-- pr0116: INFINITY (uchu-infinity)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0116', 'mk0000', 'INFINITY', 'st0094', 8.5, 60, NULL, NULL, NULL, 2, NULL, 'ニュージーランド産のリワカホップと、濃縮ホップエキス「インコグニート」を用いて仕込んだダブルIPA。シトラ、ラカウ、クライオシトラのホップも重ね、アプリコットや柑橘、パイナップル、マンゴーを思わせる香りが幾重にも広がっていく。ホップの素材そのものを掘り下げた、奥行きのある一本。', 60, NULL, 'https://uchubrew.shop-pro.jp/?pid=152252998', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=152252998', 'IBU_all,Fruity');

-- pr0117: MAGNETAR (uchu-magnetar)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `IBU_Style`, `Comment`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0117', 'mk0000', 'MAGNETAR', 'st0094', 8.5, 60.0, NULL, NULL, NULL, 2, NULL, '小麦を加えた麦芽構成に、シトラとクライオ加工のシトラ、そしてNZH-109というホップ品種を重ねて仕込んだダブルIPA。キャンディを思わせる甘さに、マンダリンやピーチ、パッションフルーツ、パイナップルと多彩な果実の香りが広がる。公式のIBU値は公開されていない。', 60.0, NULL, 'https://uchubrew.shop-pro.jp/?pid=154202087', 'placeholder', 'https://uchubrew.shop-pro.jp/?pid=154202087', 'IBU_all,Fruity');
