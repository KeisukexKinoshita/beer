-- Darth Beer.com データ更新パイプライン シード
-- 目的     : 候補 57件 の反映 (新規 57件 / 更新 0件)。仕様§6 データ更新パイプライン
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260909 / seq=014
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates-20260909-all.json
-- 生成日時 : 2026-09-14 23:53:44
-- 巻き戻し : 014_seed_20260909_rollback.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- mk0031: 軽井沢ブルワリー株式会社 (karuizawa-brewery)
INSERT INTO `maker` (`MakerID`, `MakerName`, `MakerExplain`, `URL1`, `country_code`, `latitude`, `longitude`, `logo_path`, `source_url`, `logo_rights`) VALUES ('mk0031', '軽井沢ブルワリー株式会社', '長野県軽井沢町に本社を置き、佐久市に醸造工場を構えるブリュワリー。2011年に会社を設立し、2013年に「THE 軽井沢ビール」のブランドを立ち上げてビール造りを始めた。ピルスナーやヴァイツェンといった定番のラインナップに加え、柚子など副原料を使ったクラフトシリーズも手がけている。', 'https://brewery.co.jp/', 'JP', 36.288815, 138.478699, NULL, 'https://brewery.co.jp/about-company/', 'none');

-- mk0032: 網走ビール株式会社 (maker-abashiri)
INSERT INTO `maker` (`MakerID`, `MakerName`, `MakerExplain`, `URL1`, `country_code`, `latitude`, `longitude`, `logo_path`, `source_url`, `logo_rights`) VALUES ('mk0032', '網走ビール株式会社', '北海道網走市に拠点を置くブリュワリー。1998年に設立され、オホーツク地域の原料を生かしたビール・発泡酒を手がけている。流氷や知床など、土地にちなんだ名前を冠した銘柄を複数展開しているのが特徴である。', 'https://www.abashiribeer.jp/', 'JP', 44.019502, 144.26575, NULL, 'https://www.abashiribeer.jp/company/', 'none');

-- mk0033: 上閉伊酒造株式会社 (maker-kamihei-shuzo)
INSERT INTO `maker` (`MakerID`, `MakerName`, `MakerExplain`, `URL1`, `country_code`, `latitude`, `longitude`, `logo_path`, `source_url`, `logo_rights`) VALUES ('mk0033', '上閉伊酒造株式会社', '岩手県遠野市の酒造会社。前身の建屋酒造として1789年に創業し、日本酒を手がけてきた蔵である。1999年からビール醸造を始め、遠野麦酒ZUMONAとして展開している。仕込みには遠野産のホップIBUKIなど、地元の原料を積極的に取り入れている。', 'https://kamihei-shuzo.jp/', 'JP', 39.323559, 141.575317, NULL, 'https://kamihei-shuzo.jp/', 'none');

-- mk0034: BRULO (w3-maker-brulo)
INSERT INTO `maker` (`MakerID`, `MakerName`, `MakerExplain`, `URL1`, `country_code`, `latitude`, `longitude`, `logo_path`, `source_url`, `logo_rights`) VALUES ('mk0034', 'BRULO', 'スコットランド・エディンバラを拠点とするビールメーカーで、創業者はジェームズ・ブラウン。手がける銘柄はすべてアルコール度数0.5%で仕込まれ、麦芽とホップを用いる通常の醸造工程を経て造られている。創業年は公式サイトに明記されていない。', 'https://www.brulobeer.com/', 'GB', NULL, NULL, NULL, 'https://www.brulobeer.com/pages/our-story', 'none');

-- mk0035: 伊豆の国ビール (w3-maker-izunokuni)
INSERT INTO `maker` (`MakerID`, `MakerName`, `MakerExplain`, `URL1`, `country_code`, `latitude`, `longitude`, `logo_path`, `source_url`, `logo_rights`) VALUES ('mk0035', '伊豆の国ビール', '2010年から醸造を始めたビールブランド。仕込みに使う麦芽とホップはドイツ・フランス・イギリスから輸入し、仕込みには1000リットルの釜を用いている。所在地の詳細や運営法人名は公式サイト上で確認できていない。', 'https://www.izubeer-hawaiians.com/', 'JP', NULL, NULL, NULL, 'https://www.izubeer-hawaiians.com/?mode=f3', 'none');

-- mk0036: ヘリオス酒造 (maker-helios-shuzo)
INSERT INTO `maker` (`MakerID`, `MakerName`, `MakerExplain`, `URL1`, `country_code`, `latitude`, `longitude`, `logo_path`, `source_url`, `logo_rights`) VALUES ('mk0036', 'ヘリオス酒造', '沖縄県名護市に本社を置く、1961年創業の酒造メーカー。泡盛やスピリッツ、ラムなどを手がけており、発泡酒(ビール)の製造も行っている。岩手県西和賀町には沢内醸造所という別の醸造拠点を持ち、本社とは異なる商品を展開している。', 'https://www.helios-syuzo.co.jp/', 'JP', 26.538204, 127.962738, NULL, 'https://www.helios-syuzo.co.jp/company/', 'none');

-- mk0037: エチゴビール (maker-echigo-beer)
INSERT INTO `maker` (`MakerID`, `MakerName`, `MakerExplain`, `URL1`, `country_code`, `latitude`, `longitude`, `logo_path`, `source_url`, `logo_rights`) VALUES ('mk0037', 'エチゴビール', '新潟県新潟市西蒲区に本社・工場を置くブリュワリー。1994年に全国に先駆けてビール製造免許を取得し、法人としては2000年に設立された。栃木県那須町にも工場を持つ。', 'https://echigobeer.com', 'JP', 37.791302, 138.87175, NULL, 'https://echigobeer.com/profile.php', 'none');

