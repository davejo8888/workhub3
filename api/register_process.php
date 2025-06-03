<?php

// Added by repair script to define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__FILE__) . '/../'));
}
/**
 * API endpoint for user registration
 * Fixed to properly return JSON responses
 */
require_once '../config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired form submission']);
    exit;
}

try {
    // Sanitize input data
    $userData = [
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'username' => sanitize($_POST['username'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'department' => sanitize($_POST['department'] ?? ''),
        'role' => sanitize($_POST['role'] ?? '')
    ];
    
    // Make sure "other" department is properly handled
    if ($userData['department'] === 'other' && isset($_POST['other_department'])) {
        $userData['department'] = sanitize($_POST['other_department']);
    }
    
    // Validate required fields
    $requiredFields = ['full_name', 'username', 'email', 'password', 'confirm_password', 'department', 'role'];
    $errors = validateRequired($userData, $requiredFields);
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Please fill all required fields']);
        exit;
    }
    
    // Validate email
    if (!validateEmail($userData['email'])) {
        $errors['email'] = 'Invalid email address format';
    }
    
    // Validate password
    if (strlen($userData['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    // Check if passwords match
    if ($userData['password'] !== $userData['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Check if terms are accepted
    if (!isset($_POST['terms'])) {
        $errors['terms'] = 'You must accept the Terms of Service and Privacy Policy';
    }
    
    // If there are validation errors, return them
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Please fix the errors and try again']);
        exit;
    }
    
    // Attempt to register user
    $result = registerUser($userData);
    
    // Return the result (success or failure with specific error messages)
    echo json_encode($result);
    
} catch (Exception $e) {
    logError('Registration error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred during registration: ' . $e->getMessage()
    ]);
}
?>