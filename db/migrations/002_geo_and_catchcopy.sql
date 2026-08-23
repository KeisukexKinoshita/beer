-- フェーズ2: ブリュワリーの緯度経度(地図用) と スタイルのキャッチコピー投入
-- 対象RDSはMySQL。再適用時の重複更新は無害(UPDATEのみ)。
-- 座標は各社所在地の概略値(市区町村レベル)。厳密でなくてよい(地図ピン用)。

UPDATE maker SET latitude=35.770, longitude=138.430 WHERE MakerID='mk0000'; -- UCHU 山梨県北杜市
UPDATE maker SET latitude=49.280, longitude=-123.060 WHERE MakerID='mk0001'; -- Strathcona Vancouver
UPDATE maker SET latitude=35.100, longitude=138.860 WHERE MakerID='mk0002'; -- West Coast 静岡県沼津市
UPDATE maker SET latitude=57.360, longitude=-2.070 WHERE MakerID='mk0003'; -- BREWDOG Ellon
UPDATE maker SET latitude=35.810, longitude=139.100 WHERE MakerID='mk0004'; -- VERTERE 東京都奥多摩町
UPDATE maker SET latitude=55.670, longitude=12.540 WHERE MakerID='mk0005'; -- Mikkeller Copenhagen
UPDATE maker SET latitude=36.340, longitude=138.600 WHERE MakerID='mk0006'; -- ヤッホー 長野県軽井沢町
UPDATE maker SET latitude=35.920, longitude=139.480 WHERE MakerID='mk0007'; -- COEDO 埼玉県川越市
UPDATE maker SET latitude=39.700, longitude=141.150 WHERE MakerID='mk0008'; -- ベアレン 岩手県盛岡市
UPDATE maker SET latitude=35.170, longitude=136.880 WHERE MakerID='mk0009'; -- Y.MARKET 愛知県名古屋市
UPDATE maker SET latitude=35.450, longitude=139.640 WHERE MakerID='mk0010'; -- キリン 横浜
UPDATE maker SET latitude=36.740, longitude=138.420 WHERE MakerID='mk0011'; -- 玉村本店/志賀高原 長野県山ノ内町
UPDATE maker SET latitude=39.530, longitude=-119.750 WHERE MakerID='mk0012'; -- REVISION Sparks NV
UPDATE maker SET latitude=34.490, longitude=136.710 WHERE MakerID='mk0013'; -- 伊勢角屋 三重県伊勢市
UPDATE maker SET latitude=33.120, longitude=-117.080 WHERE MakerID='mk0014'; -- Stone Escondido CA
UPDATE maker SET latitude=35.700, longitude=139.750 WHERE MakerID='mk0015'; -- 三菱食品 東京
UPDATE maker SET latitude=36.450, longitude=140.480 WHERE MakerID='mk0016'; -- 常陸野ネスト/木内 茨城県那珂市
UPDATE maker SET latitude=36.360, longitude=138.330 WHERE MakerID='mk0017'; -- オラホ 長野県東御市
UPDATE maker SET latitude=36.520, longitude=138.530 WHERE MakerID='mk0018'; -- 嬬恋高原 群馬県嬬恋村

UPDATE style SET catchcopy='ホップの華やかな苦味と香り' WHERE StyleID='st0090';
UPDATE style SET catchcopy='濁りとジューシーな果実感' WHERE StyleID='st0002';
UPDATE style SET catchcopy='英国伝統、モルトと苦味の調和' WHERE StyleID='st0017';
UPDATE style SET catchcopy='松脂と柑橘、キレのある苦味' WHERE StyleID='st0084';
UPDATE style SET catchcopy='もやがかった、とろける果実味' WHERE StyleID='st0093';
UPDATE style SET catchcopy='甘く濃厚、デザートのような黒' WHERE StyleID='st0083';
UPDATE style SET catchcopy='小麦とスパイスの軽やかさ' WHERE StyleID='st0086';
UPDATE style SET catchcopy='低アルで軽快、毎日飲めるIPA' WHERE StyleID='st0087';
UPDATE style SET catchcopy='ラガーの下面発酵×ホップの香り' WHERE StyleID='st0089';
UPDATE style SET catchcopy='農家仕込みのドライで爽快な酵母感' WHERE StyleID='st0098';
UPDATE style SET catchcopy='フルーツ×ヘイジーの甘やかさ' WHERE StyleID='st0095';
UPDATE style SET catchcopy='ロースト麦芽のなめらかな黒' WHERE StyleID='st0096';
UPDATE style SET catchcopy='黄金色、飲みやすいベルジャン' WHERE StyleID='st0097';
UPDATE style SET catchcopy='薩摩芋の甘みと琥珀色の深み' WHERE StyleID='st0102';
UPDATE style SET catchcopy='バナナとクローブ、白濁の小麦' WHERE StyleID='st0100';
UPDATE style SET catchcopy='黒いのに軽い、すっきりラガー' WHERE StyleID='st0101';
UPDATE style SET catchcopy='二段仕込みの強烈ホップアロマ' WHERE StyleID='st0000';
UPDATE style SET catchcopy='高アルコール、濃密ヘイジー' WHERE StyleID='st0092';
UPDATE style SET catchcopy='マシュマロとチョコの甘い黒' WHERE StyleID='st0091';
UPDATE style SET catchcopy='爽快でキレのある黄金ラガー' WHERE StyleID='st0088';
UPDATE style SET catchcopy='度数もホップも極まった一杯' WHERE StyleID='st0085';
UPDATE style SET catchcopy='アメリカンホップの柑橘感' WHERE StyleID='st0016';
UPDATE style SET catchcopy='軽快なホップ、バランス型' WHERE StyleID='st0004';
UPDATE style SET catchcopy='スムージーのような甘酸っぱさ' WHERE StyleID='st0001';
