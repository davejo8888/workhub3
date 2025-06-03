<?php
/**
 * MyWorkHub - Session Management and Application Bootstrap
 *
 * This file should be included at the very beginning of all primary PHP entry scripts
 * (e.g., index.php, login.php, dashboard.php, API endpoints).
 * It handles:
 * - Secure session configuration.
 * - Loading the main application configuration (config.php).
 * - Loading core libraries (database, functions, authentication).
 * - Starting and managing the session.
 * - Basic session security measures.
 *
 * @author Dr. Ahmed AL-sadi (enhanced by AI)
 * @version 1.4 (Revised for clarity and correct loading order)
 */

// -----------------------------------------------------------------------------
// 1. CONFIGURE PHP SESSION SETTINGS (MUST be before session_start())
// -----------------------------------------------------------------------------
ini_set('session.cookie_httponly', 1);    // Prevent JavaScript access to session cookie
ini_set('session.use_only_cookies', 1); // Ensure session ID is only passed via cookies
ini_set('session.use_strict_mode', 1);  // Prevent session adoption of uninitialized IDs

// Determine if running over HTTPS for the 'secure' cookie flag
$is_https = false;
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) {
    $is_https = true;
} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $is_https = true; // Common for reverse proxies
}

if ($is_https) {
    ini_set('session.cookie_secure', 1); // Send cookie only over HTTPS
} else {
    // For local development over HTTP, setting secure to 0 is acceptable.
    // On a live production server, this MUST be 1 if HTTPS is used.
    // If HTTPS is used but PHP doesn't detect it, you might need to force this to 1.
    ini_set('session.cookie_secure', 0); // WARNING: Review for production if HTTPS is used but not detected
}
ini_set('session.cookie_samesite', 'Lax'); // CSRF protection: 'Lax' or 'Strict'. 'None' requires Secure attribute.

// -----------------------------------------------------------------------------
// 2. LOAD MAIN APPLICATION CONFIGURATION
// -----------------------------------------------------------------------------
// This path assumes session.php is in 'auth/' and config.php is in 'includes/'.
// ROOT_PATH will be defined within config.php.
$configPath = dirname(__DIR__) . '/includes/config.php'; // Correctly points to ROOT_PATH/includes/config.php

if (!file_exists($configPath)) {
    // Log critical error and stop execution if config is missing.
    $errorMsg = "CRITICAL FAILURE: Main configuration file (config.php) not found. Expected at: " . realpath(dirname(__DIR__) . '/includes/') . "/config.php. Application cannot start.";
    error_log($errorMsg);
    http_response_code(503); // Service Unavailable
    // Output a user-friendly message if possible, but avoid exposing paths.
    echo "A critical server configuration error occurred. Please contact support. (Error Code: SESS_CNF_MISSING)";
    exit;
}
require_once $configPath; // Loads constants like ROOT_PATH, SITE_URL, DB_HOST, SESSION_LIFETIME, etc.

// Verify ROOT_PATH was successfully defined by config.php
if (!defined('ROOT_PATH')) {
    $errorMsg = "CRITICAL FAILURE: ROOT_PATH constant was not defined after including config.php. Check config.php. Application cannot start.";
    error_log($errorMsg);
    http_response_code(503);
    echo "A critical server configuration error occurred. Please contact support. (Error Code: SESS_RP_MISSING)";
    exit;
}

// -----------------------------------------------------------------------------
// 3. LOAD CORE LIBRARIES (in correct order: DB -> Functions -> Auth)
// -----------------------------------------------------------------------------

// Load Database Class
$dbPath = ROOT_PATH . '/includes/db.php';
if (!file_exists($dbPath)) {
    error_log("CRITICAL FAILURE: Database library (db.php) not found. Path: $dbPath. Application cannot start.");
    http_response_code(503);
    echo "A critical server configuration error occurred. Please contact support. (Error Code: SESS_DB_LIB_MISSING)";
    exit;
}
require_once $dbPath; // Defines the Database class and potentially initializes $db

// Load General Helper Functions
$functionsPath = ROOT_PATH . '/includes/functions.php';
if (!file_exists($functionsPath)) {
    error_log("CRITICAL FAILURE: General functions library (functions.php) not found. Path: $functionsPath. Application cannot start.");
    http_response_code(503);
    echo "A critical server configuration error occurred. Please contact support. (Error Code: SESS_FUNC_LIB_MISSING)";
    exit;
}
require_once $functionsPath; // Defines sanitize(), redirect(), isLoggedIn(), etc.