-- mk0038: 日本ビール (maker-nippon-beer)
INSERT INTO `maker` (`MakerID`, `MakerName`, `MakerExplain`, `URL1`, `country_code`, `latitude`, `longitude`, `logo_path`, `source_url`, `logo_rights`) VALUES ('mk0038', '日本ビール', '東京都目黒区に本社を置く、1979年設立のビール輸入・販売会社。2003年には国内でのオーガニックビール製造を始め、2011年・2015年にも国内製造の実績を重ねてきた。2025年6月からは自社ブランド「NINJA BEER」の展開を始め、国内の複数の醸造委託先で製造している。', 'https://www.nipponbeer.jp', 'JP', NULL, NULL, NULL, 'http://www.nipponbeer.jp/about/', 'none');

-- mk0039: 黄桜 (maker-kizakura)
INSERT INTO `maker` (`MakerID`, `MakerName`, `MakerExplain`, `URL1`, `country_code`, `latitude`, `longitude`, `logo_path`, `source_url`, `logo_rights`) VALUES ('mk0039', '黄桜', '京都市伏見区に蔵を構える、1925年創業の酒造メーカー。清酒造りを本業とし、1995年からビール造りにも参入した。「LUCKY BREW」「悪魔のビール」などのブランドでクラフトビールを展開している。', 'https://kizakura.co.jp', 'JP', 34.930668, 135.760437, NULL, 'https://kizakura.co.jp/company/profile.html', 'none');

-- pr0155: クリア (karuizawa-clear)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0155', 'mk0031', 'クリア', 'st0088', 5.0, 35, NULL, 2, 1, 1, '軽井沢ブルワリーがオリジナルシリーズと位置づけるピルスナータイプ。麦芽に佐久のブランド米やコーン、スターチを組み合わせ、アロマホップの香りをまとわせながらすっきりとした喉ごしとキレの良い後味に仕立てている。麦芽だけで仕込み度数も異なる「プレミアム・クリア」とは、原材料の設計からして別の狙いを持つ一本。', 'https://brewery.co.jp/product/clear/', 'placeholder', 'https://brewery.co.jp/product/clear/', 'IBU_all,Color,Clarity,Fruity');

-- pr0156: プレミアム・クリア (karuizawa-premium-clear)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0156', 'mk0031', 'プレミアム・クリア', 'st0066', 5.5, 37.5, NULL, 3, 1, 1, '麦芽100%で仕込む、軽井沢ブルワリーのプレミアムシリーズに連なるピルスナータイプ。ザーツホップの香りを利かせながら、麦芽由来の甘みでまとめている造り。ラベルには日本画家・千住博の作品「星のふる夜に」と浅間山にちなむ意匠をあしらう。米やコーンを加える「クリア」とは原材料も度数(5.5%)も異なる一本。', 'https://brewery.co.jp/product/premium-clear/', 'placeholder', 'https://brewery.co.jp/product/premium-clear/', 'IBU_all,Color,Clarity,Fruity');

-- pr0157: ダーク (karuizawa-dark)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0157', 'mk0031', 'ダーク', 'st0009', 5.0, 20.5, NULL, 6, 1, 1, '軽井沢ブルワリーのオリジナルシリーズが手がける濃色ラガー。ローストした麦芽の香ばしさを軸に、柔らかな甘さを残す造りで、米やコーンも使ってキレを整えている。麦芽100%で仕込む「プレミアム・ダーク」とは原材料と度数が異なり、同じ濃色でも狙う飲み口が違う。', 'https://brewery.co.jp/product/dark/', 'placeholder', 'https://brewery.co.jp/product/dark/', 'IBU_all,Color,Clarity,Fruity');

-- pr0158: プレミアム・ダーク (karuizawa-premium-dark)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0158', 'mk0031', 'プレミアム・ダーク', 'st0009', 5.5, 20.5, NULL, 6, 1, 1, '麦芽100%で仕込むプレミアムシリーズの濃色ラガー。アロマホップとザーツホップを重ね、香ばしさと芳醇な甘さを柔らかくまとめている。プレミアム・クリアと同様、千住博の「星のふる夜に」にちなむ意匠を持ち、米やコーンを使う「ダーク」より原材料も度数も異なる。', 'https://brewery.co.jp/product/premium-dark/', 'placeholder', 'https://brewery.co.jp/product/premium-dark/', 'IBU_all,Color,Clarity,Fruity');

-- pr0159: 黒ビール（ブラック） (karuizawa-black)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0159', 'mk0031', '黒ビール（ブラック）', 'st0011', 5.0, 26, NULL, 9, 1, 1, '軽井沢ブルワリーのオリジナルシリーズにある黒いラガー。麦芽に米・コーン・スターチを重ねた造りで、コーヒーを思わせる香ばしさとほろ苦さの奥に甘みを残す。同シリーズの「ダーク」がデュンケルまたはアンバー系の濃色を持ち味にするのに対し、この一本はロースト由来の香ばしさそのものを主役に据えている。', 'https://brewery.co.jp/product/black/', 'placeholder', 'https://brewery.co.jp/product/black/', 'IBU_all,Color,Clarity,Fruity');

