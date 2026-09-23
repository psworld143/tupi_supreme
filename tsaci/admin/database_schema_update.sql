-- Enhanced Database Schema for Dynamic Content
-- Run this after the initial database.sql to add new tables and update existing ones

USE tsaci_cms;

-- Site Settings Table (for company info, contact details, etc.)
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Homepage Features Table (for index.php features section)
CREATE TABLE IF NOT EXISTS homepage_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statistics Table (for homepage stats)
CREATE TABLE IF NOT EXISTS statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    value VARCHAR(50) NOT NULL,
    description VARCHAR(200),
    icon VARCHAR(100),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- About Page Content Table
CREATE TABLE IF NOT EXISTS about_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) NOT NULL,
    title VARCHAR(200),
    content TEXT,
    image_url VARCHAR(500),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_section (section_name),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Company Values Table (for about page)
CREATE TABLE IF NOT EXISTS company_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Team Members Table
CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    position VARCHAR(200),
    bio TEXT,
    photo_url VARCHAR(500),
    email VARCHAR(100),
    linkedin_url VARCHAR(500),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Timeline Events Table (for about page)
CREATE TABLE IF NOT EXISTS timeline_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_year (year),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update contact_messages to save to database instead of just email
-- (Already exists, but ensure it's being used)

-- Social Media Links Table
CREATE TABLE IF NOT EXISTS social_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50) NOT NULL,
    url VARCHAR(500) NOT NULL,
    icon_class VARCHAR(100),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_platform (platform),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Footer Links Table
CREATE TABLE IF NOT EXISTS footer_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    label VARCHAR(200) NOT NULL,
    url VARCHAR(500) NOT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carousel slides: per-slide overlay opacity (0-100, controls how strong the
-- dark green gradient overlay is over the background image; 100 = fully hidden,
-- 0 = image fully visible). Defaults to 92 to match the original fixed overlay.
ALTER TABLE carousel_slides
    ADD COLUMN overlay_opacity TINYINT UNSIGNED NOT NULL DEFAULT 92 AFTER image_url;


-- Applications Table — the "Applications" card grid on products.php.
-- Replaces the 8 cards that were previously hardcoded in the template.
-- is_primary marks the featured card (dark background + "PRIMARY APPLICATION" badge);
-- only one row should have is_primary = 1 at a time (the admin UI enforces this).
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    icon VARCHAR(100) DEFAULT 'fas fa-th-large',
    is_primary TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_title (title),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the cards that were previously hardcoded in products.php
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

-- Certifications page wiring — the public certifications.php previously rendered
-- hardcoded cards and ignored this table entirely. These columns let each row
-- render as either an ISO-style card (subtitle + checkmark bullet list) or a
-- Product-certification card (centered icon + badge pill), by category.
ALTER TABLE certifications
    ADD COLUMN category VARCHAR(20) NOT NULL DEFAULT 'iso' AFTER title,
    ADD COLUMN subtitle VARCHAR(200) DEFAULT NULL AFTER category,
    ADD COLUMN icon VARCHAR(100) DEFAULT 'fas fa-certificate' AFTER subtitle,
    ADD COLUMN features TEXT DEFAULT NULL AFTER description,
    ADD COLUMN badge_label VARCHAR(100) DEFAULT NULL AFTER features;

-- Backfill the two existing ISO rows to match the card content they replaced
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

-- Seed the three Product Certification cards that were hardcoded
INSERT INTO certifications (title, category, icon, description, badge_label, display_order, is_active) VALUES
('NSF/ANSI Standards', 'product', 'fas fa-check-circle', 'Certified for drinking water treatment applications. Our activated carbon products meet NSF/ANSI Standard 61 for drinking water system components.', 'Compliant', 10, 1),
('Water Quality Standards', 'product', 'fas fa-award', 'Compliance with EPA and WHO drinking water quality standards. Our 2mm granulated activated carbon meets all regulatory requirements for municipal applications.', 'Regulatory Compliant', 20, 1),
('Quality Control Testing', 'product', 'fas fa-flask', 'Rigorous batch testing and quality control processes ensure consistent product specifications, including our strict 2mm granulated particle size standard.', 'Batch Consistency', 30, 1)
ON DUPLICATE KEY UPDATE category=VALUES(category), icon=VALUES(icon), description=VALUES(description), badge_label=VALUES(badge_label);

-- Editable section copy for certifications.php (managed via Admin -> Pages)
INSERT INTO page_content (page_name, section_name, content_type, content, display_order, is_active) VALUES
('certifications', 'page_header_title', 'text', 'Certifications & Quality Standards', 1, 1),
('certifications', 'page_header_subtitle', 'text', 'Committed to quality, compliance, and industry standards', 2, 1),
('certifications', 'iso_title', 'text', 'ISO Certifications', 3, 1),
('certifications', 'iso_subtitle', 'text', 'International quality management standards', 4, 1),
('certifications', 'product_certs_title', 'text', 'Product Certifications', 5, 1),
('certifications', 'product_certs_subtitle', 'text', 'Industry standards and regulatory compliance for municipal water treatment', 6, 1),
('certifications', 'quality_title', 'text', 'Quality Management Systems', 7, 1),
('certifications', 'quality_subtitle', 'text', 'Comprehensive quality assurance for municipal water treatment clients', 8, 1),
('certifications', 'compliance_title', 'text', 'Industry Compliance', 9, 1),
('certifications', 'compliance_subtitle', 'text', 'Meeting and exceeding industry standards for activated carbon', 10, 1),
('certifications', 'testing_title', 'text', 'Testing & Validation', 11, 1),
('certifications', 'testing_subtitle', 'text', 'Rigorous testing ensures consistent quality for municipal water treatment', 12, 1),
('certifications', 'testing_note', 'text', 'All testing is performed in our certified laboratory with full documentation available upon request for municipal water treatment facilities.', 13, 1),
('certifications', 'cta_title', 'text', 'Request Certification Documentation', 14, 1),
('certifications', 'cta_description', 'text', 'Contact us to receive detailed certification documents, test reports, and compliance information for your municipal water treatment facility', 15, 1),
('certifications', 'cta_button_1_text', 'text', 'Request Certifications', 16, 1),
('certifications', 'cta_button_1_link', 'text', 'contact.php', 17, 1),
('certifications', 'cta_button_2_text', 'text', 'Download Test Reports', 18, 1),
('certifications', 'cta_button_2_link', 'text', 'resources.php', 19, 1)
ON DUPLICATE KEY UPDATE content=VALUES(content);

