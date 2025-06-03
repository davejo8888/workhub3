<?php
// Set headers for plain text output
header('Content-Type: text/plain');

echo "MyWorkHub Config Finder\n";
echo "=====================\n\n";

// Define paths to check
$possible_paths = [
    __DIR__ . '/config.php',                    // Current directory
    __DIR__ . '/../config.php',                 // One level up
    $_SERVER['DOCUMENT_ROOT'] . '/config.php',  // Document root
    dirname($_SERVER['DOCUMENT_ROOT']) . '/config.php', // Above document root
    // Add other possible paths here
];

echo "Script location: " . __DIR__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

echo "Checking for config.php:\n";
foreach ($possible_paths as $path) {
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    
    echo "Path: $path\n";
    echo "  - Exists: " . ($exists ? 'Yes' : 'No') . "\n";
    echo "  - Readable: " . ($readable ? 'Yes' : 'No') . "\n";
    
    if ($exists && $readable) {
        echo "  - Content preview: ";
        $content = file_get_contents($path, false, null, 0, 100);
        echo preg_replace('/\$db_config\[[\'"]password[\'"]\]\s*=\s*[\'"].*?[\'"]/', '$db_config[\'password\'] = "***REDACTED***"', $content);
        echo "...\n";
    }
    
    echo "\n";
}

// Try to include config and test database connection
echo "Testing database connection:\n";
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        echo "Attempting to include: $path\n";
        
        // Clear any previous config
        if (isset($db_config)) {
            unset($db_config);
        }
        
        // Include the config
        include $path;
        
        if (isset($db_config)) {
            echo "  - Config loaded: Yes\n";
            echo "  - Host: " . $db_config['host'] . "\n";
            echo "  - Database: " . $db_config['database'] . "\n";
            
            try {
                $pdo = new PDO(
                    "mysql:host={$db_config['host']};dbname={$db_config['database']}",
                    $db_config['username'],
                    $db_config['password']
                );
                
                echo "  - Connection: Success\n";
                
                // Test tables
                $tables = ['Users', 'Periods', 'MajorTasks', 'SubTasks'];
                foreach ($tables as $table) {
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                        $count = $stmt->fetchColumn();
                        echo "  - $table table: $count records\n";
                    } catch (Exception $e) {
                        echo "  - $table table: Error - " . $e->getMessage() . "\n";
                    }
                }
                
                echo "\nThis is the correct config file! Use this path in your API files.\n";
                break;
            } catch (PDOException $e) {
                echo "  - Connection: Failed - " . $e->getMessage() . "\n\n";
            }
        } else {
            echo "  - Config loaded: No (db_config variable not found)\n\n";
        }
    }
}
?>
