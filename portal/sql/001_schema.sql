-- Troy employee portal schema.
-- Safe to apply more than once: every table uses IF NOT EXISTS.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS branches (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(50) NOT NULL,
  latitude DECIMAL(10,7) NOT NULL DEFAULT 0.0000000,
  longitude DECIMAL(10,7) NOT NULL DEFAULT 0.0000000,
  radius_meters INT UNSIGNED NOT NULL DEFAULT 200,
  accuracy_ceiling_meters INT UNSIGNED NOT NULL DEFAULT 100,
  accuracy_allowance_meters DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_branch_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dtr_employee_profile (
  idno VARCHAR(100) NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  work_arrangement VARCHAR(16) NOT NULL DEFAULT 'onsite',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (idno),
  KEY idx_profile_branch (branch_id),
  CONSTRAINT fk_profile_branch FOREIGN KEY (branch_id)
    REFERENCES branches (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
  id TINYINT UNSIGNED NOT NULL,
  breaks_enabled TINYINT(1) NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  idno VARCHAR(100) NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  work_date DATE NOT NULL,
  time_in DATETIME NOT NULL,
  time_out DATETIME NULL,
  break_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  work_minutes INT UNSIGNED NULL,
  is_late TINYINT(1) NOT NULL DEFAULT 0,
  is_early_timeout TINYINT(1) NOT NULL DEFAULT 0,
  is_overbreak TINYINT(1) NOT NULL DEFAULT 0,
  source VARCHAR(16) NOT NULL DEFAULT 'app',
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_att_emp_date (idno, work_date),
  KEY idx_att_emp_in (idno, time_in),
  KEY idx_att_open (idno, time_out),
  KEY idx_att_timeout (time_out),
  KEY idx_att_branch (branch_id),
  CONSTRAINT fk_att_branch FOREIGN KEY (branch_id)
    REFERENCES branches (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_punches (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attendance_id BIGINT UNSIGNED NOT NULL,
  idno VARCHAR(100) NOT NULL,
  uuid CHAR(36) NOT NULL,
  type VARCHAR(16) NOT NULL,
  punched_at DATETIME NOT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  gps_accuracy_meters DECIMAL(10,2) NULL,
  distance_from_branch_meters DECIMAL(12,2) NULL,
  is_within_radius TINYINT(1) NULL,
  verified_against_type VARCHAR(32) NULL,
  verified_against_id BIGINT UNSIGNED NULL,
  break_kind VARCHAR(20) NULL,
  expected_end_at DATETIME NULL,
  break_minutes INT UNSIGNED NULL,
  is_overbreak TINYINT(1) NOT NULL DEFAULT 0,
  photo_path VARCHAR(255) NULL,
  source VARCHAR(16) NOT NULL DEFAULT 'app',
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_punch_uuid (uuid),
  KEY idx_punch_att (attendance_id, punched_at),
  KEY idx_punch_emp (idno, punched_at),
  CONSTRAINT fk_punch_att FOREIGN KEY (attendance_id)
    REFERENCES attendance (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  idno VARCHAR(100) NOT NULL,
  type VARCHAR(32) NOT NULL,
  granted TINYINT(1) NOT NULL DEFAULT 0,
  granted_at DATETIME NULL,
  revoked_at DATETIME NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_consent_emp_type (idno, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS home_locations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  idno VARCHAR(100) NOT NULL,
  label VARCHAR(255) NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  radius_meters INT UNSIGNED NOT NULL DEFAULT 200,
  address_text VARCHAR(500) NULL,
  street VARCHAR(255) NULL,
  city VARCHAR(255) NULL,
  province VARCHAR(255) NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'pending',
  review_note VARCHAR(500) NULL,
  reviewed_by VARCHAR(100) NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_home_emp_status (idno, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portal_notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  idno VARCHAR(100) NOT NULL,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  data JSON NULL,
  dedupe_key VARCHAR(191) NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_notif_dedupe (dedupe_key),
  KEY idx_notif_emp (idno, read_at, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS push_subscriptions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  idno VARCHAR(100) NOT NULL,
  endpoint_hash CHAR(64) NOT NULL,
  endpoint VARCHAR(2048) NOT NULL,
  public_key VARCHAR(255) NOT NULL,
  auth_token VARCHAR(255) NOT NULL,
  content_encoding VARCHAR(16) NOT NULL DEFAULT 'aes128gcm',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_push_endpoint_hash (endpoint_hash),
  KEY idx_push_employee (idno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fraud_flags (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attendance_id BIGINT UNSIGNED NOT NULL,
  punch_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(32) NOT NULL,
  severity VARCHAR(16) NOT NULL,
  details JSON NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_fraud_punch_type (punch_id, type),
  KEY idx_fraud_attendance (attendance_id),
  KEY idx_fraud_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(191) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_login_lookup (email, ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
