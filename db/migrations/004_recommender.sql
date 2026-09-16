-- 写真から似た1本を薦める機能のための5テーブル(設計書 §4)。
-- 既存の products / maker / style には一切触らない。
-- 対象RDSはMySQL 8.4。文字コードは utf8mb4。

CREATE TABLE IF NOT EXISTS visitor (
  visitor_id    CHAR(32)     NOT NULL,            -- ランダム。Cookie に入れる値そのもの
  first_seen    DATETIME     NOT NULL,
  last_seen     DATETIME     NOT NULL,
  line_user_id  VARCHAR(64)  NULL,                -- LINE連携はこの1列を埋めるだけ
  age_confirmed TINYINT(1)   NOT NULL DEFAULT 0,  -- 年齢確認の記憶(計画2で使う)
  PRIMARY KEY (visitor_id),
  UNIQUE KEY uq_visitor_line (line_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS upload (
  upload_id     BIGINT       NOT NULL AUTO_INCREMENT,
  visitor_id    CHAR(32)     NOT NULL,
  created_at    DATETIME     NOT NULL,
  image_path    VARCHAR(255) NULL,                -- ビール以外と判定したものは保存しないので NULL
  image_hash    CHAR(64)     NOT NULL,            -- sha256。同じ写真の再送を API を呼ばずに返す
  is_beer       TINYINT(1)   NOT NULL,
  product_id    CHAR(6)      NULL,                -- 同定できた銘柄。できなければ NULL
  brand_text    VARCHAR(191) NULL,                -- ラベルから読めた銘柄名
  brewery_text  VARCHAR(191) NULL,
  style_guess   CHAR(6)      NULL,                -- style.StyleID
  color         TINYINT      NULL,                -- 1-10
  clarity       TINYINT      NULL,                -- 1-4
  confidence    DECIMAL(4,3) NULL,                -- 0.000-1.000
  model         VARCHAR(40)  NULL,                -- 使ったモデル。後で精度を振り返るため
  PRIMARY KEY (upload_id),
  KEY idx_upload_visitor (visitor_id, created_at),
  KEY idx_upload_hash (image_hash),
  KEY idx_upload_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS unknown_beer (
  unknown_id    BIGINT       NOT NULL AUTO_INCREMENT,
  brand_text    VARCHAR(191) NOT NULL,
  brewery_text  VARCHAR(191) NULL,
  hits          INT          NOT NULL DEFAULT 1,
  last_seen     DATETIME     NOT NULL,
  PRIMARY KEY (unknown_id),
  UNIQUE KEY uq_unknown_name (brand_text, brewery_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recommendation (
  reco_id       BIGINT       NOT NULL AUTO_INCREMENT,
  upload_id     BIGINT       NOT NULL,
  position      TINYINT      NOT NULL,            -- 1,2,3
  product_id    CHAR(6)      NOT NULL,
  stage         TINYINT      NOT NULL,            -- カスケードの何段目で拾ったか
  pr_product_id CHAR(6)      NULL,                -- PR枠に出した銘柄(計画3で使う)
  created_at    DATETIME     NOT NULL,
  PRIMARY KEY (reco_id),
  UNIQUE KEY uq_reco_pos (upload_id, position),
  KEY idx_reco_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS event (
  event_id      BIGINT       NOT NULL AUTO_INCREMENT,
  visitor_id    CHAR(32)     NOT NULL,
  created_at    DATETIME     NOT NULL,
  kind          VARCHAR(24)  NOT NULL,            -- reco_view / reco_click / pr_click / confirm_yes / confirm_no
  target        VARCHAR(64)  NULL,                -- ProductID など
  upload_id     BIGINT       NULL,
  PRIMARY KEY (event_id),
  KEY idx_event_visitor (visitor_id, created_at),
  KEY idx_event_kind (kind, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