-- pr0160: 白ビール（ヴァイス） (karuizawa-weiss)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0160', 'mk0031', '白ビール（ヴァイス）', 'st0100', 5.5, 20, NULL, 2, 4, 3, 'ドイツ産小麦麦芽を使う、軽井沢ブルワリーのオリジナルシリーズのヴァイツェン・ヴァイス。バナナを思わせる香りとほのかな酸味が特徴で、軽井沢の白樺林をイメージしたラベルをまとう。同じ配合は「いい日旅立ち〈ヴァイス〉」にも受け継がれているが、商品名も背景の意匠も異なる定番の一本。', 'https://brewery.co.jp/product/weiss/', 'placeholder', 'https://brewery.co.jp/product/weiss/', 'IBU_all,Color,Clarity,Fruity');

-- pr0161: 赤ビール（アルト） (karuizawa-alt)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0161', 'mk0031', '赤ビール（アルト）', 'st0040', 5.0, 38.5, NULL, 6, 2, 1, '軽井沢ブルワリーのオリジナルシリーズが手がけるアルト。ルビー色を帯びた深い色合いに、香ばしさと繊細な酸味、柔らかな甘さを重ねている。ドイツでソーセージ料理と合わせて親しまれてきた系統で、同シリーズの淡色や黒色のラガーとは酵母も製法も異なる上面発酵の一本。', 'https://brewery.co.jp/product/alt/', 'placeholder', 'https://brewery.co.jp/product/alt/', 'IBU_all,Color,Clarity,Fruity');

-- pr0162: 軽井沢エール＜エクセラン＞ (karuizawa-ale-excellent)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0162', 'mk0031', '軽井沢エール＜エクセラン＞', 'st0097', 5.5, 30, NULL, 3, 2, 4, 'シトラとギャラクシー、二種類のホップを使うゴールデンエール。柑橘やトロピカルフルーツを思わせる香りが立ち、クリーミーな口当たりでまとめている。同じくゴールデンエールを名乗る麦芽100%の「清涼飛泉プレミアム」とはホップの品種が異なり、香りの方向性で分かれる一本。', 'https://brewery.co.jp/product/karuizawa-ale/', 'placeholder', 'https://brewery.co.jp/product/karuizawa-ale/', 'IBU_all,Color,Clarity,Fruity');

-- pr0163: 清涼飛泉プレミアム (karuizawa-seiryo-hisen-premium)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0163', 'mk0031', '清涼飛泉プレミアム', 'st0097', 5.5, 30, NULL, 3, 2, 4, '麦芽100%で仕込む、軽井沢ブルワリーのプレミアムシリーズに連なるゴールデンエール。シトラとネルソンソーヴィン、二種のホップが白ぶどうやグレープフルーツを思わせる香りを引き出し、クリーミーな泡としっかり結びついている。日本画家・千住博の作品「ウォーターフォール」にちなむ意匠をまとい、ギャラクシーホップを使う「軽井沢エール＜エクセラン＞」とはホップの品種と香りの方向性で分かれる一本。', 'https://brewery.co.jp/product/seiryo-hisen-premium/', 'placeholder', 'https://brewery.co.jp/product/seiryo-hisen-premium/', 'IBU_all,Color,Clarity,Fruity');

-- pr0164: Dear Family 星のふる夜に〈クリア〉 (karuizawa-dear-family)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0164', 'mk0031', 'Dear Family 星のふる夜に〈クリア〉', 'st0088', 5.0, 35, NULL, 2, 1, 1, '軽井沢ビールの生誕12周年を記念して仕立てられたピルスナータイプ。麦芽に信州産コシヒカリなど米・コーン・スターチを重ねる造りは定番の「クリア」と同じ設計で、ラベルには日本画家・千住博の「星のふる夜に」と浅間山系の意匠をあしらっている。記念仕様としての位置づけが「クリア」との違いになる。', 'https://brewery.co.jp/product/dear-family/', 'placeholder', 'https://brewery.co.jp/product/dear-family/', 'IBU_all,Color,Clarity,Fruity');

-- pr0165: 軽井沢 香りのクラフト 柚子 (karuizawa-kaorino-craft-yuzu)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0165', 'mk0031', '軽井沢 香りのクラフト 柚子', 'st0074', 4.5, 25, NULL, 2, 2, 3, '麦芽とホップに柚子の果汁と果皮を加えた、軽井沢ブルワリーの香りのクラフトシリーズの一本。柚子由来のジューシーな香りと爽やかな後味が特徴で、酒税法上は発泡酒に区分される。同シリーズの中でも柚子という副原料の個性が前面に出た造りである。', 'https://brewery.co.jp/product/kaorino-craft-yuzu/', 'placeholder', 'https://brewery.co.jp/product/kaorino-craft-yuzu/', 'IBU_all,Color,Clarity,Fruity');

-- pr0166: いい日旅立ち〈ヴァイス〉 (karuizawa-iihitabidachi-weiss)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0166', 'mk0031', 'いい日旅立ち〈ヴァイス〉', 'st0100', 5.5, 20, NULL, 2, 4, 3, 'ドイツ産小麦麦芽で仕込むヴァイツェン・ヴァイス。淡い色合いとフルーティーな香りを、東京駅舎と汽車を思わせる意匠にまとめている。定番の「白ビール(ヴァイス)」と同じ配合を、旅情をテーマにした独自のラベルで届ける一本。', 'https://brewery.co.jp/product/iihitabidachi/', 'placeholder', 'https://brewery.co.jp/product/iihitabidachi/', 'IBU_all,Color,Clarity,Fruity');

