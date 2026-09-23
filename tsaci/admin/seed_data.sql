-- Seed Data for TSACI Website
-- This file contains all the static content extracted from the pages
-- Run this after database_schema_update.sql

USE tsaci_cms;

-- Site Settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('company_name', 'Tupi Supreme Activated Carbon, Inc.', 'text', 'Company full name'),
('company_short_name', 'Tupi Supreme', 'text', 'Company short name'),
('company_tagline', 'Leading provider of premium activated carbon solutions for environmental protection and industrial applications.', 'text', 'Company tagline'),
('contact_address', '123 Industrial Ave, Tupi City', 'text', 'Company address'),
('contact_phone', '+1 (555) 123-4567', 'text', 'Contact phone number'),
('contact_email', 'info@tupisupreme.com', 'text', 'Contact email'),
('copyright_year', '2024', 'text', 'Copyright year'),
('meta_description', 'Premium activated carbon solutions for municipal water treatment facilities. 2mm granulated activated carbon with proven track record.', 'text', 'Default meta description'),
('meta_keywords', 'municipal water treatment activated carbon, granulated activated carbon, 2mm activated carbon, water treatment carbon', 'text', 'Default meta keywords')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

-- Homepage Features
INSERT INTO homepage_features (title, description, icon, display_order, is_active) VALUES
('Premium Quality', 'Our 2mm granulated activated carbon products meet the highest industry standards with consistent quality and specifications.', 'fas fa-award', 1, 1),
('Municipal Water Treatment Expertise', 'Proven track record serving municipal water treatment facilities with reliable, high-performance activated carbon solutions.', 'fas fa-building', 2, 1),
('Sustainable Zero-Waste Operations', 'Eco-friendly production processes with zero-waste operations, maximizing resource utilization and environmental responsibility.', 'fas fa-leaf', 3, 1),
('Technical Support & Consultation', 'Expert technical consultation, 24/7 support, and customized solutions for your municipal and industrial needs.', 'fas fa-headset', 4, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), icon=VALUES(icon);

-- Statistics
INSERT INTO statistics (label, value, description, icon, display_order, is_active) VALUES
('Years Experience', '25+', 'Years of industry experience', 'fas fa-calendar', 1, 1),
('Happy Clients', '500+', 'Including 200+ Municipal Facilities', 'fas fa-users', 2, 1),
('Product Varieties', '50+', 'Different product varieties', 'fas fa-cubes', 3, 1),
('Support Available', '24/7', '24/7 customer support', 'fas fa-clock', 4, 1)
ON DUPLICATE KEY UPDATE label=VALUES(label), value=VALUES(value), description=VALUES(description);

-- Homepage Hero Content (via page_content)
INSERT INTO page_content (page_name, section_name, content_type, content, display_order, is_active) VALUES
('index', 'hero_title', 'text', 'Premium Activated Carbon Solutions for Municipal Water Treatment', 1, 1),
('index', 'hero_description', 'text', 'Leading provider of high-quality activated carbon products for municipal water treatment facilities, industrial applications, and environmental solutions. Your trusted partner for reliable water purification and sustainable environmental solutions.', 2, 1),
('index', 'hero_cta_primary_text', 'text', 'Request Technical Consultation', 3, 1),
('index', 'hero_cta_primary_link', 'text', 'contact.php', 4, 1),
('index', 'hero_cta_secondary_text', 'text', 'Download Product Specs', 5, 1),
('index', 'hero_cta_secondary_link', 'text', 'resources.php', 6, 1),
('index', 'features_title', 'text', 'Why Choose Tupi Supreme?', 7, 1),
('index', 'features_subtitle', 'text', 'We deliver excellence in every product and service we provide', 8, 1),
('index', 'products_title', 'text', 'Our Premium Products', 9, 1),
('index', 'products_subtitle', 'text', 'Discover our range of high-quality activated carbon solutions', 10, 1),
('index', 'cta_title', 'text', 'Ready to Get Started?', 11, 1),
('index', 'cta_description', 'text', 'Contact us today for a free technical consultation and quote on your activated carbon needs. Serving municipal water treatment facilities nationwide.', 12, 1)
ON DUPLICATE KEY UPDATE content=VALUES(content);

