<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload limit (' . ini_get('upload_max_filesize') . '). Raise upload_max_filesize in php.ini or pick a smaller image.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds the maximum allowed size.',
        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server misconfiguration: missing temporary upload folder.',
        UPLOAD_ERR_CANT_WRITE => 'Server error: could not write the file to disk.',
        UPLOAD_ERR_EXTENSION  => 'The upload was blocked by a server extension.',
    ];
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => ($messages[$code] ?? 'Upload error (code ' . $code . ')')]);
    exit;
}

$file = $_FILES['image'];
$file_size = $file['size'];
$file_type = $file['type'];
$file_tmp = $file['tmp_name'];
$file_name = $file['name'];

// Validate file size
if ($file_size > MAX_FILE_SIZE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File size exceeds maximum allowed size']);
    exit;
}

// Validate file type
if (!in_array($file_type, ALLOWED_IMAGE_TYPES)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
    exit;
}

// Generate unique filename
$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
$unique_name = uniqid('img_', true) . '.' . $file_ext;
$upload_path = UPLOAD_DIR . 'images/' . $unique_name;

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR . 'images/')) {
    @mkdir(UPLOAD_DIR . 'images/', 0755, true);
}

// Move uploaded file
if (move_uploaded_file($file_tmp, $upload_path)) {
    // Generate URL relative to site root — adapts to subfolder installs
    // (local: /tupi_supreme/tsaci) and domain-root installs (live: /)
    $site_base = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    $image_url = $site_base . '/uploads/images/' . $unique_name;
    
    logActivity('upload', 'images', null, "Uploaded image: {$file_name}");
    
    echo json_encode([
        'success' => true,
        'url' => $image_url,
        'filename' => $unique_name
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
}

