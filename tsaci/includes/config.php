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

