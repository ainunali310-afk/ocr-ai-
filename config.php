<?php
/**
 * Application Bootstrap & Configuration Loader
 * Central entry point for all configuration and helpers
 */

// Load database configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/settings.php';

// Backward compatible path variables for legacy code
$uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : __DIR__ . '/../uploads/';
$logsDir = defined('LOGS_DIR') ? LOGS_DIR : __DIR__ . '/../logs/';

// Load helper files
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/sanitize.php';
require_once __DIR__ . '/helpers/gemini.php';
require_once __DIR__ . '/helpers/ocr.php';
require_once __DIR__ . '/helpers/file.php';

// Verify database tables exist (simplified from old schema creation)
$requiredTables = ['archives', 'users', 'categories'];
$result = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '" . getenv('DB_NAME') . "'");
$existingTables = [];
while ($row = $result->fetch_assoc()) {
    $existingTables[] = $row['table_name'];
}

// Log any missing tables
foreach ($requiredTables as $table) {
    if (!in_array($table, $existingTables)) {
        error_log("Warning: Table '$table' not found in database");
    }
}

?>
