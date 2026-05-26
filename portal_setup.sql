-- ============================================================
--  Texas Skill Masters CRM — Client Portal Auth
--  Run this in phpMyAdmin AFTER running database_setup.sql
-- ============================================================

USE oph0n93djre1wlxy_texass;

-- ============================================================
--  TABLE: client_users  (portal login accounts)
-- ============================================================
CREATE TABLE IF NOT EXISTS client_users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    username        VARCHAR(100) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    full_name       VARCHAR(150),
    email           VARCHAR(150),
    role            ENUM('owner','manager','viewer') DEFAULT 'owner',
    is_active       TINYINT(1) DEFAULT 1,
    last_login      DATETIME DEFAULT NULL,
    reset_token     VARCHAR(64)  DEFAULT NULL,
    reset_expires   DATETIME     DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE: portal_messages  (client → TSM messaging)
-- ============================================================
CREATE TABLE IF NOT EXISTS portal_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    user_id     INT NOT NULL,
    subject     VARCHAR(200),
    body        TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    replied_at  DATETIME DEFAULT NULL,
    reply_body  TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES client_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  Demo portal accounts (password = "demo1234" for all)
--  bcrypt hash of "demo1234"
-- ============================================================
-- Password for all demo accounts: demo1234
-- Hash generated with bcrypt cost=10, verified with PHP password_verify()
INSERT INTO client_users (client_id, username, password_hash, full_name, email, role) VALUES
(1, 'luckystar',  '$2y$10$4o1K2pPlwnpXARYYx/DO8eStzZfwBKoZC.TTk6gtPftmSbPQpe6Ym', 'Mike Hernandez',  'mike@luckystarbar.com',     'owner'),
(2, 'lonestar',   '$2y$10$4o1K2pPlwnpXARYYx/DO8eStzZfwBKoZC.TTk6gtPftmSbPQpe6Ym', 'Sandra Reyes',    'sandra@lonestarlounge.com', 'owner'),
(3, 'elrancho',   '$2y$10$4o1K2pPlwnpXARYYx/DO8eStzZfwBKoZC.TTk6gtPftmSbPQpe6Ym', 'Carlos Munoz',    'carlos@elrancho.com',       'owner'),
(4, 'pitstop',    '$2y$10$4o1K2pPlwnpXARYYx/DO8eStzZfwBKoZC.TTk6gtPftmSbPQpe6Ym', 'Tom Nguyen',      'tom@pitstoptx.com',         'manager'),
(5, 'frontier',   '$2y$10$4o1K2pPlwnpXARYYx/DO8eStzZfwBKoZC.TTk6gtPftmSbPQpe6Ym', 'Janet Williams',  'janet@frontiertavern.com',  'owner'),
(6, 'champions',  '$2y$10$4o1K2pPlwnpXARYYx/DO8eStzZfwBKoZC.TTk6gtPftmSbPQpe6Ym', 'David Park',      'david@championssports.com', 'owner'),
(8, 'neonnights', '$2y$10$4o1K2pPlwnpXARYYx/DO8eStzZfwBKoZC.TTk6gtPftmSbPQpe6Ym', 'Alex Carter',     'alex@neonnights.com',       'owner');

-- ============================================================
--  Also add the admin panel link to clients table
--  (adds portal_enabled flag so admin can toggle access)
-- ============================================================
ALTER TABLE clients
    ADD COLUMN IF NOT EXISTS portal_enabled TINYINT(1) DEFAULT 1 AFTER notes;

-- ============================================================
--  Verify
-- ============================================================
SELECT cu.username, c.business_name, cu.role, cu.is_active
FROM client_users cu
JOIN clients c ON c.id = cu.client_id
ORDER BY c.business_name;
