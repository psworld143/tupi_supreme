<?php
/**
 * Frontend Configuration File
 * Database connection and helper functions for public pages
 */

// Database Configuration (only define if not already defined)
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'tsaci_cms');
}

// Database Connection Class (only define if not already defined)
if (!class_exists('Database')) {
    class Database {
        private static $instance = null;
        private $conn;
        
        private function __construct() {
            try {
                // Try connecting with socket path for XAMPP
                $socket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
                if (file_exists($socket)) {
                    // mysqli(host, username, password, dbname, port, socket)
                    $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306, $socket);
                } else {
                    // Fallback to regular connection
                    $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                }
                
                if ($this->conn->connect_error) {
                    throw new Exception("Connection failed: " . $this->conn->connect_error);
                }
                
                $this->conn->set_charset("utf8mb4");
            } catch (Exception $e) {
                // Log error but don't fail silently - set conn to null
                error_log("Database connection error: " . $e->getMessage());
                $this->conn = null;
            }
        }
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function getConnection() {
            return $this->conn;
        }
        
        public function query($sql) {
            return $this->conn->query($sql);
        }
        
        public function prepare($sql) {
            return $this->conn->prepare($sql);
        }
    }
}

// Helper Functions (only define if not already defined)
if (!function_exists('getDB')) {
    function getDB() {
        return Database::getInstance()->getConnection();
    }
}

function getSiteSetting($key, $default = '') {
    $db = getDB();
    if (!$db) return $default;
    
    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

function getPageContent($page_name, $section_name, $default = '') {
    $db = getDB();
    if (!$db) return $default;
    
    $stmt = $db->prepare("SELECT content FROM page_content WHERE page_name = ? AND section_name = ? AND is_active = 1");
    $stmt->bind_param("ss", $page_name, $section_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['content'];
    }
    return $default;
}

function getHomepageFeatures() {
    $db = getDB();
    if (!$db) return [];
    
    $result = $db->query("SELECT * FROM homepage_features WHERE is_active = 1 ORDER BY display_order");
    $features = [];
    while ($row = $result->fetch_assoc()) {
        $features[] = $row;
    }
    return $features;
}

function getStatistics() {
    $db = getDB();
    if (!$db) return [];
    
    $result = $db->query("SELECT * FROM statistics WHERE is_active = 1 ORDER BY display_order");
    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
    return $stats;
}

function getProducts($limit = null, $featured_only = false) {
    $db = getDB();
    if (!$db) return [];
    
    $sql = "SELECT * FROM products WHERE is_active = 1";
    if ($featured_only) {
        $sql .= " AND is_featured = 1";
    }
    $sql .= " ORDER BY display_order, name";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $db->query($sql);
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    return $products;
}

/**
 * Fetch the product tab definitions shown on the public Products page.
 * Each tab has: tab_key, label, icon (FA class), keywords (comma-separated,
 * matched against product slug/name), display_order, is_active, is_system.
 *
 * Fail-soft: if the product_tabs table doesn't exist or is empty, returns the
 * three built-in tabs so the page always renders.
 *
 * @return array
 */
function getProductTabs() {
    $db = getDB();
    if (!$db) return [];

    // Fail-soft: tolerate a missing table (e.g. before the admin page seeds it)
    $result = $db->query("SELECT * FROM product_tabs WHERE is_active = 1 ORDER BY display_order, id");
    $tabs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tabs[] = $row;
        }
    }
    if (!empty($tabs)) {
        return $tabs;
    }

    // Defaults (table missing or empty)
    return [
        ['tab_key' => 'granulated', 'label' => 'Granulated Activated Carbon', 'icon' => 'fa-cubes',   'keywords' => 'granulated',           'is_system' => 1, 'display_order' => 1],
        ['tab_key' => 'husk',       'label' => 'Coconut Husk Products',       'icon' => 'fa-seedling', 'keywords' => 'husk,coconut',         'is_system' => 1, 'display_order' => 2],
        ['tab_key' => 'custom',     'label' => 'Custom Formulations',        'icon' => 'fa-cogs',     'keywords' => 'custom',               'is_system' => 1, 'display_order' => 3],
    ];
}

/**
 * Filter a list of products by a comma-separated keyword string.
 * A product matches if any keyword appears (case-insensitive) in its slug or name.
 *
 * @param array $products List of product rows.
 * @param string $keywords Comma-separated keywords (e.g. "husk,coconut").
 * @return array Matching products.
 */
