<?php
/**
 * Users API
 * * CRUD operations for users and profile management
 * * @author Dr. Ahmed AL-sadi
 * @version 1.0
 */
if (!defined('ROOT_PATH')) {
    // If api/user.php is in /workhub/api/, then dirname(__DIR__) is /workhub/
    define('ROOT_PATH', dirname(__DIR__));
}

require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/auth/session.php'; // session.php might not be needed if it's a public action not requiring login state

// Ensure user is logged in for most actions
// Specific public actions like 'check_username' or 'check_email' might be handled differently if needed.

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    $action = $_POST['action'] ?? ($_GET['action'] ?? null);

    // Public actions (no login required) can be whitelist here if any
    // Example: if ($action === 'check_availability') { /* ... */ }

    if (!isLoggedIn() && !in_array($action, [''])) { // Add public actions to array
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    switch ($action) {
        case 'get_profile':
            $userId = getCurrentUserId();
            if ($userId) {
                // Exclude password from being sent to client
                $sql = "SELECT id, username, email, first_name, last_name, job_title, department, profile_image, role, last_login, created_at FROM Users WHERE id = ?";
                $user = getRecord($sql, [$userId]);
                if ($user) {
                    $response = ['success' => true, 'data' => $user];
                } else {
                    $response = ['success' => false, 'message' => 'User profile not found.'];
                }
            } else {
                 $response = ['success' => false, 'message' => 'User not identified.'];
            }
            break;

        case 'update_profile':
            $userId = getCurrentUserId();
            if (!$userId) {
                $response = ['success' => false, 'message' => 'User not identified for update.'];
                break;
            }

            $allowedFields = ['first_name', 'last_name', 'job_title', 'department', 'email']; // Add 'profile_image' if handling file uploads
            $updateData = [];
            $errors = [];

            foreach ($allowedFields as $field) {
                if (isset($_POST[$field])) {
                    $updateData[$field] = sanitizeInput($_POST[$field]);
                }
            }
            
            // Validate email if provided
            if (!empty($updateData['email'])) {
                if (!validateEmail($updateData['email'])) {
                    $errors[] = 'Invalid email format.';
                } else {
                    // Check if email is already in use by another user
                    $sql = "SELECT id FROM Users WHERE email = ? AND id != ?";
                    $existingUser = getRecord($sql, [$updateData['email'], $userId]);
                    if ($existingUser) {
                        $errors[] = 'Email is already in use by another account.';
                    }
                }
            }
            
            // Handle password update separately if a new password is provided
            if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
                if (empty($_POST['confirm_new_password'])) {
                    $errors[] = 'Please confirm your new password.';
                } elseif ($_POST['new_password'] !== $_POST['confirm_new_password']) {
                    $errors[] = 'New passwords do not match.';
                } elseif (strlen($_POST['new_password']) < 8) {
                    $errors[] = 'New password must be at least 8 characters long.';
                } else {
                    // Verify current password
                    $sql = "SELECT password FROM Users WHERE id = ?";
                    $user = getRecord($sql, [$userId]);
                    if ($user && verifyPassword($_POST['current_password'], $user['password'])) {
                        $updateData['password'] = hashPassword($_POST['new_password']);
                    } else {
                        $errors[] = 'Incorrect current password.';
                    }
                }
            } elseif (!empty($_POST['new_password']) && empty($_POST['current_password'])) {
                 $errors[] = 'Current password is required to set a new password.';
            }


            // TODO: Handle profile_image upload if `profile_image` field is used.
            // This would involve checking $_FILES, validating the file, moving it to a secure location,
            // and storing the file path in $updateData['profile_image'].

            if (!empty($errors)) {
                $response = ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            } elseif (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $updated = updateRecord('Users', $updateData, 'id = ?', [$userId]);
                if ($updated) {
                    logActivity('profile_updated', "User ID $userId updated their profile.");
                    // If username or email changed, and they are used in session, update session.
                    if (isset($updateData['email']) && $_SESSION['username'] !== $updateData['email'] && strpos($_SESSION['username'], '@') !== false) { // Assuming email can be username
                         // $_SESSION['username'] = $updateData['email']; // Or re-fetch user data
                    }
                    $response = ['success' => true, 'message' => 'Profile updated successfully.'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update profile or no changes made.'];
                }
            } else {
                 $response = ['success' => false, 'message' => 'No data provided for update.'];
            }
            break;

        // Admin-only actions (add role check)
        case 'list_users': // Example: Admin action
            if (!hasRole('admin')) { // [cite: 6]
                 $response = ['success' => false, 'message' => 'Access denied.'];
                 break;
            }
            $sql = "SELECT id, username, email, first_name, last_name, role, is_active, last_login FROM Users ORDER BY username ASC"; // [cite: 5]
            $users = executeQuery($sql);
            $response = ['success' => true, 'data' => $users];
            break;
            
        case 'get_user': // Example: Admin action
             if (!hasRole('admin')) {
                 $response = ['success' => false, 'message' => 'Access denied.'];
                 break;
            }
            $userId = (int)($_GET['id'] ?? 0);
            if ($userId > 0) {
                $sql = "SELECT id, username, email, first_name, last_name, job_title, department, profile_image, role, is_active, last_login, created_at, updated_at FROM Users WHERE id = ?";
                $user = getRecord($sql, [$userId]);
                if ($user) {
                    $response = ['success' => true, 'data' => $user];
                } else {
                    $response = ['success' => false, 'message' => 'User not found.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid User ID.'];
            }
            break;

        case 'create_user': // Example: Admin action for creating users
            if (!hasRole('admin')) {
                 $response = ['success' => false, 'message' => 'Access denied.'];
                 break;
            }
            // Similar to registration handler but by an admin
            $data = [
                'username' => sanitizeInput($_POST['username'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
                'job_title' => sanitizeInput($_POST['job_title'] ?? ''),
                'department' => sanitizeInput($_POST['department'] ?? ''),
                'role' => sanitizeInput($_POST['role'] ?? 'user'), // [cite: 5]
                'is_active' => isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true, // [cite: 5]
            ];

            $errors = [];
            // Validations (similar to register.php)
            if (empty($data['username']) || strlen($data['username']) < 3) $errors[] = 'Username required (min 3 chars).';
            if (empty($data['email']) || !validateEmail($data['email'])) $errors[] = 'Valid email required.';
            if (empty($data['password']) || strlen($data['password']) < 8) $errors[] = 'Password required (min 8 chars).';
            
            // Check uniqueness for username and email
            $sql = "SELECT id FROM Users WHERE username = ? OR email = ?";
            $existingUser = getRecord($sql, [$data['username'], $data['email']]);
            if ($existingUser) $errors[] = 'Username or email already exists.';

            if (empty($errors)) {
                $data['password'] = hashPassword($data['password']);
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                $userId = insertRecord('Users', $data);
                if ($userId) {
                    logActivity('user_created_by_admin', "Admin created user '{$data['username']}' (ID $userId).", getCurrentUserId());
                    $response = ['success' => true, 'message' => 'User created successfully.', 'id' => $userId];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to create user.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
            }
            break;
            
        case 'update_user': // Example: Admin action for updating users
            if (!hasRole('admin')) {
                 $response = ['success' => false, 'message' => 'Access denied.'];
                 break;
            }
            $userIdToUpdate = (int)($_POST['id'] ?? 0);
            if ($userIdToUpdate <= 0) {
                 $response = ['success' => false, 'message' => 'Invalid User ID for update.'];
                 break;
            }
            
            $updateData = [];
            $adminAllowedFields = ['username', 'email', 'first_name', 'last_name', 'job_title', 'department', 'role', 'is_active'];
             foreach ($adminAllowedFields as $field) {
                if (isset($_POST[$field])) {
                    $updateData[$field] = sanitizeInput($_POST[$field]);
                    if($field === 'is_active') $updateData[$field] = (bool)$_POST[$field]; // Ensure boolean
                }
            }

            $errors = [];
            // Validate email if provided and changed
             if (!empty($updateData['email'])) {
                if (!validateEmail($updateData['email'])) {
                    $errors[] = 'Invalid email format.';
                } else {
                    $sql = "SELECT id FROM Users WHERE email = ? AND id != ?";
                    $existingUser = getRecord($sql, [$updateData['email'], $userIdToUpdate]);
                    if ($existingUser) $errors[] = 'Email is already in use by another account.';
                }
            }
            // Validate username if provided and changed
            if (!empty($updateData['username'])) {
                 $sql = "SELECT id FROM Users WHERE username = ? AND id != ?";
                 $existingUser = getRecord($sql, [$updateData['username'], $userIdToUpdate]);
                 if ($existingUser) $errors[] = 'Username is already in use by another account.';
            }

            // Handle password reset by admin (optional, ensure secure process)
            if(!empty($_POST['new_password_admin'])) {
                if(strlen($_POST['new_password_admin']) < 8) {
                    $errors[] = 'New password must be at least 8 characters.';
                } else {
                    $updateData['password'] = hashPassword($_POST['new_password_admin']);
                }
            }

            if (!empty($errors)) {
                $response = ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
            } elseif(!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                $updated = updateRecord('Users', $updateData, 'id = ?', [$userIdToUpdate]);
                 if ($updated) {
                    logActivity('user_updated_by_admin', "Admin updated user ID $userIdToUpdate.", getCurrentUserId());
                    $response = ['success' => true, 'message' => 'User updated successfully.'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update Users or no changes made.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'No data provided for update.'];
            }
            break;

        case 'delete_user': // Example: Admin action
            if (!hasRole('admin')) {
                 $response = ['success' => false, 'message' => 'Access denied.'];
                 break;
            }
            $userIdToDelete = (int)($_POST['id'] ?? 0);
             if ($userIdToDelete <= 0) {
                 $response = ['success' => false, 'message' => 'Invalid User ID for deletion.'];
                 break;
            }
            if ($userIdToDelete === getCurrentUserId()) { // Prevent admin from deleting self
                 $response = ['success' => false, 'message' => 'Cannot delete your own account.'];
                 break;
            }

            // Consider what happens to tasks assigned to this user.
            // ON DELETE SET NULL is used in schema for assigned_to, created_by [cite: 9, 11, 15]
            $user = getRecord("SELECT username FROM Users WHERE id = ?", [$userIdToDelete]);
            $deleted = deleteRecord('Users', 'id = ?', [$userIdToDelete]);
            if ($deleted) {
                logActivity('user_deleted_by_admin', "Admin deleted user '{$user['username']}' (ID $userIdToDelete).", getCurrentUserId());
                $response = ['success' => true, 'message' => 'User deleted successfully.'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete user.'];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid user action requested.'];
            break;
    }
    
    echo json_encode($response);
    exit;
}

// For non-AJAX scenarios, like rendering a profile page directly (less common for APIs)
// if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
//    $action = $_GET['action'] ?? 'view_profile'; // Default action
//    if ($action === 'view_profile' && isLoggedIn()) {
//        // Fetch user data and include a profile view template
//        // $userId = getCurrentUserId();
//        // $user = getRecord("SELECT id, username, email, first_name, last_name, job_title, department, profile_image FROM Users WHERE id = ?", [$userId]);
//        // include '../views/profile/view.php'; // Example path
//        exit;
//    }
// }
?>