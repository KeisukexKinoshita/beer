-- Darth Beer.com データ更新パイプライン シード
-- 目的     : 候補 19件 の反映 (新規 0件 / 更新 19件)。仕様§6 データ更新パイプライン
-- 対象DB   : dev = beer_dev / 本番昇格時 = beer
-- 種別     : DML のみ (DDL を含まない)
-- 冪等性   : deploy/apply_sql.php が schema_migrations(filename + sha256)で二重適用を防ぐ。
--            適用済みのこのファイルは書き換えないこと(内容の改変は runner が検知して拒否する)。
--            直し直すときは新しい番号のファイルを作る。
-- 生成元   : scripts/make_sql.py / run_id=20260829 / seq=007
--            候補ファイル = /workspace/tool/beer/beer-data-work/candidates-maker-all.json
-- 生成日時 : 2026-08-29 14:50:32
-- 巻き戻し : 007_seed_20260829_rollback.sql
-- 適用     : remote_sql.sh dev --apply <このファイル> --dry-run  で件数を確認してから本適用
--            (このパイプラインは DB を直接書き換えない。適用は必ず runner 経由)
-- 文字コード : SET NAMES は書かない。runner が DSN の charset=utf8mb4 を検査して保証する

-- mk0000 (UCHU BREWING)
--   変更前: MakerExplain='UCHU BREWING is...', source_url=NULL
UPDATE `maker` SET `MakerExplain` = 'UCHU BREWING(うちゅうブルーイング)は、山梨県北杜市高根町を拠点とする宇宙カンパニー合同会社が展開するビールブランド。運営統括責任者は楠瀬正紘。発泡酒製造免許(甲酒27)と酒類販売業免許(甲酒245)を保有している。', `source_url` = 'https://uchubrew.shop-pro.jp/?mode=sk' WHERE `MakerID` = 'mk0000';

-- mk0001 (Strathcona Beer Company)
--   変更前: MakerExplain='NYのデザイナーが手掛ける、目を引くパッケージが特徴。', source_url=NULL
UPDATE `maker` SET `MakerExplain` = 'カナダ・ブリティッシュコロンビア州バンクーバーのストラスコナ地区に拠点を置くブリュワリー。2016年、東ヘイスティングス通り895番地に醸造所を開いた。現在はHastings Street TaproomとStrathcona North Taproomの2拠点を運営している。フルーツビールの分野で評価を受けており、BC Beer Awards 2022ではLemon Mandarin Radlerがフルーツビール部門を、Canadian Brewing Awards 2025ではLove Buzzが同部門を受賞している。', `source_url` = 'https://www.strathconabeer.com/pages/contact' WHERE `MakerID` = 'mk0001';

-- mk0002 (West Coast Brewing)
--   変更前: MakerExplain='West Coast Brewing is…', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '静岡市用宗の地下60mから汲み上げる井戸水を仕込みに使うブリュワリー。オーナー兼Managing DirectorのDerrek Bustonは建築家としての顔も持ち、2019年7月の醸造開始に先立ち同年6月に酒造免許を取得している。ホップは米国・ニュージーランド・オーストラリア産のペレットタイプを中心に冷蔵管理し、毎週新作をリリースする体制を敷く。直営の店舗とホテル「THE VILLA」も運営している。', `source_url` = 'https://www.westcoastbrewing.jp/about/' WHERE `MakerID` = 'mk0002';

-- mk0003 (BREWDOG)
--   変更前: MakerExplain='BREWDOG is…', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '英国スコットランド・エロンに拠点を置くブリュワリー。2007年4月、ジェームズ・ワットとマーティン・ディッキーの2人がフレーザーバラの工業団地で創業し、少量醸造からビール造りを始めた。2008年に当時英国史上最強度数を謳う「TOKYO」を、2010年にはアルコール度数55%の「The End of History」を発売している。株式クラウドファンディング「Equity for Punks」の株主数は2021年時点で約20万人強にのぼる。2020年には世界初のカーボンネガティブなビール醸造所になったと発表した。', `source_url` = 'https://www.brewdog.jp/story/' WHERE `MakerID` = 'mk0003';

-- mk0004 (VERTERE)
--   変更前: MakerExplain='VERTERE is…', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '「バテレ」と読むVERTEREは、東京都西多摩郡奥多摩町氷川の醸造所を拠点とする醸造会社である。2014年12月にVERTERE合同会社として設立され、鈴木光・辻野木景の2名が創業者にあたる。醸造を始めたのは、設立から1年余り後の2016年2月からである。醸造所と同じ敷地内にはBrewery & Bottle Shopを併設し(駐車場15台)、直営のタップルームは奥多摩の本店を含め都内外に複数展開している。', `source_url` = 'http://verterebrew.com/story/' WHERE `MakerID` = 'mk0004';

