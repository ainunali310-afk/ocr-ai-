<?php
/**
 * XSS Prevention Helper Functions
 * Sanitize and escape user inputs to prevent XSS attacks
 */

/**
 * Sanitize string input (for database storage)
 * Remove/escape potentially dangerous characters
 * @param string $input User input
 * @return string Sanitized string
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    $input = trim($input);
    $input = stripslashes($input);
    return $input;
}

/**
 * Escape HTML output (for displaying in HTML)
 * Convert special characters to HTML entities
 * @param string $input String to escape
 * @param int $flags ENT_QUOTES (default) or ENT_COMPAT
 * @return string Escaped string safe for HTML output
 */
function escapeHtml($input, $flags = ENT_QUOTES) {
    return htmlspecialchars((string)$input, $flags, 'UTF-8');
}

/**
 * Escape HTML attribute (for attributes like value, href, onclick, etc)
 * @param string $input String to escape
 * @return string Escaped string safe for HTML attributes
 */
function escapeAttr($input) {
    return htmlspecialchars((string)$input, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize and escape for safe database and HTML output
 * Combine sanitization with HTML escaping
 * @param string $input User input
 * @return string Sanitized and escaped string
 */
function sanitizeAndEscape($input) {
    $sanitized = sanitizeInput($input);
    return escapeHtml($sanitized);
}

/**
 * Validate and sanitize URL
 * @param string $url URL to validate
 * @return string|false Valid URL or false
 */
function sanitizeUrl($url) {
    $url = filter_var($url, FILTER_SANITIZE_URL);
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    return false;
}

/**
 * Sanitize email address
 * @param string $email Email to sanitize
 * @return string|false Valid email or false
 */
function sanitizeEmail($email) {
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}

/**
 * Sanitize integer
 * @param mixed $input Value to sanitize
 * @return int Sanitized integer
 */
function sanitizeInt($input) {
    return (int)filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize float
 * @param mixed $input Value to sanitize
 * @return float Sanitized float
 */
function sanitizeFloat($input) {
    return (float)filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Sanitize JSON string
 * @param string $input JSON string
 * @return array|false Decoded JSON or false if invalid
 */
function sanitizeJson($input) {
    $decoded = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }
    return false;
}

/**
 * Strip HTML tags from string
 * @param string $input String with HTML tags
 * @return string String without HTML tags
 */
function stripHtmlTags($input) {
    return strip_tags($input);
}

/**
 * Validate color hex code
 * @param string $color Color hex code (e.g., #03e6ff)
 * @return bool True if valid hex color
 */
function isValidHexColor($color) {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
}

?>
