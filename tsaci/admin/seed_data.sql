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
INSERT INTO products (name, slug, description, specifications, features, applications, image_url, display_order, is_featured, is_active) VALUES
('Granulated Activated Carbon (GAC)', 'granulated-activated-carbon', 'Premium 2mm granulated activated carbon optimized for municipal water treatment facilities. Strict quality specifications ensure consistent performance.', '2mm below (granulated) • High surface area • Low ash content • Consistent particle size distribution', 'Premium quality • Strict specifications • Consistent performance • GAC certified', 'Municipal water treatment • Industrial water purification • Wastewater treatment • Air purification', NULL, 1, 1, 1),
('Municipal Water Treatment Solutions', 'municipal-water-treatment', 'Specialized activated carbon solutions designed specifically for municipal water treatment facilities. Proven track record with 200+ municipal clients.', 'Custom formulations • System integration • Technical support • Compliance guaranteed', 'Municipal expertise • Proven results • Technical consultation • 24/7 support', 'Municipal water treatment • Public water systems • Water quality compliance', NULL, 2, 1, 1),
('Coconut Husk Chips', 'coconut-husk-chips', 'Sustainable growing medium made from coconut husks. Ideal for horticultural and agricultural applications.', 'Natural organic material • Excellent drainage • High water retention • pH balanced', 'Sustainable • Organic • Renewable resource • Eco-friendly', 'Horticulture • Agriculture • Landscaping • Hydroponics', NULL, 3, 0, 1),
('Coconut Pit', 'coconut-pit', 'Premium horticultural growing medium. Finely processed coconut coir for optimal plant growth.', 'Fine texture • High water retention • Excellent aeration • Nutrient rich', 'Premium quality • Sustainable • Organic • Versatile', 'Horticulture • Greenhouse production • Container gardening • Seed starting', NULL, 4, 0, 1)
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description);

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