-- mk0005 (Mikkeller)
--   変更前: MakerExplain='Mikkeller is…', source_url=NULL
UPDATE `maker` SET `MakerExplain` = 'デンマークのコペンハーゲンで2006年に設立された醸造会社。運営法人はMikkeller ApSで、独自のPodcastも配信している。物流拠点はケゲ（Køge）に置かれている。', `source_url` = 'https://mikkeller.com' WHERE `MakerID` = 'mk0005';

-- mk0006 (ヤッホーブルーイング)
--   変更前: MakerExplain='ヤッホーブルーイング is…', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '1997年に創業し、同年7月7日に代表銘柄「よなよなエール」を発売したのが、長野県軽井沢町に本社を置く醸造会社ヤッホーブルーイングである。本社機能を併設する御代田醸造所のほか、佐久醸造所、大阪の「よなよなビアライズ」、北海道のそらとしば醸造所など、複数の醸造拠点を国内に展開している。従業員数は223名(2026年4月時点)。', `source_url` = 'https://yohobrewing.com/company/' WHERE `MakerID` = 'mk0006';

-- mk0007 (COEDO BREWERY)
--   変更前: MakerExplain='COEDO BREWERY is…', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '埼玉県川越市を拠点とする、協同商事株式会社のクラフトビールブランド。母体の協同商事は1970年代から川越で有機農業に取り組んできた企業である。1996年に発泡酒製造免許を取得してコエドブルワリーを開設し、さつまいもを原料とした「サツマイモラガー」(現・紅赤-Beniaka-)の醸造を始めた。ここで働く職人たちは1997年から5年間、ドイツ出身の4代目ブラウマイスター、クリスチャン・ミッターバウアー氏のもとで醸造技術を学んでいる。2006年には地ビールからの転換として「クラフトビール」ブランドのCOEDOを創設した。', `source_url` = 'https://coedobrewery.com/pages/history' WHERE `MakerID` = 'mk0007';

-- mk0008 (ベアレン)
--   変更前: MakerExplain='ベアレン is…', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '岩手県盛岡市北山に本社工場を置くブリュワリー。社名はドイツ語で「熊」を意味し、2001年2月に設立された。現在も仕込みで使われているのは、ドイツ南部の街で手に入れ海路で運び込んだ、100年以上前の醸造設備だ。創業からの歩みは『つなぐビール　地方の小さな会社が創るもの』としてポプラ社から書籍化された。盛岡市内に直営のレストランやビアバーを複数構え、働き方改革や健康経営の分野でも表彰を受けている。', `source_url` = 'https://www.baerenbier.co.jp/corp-summary/' WHERE `MakerID` = 'mk0008';

-- mk0009 (Y.MARKET BREWING)
--   変更前: MakerExplain='Y.MARKET BREWING is…', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '前身は1935年創業の酒屋「岡田屋」で、2007年からクラフトビールを扱ってきたのが、名古屋市中村区に本社を置くY.MARKET BREWINGである。2012年に株式会社ワイマーケットとして法人化し、2014年にビール・発泡酒製造免許を取得して柳橋工場で最初の仕込み「Primale」を手がけた。2020年からは豊田市大野瀬町の稲武ホップファームで自社栽培も行っている。', `source_url` = 'https://craftbeer.nagoya/about/' WHERE `MakerID` = 'mk0009';

-- mk0010 (キリン)
--   変更前: MakerExplain='キリンは...', source_url=NULL
UPDATE `maker` SET `MakerExplain` = 'SPRING VALLEY BREWERYは、キリンホールディングスのクラフトビール部門である。母体のキリンホールディングスは1907年2月23日に設立された大手飲料グループで、資本金1,020億円、グループ企業数190社、従業員数31,144人(2025年実績)を数える。本社は東京都中野区中野四丁目10番2号の中野セントラルパークサウス(キリンビール株式会社本社)に置かれている。', `source_url` = 'https://www.kirinholdings.com/jp/profile/' WHERE `MakerID` = 'mk0010';

-- mk0011 (玉村本店)
--   変更前: MakerExplain='...', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '長野県下高井郡山ノ内町の株式会社玉村本店は、文化二年(1805年)に初代・喜惣治が清酒蔵として創業して以来、約220年の歴史を持つ。初代が酒造りの修行に励んだ上州玉村(現・群馬県佐波郡玉村町)の地名が、屋号「玉村」の由来となっている。2004年からは「志賀高原ビール」「山伏」の醸造を始め、清酒とビールを並行して手がける酒蔵となった。約150年前築の酒蔵の一部を改装した「酒蔵美術館 ギャラリー玉村本店」を併設し、2012年からは志賀高原でビールと音楽のイベント「SNOW MONKEY BEER LIVE」を主催している。2024年には志賀高原ビール20周年を記念した書籍も刊行された。', `source_url` = 'http://tamamura-honten.co.jp/?mode=f1' WHERE `MakerID` = 'mk0011';

