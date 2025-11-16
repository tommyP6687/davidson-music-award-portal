-- Music Awards Portal schema (MySQL 8)
-- Creates core tables if they don't exist, with keys and defaults.

-- =========================
-- Table: admin_sheet
-- - One row per uploaded roster CSV (keep latest as "current")
-- =========================
CREATE TABLE IF NOT EXISTS `admin_sheet` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `csv_path`      VARCHAR(500) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `uploaded_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =========================
-- Table: submissions
-- - Student responses + signed PDF path
-- - Name/email indexes for fast matching
-- =========================
CREATE TABLE IF NOT EXISTS `submissions` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `first_name`      VARCHAR(100) NOT NULL,
  `last_name`       VARCHAR(100) NOT NULL,
  `email`           VARCHAR(255) DEFAULT NULL,
  `status`          ENUM('accepted','declined','none') NOT NULL DEFAULT 'none',
  `submitted_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `signed_pdf_path` VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`last_name`,`first_name`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =========================
-- Table: users
-- - Admin accounts (plain password by design here)
-- - Keep password_hash nullable for future migration if desired
-- =========================
CREATE TABLE IF NOT EXISTS `users` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `email`          VARCHAR(255) NOT NULL,
  `password_plain` VARCHAR(255) NOT NULL,
  `password_hash`  VARCHAR(255) DEFAULT NULL,
  `role`           ENUM('admin','student') NOT NULL DEFAULT 'admin',
  `created_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
