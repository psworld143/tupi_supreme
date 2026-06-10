-- Complete Seed Data for About Page
-- This ensures all About page content is in the database
-- Run this to seed or update About page data

USE tsaci_cms;

-- About Page Content - Complete Seed
INSERT INTO about_content (section_name, title, content, display_order, is_active) VALUES
-- Page Header
('page_header_title', 'About Tupi Supreme', NULL, 1, 1),
('page_header_subtitle', NULL, 'Leading the way in activated carbon solutions for a sustainable future', 2, 1),

-- Company Story Section
('company_story_title', 'Our Story', NULL, 3, 1),
('company_story_content', NULL, 'Founded in 1999, Tupi Supreme Activated Carbon, Inc. began with a simple mission: to provide the highest quality activated carbon solutions while protecting our environment.

What started as a small family business has grown into one of the most trusted names in the activated carbon industry. Our commitment to innovation, quality, and environmental responsibility has remained constant throughout our journey.

Today, we serve clients across multiple industries, from municipal water treatment facilities to pharmaceutical companies, helping them achieve their environmental goals with our premium activated carbon products.', 4, 1),
('company_story_icon', NULL, 'fas fa-industry', 5, 1),

-- Mission Section
('mission_title', 'Our Mission', NULL, 6, 1),
('mission_content', NULL, 'To provide premium activated carbon solutions for municipal water treatment facilities and industrial applications, delivering the highest quality 2mm granulated activated carbon while contributing to a sustainable future for generations to come.', 7, 1),
('mission_icon', NULL, 'fas fa-bullseye', 8, 1),

-- Vision Section
('vision_title', 'Our Vision', NULL, 9, 1),
('vision_content', NULL, 'To be the recognized leader in municipal water treatment activated carbon solutions, trusted by water treatment facilities nationwide for our commitment to quality, environmental stewardship, and technical excellence.', 10, 1),
('vision_icon', NULL, 'fas fa-eye', 11, 1),

-- Timeline Section (Our Journey)
('timeline_title', 'Our Journey', NULL, 12, 1),
('timeline_subtitle', NULL, 'Milestones that shaped our company\'s growth and success', 13, 1),

-- Company Values Section
('values_title', 'Our Core Values', NULL, 14, 1),
('values_subtitle', NULL, 'The principles that guide everything we do', 15, 1),

-- Team Section
('team_title', 'Our Leadership Team', NULL, 16, 1),
('team_subtitle', NULL, 'Meet the experienced professionals driving our success', 17, 1)

ON DUPLICATE KEY UPDATE 
    title = COALESCE(VALUES(title), title),
    content = COALESCE(VALUES(content), content),
    display_order = VALUES(display_order),
    is_active = VALUES(is_active);

-- Verify the data
SELECT 'About page content seeded successfully!' as status;
SELECT section_name, 
       CASE WHEN title IS NOT NULL THEN title ELSE '(no title)' END as title,
       CASE WHEN content IS NOT NULL THEN CONCAT(LEFT(content, 30), '...') ELSE '(no content)' END as content_preview,
       is_active
FROM about_content 
ORDER BY display_order, section_name;

