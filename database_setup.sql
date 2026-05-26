-- ============================================================
--  Texas Skill Masters CRM — Database Setup
--  Run this entire script in phpMyAdmin SQL tab
--  Database: oph0n93djre1wlxy_texass
-- ============================================================

USE oph0n93djre1wlxy_texass;

-- ============================================================
--  TABLE: clients
-- ============================================================
CREATE TABLE IF NOT EXISTS clients (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    business_name   VARCHAR(150) NOT NULL,
    address         VARCHAR(255),
    city            VARCHAR(100),
    state           VARCHAR(50)  DEFAULT 'TX',
    zip             VARCHAR(20),
    phone           VARCHAR(30),
    email           VARCHAR(150),
    contact_name    VARCHAR(150),
    contact_title   VARCHAR(100),
    contact_phone   VARCHAR(30),
    contact_email   VARCHAR(150),
    venue_type      ENUM('Bar','Restaurant','Convenience Store','Gaming Lounge','Other') DEFAULT 'Bar',
    status          ENUM('Active','Inactive','Pending') DEFAULT 'Active',
    contract_start  DATE,
    contract_end    DATE,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  TABLE: games
-- ============================================================
CREATE TABLE IF NOT EXISTS games (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    game_name       VARCHAR(150) NOT NULL,
    manufacturer    VARCHAR(100),
    model           VARCHAR(100),
    serial_number   VARCHAR(100),
    game_type       ENUM('Skill Game','Redemption','Arcade','Other') DEFAULT 'Skill Game',
    status          ENUM('Active','Inactive','Maintenance','Retired') DEFAULT 'Active',
    purchase_date   DATE,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  TABLE: client_games  (which games are at which location)
-- ============================================================
CREATE TABLE IF NOT EXISTS client_games (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    game_id         INT NOT NULL,
    machine_number  VARCHAR(50),
    installed_date  DATE,
    removed_date    DATE DEFAULT NULL,
    revenue_split   DECIMAL(5,2) DEFAULT 50.00 COMMENT 'TSM percentage',
    is_active       TINYINT(1) DEFAULT 1,
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id)   REFERENCES games(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  TABLE: revenue_entries
-- ============================================================
CREATE TABLE IF NOT EXISTS revenue_entries (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_game_id  INT NOT NULL,
    entry_date      DATE NOT NULL,
    cash_in         DECIMAL(10,2) DEFAULT 0.00,
    cash_out        DECIMAL(10,2) DEFAULT 0.00,
    net_revenue     DECIMAL(10,2) GENERATED ALWAYS AS (cash_in - cash_out) STORED,
    tsm_share       DECIMAL(10,2) DEFAULT 0.00,
    venue_share     DECIMAL(10,2) DEFAULT 0.00,
    collected_by    VARCHAR(100),
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_game_id) REFERENCES client_games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  TABLE: service_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS service_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    client_game_id  INT,
    service_date    DATE NOT NULL,
    service_type    ENUM('Routine Collection','Repair','Installation','Removal','Inspection','Other') DEFAULT 'Routine Collection',
    technician      VARCHAR(100),
    description     TEXT,
    resolved        TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)      REFERENCES clients(id)      ON DELETE CASCADE,
    FOREIGN KEY (client_game_id) REFERENCES client_games(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  DEMO DATA — Clients
-- ============================================================
INSERT INTO clients (business_name, address, city, state, zip, phone, email, contact_name, contact_title, contact_phone, contact_email, venue_type, status, contract_start, contract_end, notes) VALUES
('Lucky Star Bar & Grill',       '1842 S Congress Ave',     'Austin',      'TX', '78704', '(512) 555-0182', 'info@luckystarbar.com',       'Mike Hernandez',   'Owner',          '(512) 555-0183', 'mike@luckystarbar.com',     'Bar',               'Active',   '2023-01-15', '2025-01-14', 'High traffic location. Prefers collections on Mondays.'),
('Lone Star Lounge',             '3305 E Riverside Dr',     'Austin',      'TX', '78741', '(512) 555-0241', 'manager@lonestarlounge.com',  'Sandra Reyes',     'General Manager','(512) 555-0242', 'sandra@lonestarlounge.com', 'Bar',               'Active',   '2023-03-01', '2025-02-28', 'Busy weekends. Two machines top performers.'),
('El Rancho Restaurant & Bar',   '908 W Commerce St',       'San Antonio', 'TX', '78207', '(210) 555-0375', 'elrancho@gmail.com',          'Carlos Munoz',     'Owner',          '(210) 555-0376', 'carlos@elrancho.com',       'Restaurant',        'Active',   '2023-06-01', '2025-05-31', 'Family-owned. Very cooperative with collections.'),
('Pit Stop Convenience',         '4411 Harry Hines Blvd',   'Dallas',      'TX', '75219', '(214) 555-0488', 'pitstop@pitstoptx.com',       'Tom Nguyen',       'Manager',        '(214) 555-0489', 'tom@pitstoptx.com',         'Convenience Store', 'Active',   '2024-01-10', '2026-01-09', '24/7 location. High foot traffic overnight.'),
('Frontier Tavern',              '2200 N Main St',          'Fort Worth',  'TX', '76106', '(817) 555-0521', 'frontiertavern@email.com',    'Janet Williams',   'Owner',          '(817) 555-0522', 'janet@frontiertavern.com',  'Bar',               'Active',   '2023-09-15', '2025-09-14', 'Weekend volume is excellent.'),
('Champions Sports Bar',         '6712 Westheimer Rd',      'Houston',     'TX', '77057', '(713) 555-0634', 'info@championssports.com',    'David Park',       'Owner',          '(713) 555-0635', 'david@championssports.com', 'Bar',               'Active',   '2024-02-01', '2026-01-31', 'Multiple TV screens. Good ambient for games.'),
('Rio Grande Cantina',           '150 E Commerce St',       'San Antonio', 'TX', '78205', '(210) 555-0747', 'riogrande@cantina.com',       'Maria Lopez',      'Manager',        '(210) 555-0748', 'maria@riogrande.com',       'Restaurant',        'Inactive', '2022-11-01', '2024-10-31', 'Contract ended. Follow up for renewal.'),
('Neon Nights Gaming Lounge',    '1500 Greenville Ave',     'Dallas',      'TX', '75206', '(214) 555-0852', 'neon@neonights.com',          'Alex Carter',      'Owner',          '(214) 555-0853', 'alex@neonnights.com',       'Gaming Lounge',     'Active',   '2024-05-01', '2026-04-30', 'Premium location. Revenue leader in Dallas market.');


-- ============================================================
--  DEMO DATA — Games
-- ============================================================
INSERT INTO games (game_name, manufacturer, model, serial_number, game_type, status, purchase_date) VALUES
('Cash Blitz Pro',       'SkillTech Industries', 'CBP-3000', 'SN-CBP-001', 'Skill Game', 'Active', '2022-08-01'),
('Texas Hold em Skill',  'SkillTech Industries', 'THS-2500', 'SN-THS-002', 'Skill Game', 'Active', '2022-08-01'),
('Lucky Sevens Deluxe',  'AcePlay Systems',      'LSD-500',  'SN-LSD-003', 'Skill Game', 'Active', '2023-01-15'),
('Dragon Reels',         'AcePlay Systems',      'DR-750',   'SN-DR-004',  'Skill Game', 'Active', '2023-01-15'),
('Golden Nugget Skill',  'TexasGaming Co.',      'GNS-100',  'SN-GNS-005', 'Skill Game', 'Active', '2023-06-01'),
('Quick Hit Stars',      'TexasGaming Co.',      'QHS-200',  'SN-QHS-006', 'Skill Game', 'Active', '2023-06-01'),
('Lone Star Spinner',    'SkillTech Industries', 'LSS-1000', 'SN-LSS-007', 'Skill Game', 'Active', '2023-09-01'),
('Wild West Skill',      'AcePlay Systems',      'WWS-350',  'SN-WWS-008', 'Skill Game', 'Active', '2024-01-01'),
('Jackpot Journey',      'TexasGaming Co.',      'JJ-450',   'SN-JJ-009',  'Skill Game', 'Maintenance', '2024-01-01'),
('Star Spinner Elite',   'SkillTech Industries', 'SSE-2000', 'SN-SSE-010', 'Skill Game', 'Active', '2024-03-01');


-- ============================================================
--  DEMO DATA — Client Games (placement records)
-- ============================================================
INSERT INTO client_games (client_id, game_id, machine_number, installed_date, revenue_split, is_active) VALUES
(1, 1, 'LS-M01', '2023-01-15', 50.00, 1),
(1, 3, 'LS-M02', '2023-01-15', 50.00, 1),
(2, 2, 'LL-M01', '2023-03-01', 50.00, 1),
(2, 4, 'LL-M02', '2023-03-01', 50.00, 1),
(3, 5, 'ER-M01', '2023-06-01', 45.00, 1),
(4, 6, 'PS-M01', '2024-01-10', 50.00, 1),
(4, 7, 'PS-M02', '2024-01-10', 50.00, 1),
(5, 8, 'FT-M01', '2023-09-15', 50.00, 1),
(6, 10,'CS-M01', '2024-02-01', 48.00, 1),
(6, 1, 'CS-M02', '2024-02-01', 48.00, 1),
(8, 2, 'NN-M01', '2024-05-01', 55.00, 1),
(8, 3, 'NN-M02', '2024-05-01', 55.00, 1),
(8, 4, 'NN-M03', '2024-05-01', 55.00, 1);


-- ============================================================
--  DEMO DATA — Revenue Entries (last 30 days sample)
--  client_game_id references above inserts
-- ============================================================
INSERT INTO revenue_entries (client_game_id, entry_date, cash_in, cash_out, tsm_share, venue_share, collected_by) VALUES
-- Lucky Star Bar (cg 1 & 2)
(1, '2024-06-01', 850.00, 220.00, 315.00, 315.00, 'James T.'),
(1, '2024-06-08', 920.00, 250.00, 335.00, 335.00, 'James T.'),
(1, '2024-06-15', 780.00, 180.00, 300.00, 300.00, 'James T.'),
(2, '2024-06-01', 640.00, 150.00, 245.00, 245.00, 'James T.'),
(2, '2024-06-08', 710.00, 200.00, 255.00, 255.00, 'James T.'),
(2, '2024-06-15', 690.00, 170.00, 260.00, 260.00, 'James T.'),
-- Lone Star Lounge (cg 3 & 4)
(3, '2024-06-02', 1100.00, 300.00, 400.00, 400.00, 'Maria R.'),
(3, '2024-06-09', 1250.00, 350.00, 450.00, 450.00, 'Maria R.'),
(4, '2024-06-02', 980.00,  280.00, 350.00, 350.00, 'Maria R.'),
(4, '2024-06-09', 870.00,  220.00, 325.00, 325.00, 'Maria R.'),
-- El Rancho (cg 5)
(5, '2024-06-03', 560.00, 120.00, 198.00, 242.00, 'Carlos V.'),
(5, '2024-06-10', 610.00, 140.00, 211.50, 258.50, 'Carlos V.'),
-- Pit Stop (cg 6 & 7)
(6, '2024-06-04', 1400.00, 380.00, 510.00, 510.00, 'James T.'),
(6, '2024-06-11', 1520.00, 410.00, 555.00, 555.00, 'James T.'),
(7, '2024-06-04', 1300.00, 350.00, 475.00, 475.00, 'James T.'),
(7, '2024-06-11', 1410.00, 390.00, 510.00, 510.00, 'James T.'),
-- Frontier Tavern (cg 8)
(8, '2024-06-05', 730.00, 190.00, 270.00, 270.00, 'Maria R.'),
(8, '2024-06-12', 810.00, 210.00, 300.00, 300.00, 'Maria R.'),
-- Champions Sports Bar (cg 9 & 10)
(9,  '2024-06-06', 950.00, 260.00, 331.20, 358.80, 'James T.'),
(9,  '2024-06-13', 1050.00,290.00, 364.80, 395.20, 'James T.'),
(10, '2024-06-06', 880.00, 240.00, 307.20, 332.80, 'James T.'),
(10, '2024-06-13', 920.00, 250.00, 321.60, 348.40, 'James T.'),
-- Neon Nights (cg 11, 12, 13)
(11, '2024-06-07', 1600.00, 450.00, 632.50, 517.50, 'Maria R.'),
(11, '2024-06-14', 1750.00, 480.00, 698.50, 571.50, 'Maria R.'),
(12, '2024-06-07', 1400.00, 390.00, 555.50, 454.50, 'Maria R.'),
(12, '2024-06-14', 1520.00, 410.00, 610.50, 499.50, 'Maria R.'),
(13, '2024-06-07', 1300.00, 360.00, 517.00, 423.00, 'Maria R.'),
(13, '2024-06-14', 1420.00, 395.00, 564.75, 460.25, 'Maria R.');


-- ============================================================
--  DEMO DATA — Service Logs
-- ============================================================
INSERT INTO service_logs (client_id, client_game_id, service_date, service_type, technician, description, resolved) VALUES
(1, 1, '2024-06-15', 'Routine Collection', 'James T.',     'Weekly collection completed. Machine operating normally.',         1),
(2, 3, '2024-06-09', 'Routine Collection', 'Maria R.',     'Collection done. Bill validator cleaned.',                         1),
(4, 6, '2024-06-11', 'Repair',             'James T.',     'Machine froze on startup. Performed hard reset, back online.',     1),
(6, 9, '2024-06-06', 'Routine Collection', 'James T.',     'Biweekly collection. Location very busy.',                         1),
(8, 11,'2024-06-07', 'Installation',       'Maria R.',     'Installed 3rd unit at Neon Nights per contract expansion.',        1),
(3, 5, '2024-06-10', 'Inspection',         'Carlos V.',    'Quarterly inspection completed. All hardware in good condition.',  1),
(5, 8, '2024-06-12', 'Routine Collection', 'Maria R.',     'Collection completed. Venue owner requested new machine.',         1),
(4, 7, '2024-05-30', 'Repair',             'James T.',     'Coin mechanism jammed. Cleared and tested.',                      1);


-- ============================================================
--  Verify setup
-- ============================================================
SELECT 'clients'       AS tbl, COUNT(*) AS row_count FROM clients
UNION ALL
SELECT 'games',             COUNT(*) FROM games
UNION ALL
SELECT 'client_games',      COUNT(*) FROM client_games
UNION ALL
SELECT 'revenue_entries',   COUNT(*) FROM revenue_entries
UNION ALL
SELECT 'service_logs',      COUNT(*) FROM service_logs;
