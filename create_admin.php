<?php
/**
 * Create Admin User Script
 *
 * This script should be run ONCE to create the initial admin user.
 * DELETE THIS FILE FROM THE SERVER AFTER USE FOR SECURITY.
 */

// Define ROOT_PATH if not already defined (e.g., if this script is run directly)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/db.php';      // Assumes this includes db connection logic
require_once ROOT_PATH . '/includes/functions.php'; // For hashPassword()

echo "<pre>"; // For better readability of output

// Admin User Details (CHANGE THE PASSWORD!)
$admin_username = 'Ahmed';
$admin_email    = 'ahmed@workhub.gotoaus.com';
$admin_password = 'medo123My@'; // <<< CHANGE THIS TO A STRONG PASSWORD
$admin_firstname = 'Ahmed';
$admin_lastname  = 'Admin';
$admin_role      = 'admin';

// Check if admin user already exists
$sql_check = "SELECT id FROM Users WHERE username = ? OR email = ?";
$existing_user = getRecord($sql_check, [$admin_username, $admin_email]);

if ($existing_user) {
    echo "Admin user '{$admin_username}' or email '{$admin_email}' already exists.\n";
} else {
    // Hash the password
    // Ensure your hashPassword function is correctly defined in functions.php
    $hashed_password = hashPassword($admin_password);

    if (!$hashed_password) {
        die("Error: Password hashing failed. Check your hashPassword function and PHP version/extensions.\n");
    }

    $admin_data = [
        'username'     => $admin_username,
        'email'        => $admin_email,
        'password'     => $hashed_password,
        'first_name'   => $admin_firstname,
        'last_name'    => $admin_lastname,
        'role'         => $admin_role,
        'is_active'    => true, // Assuming you want the admin to be active
        'created_at'   => date('Y-m-d H:i:s'),
        'updated_at'   => date('Y-m-d H:i:s')
    ];

    $user_id = insertRecord('Users', $admin_data);

    if ($user_id) {
        echo "Admin user '{$admin_username}' created successfully with ID: {$user_id}\n";
        echo "Username: " . htmlspecialchars($admin_username) . "\n";
        echo "Password: (the one you set in the script: '" . htmlspecialchars($admin_password) ."')\n";
        echo "IMPORTANT: DELETE THIS SCRIPT (create_admin.php) FROM YOUR SERVER NOW!\n";
    } else {
        echo "Failed to create admin user.\n";
        // You might want to check database logs or enable more detailed error reporting from db.php temporarily
    }
}
echo "</pre>";
?>