-- mk0012 (REVISION BREWING)
--   変更前: MakerExplain='REVISION is...', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '米国ネバダ州北部を拠点とするブルワリー、REVISION BREWINGはブリュワーのJeremy Warrenが創業した。手がけるIPAの一つ「Reno as F*%k Hazy IPA」は2019年のWorld Lupulin ChallengeでJuicy Double IPA部門の金賞を受賞している。IPAのラベルデザインは20人以上のアーティストが手がけてきた。', `source_url` = 'https://revisionbrewing.com/about/' WHERE `MakerID` = 'mk0012';

-- mk0013 (伊勢角屋麦酒)
--   変更前: MakerExplain='...', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '伊勢角屋麦酒は、三重県伊勢市の有限会社二軒茶屋餅角屋本店が展開するクラフトビール事業のブランド。母体となる二軒茶屋餅角屋本店の起源は天正3年(1575年)、舟着場で営んだ茶店にさかのぼり、代を21代重ねてきた老舗である。有限会社としての法人設立は平成6年(1994年)。ビールのほか生菓子や味噌・醤油の製造販売、飲食店経営も手がけている。醸造拠点は神久工場/麦酒蔵と下野工場で、内宮前・外宮前に直営店を構える。', `source_url` = 'https://www.biyagura.jp/f/corporate' WHERE `MakerID` = 'mk0013';

-- mk0014 (ストーンブリューイング)
--   変更前: MakerExplain='...', source_url=NULL
UPDATE `maker` SET `MakerExplain` = 'Greg KochとSteve Wagnerが1996年にカリフォルニア州サンマルコスで創業した醸造会社。2005年に現在の拠点であるエスコンディードへ移り、120バレル醸造設備を構えた。2016年にはドイツ・ベルリンに醸造所を開設し、欧州で自社醸造所を持つ最初のアメリカ産クラフトビール会社になったとしている。米国東海岸バージニア州にも醸造拠点を持ち、Inc. 500/5000には計11回選出されている。', `source_url` = 'https://www.stonebrewing.com/about/our-story' WHERE `MakerID` = 'mk0014';

-- mk0015 (三菱食品)
--   変更前: MakerExplain='...', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '三菱食品株式会社は、食品の卸売を手がける商社。当サイトに登録されている「ガツんとIPA」「ジューシーIPA」「ゆずふわIPA」は、同社がラベル上の販売者として名を連ねる商品であり、実際にビールを醸造した蔵ではない。', `source_url` = 'https://www.mitsubishi-shokuhin.com/corporate/' WHERE `MakerID` = 'mk0015';

-- mk0016 (常陸野ネストビール)
--   変更前: MakerExplain='...', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '清酒「菊盛」を1823年から手がけてきた木内酒造(茨城県那珂市)が、1996年に始めたビールブランドが常陸野ネストビールである。製造開始から約1年後の1997年には「アンバーエール」が世界のビールコンテストで金賞を受賞し、その後1999〜2000年には輸出も始めた。2007年には額田醸造所を新設し、現在は年間約3000キロリットル、常時15種類以上のビールを製造している。栽培が途絶えていた日本古来のビール麦「金子ゴールデン」を地元農家と復活させ、ジャパニーズエール「ニッポニア」に使用するなど、原料面での取り組みもある。', `source_url` = 'https://kiuchibrewery.co.jp/about/story/' WHERE `MakerID` = 'mk0016';

-- mk0017 (オラホビール)
--   変更前: MakerExplain='...', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '「オラホ」は地元の方言で「わたしたち」を意味する。長野県東御市を拠点にこの名を冠したビールを手がけるのが、地域振興を目的とする第三セクター・株式会社信州東御市振興公社(平成6年=1994年7月1日創立、資本金9800万円)である。醸造を始めたのは、会社設立から2年後の1996年からで、地域振興の一環としてビール造りが誕生した。2010年からは、日照時間が長く降水量の少ない気候を生かしてホップの自社栽培に取り組み、2020年には併設レストランに新たな醸造設備を導入している。代表取締役は田丸基廣。', `source_url` = 'https://ohlahobeer.com/company/' WHERE `MakerID` = 'mk0017';

-- mk0018 (嬬恋高原ブルワリー)
--   変更前: MakerExplain='...', source_url=NULL
UPDATE `maker` SET `MakerExplain` = '嬬恋産のホップを使ってビールを仕込むマイクロブルワリーで、群馬県吾妻郡嬬恋村に所在する。運営は有限会社浅間高原麦酒。醸造所にはレストランが併設され、テイスティングもできる小規模な造り手である。', `source_url` = 'https://www.tsumabru.com/about-us' WHERE `MakerID` = 'mk0018';
