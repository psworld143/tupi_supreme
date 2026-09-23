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
