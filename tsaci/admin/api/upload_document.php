<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['document'];
$file_size = $file['size'];
$file_type = $file['type'];
$file_tmp = $file['tmp_name'];
$file_name = $file['name'];

// Validate file size
if ($file_size > MAX_FILE_SIZE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File size exceeds maximum allowed size (10MB)']);
    exit;
}

// Validate file type (also allow fallback for browsers that send generic MIME types)
$allowed = ALLOWED_DOCUMENT_TYPES;
if (!in_array($file_type, $allowed)) {
    // Some browsers send 'application/octet-stream' for .doc/.docx — check by extension too
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_exts = ['pdf', 'doc', 'docx'];
    if (!in_array($ext, $allowed_exts)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only PDF, DOC, and DOCX are allowed']);
        exit;
    }
}

// Generate unique filename
$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
$unique_name = uniqid('doc_', true) . '.' . $file_ext;
$upload_path = UPLOAD_DIR . 'documents/' . $unique_name;

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR . 'documents/')) {
    @mkdir(UPLOAD_DIR . 'documents/', 0755, true);
}

// Move uploaded file
if (move_uploaded_file($file_tmp, $upload_path)) {
    // Generate URL relative to site root
    $file_url = '/tupi_supreme/tsaci/uploads/documents/' . $unique_name;

    logActivity('upload', 'documents', null, "Uploaded document: {$file_name}");

    echo json_encode([
        'success' => true,
        'url' => $file_url,
        'filename' => $unique_name
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
}
