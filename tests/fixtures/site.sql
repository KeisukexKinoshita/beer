-- 新サイトのページ(scope=site のケース)だけに足す合成データ。
-- fixtures.sql + db/migrations/ のあとに流す(tests/runner/load_fixtures.sh の追加SQL)。
-- 旧コードのケースには足さない(旧コードの golden が、このデータで変わらないようにするため)。
-- スキーマは migration が作ったものをそのまま使う(ここで列を足さない)。

-- 銘柄が 0 件のスタイル(スタイル詳細で「味覚マップ」「銘柄」の節を出さないことの検査用)
INSERT INTO style (StyleID, FamilyName, StyleName, IBU, StyleExplain, catchcopy) VALUES
  ('4', 'Sour', 'Gose', 10.00, 'Salty and tart wheat beer. No products yet.', 'Fixture catchcopy for Gose');
UPDATE style SET catchcopy = 'Fixture catchcopy for IPA' WHERE StyleID = '1';

-- 海外(GB)で、緯度経度のある蔵(蔵詳細・銘柄詳細で地図を出す経路の検査用)
INSERT INTO maker (MakerID, MakerName, MakerExplain, URL1, country_code, latitude, longitude) VALUES
  ('mk9004', 'Delta Brewing', 'Craft brewery Delta, a UK fixture with coordinates.', 'https://delta.example.com', 'GB', 57.360000, -2.070000);

-- 推定値(estimated_fields)と公式リンクを持つ銘柄
INSERT INTO products (ProductID, MakerID, ProductName, StyleID, Alcohol, IBU_all, IBU, Color, Clarity, Fruity, Favorite, ProductExplain, IBU_Style, Comment, official_url, estimated_fields, image_rights) VALUES
  (104, 'mk9004', 'Delta Estimated IPA', '1', 6.00, 45.00, NULL, '4', '2', '3', 3.90, 'An IPA whose IBU is estimated from the style.', 45.00, '', 'https://delta.example.com/ipa', 'IBU_all', 'placeholder');

-- 訪問者。db_vid Cookie の値がこの visitor_id
-- 確認済み(幕を出さない)
INSERT INTO visitor (visitor_id, first_seen, last_seen, age_confirmed, age_confirmed_at) VALUES
  ('0123456789abcdef0123456789abcdef', '2026-01-01 00:00:00', '2026-01-01 00:00:00', 1, '2026-01-01 00:00:00');
-- 行はあるが未確認(幕を出す)
-- csrf_token は固定値(try.php から飛ばされてきた「行のある訪問者」が、行ベースの csrf で「はい」を返す経路の検査用。
-- csrf_issue() は行に値があればそれをそのまま返す)
INSERT INTO visitor (visitor_id, first_seen, last_seen, age_confirmed, csrf_token) VALUES
  ('fedcba9876543210fedcba9876543210', '2026-01-01 00:00:00', '2026-01-01 00:00:00', 0,
   'c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0c0');

-- ---- NULL・空の場合分け(本番の実データは約4分の3の銘柄が未評価で、ここが一番踏まれる)----

-- 説明(MakerExplain)が空・URL1 が空・country_code が NULL・緯度経度なし・ロゴなしの蔵
INSERT INTO maker (MakerID, MakerName, MakerExplain, URL1, country_code, latitude, longitude, logo_path, logo_rights) VALUES
  ('mk9005', 'Epsilon Brewing', '', '', NULL, NULL, NULL, NULL, NULL);
-- logo_path のある蔵(ロゴ画像を出す経路)
INSERT INTO maker (MakerID, MakerName, MakerExplain, URL1, country_code, latitude, longitude, logo_path, logo_rights) VALUES
  ('mk9006', 'Zeta Brewing', 'Craft brewery Zeta, has a licensed logo.', 'https://zeta.example.com', 'JP', 35.680000, 139.760000, '/img/maker/zeta.png', 'licensed');
-- 銘柄が 0 件の蔵
INSERT INTO maker (MakerID, MakerName, MakerExplain, URL1, country_code, latitude, longitude) VALUES
  ('mk9007', 'Eta Brewing', 'Craft brewery Eta, no products yet.', 'https://eta.example.com', 'US', 40.710000, -74.000000);

-- 105: 度数・IBU・フルーティー・評価がすべて NULL(未計測)、スタイルなし(StyleID NULL)、説明が空、official_url なし
INSERT INTO products (ProductID, MakerID, ProductName, StyleID, Alcohol, IBU_all, IBU, Color, Clarity, Fruity, Favorite, ProductExplain, IBU_Style, Comment, image_rights) VALUES
  (105, 'mk9005', 'Epsilon Unmeasured', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '', 'placeholder');
-- 106: 評価だけ NULL(未評価)。ほかの値と説明はある
INSERT INTO products (ProductID, MakerID, ProductName, StyleID, Alcohol, IBU_all, IBU, Color, Clarity, Fruity, Favorite, ProductExplain, IBU_Style, Comment, image_rights) VALUES
  (106, 'mk9006', 'Zeta Unrated Lager', '2', 5.00, 25.00, 25.00, '2', '1', '1', NULL, 'A lager with no rating yet.', 30.00, '', 'placeholder');
-- 107: 度数と IBU だけ NULL。評価はある
INSERT INTO products (ProductID, MakerID, ProductName, StyleID, Alcohol, IBU_all, IBU, Color, Clarity, Fruity, Favorite, ProductExplain, IBU_Style, Comment, image_rights) VALUES
  (107, 'mk9001', 'Alpha No Spec Pale', '1', NULL, NULL, NULL, '5', '2', '4', 3.20, 'A pale ale with no measured ABV or IBU.', 45.00, '', 'placeholder');
-- 108: 度数・IBU・評価が本当に 0(未計測の「—」ではなく「0」と出ること)
INSERT INTO products (ProductID, MakerID, ProductName, StyleID, Alcohol, IBU_all, IBU, Color, Clarity, Fruity, Favorite, ProductExplain, IBU_Style, Comment, image_rights) VALUES
  (108, 'mk9001', 'Alpha Zero Stout', '3', 0.00, 0.00, 0.00, '9', '4', '0', 0.00, 'A non-alcoholic stout. Real zeros.', 35.00, '', 'placeholder');