-- About Page Content
INSERT INTO about_content (section_name, title, content, display_order, is_active) VALUES
('page_header_title', 'About Tupi Supreme', NULL, 1, 1),
('page_header_subtitle', NULL, 'Leading the way in activated carbon solutions for a sustainable future', 2, 1),
('company_story_title', 'Our Story', NULL, 3, 1),
('company_story_content', NULL, 'Founded in 1999, Tupi Supreme Activated Carbon, Inc. began with a simple mission: to provide the highest quality activated carbon solutions while protecting our environment.\n\nWhat started as a small family business has grown into one of the most trusted names in the activated carbon industry. Our commitment to innovation, quality, and environmental responsibility has remained constant throughout our journey.\n\nToday, we serve clients across multiple industries, from municipal water treatment facilities to pharmaceutical companies, helping them achieve their environmental goals with our premium activated carbon products.', 4, 1),
('mission_title', 'Our Mission', NULL, 5, 1),
('mission_content', NULL, 'To provide premium activated carbon solutions for municipal water treatment facilities and industrial applications, delivering the highest quality 2mm granulated activated carbon while contributing to a sustainable future for generations to come.', 6, 1),
('vision_title', 'Our Vision', NULL, 7, 1),
('vision_content', NULL, 'To be the recognized leader in municipal water treatment activated carbon solutions, trusted by water treatment facilities nationwide for our commitment to quality, environmental stewardship, and technical excellence.', 8, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content);

-- Company Values
INSERT INTO company_values (title, description, icon, display_order, is_active) VALUES
('Sustainability', 'We are committed to environmental responsibility and sustainable business practices in all our operations.', 'fas fa-leaf', 1, 1),
('Quality', 'We maintain strict quality standards with our 2mm granulated activated carbon specification, ensuring consistent excellence for municipal water treatment facilities.', 'fas fa-award', 2, 1),
('Innovation', 'We continuously invest in research and development to create cutting-edge solutions for our clients.', 'fas fa-lightbulb', 3, 1),
('Integrity', 'We conduct business with honesty, transparency, and ethical practices in all our relationships.', 'fas fa-handshake', 4, 1),
('Customer Focus', 'We specialize in serving municipal water treatment facilities with dedicated expertise, technical support, and solutions tailored to their unique requirements.', 'fas fa-users', 5, 1),
('Global Impact', 'We work to make a positive impact on the environment and communities worldwide.', 'fas fa-globe', 6, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), icon=VALUES(icon);

-- Timeline Events
INSERT INTO timeline_events (year, title, description, display_order, is_active) VALUES
(1999, 'Company Founded', 'Started as a small family business with a focus on local water treatment solutions.', 1, 1),
(2005, 'First Major Contract', 'Secured our first municipal water treatment contract, marking our entry into large-scale operations.', 2, 1),
(2010, 'ISO Certification', 'Achieved ISO 9001:2008 certification, demonstrating our commitment to quality management.', 3, 1),
(2015, 'International Expansion', 'Expanded operations to serve international markets, establishing partnerships worldwide.', 4, 1),
(2020, 'Innovation Center', 'Opened our state-of-the-art research and development center for product innovation.', 5, 1),
(2024, 'Industry Leader', 'Recognized as one of the top activated carbon manufacturers with 500+ satisfied clients.', 6, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description);

