<?php

// Added by repair script to define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__FILE__) . '/../'));
}
/**
 * Admin Users API
 * Handles user management operations for administrators
 */
require_once '../config.php';

// Require admin privileges
requirePermission('manage_users');

// Set content type
header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? '';
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all users with role information
            $sql = "SELECT u.*, r.name as role_name 
                    FROM Users u 
                    JOIN roles r ON u.role_id = r.id 
                    ORDER BY u.created_at DESC";
            
            $users = $db->fetchAll($sql);
            
            echo json_encode(['success' => true, 'users' => $users]);
            break;
            
        case 'POST':
            // Check CSRF token
            $token = $_POST['csrf_token'] ?? '';
            if (!verifyCSRFToken($token)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid or expired form submission']);
                exit;
            }
            
            switch ($action) {
                case 'create':
                    // Validate required fields
                    $requiredFields = ['full_name', 'username', 'email', 'password', 'role'];
                    $errors = validateRequired($_POST, $requiredFields);
                    
                    if (!empty($errors)) {
                        echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Please fill all required fields']);
                        exit;
                    }
                    
                    // Sanitize inputs
                    $userData = [
                        'full_name' => sanitize($_POST['full_name']),
                        'username' => sanitize($_POST['username']),
                        'email' => sanitize($_POST['email']),
                        'password' => $_POST['password'],
                        'department' => sanitize($_POST['department'] ?? ''),
                        'role' => sanitize($_POST['role']),
                        'status' => sanitize($_POST['status'] ?? 'active')
                    ];
                    
                    // Validate email
                    if (!validateEmail($userData['email'])) {
                        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                        exit;
                    }
                    
                    // Check if username already exists
                    $existingUser = $db->fetchOne("SELECT id FROM Users WHERE username = :username", 
                        ['username' => $userData['username']]);
                    
                    if ($existingUser) {
                        echo json_encode(['success' => false, 'message' => 'Username already exists']);
                        exit;
                    }
                    
                    // Check if email already exists
                    $existingEmail = $db->fetchOne("SELECT id FROM Users WHERE email = :email", 
                        ['email' => $userData['email']]);
                    
                    if ($existingEmail) {
                        echo json_encode(['success' => false, 'message' => 'Email already exists']);
                        exit;
                    }
                    
                    // Get role ID
                    $roleId = getRoleIdByName($userData['role']);
                    if (!$roleId) {
                        echo json_encode(['success' => false, 'message' => 'Invalid role']);
                        exit;
                    }
                    
                    // Hash password
                    $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => HASH_COST]);
                    
                    // Create user
                    $user = [
                        'full_name' => $userData['full_name'],
                        'username' => $userData['username'],
                        'email' => $userData['email'],
                        'password' => $hashedPassword,
                        'department' => $userData['department'],
                        'role_id' => $roleId,
                        'status' => $userData['status'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $userId = $db->insert('users', $user);
                    
                    if (!$userId) {
                        throw new Exception('Failed to create user');
                    }
                    
                    // Log activity
                    $db->logActivity('user_created', 'Admin created new user: ' . $userData['username'], $_SESSION['user_id'], $userId, 'user');
                    
                    // Send welcome email if requested
                    if (isset($_POST['send_welcome_email']) && $_POST['send_welcome_email']) {
                        // Implement email sending logic here
                        // sendWelcomeEmail($userData['email'], $userData['username'], $_POST['password']);
                    }
                    
                    echo json_encode(['success' => true, 'user_id' => $userId, 'message' => 'User created successfully']);
                    break;
                    
                case 'update':
                    $userId = (int)$_POST['user_id'] ?? 0;
                    if (!$userId) {
                        echo json_encode(['success' => false, 'message' => 'User ID is required']);
                        exit;
                    }
                    
                    // Check if user exists
                    $user = $db->fetchOne("SELECT * FROM Users WHERE id = :id", ['id' => $userId]);
                    if (!$user) {
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        exit;
                    }
                    
                    // Prepare update data
                    $updateData = [
                        'full_name' => sanitize($_POST['full_name']),
                        'username' => sanitize($_POST['username']),
                        'email' => sanitize($_POST['email']),
                        'department' => sanitize($_POST['department'] ?? ''),
                        'status' => sanitize($_POST['status'] ?? 'active'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Update role if provided
                    if (!empty($_POST['role'])) {
                        $roleId = getRoleIdByName($_POST['role']);
                        if ($roleId) {
                            $updateData['role_id'] = $roleId;
                        }
                    }
                    
                    // Update password if provided
                    if (!empty($_POST['password'])) {
                        $updateData['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => HASH_COST]);
                    }
                    
                    // Update Users
                    $result = $db->update('users', $updateData, 'id = :id', ['id' => $userId]);
                    
                    if ($result === false) {
                        throw new Exception('Failed to update Users');
                    }
                    
                    // Log activity
                    $db->logActivity('user_updated', 'Admin updated user: ' . $updateData['username'], $_SESSION['user_id'], $userId, 'user');
                    
                    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
                    break;
                    
                case 'delete':
                    $userId = (int)$_POST['user_id'] ?? 0;
                    if (!$userId) {
                        echo json_encode(['success' => false, 'message' => 'User ID is required']);
                        exit;
                    }
                    
                    // Check if user exists
                    $user = $db->fetchOne("SELECT * FROM Users WHERE id = :id", ['id' => $userId]);
                    if (!$user) {
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        exit;
                    }
                    
                    // Check if user is admin (prevent admin deletion)
                    $isAdmin = $db->fetchOne("SELECT r.name FROM roles r JOIN Users u ON u.role_id = r.id WHERE u.id = :id", ['id' => $userId]);
                    if ($isAdmin && $isAdmin['name'] === 'admin') {
                        echo json_encode(['success' => false, 'message' => 'Cannot delete admin user']);
                        exit;
                    }
                    
                    // Check if user has tasks (optional: you might want to reassign instead of blocking deletion)
                    $userTasks = $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE created_by = :user_id OR assigned_to = :user_id", ['user_id' => $userId]);
                    
                    if ($userTasks && $userTasks['count'] > 0) {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Cannot delete user with associated tasks. Please reassign tasks first.'
                        ]);
                        exit;
                    }
                    
                    // Delete user
                    $result = $db->delete('users', 'id = :id', ['id' => $userId]);
                    
                    if (!$result) {
                        throw new Exception('Failed to delete user');
                    }
                    
                    // Log activity
                    $db->logActivity('user_deleted', 'Admin deleted user: ' . $user['username'], $_SESSION['user_id'], $userId, 'user');
                    
                    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    logError('Admin Users API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>