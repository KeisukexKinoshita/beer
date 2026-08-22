-- beer 特性テスト 合成フィクスチャ (tests/cases.md §1 に対応)
-- DDLは合成 (実RDSのDDLは取得不可のため)。型の選定方針:
--   * 文字列ID (MakerID/StyleID/Color/Clarity/Fruity等) は VARCHAR
--   * 金額的数値は DECIMAL。小数2桁で統一 (台帳の表記に合わせる)。
--     IBU も DECIMAL(6,2) とする (台帳の "0.000" は数値比較 WHERE IBU=0.000 で等価)
--   * 連番ID (ProductID/Rate_userID/ClarityID/FruityID) は INT
-- 列順序は台帳の「想定DDL列順序」= コードの INSERT 文の列順と一致させる。

DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS maker;
DROP TABLE IF EXISTS style;
DROP TABLE IF EXISTS clarity;
DROP TABLE IF EXISTS fruity;
DROP TABLE IF EXISTS rate_user;

CREATE TABLE products (
  ProductID INT,
  MakerID VARCHAR(16),
  ProductName VARCHAR(255),
  StyleID VARCHAR(16),
  Alcohol DECIMAL(5,2),
  IBU_all DECIMAL(6,2),
  IBU DECIMAL(6,2),
  Color VARCHAR(16),
  Clarity VARCHAR(16),
  Fruity VARCHAR(16),
  Favorite DECIMAL(4,2),
  ProductExplain TEXT,
  IBU_Style DECIMAL(6,2),
  Comment TEXT
);

CREATE TABLE maker (
  MakerID VARCHAR(16),
  MakerName VARCHAR(255),
  MakerExplain TEXT,
  URL1 VARCHAR(255)
);

CREATE TABLE style (
  StyleID VARCHAR(16),
  FamilyName VARCHAR(255),
  StyleName VARCHAR(255),
  IBU DECIMAL(6,2),
  StyleExplain TEXT
);

CREATE TABLE clarity (
  ClarityID INT,
  ClarityValue INT,
  ClarityName VARCHAR(255)
);

CREATE TABLE fruity (
  FruityID INT,
  FruityValue INT,
  FruityName VARCHAR(255)
);

CREATE TABLE rate_user (
  Rate_userID INT,
  ProductID INT,
  UserID VARCHAR(64),
  Color_user VARCHAR(16),
  Clarity_user VARCHAR(16),
  Fruity_user VARCHAR(16),
  Favorite_user VARCHAR(16),
  New_Update VARCHAR(16),
  Comment TEXT
);

INSERT INTO maker VALUES
  ('mk9001','Alpha Brewing','Craft brewery Alpha, known for citrusy IPA.','https://alpha.example.com'),
  ('mk9002','Beta Brewing','Craft brewery Beta, focuses on lagers.','https://beta.example.com'),
  ('mk9003','Gamma Brewing','Craft brewery Gamma, dark beer specialist.','https://gamma.example.com');

INSERT INTO style VALUES
  ('1','Ale','IPA',45.00,'Hoppy and bitter ale style.'),
  ('2','Lager','Pilsner',30.00,'Crisp, light lager style.'),
  ('3','Ale','Stout',35.00,'Dark, roasty ale style.');

INSERT INTO clarity VALUES
  (1,1,'Brilliant'),
  (2,2,'Clear'),
  (3,3,'Slight Haze'),
  (4,4,'Hazy');

INSERT INTO fruity VALUES
  (1,1,'Not Fruity'),
  (2,2,'Bit Fruity'),
  (3,3,'Fruity Beer'),
  (4,4,'Almost Smoothie');

INSERT INTO products VALUES
  (101,'mk9001','Alpha Citrus IPA','1',5.50,42.00,42.00,'3','1','2',4.20,'A citrusy IPA from Alpha Brewing.',45.00,''),
  (102,'mk9002','Beta Light Pilsner','2',4.80,99.99,0.00,'2','2','1',3.50,'A crisp pilsner from Beta Brewing.',30.00,''),
  (103,'mk9003','Gamma Dark Stout','3',6.20,35.00,35.00,'9','4','1',4.80,'A roasty stout from Gamma Brewing.',35.00,'');

INSERT INTO rate_user VALUES
  (1,101,'us0001','3','1','2','4','new','Great IPA!'),
  (2,102,'us0002','2','2','1','3','update','Nice pilsner.');