-- pr0167: 流氷ドラフト (abashiri-ryuhyo-draft)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0167', 'mk0032', '流氷ドラフト', 'st0065', 5.0, 12, NULL, NULL, NULL, NULL, '網走ビール株式会社が手がける、酒税法上は発泡酒に区分される一本。名前の由来である流氷を仕込み水に使うことで、青色の見た目にオホーツク海の雰囲気を重ねている。飲み口は軽く、苦味は目立たない造りである。', 'https://www.abashiribeer.jp/online/list/?ca=25&pid=1716427777-795490', 'placeholder', 'https://www.abashiribeer.jp/online/list/?ca=25', 'IBU_all');

-- pr0168: 知床ドラフト (abashiri-shiretoko-draft)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0168', 'mk0032', '知床ドラフト', 'st0004', 5.0, 40, NULL, NULL, 1, 2, '網走ビールが手がける、透き通ったグリーンの発泡酒。名前は世界自然遺産に登録された知床にちなむ。柑橘を思わせる香りが立ち上がり、苦味も程よく効いた、澄んだ飲み口が持ち味。流氷ドラフトと並ぶ、色で個性を出す網走ビールならではの一本である。', 'https://www.abashiribeer.jp/online/list/?ca=27&pid=1716442540-704104', 'placeholder', 'https://www.abashiribeer.jp/online/list/?ca=27', 'IBU_all,Clarity,Fruity');

-- pr0169: 桜桃の雫 (abashiri-outo-no-shizuku)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0169', 'mk0032', '桜桃の雫', 'st0074', 5.0, 25, NULL, NULL, NULL, 3, '網走ビールが、地元・網走産のさくらんぼを使って仕込む発泡酒。果実由来の甘みと酸味を素直に生かした造りで、色つきの遊び心が目立つ同じ蔵のラインナップの中では、果実そのものの風味で勝負するタイプに位置づけられる。', 'https://www.abashiribeer.jp/online/list/?ca=44&pid=1716443174-972744', 'placeholder', 'https://www.abashiribeer.jp/online/list/?ca=44', 'IBU_all,Fruity');

-- pr0170: ABASHIRIプレミアムビール (abashiri-premium)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0170', 'mk0032', 'ABASHIRIプレミアムビール', 'st0005', 5.0, 20, NULL, NULL, NULL, NULL, '網走ビール株式会社が、網走産の麦芽を使って仕込む一本。苦味・甘味・旨味のどれも突出させない仕込みで、公式には「バランス型」と紹介されている。酒税法上はビールに区分される。', 'https://www.abashiribeer.jp/online/list/?ca=45&pid=1716446459-859176', 'placeholder', 'https://www.abashiribeer.jp/online/list/?ca=45', 'IBU_all');

-- pr0171: ABASHIRI White Ale (abashiri-white-ale)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0171', 'mk0032', 'ABASHIRI White Ale', 'st0086', 4.5, 15, NULL, NULL, NULL, NULL, '網走産の小麦にオレンジピールとコリアンダーシードを加えて仕込むホワイトエール。まろやかな口当たりに、スパイスの効いた香りが重なる造りで、網走ビールのラインナップの中では小麦を主役にした一本として位置づけられる。', 'https://www.abashiribeer.jp/online/list/?ca=47&pid=1732604629-599142', 'placeholder', 'https://www.abashiribeer.jp/online/list/?ca=47', 'IBU_all');

-- pr0172: ABASHIRI Golden Ale (abashiri-golden-ale)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0172', 'mk0032', 'ABASHIRI Golden Ale', 'st0097', 5.0, 30, NULL, NULL, NULL, 2, '網走産の麦芽に、シムコとシトラという二種のアメリカンホップを重ねて仕込んだゴールデンエール。鼻先には柑橘の皮を思わせる香りが立ち上がり、飲み込んだ後には軽い苦みが残る。網走ビールのラインナップの中では、ホップの主張が際立つ一本である。', 'https://www.abashiribeer.jp/online/list/?ca=48&pid=1732605137-767904', 'placeholder', 'https://www.abashiribeer.jp/online/list/?ca=48', 'IBU_all,Fruity');

-- pr0173: ABASHIRI Artisan Ale (abashiri-artisan-ale)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0173', 'mk0032', 'ABASHIRI Artisan Ale', 'st0105', 6.0, 45, NULL, NULL, NULL, NULL, '苦味・コク・香りのすべてを強めに造る、網走ビールの中でも骨太な一本。仕込みにはヴァイツェン酵母を用いており、小麦系ビールの酵母でIPAを仕上げるという珍しい組み合わせになっている。', 'https://www.abashiribeer.jp/online/list/?ca=26&pid=1716448509-640223', 'placeholder', 'https://www.abashiribeer.jp/online/list/?ca=26', 'IBU_all');

-- pr0174: 監極の黒 (abashiri-kankyoku-no-kuro)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0174', 'mk0032', '監極の黒', 'st0049', 5.5, 47.5, NULL, NULL, NULL, NULL, '5種類のモルトを使って仕込むスタウト。商品名は、観光地としても知られる網走監獄にちなんでいる。黒いビールでありながら飲みやすさを意識した造りで、重厚さより親しみやすさを狙った、網走ビールの中の黒い一本である。', 'https://www.abashiribeer.jp/online/list/?ca=46&pid=1716447210-909431', 'placeholder', 'https://www.abashiribeer.jp/online/list/?ca=46', 'IBU_all');

