<?php
/**
 * Server-side upload validation.
 *
 * NEVER trust $_FILES[x]['type'] — the client sends it. These helpers sniff
 * the real content with finfo (and getimagesize for images) and return a
 * whitelisted extension derived from the VERIFIED mime type, not from the
 * uploaded filename.
 *
 * Usage:
 *     require_once '../includes/uploads.php';   // from admin/api/
 *     $ext = validateImageUpload($_FILES['image'], $err);
 *     if ($ext === null) { ... reject with $err ... }
 *     $name = uniqid('img_', true) . '.' . $ext;
 */

// Verified-mime -> safe extension maps
const IMAGE_MIME_EXT = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

const DOCUMENT_MIME_EXT = [
    'application/pdf'    => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
];

/** Real mime type of the file on disk (empty string on failure). */
function realMimeType(string $tmp_path): string {
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp_path);
    return is_string($mime) ? $mime : '';
}

/**
 * Validate an uploaded image.
 * Returns the safe extension ('jpg'|'png'|'gif'|'webp') or null with $error set.
 */
function validateImageUpload(array $file, ?string &$error): ?string {
    $error = null;
    if (($file['size'] ?? 0) > MAX_FILE_SIZE) {
        $error = 'File size exceeds maximum allowed size';
        return null;
    }
    $mime = realMimeType($file['tmp_name']);
    // finfo must recognise an allowed image type AND the file must parse as an image
    if (!isset(IMAGE_MIME_EXT[$mime]) || !@getimagesize($file['tmp_name'])) {
        $error = 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed';
        return null;
    }
    return IMAGE_MIME_EXT[$mime];
}

/**
 * Validate an uploaded document.
 * Returns the safe extension ('pdf'|'doc'|'docx') or null with $error set.
 */
function validateDocumentUpload(array $file, ?string &$error): ?string {
    $error = null;
    if (($file['size'] ?? 0) > MAX_FILE_SIZE) {
        $error = 'File size exceeds maximum allowed size (10MB)';
        return null;
    }
    $mime = realMimeType($file['tmp_name']);
    if (isset(DOCUMENT_MIME_EXT[$mime])) {
        return DOCUMENT_MIME_EXT[$mime];
    }
    // .docx is a ZIP container — some systems report it as application/zip
    if ($mime === 'application/zip'
        && strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)) === 'docx') {
        return 'docx';
    }
    $error = 'Invalid file type. Only PDF, DOC, and DOCX are allowed';
    return null;
}