INSERT INTO page_content (page_name, section_name, content_type, content, display_order, is_active) VALUES
('certifications', 'meta_description', 'text', 'TSACI certifications and quality standards. ISO certifications, quality management systems, and industry compliance for activated carbon products.', 20, 1)
ON DUPLICATE KEY UPDATE content=VALUES(content);

-- Products page: husk + custom tab panes were hardcoded card markup. These
-- columns let a product render the rich card (icon + eyebrow tagline) used on
-- the system panes. Rows are assigned to panes via products.category_id
-- (product_tabs.id), which the admin Products form already exposes.

-- Ensure product_tabs exists with the three system tabs (the table is normally
-- auto-created by admin/product-tabs.php, but the live DB may not have it yet).
CREATE TABLE IF NOT EXISTS product_tabs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tab_key VARCHAR(50) UNIQUE NOT NULL,
    label VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-cube',
    keywords VARCHAR(200) DEFAULT '',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_system TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO product_tabs (tab_key, label, icon, keywords, display_order, is_active, is_system) VALUES
('granulated', 'Granulated Activated Carbon', 'fa-cubes', 'granulated', 1, 1, 1),
('husk', 'Coconut Husk Products', 'fa-seedling', 'husk,coconut', 2, 1, 1),
('custom', 'Custom Formulations', 'fa-cogs', 'custom', 3, 1, 1)
ON DUPLICATE KEY UPDATE label=VALUES(label);

ALTER TABLE products
    ADD COLUMN icon VARCHAR(100) DEFAULT NULL AFTER image_url,
    ADD COLUMN tagline VARCHAR(200) DEFAULT NULL AFTER icon;

-- Assign existing husk products to the "Coconut Husk Products" tab (id = product_tabs row)
-- and give them the icon/tagline/full feature + application lists from the
-- previously hardcoded cards.
UPDATE products p JOIN product_tabs t ON t.tab_key = 'husk' SET
    p.category_id = t.id,
    p.icon = 'fas fa-seedling',
    p.tagline = 'Premium Growing Medium',
    p.description = 'High-quality coconut husk chips processed from our zero-waste operations. An excellent sustainable alternative to traditional growing mediums, providing superior water retention and aeration for horticultural applications.',
    p.features = 'Excellent water retention capacity • Superior aeration properties • Sustainable and eco-friendly • Natural fiber composition • pH balanced and disease-free • Consistent particle size',
    p.applications = 'Horticulture and greenhouse operations • Commercial agriculture • Professional gardening • Container growing'
WHERE p.slug = 'coconut-husk-chips';

UPDATE products p JOIN product_tabs t ON t.tab_key = 'husk' SET
    p.category_id = t.id,
    p.icon = 'fas fa-leaf',
    p.tagline = 'Premium Horticultural Medium',
    p.description = 'Premium-grade coconut pit processed specifically for horticultural applications. This specialized growing medium offers exceptional performance for professional growers, greenhouse operations, and commercial agriculture.',
    p.features = 'Premium quality for professional use • Optimized particle size distribution • Superior root development support • Enhanced nutrient retention • Long-lasting performance • Zero-waste sustainable source',
    p.applications = 'Professional horticulture • Greenhouse cultivation • Commercial growing operations • Specialized crop production'
WHERE p.slug = 'coconut-pit';

-- Seed the three Custom Formulations cards as products assigned to the custom tab
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

-- Editable banner/callout copy for the husk + custom panes (Admin -> Pages -> products)
INSERT INTO page_content (page_name, section_name, content_type, content, display_order, is_active) VALUES
('products', 'husk_banner_title', 'text', 'Coconut Husk Products', 30, 1),
('products', 'husk_banner_subtitle', 'text', 'Sustainable Growing Mediums from Our Zero-Waste Operations', 31, 1),
('products', 'husk_note_title', 'text', 'Sustainable Zero-Waste Operations', 32, 1),
('products', 'husk_note_text', 'text', 'Our Coconut Husk Products are part of our integrated zero-waste operations. By utilizing all parts of the coconut, we maximize resource efficiency while providing high-quality growing mediums for the horticultural industry. These products represent our commitment to sustainability and environmental responsibility.', 33, 1),
('products', 'custom_banner_title', 'text', 'Custom Formulations', 34, 1),
('products', 'custom_banner_subtitle', 'text', 'Tailored activated carbon solutions for specialized applications', 35, 1)
ON DUPLICATE KEY UPDATE content=VALUES(content);