-- pr0175: 遠野麦酒ZUMONA WEIZEN (zumona-weizen)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0175', 'mk0033', '遠野麦酒ZUMONA WEIZEN', 'st0100', 5.0, 20, NULL, NULL, NULL, 3, '上閉伊酒造が手がける遠野麦酒ZUMONAのヴァイツェン。ドイツ産の麦芽と小麦麦芽を土台に、遠野産ホップIBUKIを加えて仕込む。バナナ様の香りが立ち、強い苦味を求めない造りで、後味は軽く抜けていく。通年で仕込まれている。', 'https://kamihei-shuzo.jp/product/zumona_weizen/', 'placeholder', 'https://kamihei-shuzo.jp/product/zumona_weizen/', 'IBU_all,Fruity');

-- pr0176: 遠野麦酒ZUMONA HAZY IPA (zumona-hazy-ipa)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0176', 'mk0033', '遠野麦酒ZUMONA HAZY IPA', 'st0093', 5.0, 50, NULL, NULL, NULL, 3, '遠野麦酒ZUMONAのヘイジーIPA。ドイツとイギリスの大麦麦芽にオーツ麦、遠野市産小麦『ゆきちから』を加え、遠野産ホップIBUKIを含む3種のホップで仕込む。複数のホップが重なり合うことで柑橘系の爽やかさがにじむ。', 'https://kamihei-shuzo.jp/product/zumona_weizen/zumona_hazyipa/', 'placeholder', 'https://kamihei-shuzo.jp/product/zumona_weizen/zumona_hazyipa/', 'IBU_all,Fruity');

-- pr0177: 遠野麦酒ZUMONA GOLDEN PILSNER (zumona-golden-pilsner)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0177', 'mk0033', '遠野麦酒ZUMONA GOLDEN PILSNER', 'st0088', 5.0, 35, NULL, NULL, NULL, 2, '遠野麦酒ZUMONAのゴールデンピルスナー。ドイツ製麦芽に、遠野産ホップIBUKIだけを合わせるシンプルな仕込みで、副原料を加えないぶんホップの個性がそのまま伝わる。柑橘系の香りとすっきりしたキレが持ち味で、通年商品としてズモナビールを代表する一本に位置づけられている。', 'https://kamihei-shuzo.jp/product/zumona_pilsner/', 'placeholder', 'https://kamihei-shuzo.jp/product/zumona_pilsner/', 'IBU_all,Fruity');

-- pr0178: 遠野麦酒ZUMONA ALTBIER (zumona-altbier)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0178', 'mk0033', '遠野麦酒ZUMONA ALTBIER', 'st0040', 5.0, 38.5, NULL, 6, NULL, NULL, '遠野麦酒ZUMONAが手がける、デュッセルドルフ発祥のアルト。ロースト麦芽とカラメルモルトを重ね、赤みを帯びた銅色に染め上げている。香ばしさに厚みのある甘みが続き、遠野産ホップIBUKIは主張を抑えて香りを穏やかに添える役に留め、麦芽由来の味わいを主役にした造りになっている。', 'https://kamihei-shuzo.jp/product/zumona_alt/', 'placeholder', 'https://kamihei-shuzo.jp/product/zumona_alt/', 'IBU_all,Color');

-- pr0179: 遠野麦酒ZUMONA GOLDEN ALE (zumona-golden-ale)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0179', 'mk0033', '遠野麦酒ZUMONA GOLDEN ALE', 'st0097', 5.0, 30, NULL, NULL, NULL, 2, '仕込みに大槌復興米を加えた、遠野麦酒ZUMONAのゴールデンエール。遠野産ホップIBUKIによる柑橘系の香りをまといながら、米の効果で口当たりは重さを感じさせないほど柔らかい。食事に合わせることを意識して造られた、ZUMONAの中でも食中酒としての性格が強い一本。', 'https://kamihei-shuzo.jp/product/zumona_goldenale/', 'placeholder', 'https://kamihei-shuzo.jp/product/zumona_goldenale/', 'IBU_all,Fruity');

-- pr0180: 仙人秘水BEER IPA (senninhisui-beer-ipa)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0180', 'mk0033', '仙人秘水BEER IPA', 'st0016', 6.0, 60, NULL, NULL, NULL, 2, '釜石鉱山が汲み上げる弱アルカリ性の岩盤湧水『仙人秘水』を仕込み水に用いたIPA。ホップは遠野産IBUKIだけでなく、釜石市の姉妹都市フランス・ディーニュ・レ・バン産のものも加えている。柑橘系の香りにすっきりとした苦味が続き、水の違いを味わいの軸に据えた造り。', 'https://kamihei-shuzo.jp/product/senninhisui_ipa/', 'placeholder', 'https://kamihei-shuzo.jp/product/senninhisui_ipa/', 'IBU_all,Fruity');

-- pr0181: 伊豆の国ビール ピルスナー (w3-izunokuni-pilsner)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0181', 'mk0035', '伊豆の国ビール ピルスナー', 'st0088', 5.0, 35, NULL, 2, 2, 1, NULL, '2010年に仕込みが始まったピルスナー。麦芽はイギリス産とドイツ産を主体に、ホップにはチェコ産のザーツを用いている。発酵の後期に少量のホップを加えるドライホッピングを取り入れており、ラガーの製法としては珍しい部類に入る。アロマホップ由来の香りが軽やかな飲み口と重なり、さわやかな喉越しに仕上がる造り。仕込みには1000リットルの釜が使われている。', 'https://www.izubeer-hawaiians.com/?mode=f3', 'placeholder', NULL, 'IBU_all,Color,Clarity,Fruity');