// Load Authentication Functions
$authFunctionsPath = ROOT_PATH . '/includes/auth.php'; // Assuming your auth.php is in 'includes/'
if (!file_exists($authFunctionsPath)) {
    error_log("CRITICAL FAILURE: Authentication functions library (auth.php) not found. Path: $authFunctionsPath. Application cannot start.");
    http_response_code(503);
    echo "A critical server configuration error occurred. Please contact support. (Error Code: SESS_AUTH_LIB_MISSING)";
    exit;
}
require_once $authFunctionsPath; // Defines loginUser(), registerUser(), validateSession(), etc.

// -----------------------------------------------------------------------------
// 4. SET SESSION COOKIE PARAMETERS (MUST be before session_start())
// -----------------------------------------------------------------------------
// Uses SESSION_LIFETIME and SESSION_NAME from config.php

if (defined('SESSION_LIFETIME') && defined('SESSION_NAME')) {
    $cookieParams = [
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/', // Available to the entire domain
        'domain'   => '',  // Current domain. Set explicitly if subdomains are involved.
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax' // Should match ini_set
    ];

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieParams);
    } else {
        // Fallback for older PHP versions (less common now)
        session_set_cookie_params(
            $cookieParams['lifetime'],
            $cookieParams['path'] . '; samesite=' . $cookieParams['samesite'], // Manually append samesite
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }
    session_name(SESSION_NAME); // Set the custom session name
} else {
    error_log("CRITICAL WARNING: SESSION_LIFETIME or SESSION_NAME constants are not defined. Session cookies might use defaults. Check config.php.");
    // Application might still run but with default session cookie settings.
}

// -----------------------------------------------------------------------------
// 5. START SESSION (if not already started)
// -----------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    if (!session_start()) {
        error_log("CRITICAL FAILURE: session_start() failed. Check server configuration and permissions for session save path.");
        http_response_code(503);
        echo "A critical server error occurred with session initialization. Please contact support. (Error Code: SESS_START_FAIL)";
        exit;
    }
}

// -----------------------------------------------------------------------------
// 6. SESSION SECURITY ENHANCEMENTS
// -----------------------------------------------------------------------------

// Prevent session fixation: Regenerate ID for new sessions or if an old session ID is presented
if (!isset($_SESSION['session_initialized_time'])) {
    session_regenerate_id(true); // Regenerate ID and delete the old session file
    $_SESSION['session_initialized_time'] = time();
}

// CSRF Token Management: Ensure a CSRF token exists in the session
if (empty($_SESSION['csrf_token'])) {
    // Ensure generateToken() is available from functions.php
    if (function_exists('generateToken')) {
        $_SESSION['csrf_token'] = generateToken();
    } else {
        error_log("WARNING: generateToken() function not found. CSRF token cannot be generated. Check functions.php.");
        // Potentially set a static fallback, though less secure, or log and proceed cautiously.
        // $_SESSION['csrf_token'] = 'fallback_static_csrf_token_if_function_missing';
    }
}

// -----------------------------------------------------------------------------
// 7. VALIDATE ACTIVE SESSION (if user appears to be logged in)
// -----------------------------------------------------------------------------
// isLoggedIn() and validateSession() are defined in functions.php or auth.php

if (function_exists('isLoggedIn') && isLoggedIn()) {
    if (function_exists('validateSession') && !validateSession()) {
        // validateSession() should handle logging out or clearing session data if invalid (e.g., timeout)
        // It returns false if session is invalid (e.g., timed out and logoutUser was called within it).
        // If redirect() is available and current page is not login.php, redirect to login.
        if (function_exists('redirect') && defined('SITE_URL')) {
            // Avoid redirect loop if already on login page or if headers sent
            if (basename($_SERVER['PHP_SELF']) !== 'login.php' && !headers_sent()) {
                redirect(SITE_URL . 'login.php?session_expired=1'); // SITE_URL should have trailing slash
                exit; // Ensure script stops after redirect
            }
        } else {
             if (basename($_SERVER['PHP_SELF']) !== 'login.php' && !headers_sent()) {
                // Fallback redirect if redirect() or SITE_URL is not defined
                header('Location: login.php?session_expired=1'); // Adjust path if login.php is not in the root
                exit;
            }
        }
    }
}
?>
