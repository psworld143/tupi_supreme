<?php
/**
 * TSACI Admin Console - Configuration File
 * Database and System Configuration
 */

// Prevent direct access
if (!defined('ADMIN_ACCESS')) {
    define('ADMIN_ACCESS', true);
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'tsaci_app');
define('DB_PASS', '52f5fc827ea1abd8463510e4');
define('DB_NAME', 'tsaci_cms');

// Site Configuration
define('SITE_NAME', 'TSACI Admin Console');
define('SITE_URL', 'http://localhost/tupi_supreme/tsaci');
define('ADMIN_URL', 'http://localhost/tupi_supreme/tsaci/admin');

// Session Configuration
define('SESSION_NAME', 'TSACI_ADMIN_SESSION');
define('SESSION_LIFETIME', 3600 * 8); // 8 hours
define('SESSION_IDLE_TIMEOUT', 20 * 60); // force logout after 20 minutes of inactivity

// Security Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting — verbose on localhost, logged-but-hidden elsewhere.
// To force silence locally: define APP_ENV = 'production' before this file.
$app_env = getenv('APP_ENV') ?: (in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'], true) ? 'development' : 'production');
error_reporting(E_ALL);
ini_set('display_errors', $app_env === 'development' ? '1' : '0');
ini_set('log_errors', '1');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    // Some hosts (e.g. LiteSpeed/lsphp) set a default session.save_path that
    // this account cannot opendir(), which makes PHP's session garbage
    // collector emit "ps_files_cleanup_dir: Permission denied" notices.
    // Use a path we control: outside the web root when possible, otherwise a
    // protected folder inside the project.
    $docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $sessionPath = $docRoot
        ? dirname($docRoot) . DIRECTORY_SEPARATOR . 'tsaci_sessions'
        : __DIR__ . '/../sessions';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0700, true);
    }
    if (!is_dir($sessionPath) || !is_writable($sessionPath)) {
        $sessionPath = __DIR__ . '/../sessions';
        if (!is_dir($sessionPath)) {
            @mkdir($sessionPath, 0700, true);
        }
        // Block web access to the fallback folder (it lives under the docroot)
        if (is_dir($sessionPath) && !file_exists($sessionPath . '/.htaccess')) {
            @file_put_contents($sessionPath . '/.htaccess', "Require all denied\nDeny from all\n");
        }
    }
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }
    // Keep session files for 30 days so "Remember me" logins survive GC;
    // normal sessions still end when their 8-hour cookie expires.
    ini_set('session.gc_maxlifetime', (string) (3600 * 24 * 30));
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name(SESSION_NAME);
    session_start();
}

// Database Connection Class
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
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
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function getLastInsertId() {
        return $this->conn->insert_id;
    }
    
    public function getAffectedRows() {
        return $this->conn->affected_rows;
    }
}

// Helper Functions
function getDB() {
    return Database::getInstance()->getConnection();
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            jsonResponse(['success' => false, 'error' => 'Your session has expired. Please reload the page and log in again.'], 401);
        }
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }

    // Idle timeout — kick the user out after SESSION_IDLE_TIMEOUT seconds
    // without a request. Sessions older than this feature simply get the
    // clock started now rather than being logged out immediately.
    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
        logActivity('logout', 'admin_users', $_SESSION['admin_id'], 'Session expired (idle timeout)');
        $_SESSION = [];
        session_destroy();
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            jsonResponse(['success' => false, 'error' => 'Your session has expired. Please reload the page and log in again.'], 401);
        }
        header('Location: ' . ADMIN_URL . '/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email, full_name, role FROM admin_users WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function logActivity($action, $table_name = null, $record_id = null, $description = null) {
    if (!isLoggedIn()) {
        return;
    }
    
    $db = getDB();
    $user_id = $_SESSION['admin_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiiss", $user_id, $action, $table_name, $record_id, $description, $ip_address, $user_agent);
    $stmt->execute();
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]+/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * CSRF protection helpers.
 * Opt-in: pages must call csrfTokenField() and verifyCsrfToken() to use them.
 * Existing pages that don't call them are unaffected.
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfTokenField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verifyCsrfToken() {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Create upload directories if they don't exist (suppress errors if permissions don't allow)
if (!file_exists(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}
if (!file_exists(UPLOAD_DIR . 'images/')) {
    @mkdir(UPLOAD_DIR . 'images/', 0755, true);
}
if (!file_exists(UPLOAD_DIR . 'documents/')) {
    @mkdir(UPLOAD_DIR . 'documents/', 0755, true);
}

