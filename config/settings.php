<?php
/**
 * Application Settings & Constants
 * Define constants, database schema, dan configuration
 */

// ===== APPLICATION SETTINGS =====
define('APP_NAME', 'Arsip Nebula');
define('APP_VERSION', '1.0.0');
define('ITEMS_PER_PAGE', 12);
define('MAX_FILE_SIZE', 10485760); // 10MB

// ===== DIRECTORIES =====
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('LOGS_DIR', __DIR__ . '/../logs/');
define('CACHE_DIR', __DIR__ . '/../cache/');

// Create directories if not exist
foreach ([UPLOAD_DIR, CACHE_DIR] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ===== API CONFIGURATION =====
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY'));
define('GEMINI_MODELS', ['gemini-2.0-flash', 'gemini-2.5-flash', 'gemini-flash-latest']);
define('API_TIMEOUT', 60);

// ===== FILE UPLOAD SETTINGS =====
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// ===== DATABASE TABLES SCHEMA =====
$DATABASE_SCHEMA = [
    'archives' => "CREATE TABLE IF NOT EXISTS archives (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        template_name VARCHAR(255) NULL,
        template_path VARCHAR(255) NULL,
        output_format VARCHAR(20) NULL,
        ai_prompt TEXT NULL,
        judul VARCHAR(255) NULL,
        nomor_dokumen VARCHAR(100) NULL,
        tahun INT NULL,
        jenis_arsip VARCHAR(100) NULL,
        jenis_kegiatan VARCHAR(100) NULL,
        nama_kegiatan VARCHAR(255) NULL,
        instansi VARCHAR(255) NULL,
        ocr_text TEXT NULL,
        raw_text TEXT NULL,
        ai_json JSON NULL,
        data_terstruktur JSON NULL,
        status VARCHAR(50) NULL,
        user_id INT NULL,
        category_id INT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_category_id (category_id),
        INDEX idx_tahun (tahun),
        INDEX idx_jenis_arsip (jenis_arsip),
        INDEX idx_jenis_kegiatan (jenis_kegiatan),
        INDEX idx_nomor_dokumen (nomor_dokumen),
        INDEX idx_instansi (instansi),
        FULLTEXT INDEX idx_ocr_text (ocr_text)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin','user') DEFAULT 'user',
        avatar VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'categories' => "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        color VARCHAR(7) DEFAULT '#03e6ff',
        icon VARCHAR(50) DEFAULT 'bi-folder',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

// Initialize database schema
global $conn;
foreach ($DATABASE_SCHEMA as $table => $schema) {
    $conn->query($schema);
}

// Ensure archive metadata columns exist for structured digital archive data
$archiveColumns = [
    'template_name VARCHAR(255) NULL',
    'template_path VARCHAR(255) NULL',
    'output_format VARCHAR(20) NULL',
    'ai_prompt TEXT NULL',
    'nomor_dokumen VARCHAR(100) NULL',
    'jenis_arsip VARCHAR(100) NULL',
    'nama_kegiatan VARCHAR(255) NULL',
    'instansi VARCHAR(255) NULL',
    'data_terstruktur JSON NULL',
    'status VARCHAR(50) NULL'
];
foreach ($archiveColumns as $columnDefinition) {
    list($columnName) = explode(' ', $columnDefinition, 2);
    $columnName = trim($columnName);
    $columnCheck = $conn->query("SHOW COLUMNS FROM archives LIKE '" . $conn->real_escape_string($columnName) . "'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE archives ADD COLUMN $columnDefinition");
    }
}

// Create default admin if not exists
$adminCheck = $conn->query("SELECT id FROM users WHERE username = 'admin'");
if ($adminCheck->num_rows === 0) {
    $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password_hash, full_name, role) VALUES ('admin', '$defaultPass', 'Administrator', 'admin')");
}

?>
