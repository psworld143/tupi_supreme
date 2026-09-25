<?php
require_once '../config.php';
require_once '../includes/uploads.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE;
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload limit (' . ini_get('upload_max_filesize') . '). Raise upload_max_filesize in php.ini or pick a smaller file.',
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

$file = $_FILES['document'];
$file_tmp = $file['tmp_name'];
$file_name = $file['name'];

// Validate real file content (never trust client-supplied MIME/extension)
$file_ext = validateDocumentUpload($file, $upload_error);
if ($file_ext === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $upload_error]);
    exit;
}

// Generate unique filename with whitelisted extension
$unique_name = uniqid('doc_', true) . '.' . $file_ext;
$upload_path = UPLOAD_DIR . 'documents/' . $unique_name;

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR . 'documents/')) {
    @mkdir(UPLOAD_DIR . 'documents/', 0755, true);
}

// Move uploaded file
if (move_uploaded_file($file_tmp, $upload_path)) {
    // Generate URL relative to site root — adapts to subfolder installs
    // (local: /tupi_supreme/tsaci) and domain-root installs (live: /)
    $site_base = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    $file_url = $site_base . '/uploads/documents/' . $unique_name;

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