-- Products (from existing products page content)
-- icon/tagline require the products columns added by database_schema_update.sql.
INSERT INTO products (name, slug, description, specifications, features, applications, image_url, icon, tagline, display_order, is_featured, is_active) VALUES
('Granulated Activated Carbon (GAC)', 'granulated-activated-carbon', 'Premium 2mm granulated activated carbon optimized for municipal water treatment facilities. Strict quality specifications ensure consistent performance.', '2mm below (granulated) • High surface area • Low ash content • Consistent particle size distribution', 'Premium quality • Strict specifications • Consistent performance • GAC certified', 'Municipal water treatment • Industrial water purification • Wastewater treatment • Air purification', NULL, 'fas fa-cubes', NULL, 1, 1, 1),
('Municipal Water Treatment Solutions', 'municipal-water-treatment', 'Specialized activated carbon solutions designed specifically for municipal water treatment facilities. Proven track record with 200+ municipal clients.', 'Custom formulations • System integration • Technical support • Compliance guaranteed', 'Municipal expertise • Proven results • Technical consultation • 24/7 support', 'Municipal water treatment • Public water systems • Water quality compliance', NULL, 'fas fa-water', NULL, 2, 1, 1),
('Coconut Husk Chips', 'coconut-husk-chips', 'High-quality coconut husk chips processed from our zero-waste operations. An excellent sustainable alternative to traditional growing mediums, providing superior water retention and aeration for horticultural applications.', 'Natural organic material • Excellent drainage • High water retention • pH balanced', 'Excellent water retention capacity • Superior aeration properties • Sustainable and eco-friendly • Natural fiber composition • pH balanced and disease-free • Consistent particle size', 'Horticulture and greenhouse operations • Commercial agriculture • Professional gardening • Container growing', NULL, 'fas fa-seedling', 'Premium Growing Medium', 3, 0, 1),
('Coconut Pit', 'coconut-pit', 'Premium-grade coconut pit processed specifically for horticultural applications. This specialized growing medium offers exceptional performance for professional growers, greenhouse operations, and commercial agriculture.', 'Fine texture • High water retention • Excellent aeration • Nutrient rich', 'Premium quality for professional use • Optimized particle size distribution • Superior root development support • Enhanced nutrient retention • Long-lasting performance • Zero-waste sustainable source', 'Professional horticulture • Greenhouse cultivation • Commercial growing operations • Specialized crop production', NULL, 'fas fa-leaf', 'Premium Horticultural Medium', 4, 0, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description);

-- Product tabs (system tabs for the public products page)
INSERT INTO product_tabs (tab_key, label, icon, keywords, display_order, is_active, is_system) VALUES
('granulated', 'Granulated Activated Carbon', 'fa-cubes', 'granulated', 1, 1, 1),
('husk', 'Coconut Husk Products', 'fa-seedling', 'husk,coconut', 2, 1, 1),
('custom', 'Custom Formulations', 'fa-cogs', 'custom', 3, 1, 1)
ON DUPLICATE KEY UPDATE label=VALUES(label);

-- Assign the husk products to the "Coconut Husk Products" tab
UPDATE products p JOIN product_tabs t ON t.tab_key = 'husk'
SET p.category_id = t.id
WHERE p.slug IN ('coconut-husk-chips', 'coconut-pit');

-- Custom Formulations cards as products on the custom tab
INSERT INTO products (name, slug, description, features, icon, category_id, display_order, is_active)
SELECT v.name, v.slug, v.description, v.features, v.icon, t.id, v.display_order, 1 FROM product_tabs t
JOIN (
    SELECT 'Custom Particle Sizes' AS name, 'custom-particle-sizes' AS slug,
        'Tailored activated carbon formulations with custom particle sizes designed for your specific application requirements beyond standard 2mm granulated specification.' AS description,
        'Custom particle size distributions • Specialized surface chemistry • Application-specific testing • Performance optimization' AS features,
        'fas fa-cogs' AS icon, 1 AS display_order
    UNION ALL
    SELECT 'System Integration', 'system-integration',
        'Complete activated carbon system design and integration for complex applications.',
        'System design • Installation support • Performance monitoring',
        'fas fa-tools', 2
    UNION ALL
    SELECT 'R&D Support', 'rd-support',
        'Research and development support for new activated carbon applications and technologies.',
        'Laboratory testing • Performance analysis • Technical consultation',
        'fas fa-microscope', 3
) v ON t.tab_key = 'custom'
WHERE NOT EXISTS (SELECT 1 FROM products p WHERE p.slug = v.slug);

