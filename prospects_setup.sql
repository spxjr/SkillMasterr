-- ============================================================
--  Texas Skill Masters CRM — Prospects / Leads Table
--  Run this in phpMyAdmin
-- ============================================================

USE oph0n93djre1wlxy_texass;

-- ============================================================
--  TABLE: prospects
-- ============================================================
CREATE TABLE IF NOT EXISTS prospects (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    store_name      VARCHAR(150) NOT NULL,
    store_type      ENUM(
                        'Bar',
                        'Restaurant',
                        'Convenience Store',
                        'Gas Station',
                        'Club / Nightclub',
                        'Supermarket',
                        'Smoke Shop',
                        'Other'
                    ) NOT NULL DEFAULT 'Bar',
    address         VARCHAR(255),
    city            VARCHAR(100),
    state           VARCHAR(50)  DEFAULT 'TX',
    zip             VARCHAR(20),
    county          VARCHAR(100),
    contact_name    VARCHAR(150),
    contact_title   VARCHAR(100),
    contact_phone   VARCHAR(30),
    contact_email   VARCHAR(150),
    status          ENUM(
                        'New Lead',
                        'Contacted',
                        'Interested',
                        'Proposal Sent',
                        'Negotiating',
                        'Converted',
                        'Not Interested',
                        'No Response'
                    ) NOT NULL DEFAULT 'New Lead',
    priority        ENUM('Low','Medium','High') DEFAULT 'Medium',
    source          ENUM(
                        'Cold Call',
                        'Drive By',
                        'Referral',
                        'Social Media',
                        'Website',
                        'Walk In',
                        'Other'
                    ) DEFAULT 'Cold Call',
    assigned_to     VARCHAR(100),
    machines_wanted INT DEFAULT NULL,
    notes           TEXT,
    last_contact    DATE DEFAULT NULL,
    follow_up_date  DATE DEFAULT NULL,
    converted_at    DATETIME DEFAULT NULL,
    client_id       INT DEFAULT NULL COMMENT 'Set when converted to client',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  TABLE: prospect_notes  (activity / call log)
-- ============================================================
CREATE TABLE IF NOT EXISTS prospect_notes (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    prospect_id  INT NOT NULL,
    note_type    ENUM('Call','Email','Visit','Follow Up','Other') DEFAULT 'Call',
    note_text    TEXT NOT NULL,
    created_by   VARCHAR(100),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prospect_id) REFERENCES prospects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  DEMO DATA — Prospects
-- ============================================================
INSERT INTO prospects (store_name, store_type, address, city, state, zip, county, contact_name, contact_title, contact_phone, contact_email, status, priority, source, assigned_to, machines_wanted, notes, last_contact, follow_up_date) VALUES

('Cold Beer & Whiskey',      'Bar',                '4802 Burnet Rd',          'Austin',        'TX', '78756', 'Travis',    'Dave Kowalski',    'Owner',          '(512) 555-1101', 'dave@coldbeertx.com',     'Interested',       'High',   'Drive By',    'James T.', 2, 'Very interested. Has space for 2 machines. Owner wants 55/45 split discussion.',    '2024-06-10', '2024-06-20'),
('Q&Q Food Mart',            'Convenience Store',  '9200 N Lamar Blvd',       'Austin',        'TX', '78753', 'Travis',    'Quan Nguyen',      'Owner',          '(512) 555-1202', 'quan@qqfoodmart.com',     'Contacted',        'High',   'Cold Call',   'Maria R.', 3, 'Existing games from competitor. Contract up in August. Good opportunity.',          '2024-06-08', '2024-06-25'),
('The Rusty Spur Saloon',    'Bar',                '1100 S 1st St',           'Austin',        'TX', '78704', 'Travis',    'Becky Simmons',    'Manager',        '(512) 555-1303', 'becky@rustyspur.com',     'Proposal Sent',    'High',   'Referral',    'James T.', 2, 'Referred by Lucky Star Bar. Waiting on owner approval. Proposal sent 6/5.',        '2024-06-05', '2024-06-18'),
('Galaxy Lounge & Bar',      'Club / Nightclub',   '3100 Red River St',       'Austin',        'TX', '78705', 'Travis',    'Marcus Webb',      'Owner',          '(512) 555-1404', 'marcus@galaxylounge.com', 'New Lead',         'Medium', 'Drive By',    'James T.', 1, 'High foot traffic Thursday-Saturday. Need to schedule visit.',                      NULL,          '2024-06-22'),
('Star Pumps #7',            'Gas Station',        '6601 Airport Blvd',       'Austin',        'TX', '78752', 'Travis',    'Priya Patel',      'Manager',        '(512) 555-1505', NULL,                      'Contacted',        'Medium', 'Cold Call',   'Maria R.', 1, 'Part of a chain. Need to talk to regional manager for approval.',                   '2024-06-07', '2024-06-21'),
('Rodriguez Supermarket',    'Supermarket',        '2801 E Cesar Chavez St',  'Austin',        'TX', '78702', 'Travis',    'Elena Rodriguez',  'Owner',          '(512) 555-1606', 'elena@rodriguezmarket.com','New Lead',         'Low',    'Drive By',    NULL,       2, 'Large store. Good foot traffic. Needs follow up.',                                  NULL,          '2024-06-28'),
('Diamond Smoke & Vape',     'Smoke Shop',         '5200 Manchaca Rd',        'Austin',        'TX', '78745', 'Travis',    'Tony Chen',        'Owner',          '(512) 555-1707', 'tony@diamondsmoke.com',   'Interested',       'High',   'Walk In',     'James T.', 2, 'Tony walked into office inquiring. Very warm lead. Wants 2 machines ASAP.',        '2024-06-12', '2024-06-17'),
('El Matador Cantina',       'Restaurant',         '4400 N Interstate 35',    'Round Rock',    'TX', '78664', 'Williamson','Jorge Salinas',     'Owner',          '(512) 555-1808', 'jorge@elmatador.com',     'Negotiating',      'High',   'Referral',    'Maria R.', 3, 'Negotiating revenue split. Owner wants 55%. Discussing 52/48 compromise.',         '2024-06-11', '2024-06-16'),
('Lucky Dragon Market',      'Convenience Store',  '1301 W Ben White Blvd',   'Austin',        'TX', '78704', 'Travis',    'Min Park',         'Owner',          '(512) 555-1909', NULL,                      'No Response',      'Low',    'Cold Call',   'James T.', 1, 'Called 3 times. No response. Try different approach.',                              '2024-05-28', '2024-06-30'),
('Cowboys Dance Hall',       'Club / Nightclub',   '10901 N Lamar Blvd',      'Austin',        'TX', '78753', 'Travis',    'Billy Ray Cobb',   'GM',             '(512) 555-2001', 'billy@cowboysdancehall.com','Interested',      'High',   'Social Media', 'Maria R.',4, 'Found us on Facebook. Huge venue. Could support 4 machines. Priority close.',      '2024-06-13', '2024-06-19'),
('Smoke City Tobacco',       'Smoke Shop',         '7800 Shoal Creek Blvd',   'Austin',        'TX', '78757', 'Travis',    'Ray Johnson',      'Owner',          '(512) 555-2102', 'ray@smokecity.com',       'Contacted',        'Medium', 'Drive By',    'James T.', 1, 'Owner was busy, left brochure. Follow up needed.',                                  '2024-06-06', '2024-06-20'),
('La Fiesta Supermarket',    'Supermarket',        '2300 E Riverside Dr',     'Austin',        'TX', '78741', 'Travis',    'Carmen Vela',      'Store Director', '(512) 555-2203', 'carmen@lafiesta.com',     'Proposal Sent',    'Medium', 'Cold Call',   'Maria R.', 2, 'Corporate approval required. GM receptive. Proposal submitted to regional VP.',    '2024-06-03', '2024-06-24'),
('Outlaw Bar & Grill',       'Bar',                '500 Main St',             'Pflugerville',  'TX', '78660', 'Travis',    'Wade Houston',     'Owner',          '(512) 555-2304', 'wade@outlawbar.com',      'Not Interested',   'Low',    'Cold Call',   'James T.', 0, 'Not interested at this time. Already has contract with another vendor.',           '2024-05-20', NULL),
('Valero Express #14',       'Gas Station',        '1800 E Palm Valley Blvd', 'Round Rock',    'TX', '78664', 'Williamson','Sam Torres',        'Manager',        '(737) 555-2405', NULL,                      'New Lead',         'Medium', 'Drive By',    NULL,       1, 'High traffic corner location. Need to cold call.',                                  NULL,          '2024-06-25'),
('The Pour House',           'Bar',                '115 E San Antonio St',    'San Marcos',    'TX', '78666', 'Hays',      'Josh Mercer',      'Owner',          '(512) 555-2506', 'josh@pourhouse.com',      'Converted',        'High',   'Referral',    'Maria R.', 2, 'Converted to client! Contract signed 6/1/2024.',                                   '2024-06-01', NULL);

-- ============================================================
--  DEMO — Activity Notes
-- ============================================================
INSERT INTO prospect_notes (prospect_id, note_type, note_text, created_by) VALUES
(1, 'Visit',      'Visited the location. Great spot, 2 ideal locations for machines near pool tables. Owner Dave very enthusiastic.', 'James T.'),
(1, 'Call',       'Follow-up call. Dave confirmed he wants to move forward. Sending proposal next week.', 'James T.'),
(2, 'Call',       'Spoke with Quan. Current contract expires August 15. He is open to switching if we match terms.', 'Maria R.'),
(3, 'Email',      'Sent proposal via email. Included 50/50 split terms and installation timeline.', 'James T.'),
(5, 'Call',       'Left voicemail for Priya. She said regional manager handles these decisions.', 'Maria R.'),
(7, 'Visit',      'Tony came to our office. Very motivated. Showed him machine demos. Ready to sign.', 'James T.'),
(8, 'Call',       'Jorge countered at 55/45. We offered 52/48. He is thinking it over.', 'Maria R.'),
(8, 'Email',      'Sent revised proposal with 52/48 split and 6-month performance guarantee.', 'Maria R.'),
(10,'Call',       'Spoke with Billy Ray. Huge venue with great potential. He wants a site visit this week.', 'Maria R.'),
(10,'Visit',      'Site visit completed. Venue has 4 perfect locations. Billy Ray very excited. Closing soon.', 'Maria R.'),
(12,'Email',      'Submitted formal proposal to Carmen Vela for La Fiesta corporate review.', 'Maria R.');

-- ============================================================
--  Verify
-- ============================================================
SELECT status, COUNT(*) AS count_val FROM prospects GROUP BY status ORDER BY count_val DESC;