-- pr0182: 伊豆の国ビール ヴァイツェン (w3-izunokuni-weizen)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0182', 'mk0035', '伊豆の国ビール ヴァイツェン', 'st0100', 5.0, 20, NULL, 2, 2, 3, NULL, '2010年から仕込まれる、南ドイツスタイルのヘーフェヴァイツェン。小麦麦芽を主体に、ドイツ・フランス・イギリスから輸入した原料を用いている。仕込みの初期比重をやや高めに取ることで、標準的なヴァイツェンとボックスタイルの中間に位置づけられる厚みを持たせているのが特徴。フルーティーで香り豊かな酵母由来の香りが、奥深い味わいを支える。仕込釜は1000リットル。', 'https://www.izubeer-hawaiians.com/?mode=f3', 'placeholder', NULL, 'IBU_all,Color,Clarity,Fruity');

-- pr0183: 伊豆の国ビール スタウト (w3-izunokuni-stout)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0183', 'mk0035', '伊豆の国ビール スタウト', 'st0051', 7.0, 20, NULL, NULL, NULL, NULL, NULL, '伊豆の国市で2010年から仕込まれるスタウト。小麦麦芽と大麦麦芽を用い、50日以上の熟成期間をかけている。発酵しきらない糖分を原料の一部に使うことで、焙煎由来の香ばしさに甘みの厚みを重ねた、どっしりとした味わいに仕上げた造り。炭酸は弱めに抑えられ、きめ細やかな泡が液面を覆う。仕込釜は1000リットル。', 'https://www.izubeer-hawaiians.com/?mode=f3', 'placeholder', NULL, 'IBU_all');

-- pr0184: 7 Hop 7 Grain DDH IPA (w3-brulo-7hop7grain-ddh-ipa)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0184', 'mk0034', '7 Hop 7 Grain DDH IPA', 'st0000', 0.5, 20, NULL, NULL, NULL, 3, NULL, 'ベルギーの醸造施設で仕込まれるBRULOのDDH IPA。7種のホップと7種の穀物を大麦麦芽に重ね、仕込みの終盤と発酵後の二段階でホップを漬け込むダブルドライホップの製法を採る。トロピカルフルーツを思わせる香りの奥に、はっきりとした苦味が続く構成で、多種の穀物を掛け合わせた複雑な下地が持ち味になっている。', 'https://www.brulobeer.com/collections/all/products/7-grain-7-hop-ddh-ipa-0-0', 'placeholder', 'https://www.brulobeer.com/collections/all/products/7-grain-7-hop-ddh-ipa-0-0', 'IBU_all,Fruity');

-- pr0185: All Good IPA (w3-brulo-all-good-ipa)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0185', 'mk0034', 'All Good IPA', 'st0090', 0.5, 50, NULL, NULL, NULL, 3, NULL, 'ベルギーで仕込まれるBRULOのIPA。マンゴーとピーチを思わせる甘やかな果実香に、松脂のような樹脂香が重なる。ホップの主張がまっすぐ前に出る造りで、原材料の細部は公式ページに明記されていないが、香りの層の厚さがこの一本の軸になっている。', 'https://www.brulobeer.com/collections/all/products/all-good-ipa', 'placeholder', 'https://www.brulobeer.com/collections/all/products/all-good-ipa', 'IBU_all,Fruity');

-- pr0186: Cascadian Tides Stout (w3-brulo-cascadian-tides-stout)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0186', 'mk0034', 'Cascadian Tides Stout', 'st0049', 0.5, 47.5, NULL, NULL, NULL, 2, NULL, 'ベルギーで仕込まれるBRULOのドライホップ・スタウト。麦芽とホップだけの構成でありながら、シトラスと松脂を思わせるホップ由来の香りが、チョコレートやコーヒーを思わせるロースト由来の香ばしさと重なり合う。焙煎の重さとホップの爽やかさ、双方の顔を併せ持つ一本。', 'https://www.brulobeer.com/collections/all/products/dry-hopped-stout', 'placeholder', 'https://www.brulobeer.com/collections/all/products/dry-hopped-stout', 'IBU_all,Fruity');

-- pr0187: Five Fruit Gose (w3-brulo-five-fruit-gose)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0187', 'mk0034', 'Five Fruit Gose', 'st0064', 0.5, 10, NULL, NULL, NULL, 3, NULL, 'ベルギーで仕込まれるBRULOのゴーゼ。マンゴー、パッションフルーツ、グァバといった果実に海塩を重ねて仕込まれる、名の通り複数の果実を掛け合わせたゴーゼ。酸味とトロピカルな果実の甘みが折り重なり、塩気がその輪郭を締める一本。', 'https://www.brulobeer.com/collections/all/products/5-fruit-gose', 'placeholder', 'https://www.brulobeer.com/collections/all/products/5-fruit-gose', 'IBU_all,Fruity');

-- pr0188: Highway To Helles Lager (w3-brulo-highway-to-helles-lager)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0188', 'mk0034', 'Highway To Helles Lager', 'st0068', 0.5, 21.5, NULL, 2, NULL, 1, NULL, 'ベルギーで仕込まれるBRULOのヘレスラガー。淡い麦芽由来の穏やかな甘みを土台に、グラッシーな香りとドライな後味でまとめられている。液色は黄金色で、ホップの主張を抑えたヘレスらしい丸みのある飲み口が持ち味の一本。', 'https://www.brulobeer.com/collections/all/products/highway-to-hell-lager', 'placeholder', 'https://www.brulobeer.com/collections/all/products/highway-to-hell-lager', 'IBU_all,Color,Fruity');

