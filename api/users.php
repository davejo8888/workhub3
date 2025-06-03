<?php
// Ensure ROOT_PATH is defined, typically points to the application root.
// This script assumes it's being placed in an 'api' subdirectory.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/../'));
}

require_once ROOT_PATH . '/includes/config.php'; // This should establish $conn or similar DB connection

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// Check if the database connection variable (e.g., $conn or $pdo from config.php) is available
// Adjust '$conn' if your config.php uses a different variable (e.g., $pdo, $db)
if (!isset($conn) && isset($pdo)) { // Example: Try with $pdo if $conn is not set
    $db_connection = $pdo;
} elseif (isset($conn)) {
    $db_connection = $conn;
} else {
    http_response_code(500);
    $response['message'] = 'Database connection not found after including config.php.';
    echo json_encode($response);
    exit;
}

$users = [];
try {
    // Assuming 'Users' is the correct table name and config.php provides a mysqli connection as $conn
    // or a PDO connection as $pdo.
    if ($db_connection instanceof PDO) {
        $stmt = $db_connection->query("SELECT id, username, email FROM Users"); // Adjust columns as needed
        if ($stmt) {
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
             throw new Exception($db_connection->errorInfo()[2] ?? 'Failed to prepare statement');
        }
    } elseif ($db_connection instanceof mysqli) {
        $sql = "SELECT id, username, email FROM Users"; // Adjust columns as needed
        $result = $db_connection->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        } else {
            throw new Exception($db_connection->error ?? 'Failed to execute query');
        }
    } else {
         throw new Exception('Unsupported database connection type.');
    }

    $response['status'] = 'success';
    $response['data'] = $users;
    unset($response['message']); // Remove default error message on success

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response['message'] = 'Failed to retrieve users.';
    $response['error_detail'] = $e->getMessage();
}

echo json_encode($response);