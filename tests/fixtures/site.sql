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
INSERT INTO visitor (visitor_id, first_seen, last_seen, age_confirmed) VALUES
  ('fedcba9876543210fedcba9876543210', '2026-01-01 00:00:00', '2026-01-01 00:00:00', 0);
