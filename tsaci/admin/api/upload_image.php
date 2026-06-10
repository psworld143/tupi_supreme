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
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
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
    // Generate URL relative to site root
    $image_url = '/tupi_supreme/tsaci/uploads/images/' . $unique_name;
    
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

