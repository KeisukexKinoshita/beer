-- ④保管(設計書 2026-09-19 §4)。写真1枚につき感想1件。
-- 匿名のまま保存し、LINE 連携は visitor 側で紐づく(この表は line_user_id を持たない)。
CREATE TABLE IF NOT EXISTS note (
  note_id    BIGINT   NOT NULL AUTO_INCREMENT,
  visitor_id CHAR(32) NOT NULL,
  upload_id  BIGINT   NOT NULL,
  body       TEXT     NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (note_id),
  UNIQUE KEY uq_note_upload (upload_id),
  KEY idx_note_visitor (visitor_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
