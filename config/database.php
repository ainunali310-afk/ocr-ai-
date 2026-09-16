<?php
/**
 * Database Configuration
 * Handles database connection and initialization
 */

// Load environment variables
function loadEnv($filePath = __DIR__ . '/../.env') {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key)) {
                putenv("$key=$value");
            }
        }
    }
}

loadEnv();

// Database credentials from environment
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'arsip_digital';

// Create logs directory
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

// Error reporting configuration
$appDebug = getenv('APP_DEBUG') === 'true';
error_reporting($appDebug ? E_ALL : E_ALL & ~E_NOTICE);
ini_set('display_errors', $appDebug ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', $logsDir . '/error.log');

// Connect to database
$conn = new mysqli($dbHost, $dbUser, $dbPass);
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Koneksi database gagal. Silakan hubungi administrator.');
}

// Create database
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbName`");
$conn->select_db($dbName);
$conn->set_charset('utf8mb4');

?>
