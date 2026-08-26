-- 仕様9-6「DBスキーマへの反映」+ データ更新パイプラインの前提となる列を追加する。
-- 後から足すと既存データの移行が発生するため、運用フェーズ前のいまの段階で入れておく。
-- 対象RDSはMySQL。ADD COLUMN IF NOT EXISTS は使えないので、
-- 再適用時の重複エラー(1060/42S21)は実行スクリプト側で無視する(冪等運用)。
-- 適用: dev = beer_dev / 本番昇格時 = beer

-- products
--   source_url       : この銘柄の情報をどこから取ったか(出典。仕様9-6)
--   image_rights     : 画像の権利区分 placeholder / affiliate / presskit / licensed (仕様9-6)
--   official_url     : 公式サイトへの表示用リンク(写真を転載しない代わりの導線。仕様9-1)
--   estimated_fields : スタイルの典型値から推定した列名をカンマ区切りで保持
--                      (例 'Alcohol,Fruity')。詳細ページで「推定値」と補記するために使う
ALTER TABLE products ADD COLUMN source_url       VARCHAR(2083) NULL;
ALTER TABLE products ADD COLUMN image_rights     VARCHAR(20)   NULL;
ALTER TABLE products ADD COLUMN official_url     VARCHAR(2083) NULL;
ALTER TABLE products ADD COLUMN estimated_fields VARCHAR(120)  NULL;

-- maker
--   source_url  : ブリュワリー情報の出典(Open Brewery DB / 公式サイト 等)
--   logo_rights : ロゴの権利区分。公式のブランドアセット利用条件を満たすものだけ画像を出す(仕様9-2)
ALTER TABLE maker ADD COLUMN source_url  VARCHAR(2083) NULL;
ALTER TABLE maker ADD COLUMN logo_rights VARCHAR(20)   NULL;

-- 既存の全銘柄は生成プレースホルダー表示に統一済み(仕様9-1)なので、その旨を記録する
UPDATE products SET image_rights = 'placeholder' WHERE image_rights IS NULL;
