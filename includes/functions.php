<?php
/**
 * General helper functions
 */

// Clean input data to prevent XSS
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Generate a random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate and store CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

// Format date for display
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Redirect to a URL
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Get current URL
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Log system error
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextString = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] ERROR: $message $contextString" . PHP_EOL;
    error_log($logMessage);
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate required fields
function validateRequired($data, $fields) {
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    return $errors;
}

// Get user role permissions
function getUserPermissions($roleId) {
    $db = Database::getInstance();
    $sql = "SELECT p.* FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = :role_id";
    
    return $db->fetchAll($sql, ['role_id' => $roleId]);
}

// Check if user has specific permission
function hasPermission($permissionName, $userId = null) {
    if (!$userId && isLoggedIn()) {
        $userId = $_SESSION['user_id'];
    }
    
    if (!$userId) {
        return false;
    }
    
    $db = Database::getInstance();
    $sql = "SELECT p.name FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN Users u ON u.role_id = rp.role_id
            WHERE u.id = :user_id AND p.name = :permission_name";
    
    $result = $db->fetchOne($sql, [
        'user_id' => $userId,
        'permission_name' => $permissionName
    ]);
    
    return !empty($result);
}

// Get file extension
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Generate a slug
function createSlug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
}

// Format file size for display
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>