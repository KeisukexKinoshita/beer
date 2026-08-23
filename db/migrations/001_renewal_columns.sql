-- リニューアル フェーズ1/2 用のスキーマ追加 (仕様§5)
-- 対象RDSはMySQLのため ADD COLUMN IF NOT EXISTS は使えない。
-- 再適用時の重複エラー(1060)は実行スクリプト側で無視する(冪等運用)。
-- 適用: dev = beer_dev / 本番昇格時 = beer に対して実行する

-- maker: 国コード(国旗用) / 緯度経度(地図用, Phase2で値を投入) / ロゴパス
ALTER TABLE maker ADD COLUMN country_code CHAR(2) NULL;
ALTER TABLE maker ADD COLUMN latitude  DECIMAL(9,6) NULL;
ALTER TABLE maker ADD COLUMN longitude DECIMAL(9,6) NULL;
ALTER TABLE maker ADD COLUMN logo_path VARCHAR(255) NULL;

-- style(実質Typeマスタ): キャッチコピー
ALTER TABLE style ADD COLUMN catchcopy VARCHAR(120) NULL;

-- 国コード投入 (既知の19ブリュワリー。海外5社以外はJP)
UPDATE maker SET country_code='JP';
UPDATE maker SET country_code='CA' WHERE MakerID='mk0001';
UPDATE maker SET country_code='GB' WHERE MakerID='mk0003';
UPDATE maker SET country_code='DK' WHERE MakerID='mk0005';
UPDATE maker SET country_code='US' WHERE MakerID IN ('mk0012','mk0014');
