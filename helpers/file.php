<?php
/**
 * File Handling Helper
 * Manages file upload, validation, and sanitization
 */

/**
 * Sanitize filename to prevent directory traversal and XSS
 * 
 * @param string $fileName Original filename
 * @return string Safe filename with timestamp
 */
function sanitizeFileName($fileName) {
    $name = pathinfo($fileName, PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    $safeName = trim($safeName, '._-');

    if ($safeName === '') {
        $safeName = 'dokumen';
    }

    return $safeName . '_' . time() . '.' . $extension;
}

/**
 * Validate uploaded file
 * 
 * @param array $file $_FILES array element
 * @return array|bool Array with validation result, false if invalid
 */
function validateUploadedFile($file) {
    // Check if file exists
    if (empty($file) || !isset($file['tmp_name'])) {
        return false;
    }

    // Check file size
    $maxSize = defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : 10485760;
    if ($file['size'] > $maxSize) {
        return ['error' => 'File terlalu besar. Maksimal ' . ($maxSize / 1024 / 1024) . 'MB'];
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = defined('ALLOWED_MIME_TYPES') ? ALLOWED_MIME_TYPES : ['image/jpeg', 'image/png'];
    if (!in_array($mimeType, $allowedMimes, true)) {
        return ['error' => 'Tipe file tidak didukung. Gunakan JPG atau PNG'];
    }

    return ['success' => true, 'mime' => $mimeType];
}

/**
 * Move uploaded file to storage directory
 * 
 * @param string $tmpPath Temporary file path
 * @param string $uploadDir Upload directory path
 * @return string|false Final file path or false if failed
 */
function moveUploadedFile($tmpPath, $uploadDir) {
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = sanitizeFileName(basename($tmpPath));
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($tmpPath, $targetPath)) {
        return 'uploads/' . $fileName;
    }

    return false;
}

?>