function filterProductsByKeywords($products, $keywords) {
    if (empty($keywords)) return [];
    $kw = array_map('trim', explode(',', $keywords));
    $kw = array_filter($kw);
    if (empty($kw)) return [];
    $matched = [];
    foreach ($products as $p) {
        $haystack = strtolower(($p['slug'] ?? '') . ' ' . ($p['name'] ?? ''));
        foreach ($kw as $k) {
            if ($k !== '' && stripos($haystack, strtolower($k)) !== false) {
                $matched[] = $p;
                break;
            }
        }
    }
    return $matched;
}

/**
 * Get the products that belong to a given product tab (category).
 *
 * A product is included if EITHER:
 *   - it is directly assigned to the tab via its `category_id` column, OR
 *   - its slug/name matches any of the tab's comma-separated `keywords`.
 *
 * Results are de-duplicated by product id so a product assigned to the tab
 * won't appear twice even if it also matches the keywords. This keeps the
 * legacy keyword-matching behaviour working alongside the new explicit
 * category assignment.
 *
 * @param array $products List of product rows (each may have category_id).
 * @param array $tab      A product_tabs row (must contain id + keywords).
 * @return array Matching products.
 */
function getProductsForTab($products, $tab) {
    $tab_id = (int)($tab['id'] ?? 0);
    $keywords = $tab['keywords'] ?? '';
    $seen = [];
    $result = [];
    foreach ($products as $p) {
        $pid = (int)($p['id'] ?? 0);
        if (isset($seen[$pid])) continue;
        $assigned = $tab_id > 0 && (int)($p['category_id'] ?? 0) === $tab_id;
        if ($assigned) {
            $seen[$pid] = true;
            $result[] = $p;
            continue;
        }
    }
    // Then add keyword-matched products not already included by assignment.
    $keyword_matched = filterProductsByKeywords($products, $keywords);
    foreach ($keyword_matched as $p) {
        $pid = (int)($p['id'] ?? 0);
        if (isset($seen[$pid])) continue;
        $seen[$pid] = true;
        $result[] = $p;
    }
    return $result;
}

function getServices($limit = null) {
    $db = getDB();
    if (!$db) return [];
    
    $sql = "SELECT * FROM services WHERE is_active = 1 ORDER BY display_order, title";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $db->query($sql);
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    return $services;
}

function getCaseStudies($limit = null, $featured_only = false) {
    $db = getDB();
    if (!$db) return [];
    
    $sql = "SELECT * FROM case_studies WHERE is_active = 1";
    if ($featured_only) {
        $sql .= " AND is_featured = 1";
    }
    $sql .= " ORDER BY display_order, title";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $db->query($sql);
    $cases = [];
    while ($row = $result->fetch_assoc()) {
        $cases[] = $row;
    }
    return $cases;
}

function getCertifications($limit = null) {
    $db = getDB();
    if (!$db) return [];
    
    $sql = "SELECT * FROM certifications WHERE is_active = 1 ORDER BY display_order, title";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $db->query($sql);
    $certs = [];
    while ($row = $result->fetch_assoc()) {
        $certs[] = $row;
    }
    return $certs;
}

function getResources($category = null, $limit = null) {
    $db = getDB();
    if (!$db) return [];
    
    $sql = "SELECT * FROM resources WHERE is_active = 1";
    if ($category) {
        $sql .= " AND category = '" . $db->real_escape_string($category) . "'";
    }
    $sql .= " ORDER BY display_order, title";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $db->query($sql);
    $resources = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $resources[] = $row;
        }
    }
    return $resources;
}

/**
 * Fetch active resources whose category is NOT one of the three "known"
 * categories shown in dedicated sections on the public Resources page.
 * These are surfaced in the catch-all "Other Resources" section so that
 * resources saved with a custom category still appear on the site.
 *
 * @param array $exclude Category strings to exclude (the known sections).
 * @param int|null $limit Optional row limit.
 * @return array
 */