-- Service Items — "Our Service Process" steps + "Why Choose Our Services" rows
-- (requires the service_items table from database_schema_update.sql)
INSERT INTO service_items (section, title, description, icon, display_order, is_active) VALUES
('process', 'Assessment', 'We begin by thoroughly assessing your specific needs, requirements, and application challenges.', 'fas fa-search', 1, 1),
('process', 'Solution Design', 'Our experts design customized solutions tailored to your specific application and requirements.', 'fas fa-lightbulb', 2, 1),
('process', 'Implementation', 'We implement the solution with precision, ensuring optimal performance and reliability.', 'fas fa-cogs', 3, 1),
('process', 'Monitoring', 'Continuous monitoring and support to ensure long-term success and optimal performance.', 'fas fa-chart-line', 4, 1),
('features', 'Expert Team', 'Our team consists of certified professionals with decades of experience in activated carbon applications.', 'fas fa-award', 1, 1),
('features', '24/7 Support', 'Round-the-clock technical support and emergency services to ensure your operations never stop.', 'fas fa-clock', 2, 1),
('features', 'Quality Assurance', 'Rigorous quality control processes ensure consistent, reliable service delivery every time.', 'fas fa-shield-alt', 3, 1),
('features', 'Sustainable Solutions', 'Environmentally responsible service practices that align with your sustainability goals.', 'fas fa-leaf', 4, 1),
('features', 'Long-term Partnership', 'We build lasting relationships with our clients, providing ongoing support and value.', 'fas fa-handshake', 5, 1),
('features', 'Performance Optimization', 'Continuous improvement services to maximize efficiency and reduce operational costs.', 'fas fa-chart-bar', 6, 1)
ON DUPLICATE KEY UPDATE description=VALUES(description), icon=VALUES(icon), display_order=VALUES(display_order);

-- Services (from services page)
INSERT INTO services (title, slug, description, features, benefits, icon, image_url, display_order, is_active) VALUES
('Technical Consultation', 'technical-consultation', 'Expert technical support and consultation for all activated carbon applications and system design.', 'Application analysis\nSystem design\nProduct selection\nPerformance optimization', 'Expert guidance\nCustomized solutions\nCost optimization\nImproved efficiency', 'fas fa-headset', NULL, 1, 1),
('Custom Formulations', 'custom-formulations', 'Tailored activated carbon formulations designed to meet your specific application requirements.', 'Custom specifications\nQuality testing\nBatch consistency\nTechnical documentation', 'Perfect fit for your needs\nOptimized performance\nQuality assurance\nDocumentation provided', 'fas fa-cogs', NULL, 2, 1),
('System Integration', 'system-integration', 'Complete system design and integration services for activated carbon filtration systems.', 'System design\nInstallation support\nCommissioning\nTraining', 'Seamless integration\nProfessional installation\nSystem optimization\nStaff training', 'fas fa-tools', NULL, 3, 1),
('Quality Assurance', 'quality-assurance', 'Comprehensive quality testing and certification services for all activated carbon products.', 'Batch testing\nQuality certification\nCompliance verification\nDocumentation', 'Quality guaranteed\nCompliance assured\nCertified products\nFull documentation', 'fas fa-certificate', NULL, 4, 1),
('24/7 Support', 'support', 'Round-the-clock technical support and customer service for all your activated carbon needs.', '24/7 availability\nTechnical assistance\nEmergency support\nRapid response', 'Always available\nExpert assistance\nPeace of mind\nQuick resolution', 'fas fa-clock', NULL, 5, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description);

