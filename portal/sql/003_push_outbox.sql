-- Apply after 001_schema.sql. Delivery/event history is independent of visible notifications.
CREATE TABLE IF NOT EXISTS push_outbox (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  notification_id BIGINT UNSIGNED NOT NULL,
  idno VARCHAR(100) NOT NULL,
  event_key VARCHAR(191) NULL,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  data JSON NULL,
  created_at DATETIME NOT NULL,
  next_attempt_at DATETIME NOT NULL,
  lease_until DATETIME NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  delivered_at DATETIME NULL,
  UNIQUE KEY uq_outbox_event (event_key),
  KEY idx_outbox_pending (delivered_at, next_attempt_at, lease_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