function getOtherResources($exclude = ['Technical Data Sheets', 'Product Catalogs', 'Application Guides'], $limit = null) {
    $db = getDB();
    if (!$db) return [];

    $excluded = [];
    foreach ($exclude as $c) {
        $excluded[] = "'" . $db->real_escape_string($c) . "'";
    }
    $excluded_sql = implode(',', $excluded);

    $sql = "SELECT * FROM resources WHERE is_active = 1"
         . " AND category IS NOT NULL AND category != ''"
         . " AND category NOT IN ($excluded_sql)"
         . " ORDER BY category, display_order, title";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }

    $result = $db->query($sql);
    $resources = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $resources[] = $row;
        }
    }
    return $resources;
}

function getFAQs($limit = null) {
    $db = getDB();
    if (!$db) return [];
    
    $sql = "SELECT * FROM faqs WHERE is_active = 1 ORDER BY display_order, id";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $db->query($sql);
    $faqs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $faqs[] = $row;
        }
    }
    return $faqs;
}

function getGalleryImages($category = null, $limit = null) {
    $db = getDB();
    if (!$db) return [];
    
    $sql = "SELECT * FROM gallery_images WHERE is_active = 1";
    if ($category) {
        $sql .= " AND category = '" . $db->real_escape_string($category) . "'";
    }
    $sql .= " ORDER BY display_order, title";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $db->query($sql);
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    return $images;
}

function getAboutContent($section_name) {
    $db = getDB();
    if (!$db) return null;
    
    $stmt = $db->prepare("SELECT * FROM about_content WHERE section_name = ? AND is_active = 1");
    $stmt->bind_param("s", $section_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function getCompanyValues() {
    $db = getDB();
    if (!$db) return [];
    
    $result = $db->query("SELECT * FROM company_values WHERE is_active = 1 ORDER BY display_order");
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $values[] = $row;
    }
    return $values;
}

function getTimelineEvents() {
    $db = getDB();
    if (!$db) return [];
    
    $result = $db->query("SELECT * FROM timeline_events WHERE is_active = 1 ORDER BY year, display_order");
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    return $events;
}

function getSocialMedia() {
    $db = getDB();
    if (!$db) return [];
    
    $result = $db->query("SELECT * FROM social_media WHERE is_active = 1 ORDER BY display_order");
    $social = [];
    while ($row = $result->fetch_assoc()) {
        $social[] = $row;
    }
    return $social;
}

function getFooterLinks($category = null) {
    $db = getDB();
    if (!$db) return [];
    
    $sql = "SELECT * FROM footer_links WHERE is_active = 1";
    if ($category) {
        $sql .= " AND category = '" . $db->real_escape_string($category) . "'";
    }
    $sql .= " ORDER BY display_order";
    
    $result = $db->query($sql);
    $links = [];
    while ($row = $result->fetch_assoc()) {
        $links[] = $row;
    }
    return $links;
}

function saveContactMessage($name, $email, $phone, $company, $subject, $message) {
    $db = getDB();
    if (!$db) return false;
    
    $stmt = $db->prepare("INSERT INTO contact_messages (name, email, phone, company, subject, message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $phone, $company, $subject, $message);
    
    return $stmt->execute();
}

function getContactInfo($type = null) {
    $db = getDB();
    if (!$db) return [];
    
    $sql = "SELECT * FROM contact_info WHERE is_active = 1";
    if ($type) {
        $sql .= " AND type = '" . $db->real_escape_string($type) . "'";
    }
    $sql .= " ORDER BY display_order, id";
    
    $result = $db->query($sql);
    $contacts = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $contacts[] = $row;
        }
    }
    return $contacts;
}

function getOfficeHours() {
    $db = getDB();
    if (!$db) return [];
    
    $result = $db->query("SELECT * FROM office_hours WHERE is_active = 1 ORDER BY display_order");
    $hours = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $hours[] = $row;
        }
    }
    return $hours;
}

function getContactSubjectOptions() {
    $db = getDB();
    if (!$db) return [];
    
    $result = $db->query("SELECT * FROM contact_subject_options WHERE is_active = 1 ORDER BY display_order");
    $options = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $options[] = $row;
        }
    }
    return $options;
}

function htmlspecialchars_safe($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function nl2br_safe($string) {
    return nl2br(htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8'));
}

function getCarouselSlides() {
    $db = getDB();
    if (!$db) return [];
    
    $result = $db->query("SELECT * FROM carousel_slides WHERE is_active = 1 ORDER BY display_order");
    $slides = [];
    while ($row = $result->fetch_assoc()) {
        $slides[] = $row;
    }
    return $slides;
}