-- Case Studies
INSERT INTO case_studies (title, slug, client_name, location, industry, challenge, solution, results, image_url, display_order, is_featured, is_active) VALUES
('Metropolitan City Water Plant - 30% Efficiency Improvement', 'metropolitan-city-water-plant', 'Metropolitan City Water Plant', 'Major Metropolitan Area', 'Municipal Water Treatment', 'The facility was experiencing inconsistent water quality, high operational costs, and frequent filter replacements. They needed a reliable activated carbon solution that could handle high flow rates while maintaining consistent performance.', 'We provided our premium 2mm granulated activated carbon (GAC) with strict quality specifications. Our technical team conducted a comprehensive analysis and designed a custom filtration system optimized for their specific water chemistry and flow requirements.', '30% improvement in filtration efficiency\n$2.5 million in annual operational cost savings\nExtended filter life by 40%\n100% compliance with water quality standards\nReduced maintenance downtime by 50%', NULL, 1, 1, 1),
('Regional Water Authority - Cost Reduction Success', 'regional-water-authority', 'Regional Water Authority', 'Regional Area', 'Municipal Water Treatment', 'Facing budget constraints and increasing operational costs, the water authority needed to reduce expenses while maintaining water quality standards.', 'We implemented our cost-effective 2mm granulated activated carbon solution with optimized replacement schedules and bulk purchasing arrangements.', '25% reduction in operational costs\nMaintained 100% water quality compliance\nExtended filter replacement intervals\nImproved system reliability', NULL, 2, 1, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), challenge=VALUES(challenge), solution=VALUES(solution), results=VALUES(results);

-- Certifications
INSERT INTO certifications (title, issuing_organization, certificate_number, issue_date, expiry_date, description, image_url, document_url, display_order, is_active) VALUES
('ISO 9001:2015', 'International Organization for Standardization', 'ISO-9001-2015', '2010-01-01', NULL, 'Quality Management Systems - Certified since 2010, demonstrating our commitment to consistent quality management and continuous improvement in all our operations.', NULL, NULL, 1, 1),
('ISO 14001:2015', 'International Organization for Standardization', 'ISO-14001-2015', '2015-01-01', NULL, 'Environmental Management Systems - Certification demonstrating our commitment to environmental responsibility and sustainable operations in activated carbon production.', NULL, NULL, 2, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description);

-- Resources
INSERT INTO resources (title, description, file_url, file_type, category, display_order, is_active) VALUES
('Granulated Activated Carbon (GAC) Technical Data Sheet', '2mm below (granulated) specification • Surface area, iodine number, ash content • Municipal water treatment applications', '#', 'PDF', 'Technical Data Sheets', 1, 1),
('Coconut Husk Chips Technical Data Sheet', 'Growing medium specifications • Horticultural applications • Packaging information', '#', 'PDF', 'Technical Data Sheets', 2, 1),
('Coconut Pit Technical Data Sheet', 'Premium horticultural growing medium • Specifications • Application guide', '#', 'PDF', 'Technical Data Sheets', 3, 1),
('Product Catalog 2024', 'Complete product catalog featuring all our activated carbon products and coconut husk products', '#', 'PDF', 'Product Catalogs', 4, 1),
('Municipal Water Treatment Application Guide', 'Comprehensive guide for municipal water treatment facilities using activated carbon', '#', 'PDF', 'Application Guides', 5, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description);

-- Social Media Links
INSERT INTO social_media (platform, url, icon_class, display_order, is_active) VALUES
('Facebook', '#', 'fab fa-facebook', 1, 1),
('Twitter', '#', 'fab fa-twitter', 2, 1),
('LinkedIn', '#', 'fab fa-linkedin', 3, 1),
('Instagram', '#', 'fab fa-instagram', 4, 1)
ON DUPLICATE KEY UPDATE url=VALUES(url);

-- Team Members
INSERT INTO team_members (name, position, bio, photo_url, display_order, is_active) VALUES
('John Smith', 'Chief Executive Officer', '25+ years of experience in the activated carbon industry, leading our company\'s strategic vision and growth.', NULL, 1, 1),
('Sarah Johnson', 'Chief Operations Officer', 'Expert in manufacturing processes and quality control, ensuring our products meet the highest standards.', NULL, 2, 1),
('Michael Chen', 'Chief Technology Officer', 'Leading our R&D efforts and driving innovation in activated carbon technology and applications.', NULL, 3, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name), position=VALUES(position), bio=VALUES(bio);

