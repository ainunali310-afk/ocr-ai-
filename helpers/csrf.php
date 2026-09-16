<?php
/**
 * CSRF Token Helper Functions
 * Prevents Cross-Site Request Forgery attacks
 */

/**
 * Initialize CSRF token for session
 * Call this once at the beginning of script
 */
function initializeCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token for forms
 * @return string CSRF token
 */
function getCsrfToken() {
    initializeCsrfToken();
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Generate HTML hidden input for CSRF token
 * @return string HTML hidden input element
 */
function getCsrfTokenInput() {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF token from POST request
 * @param string $token Token to validate (usually from $_POST['csrf_token'])
 * @return bool True if token is valid, false otherwise
 */
function validateCsrfToken($token = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionToken = initializeCsrfToken();
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    
    if (empty($token) || empty($sessionToken)) {
        return false;
    }
    
    // Use hash_equals to prevent timing attacks
    return hash_equals($sessionToken, $token);
}

/**
 * Require valid CSRF token, die if invalid
 * Use this in POST handlers
 */
function requireValidCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCsrfToken()) {
            http_response_code(403);
            die('CSRF validation failed. Invalid or missing token.');
        }
    }
}

/**
 * Regenerate CSRF token (call after login)
 */
function regenerateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
