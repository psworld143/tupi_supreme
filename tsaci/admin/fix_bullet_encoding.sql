-- One-time repair: seeds run through the Windows mysql client without
-- --default-character-set=utf8mb4 stored the bullet character as the
-- mojibake sequence "ÔÇó". This restores the real "•" everywhere it appears.
-- Run with:  mysql --default-character-set=utf8mb4 -u root tsaci_cms < fix_bullet_encoding.sql
-- Safe to re-run (idempotent — REPLACE is a no-op once fixed).

USE tsaci_cms;

UPDATE products SET
    description    = REPLACE(description,    'ÔÇó', '•'),
    specifications = REPLACE(specifications, 'ÔÇó', '•'),
    features       = REPLACE(features,       'ÔÇó', '•'),
    applications   = REPLACE(applications,   'ÔÇó', '•')
WHERE description LIKE '%ÔÇó%' COLLATE utf8mb4_bin
   OR specifications LIKE '%ÔÇó%' COLLATE utf8mb4_bin
   OR features LIKE '%ÔÇó%' COLLATE utf8mb4_bin
   OR applications LIKE '%ÔÇó%' COLLATE utf8mb4_bin;

UPDATE certifications SET
    description = REPLACE(description, 'ÔÇó', '•'),
    features    = REPLACE(features,    'ÔÇó', '•')
WHERE description LIKE '%ÔÇó%' COLLATE utf8mb4_bin
   OR features LIKE '%ÔÇó%' COLLATE utf8mb4_bin;

UPDATE applications SET
    description = REPLACE(description, 'ÔÇó', '•')
WHERE description LIKE '%ÔÇó%' COLLATE utf8mb4_bin;

UPDATE services SET
    description = REPLACE(description, 'ÔÇó', '•'),
    features    = REPLACE(features,    'ÔÇó', '•'),
    benefits    = REPLACE(benefits,    'ÔÇó', '•')
WHERE description LIKE '%ÔÇó%' COLLATE utf8mb4_bin
   OR features LIKE '%ÔÇó%' COLLATE utf8mb4_bin
   OR benefits LIKE '%ÔÇó%' COLLATE utf8mb4_bin;

UPDATE page_content SET
    content = REPLACE(content, 'ÔÇó', '•')
WHERE content LIKE '%ÔÇó%' COLLATE utf8mb4_bin;

UPDATE site_settings SET
    setting_value = REPLACE(setting_value, 'ÔÇó', '•')
WHERE setting_value LIKE '%ÔÇó%' COLLATE utf8mb4_bin;