-- Footer Links
INSERT INTO footer_links (category, label, url, display_order, is_active) VALUES
('quick_links', 'Home', 'index.php', 1, 1),
('quick_links', 'About Us', 'about.php', 2, 1),
('quick_links', 'Products', 'products.php', 3, 1),
('quick_links', 'Services', 'services.php', 4, 1),
('quick_links', 'Case Studies', 'case-studies.php', 5, 1),
('quick_links', 'Gallery', 'gallery.php', 6, 1),
('quick_links', 'Resources', 'resources.php', 7, 1),
('products', 'Granulated Activated Carbon', 'products.php#granulated', 1, 1),
('products', 'Municipal Water Treatment', 'products.php#municipal', 2, 1),
('products', 'Coconut Husk Products', 'products.php#husk', 3, 1),
('products', 'Custom Formulations', 'products.php#custom', 4, 1),
('legal', 'Privacy Policy', '#', 1, 1),
('legal', 'Terms of Service', '#', 2, 1)
ON DUPLICATE KEY UPDATE label=VALUES(label), url=VALUES(url);


-- Applications (products.php "Applications" grid)
INSERT INTO applications (title, description, icon, is_primary, display_order, is_active) VALUES
('Municipal Water Treatment', 'Drinking water treatment for cities and towns - 200+ facilities served', 'fas fa-building', 1, 10, 1),
('Industrial Wastewater', 'Treatment of industrial process water', 'fas fa-industry', 0, 20, 1),
('Healthcare', 'Medical air filtration and sterilization', 'fas fa-hospital', 0, 30, 1),
('Pharmaceutical', 'Drug purification and manufacturing', 'fas fa-flask', 0, 40, 1),
('Oil & Gas', 'Fuel purification and gas treatment', 'fas fa-gas-pump', 0, 50, 1),
('Residential', 'Home water and air filtration', 'fas fa-home', 0, 60, 1),
('Aquaculture', 'Fish farming and aquarium systems', 'fas fa-fish', 0, 70, 1),
('Environmental', 'Pollution control and remediation', 'fas fa-leaf', 0, 80, 1)
ON DUPLICATE KEY UPDATE description=VALUES(description), icon=VALUES(icon), is_primary=VALUES(is_primary);

-- Certifications — ISO cards + product certification cards (certifications.php)
-- Requires the category/subtitle/icon/features/badge_label columns from database_schema_update.sql.
UPDATE certifications SET
    category = 'iso',
    subtitle = 'Quality Management Systems',
    icon = 'fas fa-certificate',
    description = 'Certified since 2010, demonstrating our commitment to consistent quality management and continuous improvement in all our operations.',
    features = 'Quality management system certification • Process standardization • Continuous improvement framework • Customer satisfaction focus'
WHERE title = 'ISO 9001:2015';

UPDATE certifications SET
    category = 'iso',
    subtitle = 'Environmental Management Systems',
    icon = 'fas fa-shield-alt',
    description = 'Certification demonstrating our commitment to environmental responsibility and sustainable operations in activated carbon production.',
    features = 'Environmental management system • Zero-waste operations • Sustainable resource utilization • Environmental compliance'
WHERE title = 'ISO 14001:2015';

INSERT INTO certifications (title, category, icon, description, badge_label, display_order, is_active) VALUES
('NSF/ANSI Standards', 'product', 'fas fa-check-circle', 'Certified for drinking water treatment applications. Our activated carbon products meet NSF/ANSI Standard 61 for drinking water system components.', 'Compliant', 10, 1),
('Water Quality Standards', 'product', 'fas fa-award', 'Compliance with EPA and WHO drinking water quality standards. Our 2mm granulated activated carbon meets all regulatory requirements for municipal applications.', 'Regulatory Compliant', 20, 1),
('Quality Control Testing', 'product', 'fas fa-flask', 'Rigorous batch testing and quality control processes ensure consistent product specifications, including our strict 2mm granulated particle size standard.', 'Batch Consistency', 30, 1)
ON DUPLICATE KEY UPDATE category=VALUES(category), icon=VALUES(icon), description=VALUES(description), badge_label=VALUES(badge_label);
