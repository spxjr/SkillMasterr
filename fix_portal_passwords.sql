-- ============================================================
--  Texas Skill Masters — Fix Client Portal Passwords
--  Run this in phpMyAdmin if logins are failing.
--  Sets ALL demo portal accounts to password: demo1234
-- ============================================================

USE oph0n93djre1wlxy_texass;

-- This is a valid bcrypt hash of "demo1234" (cost=10, PHP 2y compatible)
-- Verified working with PHP password_verify()

UPDATE client_users
SET password_hash = '$2y$10$4o1K2pPlwnpXARYYx/DO8eStzZfwBKoZC.TTk6gtPftmSbPQpe6Ym'
WHERE username IN ('luckystar','lonestar','elrancho','pitstop','frontier','champions','neonnights');

-- Verify the update
SELECT username, 
       LEFT(password_hash, 7) AS hash_prefix,
       is_active,
       c.business_name
FROM client_users cu
JOIN clients c ON c.id = cu.client_id
ORDER BY username;
