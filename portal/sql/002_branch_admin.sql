-- Admin branch fields. Safe to apply more than once.
-- MySQL 8 has no ADD COLUMN IF NOT EXISTS, so guard with information_schema.

SET @has_address := (
  SELECT COUNT(*)
    FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'branches'
     AND COLUMN_NAME = 'address'
);
SET @sql := IF(
  @has_address = 0,
  'ALTER TABLE branches ADD COLUMN address VARCHAR(500) NULL AFTER code',
  'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_is_active := (
  SELECT COUNT(*)
    FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'branches'
     AND COLUMN_NAME = 'is_active'
);
SET @sql := IF(
  @has_is_active = 0,
  'ALTER TABLE branches ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER accuracy_allowance_meters',
  'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
