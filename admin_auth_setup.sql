-- ============================================================
--  Texas Skill Masters CRM — Admin Auth Setup
--  Run this in phpMyAdmin AFTER database_setup.sql
-- ============================================================

USE oph0n93djre1wlxy_texass;

-- ============================================================
--  TABLE: admin_users
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(150),
    email         VARCHAR(150),
    role          ENUM('superadmin','admin') DEFAULT 'admin',
    is_active     TINYINT(1) DEFAULT 1,
    last_login    DATETIME DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  Default admin account
--  Username : admin
--  Password : SkillMaster2024!
--  (Change this immediately after first login)
-- ============================================================
INSERT INTO admin_users (username, password_hash, full_name, email, role)
VALUES (
    'admin',
    '$2y$10$6pF622LFh2bnv6ccFjyWkuI8ifjJKcMihlhF5WdVXGk8EMxvkBRI6',
    'TSM Administrator',
    'admin@texasskillmasters.com',
    'superadmin'
);

-- ============================================================
--  Verify
-- ============================================================
SELECT id, username, full_name, role, is_active FROM admin_users;
