-- Initial portal settings. Safe to run more than once.
INSERT INTO app_settings (id, breaks_enabled, updated_at)
VALUES (1, 1, NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
