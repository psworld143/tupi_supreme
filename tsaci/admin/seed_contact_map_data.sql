-- Contact Page Map Data
-- This file contains the map embed URL and contact address for Tupi Supreme Activated Carbon, Inc.
-- Location: 6.2938775°N, 124.9847734749907°E, Philippines

USE tsaci_cms;

-- Insert or update map embed URL
INSERT INTO page_content (page_name, section_name, content_type, content, is_active)
VALUES ('contact', 'map_embed_url', 'text', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.7703916584064!2d124.9847734749907!3d6.293877493695194!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32f78ee4e9ab4bd5%3A0xa78774472d2fc69d!2sTupi%20Supreme%20Activated%20Carbon%2C%20Inc.!5e0!3m2!1sen!2sph!4v1764687549707!5m2!1sen!2sph', 1)
ON DUPLICATE KEY UPDATE 
    content = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.7703916584064!2d124.9847734749907!3d6.293877493695194!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32f78ee4e9ab4bd5%3A0xa78774472d2fc69d!2sTupi%20Supreme%20Activated%20Carbon%2C%20Inc.!5e0!3m2!1sen!2sph!4v1764687549707!5m2!1sen!2sph',
    is_active = 1,
    updated_at = CURRENT_TIMESTAMP;

-- Update contact address with actual location
UPDATE contact_info 
SET value = 'Tupi Supreme Activated Carbon, Inc.\nCoordinates: 6.2938775°N, 124.9873484°E\nPhilippines'
WHERE type = 'address' AND label = 'Visit Us';

-- Verify updates
SELECT 'Map data seeded successfully!' as status;
SELECT 'Map Embed URL' as item, LENGTH(content) as length FROM page_content WHERE page_name = 'contact' AND section_name = 'map_embed_url'
UNION ALL
SELECT 'Contact Address' as item, LENGTH(value) as length FROM contact_info WHERE type = 'address' AND label = 'Visit Us';