-- pr0189: King For A Day NEIPA (w3-brulo-king-for-a-day-neipa)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0189', 'mk0034', 'King For A Day NEIPA', 'st0002', 0.5, 25, NULL, 2, 4, 2, NULL, 'ベルギーで仕込まれるBRULOのニューイングランドIPA。パイナップルとグレープフルーツの果実香に、マダガスカル産バニラの甘い香りが重なる、珍しい組み合わせの一本。ジューシーな果実味を前面に出しながら、バニラの丸みが後味に余韻を残す造り。', 'https://www.brulobeer.com/collections/all/products/king-for-a-day-neipa-0-5', 'placeholder', 'https://www.brulobeer.com/collections/all/products/king-for-a-day-neipa-0-5', 'IBU_all,Color,Clarity,Fruity');

-- pr0190: Lust For Life DDH IPA (w3-brulo-lust-for-life-ddh-ipa)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0190', 'mk0034', 'Lust For Life DDH IPA', 'st0000', 0.5, 20, NULL, NULL, NULL, 3, NULL, 'BRULOのDDH IPAのひとつ。仕込みの終盤と発酵後の二段階でホップを漬け込むダブルドライホップの製法を採り、マンゴーとピーチを思わせる果実香に松脂のような樹脂香を重ねている。同ブランドのIPAと共通する果実の方向性を持ちながら、二段仕込みによって香りの層をより厚くした一本。', 'https://www.brulobeer.com/collections/all/products/ddh-ipa-00-alcohol-free', 'placeholder', 'https://www.brulobeer.com/collections/all/products/ddh-ipa-00-alcohol-free', 'IBU_all,Fruity');

-- pr0191: Lust For Life IPA (w3-brulo-lust-for-life-ipa)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `Favorite`, `ProductExplain`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0191', 'mk0034', 'Lust For Life IPA', 'st0090', 0.5, 50, NULL, NULL, NULL, 3, NULL, 'BRULOのIPA。水、麦芽、小麦、マルトデキストリン、酵母、ホップとシンプルな原材料で仕込まれ、マンゴーとピーチの果実香に松脂のような樹脂香が重なる、ジューシーな飲み口が持ち味の一本。麦芽に小麦を配合することで、まろやかな口当たりを支えている。', 'https://www.brulobeer.com/collections/all/products/lust-for-life-ddh-ipa', 'placeholder', 'https://www.brulobeer.com/collections/all/products/lust-for-life-ddh-ipa', 'IBU_all,Fruity');

-- pr0192: ゴーヤーDRY (helios-goya-dry)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `IBU_Style`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0192', 'mk0036', 'ゴーヤーDRY', 'st0074', 5.0, 25, NULL, NULL, NULL, NULL, '沖縄県産のゴーヤーを使って仕込んだ発泡酒。ゴーヤー特有のほろ苦さを麦芽の甘みで受け止めた、沖縄ならではの一杯に仕立てられている。原材料の詳細や苦味の数値は公式には示されておらず、ゴーヤーという素材の個性がそのまま伝わるシンプルな構成のビールである。', 25, 'https://www.helios-shop.jp/shopbrand/ct81/', 'placeholder', 'https://www.helios-shop.jp/shopbrand/ct81/', 'IBU_all');

-- pr0193: 青い空と海のビール (helios-aoi-sora-to-umi)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `IBU_Style`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0193', 'mk0036', '青い空と海のビール', 'st0100', 5.0, 20, NULL, NULL, NULL, NULL, '小麦麦芽を使って仕込む、南ドイツスタイルの小麦ビール。泡盛やラムを長く手がけてきた沖縄のヘリオス酒造が造る一本で、同じ蔵のゴーヤーやシークヮーサーを使ったビールとは違い、副原料に頼らず小麦麦芽そのものの性格で組み立てられている。', 20, 'https://www.helios-shop.jp/shopbrand/ct82/', 'placeholder', 'https://www.helios-shop.jp/shopbrand/ct82/', 'IBU_all');

-- pr0194: シークヮーサーホワイトエール (helios-shikuwasa-white-ale)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `IBU_Style`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0194', 'mk0036', 'シークヮーサーホワイトエール', 'st0086', 5.0, 15, NULL, 1, 2, 2, '沖縄産のシークヮーサーを使って仕上げたホワイトエール。柑橘の爽やかな香りを軸に据え、小麦を使うホワイトエール本来の軽やかな飲み口と組み合わせている。沖縄ならではの柑橘を主役にした一本で、度数以外の詳しい数値は公式には示されていない。', 15, 'https://www.helios-shop.jp/shopbrand/ct84/', 'placeholder', 'https://www.helios-shop.jp/shopbrand/ct84/', 'IBU_all,Color,Clarity,Fruity');

-- pr0195: 星空のポーター (helios-hoshizora-porter)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `IBU_Style`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0195', 'mk0036', '星空のポーター', 'st0096', 5.0, 35, NULL, NULL, NULL, NULL, 'ヘリオス酒造が手がけるポーター。商品一覧では『濃色系エールビール』とも表記されており、沖縄の蔵が展開する濃色ビールのひとつに位置づけられる。原材料の詳細や苦味の数値は公式には示されていない。', 35, 'https://www.helios-shop.jp/shopbrand/ct83/', 'placeholder', 'https://www.helios-shop.jp/shopbrand/ct83/', 'IBU_all');

