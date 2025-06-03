<?php
/**
 * Authentication helper functions
 */

// Register a new user
function registerUser($userData) {
    $db = Database::getInstance();
    
    try {
        // Validate required fields
        $requiredFields = ['full_name', 'username', 'email', 'password', 'department', 'role'];
        $errors = validateRequired($userData, $requiredFields);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate email
        if (!validateEmail($userData['email'])) {
            $errors['email'] = 'Invalid email address';
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if username already exists
        $existingUser = $db->fetchOne("SELECT id FROM Users WHERE username = :username", 
            ['username' => $userData['username']]);
        
        if ($existingUser) {
            return ['success' => false, 'errors' => ['username' => 'Username already exists']];
        }
        
        // Check if email already exists
        $existingEmail = $db->fetchOne("SELECT id FROM Users WHERE email = :email", 
            ['email' => $userData['email']]);
        
        if ($existingEmail) {
            return ['success' => false, 'errors' => ['email' => 'Email already exists']];
        }
        
        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        
        // Get role ID
        $roleId = getRoleIdByName($userData['role']);
        
        if (!$roleId) {
            return ['success' => false, 'errors' => ['role' => 'Invalid role']];
        }
        
        // Prepare user data for insertion
        $user = [
            'full_name' => $userData['full_name'],
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password' => $hashedPassword,
            'department' => $userData['department'],
            'role_id' => $roleId,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];
        
        // Insert user
        $userId = $db->insert('users', $user);
        
        if (!$userId) {
            return ['success' => false, 'errors' => ['db' => 'Failed to create user account']];
        }
        
        // Log activity
        $db->logActivity('registration', 'New user registered: ' . $userData['username'], $userId, $userId, 'user');
        
        return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful'];
        
    } catch (Exception $e) {
        logError('Registration error: ' . $e->getMessage());
        return ['success' => false, 'errors' => ['system' => 'An error occurred during registration']];
    }
}

// Authenticate user
function loginUser($username, $password) {
    $db = Database::getInstance();
    
    try {
        // Get user by username or email
        $user = $db->fetchOne(
            "SELECT id, username, email, password, full_name, role_id, status FROM Users 
            WHERE (username = :username OR email = :email)",
            ['username' => $username, 'email' => $username]
        );
        
        // Check if user exists
        if (!$user) {
            $db->logActivity('login_failed', 'Failed login attempt for \'' . $username . '\'', null, null, 'auth');
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Check user status
        if ($user['status'] !== 'active') {
            $db->logActivity('login_failed', 'Login attempt for inactive account: ' . $username, null, $user['id'], 'user');
            return ['success' => false, 'message' => 'Account is not active'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            $db->logActivity('login_failed', 'Failed login attempt for \'' . $username . '\'', null, $user['id'], 'user');
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Update password hash if needed
        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => HASH_COST])) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
            $db->update('users', ['password' => $hashedPassword], 'id = :id', ['id' => $user['id']]);
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID
        session_regenerate_id(true);
        
        // Log activity
        $db->logActivity('login', 'User \'' . $user['username'] . '\' logged in.', $user['id'], $user['id'], 'user');
        
        return ['success' => true, 'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role_id' => $user['role_id']
        ]];
        
    } catch (Exception $e) {
        logError('Login error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during login'];
    }
}

// Log out user
function logoutUser() {
    $db = Database::getInstance();
    
    try {
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $username = $_SESSION['username'];
            
            // Log activity before destroying session
            $db->logActivity('logout', 'User \'' . $username . '\' logged out.', $userId, $userId, 'user');
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        return ['success' => true, 'message' => 'Logout successful'];
        
    } catch (Exception $e) {
        logError('Logout error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during logout'];
    }
}

// Get role ID by name
function getRoleIdByName($roleName) {
    $db = Database::getInstance();
    $role = $db->fetchOne("SELECT id FROM roles WHERE name = :name", ['name' => $roleName]);
    
    return $role ? $role['id'] : null;
}

// Check if user session is valid
function validateSession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
        return false;
    }
    
    if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
        // Session expired
        logoutUser();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    $sql = "SELECT u.id, u.username, u.full_name, u.email, u.department, 
                   u.status, u.created_at, r.name AS role_name
            FROM Users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = :user_id";
            
    return $db->fetchOne($sql, ['user_id' => $userId]);
}

// Require authentication
function requireLogin() {
    if (!isLoggedIn() || !validateSession()) {
        // Store the requested URL to redirect after login
        $_SESSION['redirect_after_login'] = getCurrentUrl();
        redirect(SITE_URL . '/login.php');
    }
}

// Check if user has permission to access a resource
function requirePermission($permission) {
    requireLogin();
    
    if (!hasPermission($permission)) {
        http_response_code(403);
        die('Access denied. You do not have permission to access this resource.');
    }
}
?>