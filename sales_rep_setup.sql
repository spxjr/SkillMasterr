-- ============================================================
--  Texas Skill Masters CRM — Sales Rep Setup
--  Run this in phpMyAdmin
-- ============================================================

USE oph0n93djre1wlxy_texass;

-- ============================================================
--  TABLE: sales_reps
-- ============================================================
CREATE TABLE IF NOT EXISTS sales_reps (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(100) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    full_name       VARCHAR(150) NOT NULL,
    first_name      VARCHAR(80),
    last_name       VARCHAR(80),
    email           VARCHAR(150),
    phone           VARCHAR(30),
    territory       VARCHAR(150),
    is_active       TINYINT(1) DEFAULT 1,
    last_login      DATETIME DEFAULT NULL,
    hired_date      DATE DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE: sales_targets  (monthly goals per rep)
-- ============================================================
CREATE TABLE IF NOT EXISTS sales_targets (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    rep_id          INT NOT NULL,
    target_month    DATE NOT NULL COMMENT 'First day of the month',
    leads_target    INT DEFAULT 10,
    contacts_target INT DEFAULT 20,
    closes_target   INT DEFAULT 3,
    revenue_target  DECIMAL(10,2) DEFAULT 0.00,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_rep_month (rep_id, target_month),
    FOREIGN KEY (rep_id) REFERENCES sales_reps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE: sales_messages  (rep ↔ admin messaging)
-- ============================================================
CREATE TABLE IF NOT EXISTS sales_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    rep_id      INT NOT NULL,
    direction   ENUM('rep_to_admin','admin_to_rep') DEFAULT 'rep_to_admin',
    subject     VARCHAR(200),
    body        TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rep_id) REFERENCES sales_reps(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  Add rep_id to prospects so reps own their leads
-- ============================================================
ALTER TABLE prospects
    ADD COLUMN IF NOT EXISTS rep_id INT DEFAULT NULL AFTER assigned_to,
    ADD FOREIGN KEY IF NOT EXISTS fk_prospect_rep (rep_id) REFERENCES sales_reps(id) ON DELETE SET NULL;

-- ============================================================
--  Sales Reps — Danny A & Steven P
--  Default password: TsmSales2024!
-- ============================================================
INSERT INTO sales_reps (username, password_hash, full_name, first_name, last_name, email, phone, territory, hired_date) VALUES
(
    'danny',
    '$2y$10$K43/FUPimAIs4t7M2H6KgOxbGzrZWxjAvuLWHMj8v.Ueuc2.0Jo7O',
    'Danny A',
    'Danny',
    'A',
    'danny@texasskillmasters.com',
    '(512) 555-3001',
    'Austin Central / Travis County',
    CURDATE()
),
(
    'steven',
    '$2y$10$K43/FUPimAIs4t7M2H6KgOxbGzrZWxjAvuLWHMj8v.Ueuc2.0Jo7O',
    'Steven P',
    'Steven',
    'P',
    'steven@texasskillmasters.com',
    '(512) 555-3002',
    'Austin North / Williamson County',
    CURDATE()
);

-- ============================================================
--  Assign existing prospects to reps (split between Danny & Steven)
-- ============================================================
UPDATE prospects SET rep_id = (SELECT id FROM sales_reps WHERE username='danny'), assigned_to='Danny A'
WHERE id IN (1,3,4,7,9,11,13);

UPDATE prospects SET rep_id = (SELECT id FROM sales_reps WHERE username='steven'), assigned_to='Steven P'
WHERE id IN (2,5,6,8,10,12,14,15);

-- ============================================================
--  Monthly targets for current month
-- ============================================================
INSERT INTO sales_targets (rep_id, target_month, leads_target, contacts_target, closes_target, revenue_target)
SELECT id, DATE_FORMAT(CURDATE(), '%Y-%m-01'), 10, 25, 3, 5000.00
FROM sales_reps;

-- ============================================================
--  Welcome messages from admin
-- ============================================================
INSERT INTO sales_messages (rep_id, direction, subject, body) VALUES
((SELECT id FROM sales_reps WHERE username='danny'), 'admin_to_rep',
 'Welcome to the TSM Sales Portal!',
 'Hey Danny! Welcome to the Texas Skill Masters sales portal. Use this dashboard to manage your leads, log activity, and track your progress. Your territory is Austin Central / Travis County. Default password is TsmSales2024! — please change it. Good luck out there!'),
((SELECT id FROM sales_reps WHERE username='steven'), 'admin_to_rep',
 'Welcome to the TSM Sales Portal!',
 'Hey Steven! Welcome to the Texas Skill Masters sales portal. Use this dashboard to manage your leads, log activity, and track your progress. Your territory is Austin North / Williamson County. Default password is TsmSales2024! — please change it. Lets close some deals!');

-- ============================================================
--  Verify
-- ============================================================
SELECT sr.full_name, sr.territory,
       COUNT(p.id) AS assigned_prospects
FROM sales_reps sr
LEFT JOIN prospects p ON p.rep_id = sr.id
GROUP BY sr.id;