-- pr0196: ユキノチカラ 白ビール (helios-yukinochikara-white)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `IBU_Style`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0196', 'mk0036', 'ユキノチカラ 白ビール', 'st0086', 5.0, 15, NULL, 1, 2, 3, 'ヘリオス酒造の岩手県西和賀町にある沢内醸造所が手がけるホワイトエール。沖縄本社の副原料系ラインとは異なり、雪深い岩手の拠点で仕込まれる一本で、果実を思わせる香りとほのかな酸味が、小麦由来の軽やかな飲み口に重なる。原材料の詳細や度数以外の数値は公式には示されていない。', 15, 'https://helios-sawauchi.jp/products/151917514', 'placeholder', 'https://helios-sawauchi.jp/collections/%E3%83%A6%E3%82%AD%E3%83%8E%E3%83%81%E3%82%AB%E3%83%A9%E7%99%BD%E3%83%93%E3%83%BC%E3%83%AB', 'IBU_all,Color,Clarity,Fruity');

-- pr0197: FLYING IPA (echigo-flying-ipa)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `IBU_Style`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0197', 'mk0037', 'FLYING IPA', 'st0016', 5.5, 55, 55, 2, NULL, NULL, '新潟のエチゴビールが定番として造るアメリカンスタイルのIPA。原料は大麦麦芽とホップのみとシンプルな構成で、注ぐとゴールドの液色を見せる。度数はIPAとしては標準的な範囲に置かれ、通年で手に入る定番の一本である。', 60, 'https://echigobeer.com/products.php', 'placeholder', 'https://echigobeer.com/products.php', 'Color');

-- pr0198: NINJA WHITE (nippon-beer-ninja-white)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `IBU_Style`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0198', 'mk0038', 'NINJA WHITE', 'st0086', 5.0, 15, NULL, 2, 3, 2, '麦芽にオーツ麦を重ね、オレンジピールとコリアンダーシードを加えて仕込んだホワイトエール。注ぐと白く濁った金色の液体が現れ、柑橘とスパイスの穏やかな香りをまとう。2025年に発売された「NINJA BEER」ブランドの基本ラインナップの一本で、岩手県の醸造委託先で製造されている。', 15, 'https://www.nipponbeer.jp/lineup/ninja-white/', 'placeholder', 'https://www.nipponbeer.jp/lineup/ninja-white/', 'IBU_all,Color,Clarity,Fruity');

-- pr0199: LUCKY DOG (kizakura-lucky-dog)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `IBU_Style`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0199', 'mk0039', 'LUCKY DOG', 'st0004', 5.0, 40, NULL, NULL, NULL, NULL, '麦芽とホップ、米を使って仕込まれたアメリカンペールエール系のビール。黄桜が「LUCKY BREW」の名で展開するシリーズの一本で、通年で扱われる定番として位置づけられている。原材料以外の詳しい数値は公式には十分な形で確認できていない。', 40, 'https://kizakura.co.jp/ja/product_introduction/info.php?type=items4&id=IC000118', 'placeholder', 'https://kizakura.co.jp/ja/product_introduction/info.php?type=items4&id=IC000118', 'IBU_all');

-- pr0200: LUCKY CAT (kizakura-lucky-cat)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `IBU_Style`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0200', 'mk0039', 'LUCKY CAT', 'st0076', 5.0, 23, NULL, NULL, NULL, 2, '柚子と山椒を加えて仕込まれた、麦芽・ホップ・米をベースにしたビール。ライトボディで軽快な飲み口に仕上げられており、柑橘と山椒の穏やかな香りが特徴になっている。黄桜が「LUCKY BREW」シリーズの一本として展開している。', 23, 'https://kizakura.co.jp/lp/lucky.html', 'placeholder', 'https://kizakura.co.jp/lp/lucky.html', 'IBU_all,Fruity');

-- pr0201: 悪魔のビール レッドセッションIPA (kizakura-akuma-red-session-ipa)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `IBU_Style`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0201', 'mk0039', '悪魔のビール レッドセッションIPA', 'st0013', 5.0, 35, NULL, 6, NULL, 2, '黄桜「悪魔のビール」シリーズに数えられる、アメリカンスタイルのブラウンエール。麦芽とホップに米を重ねて仕込まれ、ホップは南国の果実を思わせる香りをもたらす。液色は赤みの強い色合いに仕上がり、ブラウンエールらしい麦芽のコクとホップの苦味が響き合う一本。', 35, 'https://kizakura.co.jp/ja/product_introduction/info.php?type=items4&id=IC000163', 'placeholder', 'https://kizakura.co.jp/ja/product_introduction/info.php?type=items4&id=IC000163', 'IBU_all,Color,Fruity');

-- pr0202: CAPTAIN CROW エクストラペールエール (oraho-captain-crow)
INSERT INTO `products` (`ProductID`, `MakerID`, `ProductName`, `StyleID`, `Alcohol`, `IBU_all`, `IBU`, `Color`, `Clarity`, `Fruity`, `ProductExplain`, `IBU_Style`, `source_url`, `image_rights`, `official_url`, `estimated_fields`) VALUES ('pr0202', 'mk0017', 'CAPTAIN CROW エクストラペールエール', 'st0004', 5.0, 57, 57, NULL, NULL, NULL, 'オラホビールが2014年から手がける、エクストラペールエールと名乗るアメリカンペールエール系の一本。2024年に発売10周年を迎えた定番で、ワールドビアアワード2017ではBitter 4%-5%部門で世界一に、ジャパン・グレートビアアワード2019ではアメリカンスタイルペールエール部門で銀賞に選ばれている。ホップの苦味がしっかり効いた、骨太な飲み口が持ち味の一本。', 40, 'https://ohlahobeer.com/captaincrow/', 'placeholder', 'https://ohlahobeer.com/captaincrow/', NULL);
