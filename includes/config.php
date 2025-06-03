<?php
/**
 * MyWorkHub - Main Configuration File
 *
 * This file contains all the critical configuration settings for the application.
 * It should be included at the very beginning of any script that needs access
 * to these settings or initiates core functionalities.
 * This is the single, authoritative configuration file for the application.
 *
 * @author Dr. Ahmed AL-sadi (enhanced by AI)
 * @version 1.4 (Revised for clarity and loading order)
 */

// -----------------------------------------------------------------------------
// ROOT PATH DEFINITION
// -----------------------------------------------------------------------------
/**
 * ROOT_PATH
 * Absolute path to the main application directory.
 * This config file is in 'includes/', so ROOT_PATH is its parent directory.
 * This ensures that ROOT_PATH is always correctly set relative to this file's location.
 */
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__)); // Assumes config.php is in an 'includes' subdirectory of the root
}

// -----------------------------------------------------------------------------
// ERROR REPORTING & ENVIRONMENT
// -----------------------------------------------------------------------------

/**
 * Environment Mode
 * Set to 'development' for detailed error reporting during development.
 * Set to 'production' to suppress errors and log them instead for live sites.
 */
define('ENVIRONMENT', 'development'); // Options: 'development', 'production'

if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    // For production, ensure a proper error logging mechanism is set up:
    // ini_set('log_errors', 1);
    // ini_set('error_log', ROOT_PATH . '/logs/php_error.log'); // Ensure this path exists and is writable
}

/**
 * Default Timezone
 * Set this to your server's or application's primary timezone.
 * See: https://www.php.net/manual/en/timezones.php
 */
date_default_timezone_set('Australia/Sydney');

// -----------------------------------------------------------------------------
// PATHS & URLS (Dependent on ROOT_PATH)
// -----------------------------------------------------------------------------

/**
 * Site URL
 * The full base URL of your application, with a trailing slash.
 * Example: 'https://www.yourdomain.com/workhub/' or 'http://localhost/workhub/'
 * It's crucial this is correct for redirects and asset linking.
 */
// Attempt to auto-detect protocol and host if not hardcoded, but hardcoding is often more reliable.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Fallback for CLI or misconfigured server
$script_name = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']); // Gets the directory path
// Assuming your app is directly in workhub.gotoaus.com, SITE_URL would be $protocol . $host . '/'
// If it's in a subdirectory like /myworkhub/, it would be $protocol . $host . '/myworkhub/'
define('SITE_URL', $protocol . $host . '/'); // Adjust if your app is in a subdirectory

/**
 * Assets URL
 * URL to your public assets folder (CSS, JS, images).
 */
define('ASSETS_URL', SITE_URL . 'assets/');

// -----------------------------------------------------------------------------
// SITE CONFIGURATION
// -----------------------------------------------------------------------------

define('SITE_NAME', 'MyWorkHub');
define('SITE_EMAIL', 'admin@workhub.gotoaus.com'); // For administrative purposes

// -----------------------------------------------------------------------------
// DATABASE CONFIGURATION
// -----------------------------------------------------------------------------
// These constants are used by includes/db.php (Database class)

define('DB_HOST', 'localhost');
define('DB_NAME', 'gotoa957_my_work_hub_db'); // From your SQL dump
define('DB_USER', 'gotoa957_admin');        // From your SQL dump
define('DB_PASS', 'medo123My@');            // From your SQL dump - consider environment variables for sensitive data
define('DB_CHARSET', 'utf8mb4');

// -----------------------------------------------------------------------------
// SESSION CONFIGURATION CONSTANTS
// -----------------------------------------------------------------------------
// These constants are used by auth/session.php

define('SESSION_LIFETIME', 3600);       // Session lifetime in seconds (1 hour)
define('SESSION_NAME', 'MyWorkHubSess'); // Custom session name

// -----------------------------------------------------------------------------
// SECURITY CONSTANTS
// -----------------------------------------------------------------------------

/**
 * Secret Key / Salt
 * IMPORTANT: Change this to a unique, cryptographically secure random string for your application!
 * Generate one from: https://randomkeygen.com/
 */
define('SECRET_KEY', 'CyFz5rLfmoWpAcfYGHuyEHDz5YEPT6C0U'); // !!! REPLACE THIS !!!

define('HASH_COST', 10); // Cost factor for bcrypt password hashing

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// -----------------------------------------------------------------------------
// FILE UPLOAD CONSTANTS (Example - adjust as needed)
// -----------------------------------------------------------------------------

define('UPLOAD_PATH', ROOT_PATH . '/uploads/'); // Ensure 'uploads' directory exists and is writable
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024);   // 10MB in bytes
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip');


// -----------------------------------------------------------------------------
// IMPORTANT: DO NOT REQUIRE/INCLUDE FUNCTION LIBRARIES OR START SESSIONS HERE
// -----------------------------------------------------------------------------
// Core function libraries (db.php, functions.php, auth.php) and session_start()
// will be handled by 'auth/session.php' to ensure correct loading order and
// that all session parameters are set *before* the session is initiated.
// This file's sole responsibility is to define configurations and constants.

?>
