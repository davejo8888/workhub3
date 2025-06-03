<?php
// generate_hash.php
// Make sure this script can access your config.php and functions.php
// Adjust paths if this script is not in the root of your 'workhub' directory.

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

require_once ROOT_PATH . '/includes/config.php';    // For HASH_SALT
require_once ROOT_PATH . '/includes/functions.php'; // For hashPassword()

$plainPassword = 'medo123My@'; // <<< SET YOUR DESIRED PASSWORD HERE

// Check if HASH_SALT is defined if your function uses it
if (strpos(file_get_contents(ROOT_PATH . '/includes/functions.php'), 'HASH_SALT') !== false && !defined('HASH_SALT')) {
    die("Error: HASH_SALT is used in hashPassword() but not defined in config.php. Please define it.");
}

$hashedPassword = hashPassword($plainPassword);

if ($hashedPassword) {
    echo "Plain Password: " . htmlspecialchars($plainPassword) . "<br>";
    echo "Hashed Password (copy this value): <pre>" . htmlspecialchars($hashedPassword) . "</pre>";
} else {
    echo "Password hashing failed. Check your PHP version, extensions, and the hashPassword function.";
}
?>