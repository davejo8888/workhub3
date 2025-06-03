<?php
/**
 * WorkHub2 Comprehensive Diagnostic & Repair Tool
 *
 * This script will:
 * 1. Diagnose all issues in your WorkHub application
 * 2. Provide options to automatically fix the identified problems
 * 3. Generate detailed reports of changes made
 *
 * IMPORTANT: Make a backup of your application before running this script!
 */

// Set to true to actually make repairs (default is just diagnostic)
$REPAIR_MODE = true;

// Start output buffering to capture all output
ob_start();

// Set up error reporting for diagnostics
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Basic header
echo "========================================================\n";
echo "WorkHub2 Comprehensive Diagnostic & Repair Tool\n";
echo "========================================================\n\n";
echo "Mode: " . ($REPAIR_MODE ? "REPAIR (changes will be made)" : "DIAGNOSTIC ONLY (no changes)") . "\n\n";

// Track issues found
$issuesFound = [];
$fixesApplied = [];
$filesWithIssues = []; // Unique files that had issues or were modified

// Define document root and application path
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'); // Added null coalescing for safety in CLI
$appRoot = dirname(__FILE__);

echo "System Information:\n";
echo "- PHP Version: " . phpversion() . "\n";
if (!empty($docRoot)) {
    echo "- Document Root: $docRoot\n";
} else {
    echo "- Document Root: Not available (likely running in CLI)\n";
}
echo "- Application Root: $appRoot\n\n";

// ================ DIRECTORY STRUCTURE CHECK ================
echo "========================================================\n";
echo "CHECKING DIRECTORY STRUCTURE\n";
echo "========================================================\n\n";

$requiredDirs = [
    'api',
    'auth',
    'includes',
    'assets',
    'assets/css',
    'assets/js',
];

foreach ($requiredDirs as $dir) {
    $path = "$appRoot/$dir";
    if (!is_dir($path)) {
        echo "❌ Missing directory: $dir\n";
        $issuesFound[] = "Directory structure: Missing $dir directory";
        $filesWithIssues[] = $path; // Technically a path, but represents a file system entity with an issue

        if ($REPAIR_MODE) {
            if (mkdir($path, 0755, true)) {
                echo "  ✅ Created directory: $dir\n";
                $fixesApplied[] = "Created missing directory: $dir";
            } else {
                echo "  ❌ Failed to create directory: $dir\n";
                $issuesFound[] = "Directory structure: Failed to create $dir directory";
            }
        } else {
            echo "  ℹ️ DIAGNOSTIC: Would create directory: $dir\n";
        }
    } else {
        echo "✅ Found directory: $dir\n";
    }
}

// ================ CONFIG FILE CHECK ================
echo "\n========================================================\n";
echo "CHECKING CONFIGURATION FILES\n";
echo "========================================================\n\n";

$configPaths = [
    "$appRoot/config.php",
    "$appRoot/includes/config.php"
];

$configFound = false;
$configPath = null;

foreach ($configPaths as $path) {
    if (file_exists($path)) {
        echo "✅ Found config file: $path\n";
        $configFound = true;
        $configPath = $path;
        break;
    }
}

if (!$configFound) {
    echo "❌ Could not find config.php in common locations\n";
    $issuesFound[] = "Configuration: config.php not found in expected locations";
    // No specific file to add to $filesWithIssues if not found
} else {
    $configContents = file_get_contents($configPath);
    // Check if ROOT_PATH definition and usage seems okay.
    // This original check is a bit heuristic. A more robust check might look for define('ROOT_PATH', ...)
    if (strpos($configContents, 'ROOT_PATH') !== false &&
        strpos($configContents, 'define') !== false && // Ensure it's likely a definition
        strpos($configContents, 'ROOT_PATH') > strpos($configContents, 'define')) { // Check if ROOT_PATH is part of a define statement
        echo "✅ config.php appears to define ROOT_PATH\n";
    } else {
        echo "⚠️ config.php may not be defining ROOT_PATH properly or it's missing.\n";
        $issuesFound[] = "Configuration: ROOT_PATH definition issue or missing in config.php";
        $filesWithIssues[] = $configPath;

        if ($REPAIR_MODE) {
            // Create backup
            if (copy($configPath, $configPath . '.bak')) {
                echo "  ℹ️ Backup of config.php created at $configPath.bak\n";
                // Add ROOT_PATH check/definition if not robustly found.
                // This repair adds a check for ROOT_PATH, assuming it should be defined *before* config.php is included,
                // or config.php itself should define it. The provided fix adds a runtime check.
                // A more common pattern is for config.php to define ROOT_PATH itself.
                // Let's refine the fix to add a ROOT_PATH definition if a simple one isn't found.

                if (strpos($configContents, "define('ROOT_PATH'") === false && strpos($configContents, 'define("ROOT_PATH"') === false) {
                    $newContents = "<?php\n// Added by repair script\nif (!defined('ROOT_PATH')) {\n    define('ROOT_PATH', dirname(__FILE__)); // Or a more appropriate path\n}\n\n";
                    $newContents .= preg_replace('/^<\?php\s*/i', '', $configContents); // Remove existing opening tag if present at start

                    if (file_put_contents($configPath, $newContents)) {
                        echo "  ✅ Added ROOT_PATH definition to config.php\n";
                        $fixesApplied[] = "Added ROOT_PATH definition to config.php";
                    } else {
                        echo "  ❌ Failed to modify config.php to add ROOT_PATH definition\n";
                        $issuesFound[] = "Configuration: Failed to write ROOT_PATH definition to config.php";
                    }
                } else {
                     echo "  ℹ️ ROOT_PATH definition seems to exist. Manual check recommended.\n";
                }
            } else {
                echo "  ❌ Failed to create backup for config.php. No changes made.\n";
                $issuesFound[] = "Configuration: Failed to backup config.php";
            }
        } else {
            echo "  ℹ️ DIAGNOSTIC: Would attempt to add ROOT_PATH definition/check to config.php\n";
        }
    }
}

// ================ FILE INCLUSION CHECK ================
echo "\n========================================================\n";
echo "CHECKING FILE INCLUSIONS\n";
echo "========================================================\n\n";

$phpFiles = [];
function scanForPhpFiles($dir) {
    global $phpFiles; // Use global
    $items = scandir($dir);
    if ($items === false) return; // Could not scan directory

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            scanForPhpFiles($path);
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $phpFiles[] = $path;
        }
    }
}

scanForPhpFiles($appRoot);
echo "Found " . count($phpFiles) . " PHP files to check\n";

$inclusionIssues = []; // Files including config.php without ROOT_PATH definition
$sessionPathIssues = []; // Files with incorrect session.php path

foreach ($phpFiles as $file) {
    if ($file === __FILE__) continue; // Skip the diagnostic script itself

    $contents = file_get_contents($file);
    if ($contents === false) {
        echo "⚠️ Could not read file: $file\n";
        continue;
    }
    $relPath = str_replace($appRoot . '/', '', $file);

    // Check for config.php inclusion without ROOT_PATH (heuristic: define('ROOT_PATH'...) not found before include)
    if (preg_match('/(include|require)(_once)?\s*\(?\s*[\'"].*config\.php[\'"]\s*\)?/i', $contents) &&
        !preg_match('/define\s*\(\s*[\'"]ROOT_PATH[\'"]/', $contents)) {
        // Further check: ensure the config.php inclusion is not within a class/function that might have ROOT_PATH defined in an outer scope.
        // This simple regex check is broad.
        $inclusionIssues[] = $file;
    }

    // Check for incorrect session.php path
    if (preg_match('/(include|require)(_once)?\s*\(?\s*[\'"]\/?home\/gotoa957\/auth\/session\.php[\'"]\s*\)?/i', $contents)) {
        $sessionPathIssues[] = $file;
    }
}

if (count($inclusionIssues) > 0) {
    echo "\nFiles potentially including config.php without ROOT_PATH definition: " . count($inclusionIssues) . "\n";
    foreach ($inclusionIssues as $file) {
        $relPath = str_replace($appRoot . '/', '', $file);
        echo "❌ $relPath\n";
        $issuesFound[] = "File inclusion: $relPath may include config.php without prior ROOT_PATH definition";
        $filesWithIssues[] = $file;

        if ($REPAIR_MODE) {
            if (copy($file, $file . '.bak')) {
                echo "  ℹ️ Backup of $relPath created at $file.bak\n";
                $contents = file_get_contents($file);
                // Determine the correct relative path for ROOT_PATH definition
                // Example: if file is $appRoot/api/some.php, ROOT_PATH should be dirname(__FILE__)/../
                // if file is $appRoot/some.php, ROOT_PATH should be dirname(__FILE__)
                $depth = substr_count(str_replace($appRoot, '', dirname($file)), '/');
                $rootPathDefine = "define('ROOT_PATH', realpath(dirname(__FILE__) . '/".str_repeat('../', $depth)."'));";
                if ($depth == 0) { // File is in appRoot
                     $rootPathDefine = "define('ROOT_PATH', dirname(__FILE__));";
                }


                // Prepend ROOT_PATH definition before including config.php
                // This is a common pattern, assumes config.php relies on ROOT_PATH
                $pattern = '/(<\?php\s*)/i'; // Find the first opening PHP tag
                $replacement = "$1\n// Added by repair script to define ROOT_PATH\nif (!defined('ROOT_PATH')) {\n    " . $rootPathDefine . "\n}\n";
                $newContents = preg_replace($pattern, $replacement, $contents, 1); // Replace only the first occurrence

                if ($newContents !== $contents && $newContents !== null) {
                    if (file_put_contents($file, $newContents)) {
                        echo "  ✅ Attempted to add ROOT_PATH definition to $relPath\n";
                        $fixesApplied[] = "Attempted ROOT_PATH definition in $relPath";
                    } else {
                        echo "  ❌ Failed to modify $relPath to add ROOT_PATH\n";
                        $issuesFound[] = "File inclusion: Failed to write ROOT_PATH definition to $relPath";
                    }
                } else {
                    echo "  ⚠️ Could not find a place to insert ROOT_PATH definition or no change made in $relPath (manual check needed).\n";
                }
            } else {
                echo "  ❌ Failed to create backup for $relPath. No changes made.\n";
                $issuesFound[] = "File inclusion: Failed to backup $relPath";
            }
        } else {
            echo "  ℹ️ DIAGNOSTIC: Would attempt to add ROOT_PATH definition to $relPath\n";
        }
    }
} else {
    echo "\n✅ No files found directly including config.php without a preceding ROOT_PATH definition (based on current check).\n";
}


if (count($sessionPathIssues) > 0) {
    echo "\nFiles with incorrect session.php path: " . count($sessionPathIssues) . "\n";
    foreach ($sessionPathIssues as $file) {
        $relPath = str_replace($appRoot . '/', '', $file);
        echo "❌ $relPath uses hardcoded old session.php path\n";
        $issuesFound[] = "Path issue: $relPath has incorrect/hardcoded path to session.php";
        $filesWithIssues[] = $file;

        if ($REPAIR_MODE) {
            if (copy($file, $file . '.bak')) {
                echo "  ℹ️ Backup of $relPath created at $file.bak\n";
                $contents = file_get_contents($file);
                $pattern = '/((include|require)(_once)?\s*\(?\s*[\'"])\/?home\/gotoa957\/auth\/session\.php([\'"]\s*\)?)/i';
                $replacement = '${1}ROOT_PATH . \'/auth/session.php\'${4}'; // Uses captured groups
                $newContents = preg_replace($pattern, $replacement, $contents);

                if ($newContents !== $contents && $newContents !== null) {
                    if (file_put_contents($file, $newContents)) {
                        echo "  ✅ Fixed session.php path in $relPath to use ROOT_PATH\n";
                        $fixesApplied[] = "Fixed session.php path in $relPath";
                    } else {
                        echo "  ❌ Failed to modify $relPath for session.php path\n";
                        $issuesFound[] = "Path issue: Failed to write session.php path fix to $relPath";
                    }
                } else {
                     echo "  ⚠️ No changes made to $relPath for session.php path (pattern might not have matched).\n";
                }
            } else {
                 echo "  ❌ Failed to create backup for $relPath. No changes made.\n";
                 $issuesFound[] = "Path issue: Failed to backup $relPath";
            }
        } else {
            echo "  ℹ️ DIAGNOSTIC: Would fix session.php path in $relPath to use ROOT_PATH\n";
        }
    }
} else {
    echo "\n✅ No files found with the specified incorrect session.php path.\n";
}


// ================ TABLE CASE ISSUES CHECK (Generic) ================
echo "\n========================================================\n";
echo "CHECKING FOR GENERIC TABLE CASE ISSUES IN SQL QUERIES\n";
echo "========================================================\n\n";

$tableNameIssues = [];
// Ensure these are the actual correct casings as per your database schema
$tablesToLower = ['users', 'periods', 'majortasks', 'subtasks', 'activity_log'];
$tablesCorrectCase = ['Users', 'Periods', 'MajorTasks', 'SubTasks', 'ActivityLog']; // PascalCase example
$tableMapping = array_combine($tablesToLower, $tablesCorrectCase);

$genericTableCaseFilesFound = [];

foreach ($phpFiles as $file) {
    if ($file === __FILE__) continue;
    $contents = file_get_contents($file);
    if ($contents === false) continue;

    $relPath = str_replace($appRoot . '/', '', $file);
    $fileHasIssue = false;

    foreach ($tableMapping as $lower => $upper) {
        if ($lower === $upper) continue; // Skip if casing is already "correct" or same

        // Regex to find table names ensuring they are standalone words (e.g., avoid matching 'userstory')
        // Looks for FROM table, JOIN table, INTO table, UPDATE table
        $sqlKeywords = ['FROM', 'JOIN', 'INTO', 'UPDATE'];
        foreach ($sqlKeywords as $keyword) {
            if (preg_match('/\b' . $keyword . '\s+' . preg_quote($lower, '/') . '\b/i', $contents)) {
                if (!$fileHasIssue) {
                    $genericTableCaseFilesFound[] = $file;
                    $fileHasIssue = true;
                }
                $issuesFound[] = "Table case: $relPath may use lowercase '$lower' instead of '$upper' near '$keyword'";
                break; // Found an issue with this table in this file for one keyword
            }
        }
    }
}


if (count($genericTableCaseFilesFound) > 0) {
    echo "Files with potential generic table case issues: " . count(array_unique($genericTableCaseFilesFound)) . "\n";
    foreach (array_unique($genericTableCaseFilesFound) as $file) {
        $relPath = str_replace($appRoot . '/', '', $file);
        echo "❌ $relPath (potential mixed case table names)\n";
        $filesWithIssues[] = $file;

        if ($REPAIR_MODE) {
            if (copy($file, $file . '.bak')) {
                echo "  ℹ️ Backup of $relPath created at $file.bak\n";
                $currentContents = file_get_contents($file);
                $newContents = $currentContents;

                foreach ($tableMapping as $lower => $upper) {
                    if ($lower === $upper) continue;
                    // More precise replacement patterns
                    $patterns = [
                        '/(\bFROM\s+)' . preg_quote($lower, '/') . '(\b)/i',
                        '/(\bJOIN\s+)' . preg_quote($lower, '/') . '(\b)/i',
                        '/(\bINTO\s+)' . preg_quote($lower, '/') . '(\b)/i',
                        '/(\bUPDATE\s+)' . preg_quote($lower, '/') . '(\b)/i',
                    ];
                    $replacements = [
                        '$1' . $upper . '$2',
                        '$1' . $upper . '$2',
                        '$1' . $upper . '$2',
                        '$1' . $upper . '$2',
                    ];
                    $newContents = preg_replace($patterns, $replacements, $newContents);
                }

                if ($newContents !== $currentContents && $newContents !== null) {
                    if (file_put_contents($file, $newContents)) {
                        echo "  ✅ Fixed generic table case issues in $relPath\n";
                        $fixesApplied[] = "Fixed generic table name cases in $relPath";
                    } else {
                        echo "  ❌ Failed to modify $relPath for generic table cases\n";
                        $issuesFound[] = "Table case: Failed to write fixes to $relPath";
                    }
                } else {
                    echo "  ⚠️ No changes made to $relPath for generic table cases (already correct or pattern mismatch).\n";
                }
            } else {
                echo "  ❌ Failed to create backup for $relPath. No changes made.\n";
                $issuesFound[] = "Table case: Failed to backup $relPath";
            }
        } else {
            echo "  ℹ️ DIAGNOSTIC: Would fix generic table case issues in $relPath\n";
        }
    }
} else {
    echo "✅ No files found with specified generic table case issues.\n";
}


// ================ CHECKING FOR SINGULAR 'user' TABLE REFERENCES ================
echo "\n========================================================\n";
echo "CHECKING FOR SINGULAR 'user' TABLE REFERENCES\n";
echo "========================================================\n\n";

$singularUserTableIssueFiles = [];
$singularUserFixedCount = 0;

foreach ($phpFiles as $file) {
    if ($file === __FILE__) continue;
    $content = file_get_contents($file);
    if ($content === false) continue;

    $relPath = str_replace($appRoot . '/', '', $file);
    $needsFix = false;

    if (preg_match('/\b(FROM|JOIN|UPDATE|INTO)\s+user\b/i', $content)) {
        $singularUserTableIssueFiles[] = $file;
        $issuesFound[] = "Table case: $relPath uses singular 'user' table reference. Should be 'Users'.";
        $filesWithIssues[] = $file;
        $needsFix = true;
    }

    if ($needsFix && $REPAIR_MODE) {
        if (copy($file, $file . '.bak')) {
            echo "  ℹ️ Backup of $relPath created at $file.bak\n";
            $new_content = preg_replace('/\b(FROM|JOIN|UPDATE|INTO)\s+user\b/i', '$1 Users', $content);
            if ($new_content !== $content && $new_content !== null) {
                if (file_put_contents($file, $new_content)) {
                    echo "  ✅ Fixed 'user' to 'Users' table references in $relPath\n";
                    $fixesApplied[] = "Fixed 'user' to 'Users' table reference in $relPath";
                    $singularUserFixedCount++;
                } else {
                    echo "  ❌ Failed to modify $relPath to fix 'user' table reference.\n";
                    $issuesFound[] = "Table case: Failed to write 'user' fix to $relPath";
                }
            } else {
                 echo "  ⚠️ No changes made to $relPath for 'user' to 'Users' (pattern might not have matched or already correct).\n";
            }
        } else {
            echo "  ❌ Failed to create backup for $relPath. No changes made for 'user' fix.\n";
            $issuesFound[] = "Table case: Failed to backup $relPath for 'user' fix";
        }
    } elseif ($needsFix) {
        echo "❌ $relPath (uses singular 'user' table)\n";
        echo "  ℹ️ DIAGNOSTIC: Would fix 'user' to 'Users' in $relPath\n";
    }
}

if (empty($singularUserTableIssueFiles)) {
    echo "✅ No lowercase singular 'user' table references found that need fixing to 'Users'.\n";
} elseif ($REPAIR_MODE && $singularUserFixedCount > 0) {
    echo "✅ Total files fixed for 'user' to 'Users': $singularUserFixedCount\n";
} elseif (!$REPAIR_MODE && !empty($singularUserTableIssueFiles)) {
    echo "Found " . count(array_unique($singularUserTableIssueFiles)) . " files with 'user' table references that would be fixed in REPAIR_MODE.\n";
}


// ================ CHECK API ENDPOINTS ================
echo "\n========================================================\n";
echo "CHECKING API ENDPOINTS\n";
echo "========================================================\n\n";

$apiEndpoints = [
    'api/periods.php',
    'api/tasks.php',
    'api/subtasks.php',
    'api/users.php' // This specific endpoint will have creation logic if missing
];

foreach ($apiEndpoints as $endpoint) {
    $path = "$appRoot/$endpoint";
    $relEndpointPath = $endpoint;

    if (file_exists($path)) {
        echo "✅ Found API endpoint: $relEndpointPath\n";
        $filesWithIssues[] = $path; // Add to filesWithIssues if we are checking it.

        // Check for ROOT_PATH issues in existing API files
        $contents = file_get_contents($path);
        if ($contents !== false &&
            !preg_match('/define\s*\(\s*[\'"]ROOT_PATH[\'"]/', $contents) &&
            (strpos($contents, 'config.php') !== false)) { // If it includes config.php but doesn't define ROOT_PATH

            echo "❌ API endpoint $relEndpointPath includes config.php without known ROOT_PATH definition\n";
            $issuesFound[] = "API issue: $relEndpointPath includes config.php without ROOT_PATH";


            if ($REPAIR_MODE) {
                if (copy($path, $path . '.bak')) {
                    echo "  ℹ️ Backup of $relEndpointPath created at $path.bak\n";
                    // ROOT_PATH for files in api/ typically needs to go up one level.
                    $rootPathDefinition = "<?php\n// Added by repair script\nif(!defined('ROOT_PATH')) {\n    define('ROOT_PATH', realpath(dirname(__FILE__) . '/../'));\n}\n\n";
                    $newContents = $rootPathDefinition . preg_replace('/^<\?php\s*/i', '', $contents);


                    if (file_put_contents($path, $newContents)) {
                        echo "  ✅ Added ROOT_PATH definition to $relEndpointPath\n";
                        $fixesApplied[] = "Added ROOT_PATH definition to $relEndpointPath";
                    } else {
                        echo "  ❌ Failed to modify $relEndpointPath to add ROOT_PATH\n";
                        $issuesFound[] = "API issue: Failed to write ROOT_PATH fix to $relEndpointPath";
                    }
                } else {
                    echo "  ❌ Failed to create backup for $relEndpointPath. No changes made.\n";
                    $issuesFound[] = "API issue: Failed to backup $relEndpointPath";
                }
            } else {
                echo "  ℹ️ DIAGNOSTIC: Would add ROOT_PATH definition to $relEndpointPath\n";
            }
        }

    } else {
        echo "❌ Missing API endpoint: $relEndpointPath\n";
        $issuesFound[] = "API issue: Missing endpoint $relEndpointPath";
        $filesWithIssues[] = $path; // The path that is missing

        // Specific fix for missing api/users.php
        if ($endpoint === 'api/users.php' && $REPAIR_MODE) {
            echo "  Attempting to create missing endpoint: $relEndpointPath\n";
            $users_api_code = <<<PHP
<?php
// Ensure ROOT_PATH is defined, typically points to the application root.
// This script assumes it's being placed in an 'api' subdirectory.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/../'));
}

require_once ROOT_PATH . '/includes/config.php'; // This should establish \$conn or similar DB connection

header('Content-Type: application/json');
\$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// Check if the database connection variable (e.g., \$conn or \$pdo from config.php) is available
// Adjust '\$conn' if your config.php uses a different variable (e.g., \$pdo, \$db)
if (!isset(\$conn) && isset(\$pdo)) { // Example: Try with \$pdo if \$conn is not set
    \$db_connection = \$pdo;
} elseif (isset(\$conn)) {
    \$db_connection = \$conn;
} else {
    http_response_code(500);
    \$response['message'] = 'Database connection not found after including config.php.';
    echo json_encode(\$response);
    exit;
}

\$users = [];
try {
    // Assuming 'Users' is the correct table name and config.php provides a mysqli connection as \$conn
    // or a PDO connection as \$pdo.
    if (\$db_connection instanceof PDO) {
        \$stmt = \$db_connection->query("SELECT id, username, email FROM Users"); // Adjust columns as needed
        if (\$stmt) {
            \$users = \$stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
             throw new Exception(\$db_connection->errorInfo()[2] ?? 'Failed to prepare statement');
        }
    } elseif (\$db_connection instanceof mysqli) {
        \$sql = "SELECT id, username, email FROM Users"; // Adjust columns as needed
        \$result = \$db_connection->query(\$sql);
        if (\$result) {
            while (\$row = \$result->fetch_assoc()) {
                \$users[] = \$row;
            }
        } else {
            throw new Exception(\$db_connection->error ?? 'Failed to execute query');
        }
    } else {
         throw new Exception('Unsupported database connection type.');
    }

    \$response['status'] = 'success';
    \$response['data'] = \$users;
    unset(\$response['message']); // Remove default error message on success

} catch (Exception \$e) {
    http_response_code(500); // Internal Server Error
    \$response['message'] = 'Failed to retrieve users.';
    \$response['error_detail'] = \$e->getMessage();
}

echo json_encode(\$response);
PHP;
            // Ensure parent directory 'api' exists
            if (!is_dir(dirname($path))) {
                if (mkdir(dirname($path), 0755, true)) {
                     echo "  ✅ Created parent directory: " . dirname($relEndpointPath) . "\n";
                } else {
                    echo "  ❌ Failed to create parent directory for $relEndpointPath. Cannot create file.\n";
                    $issuesFound[] = "API issue: Failed to create parent directory for $relEndpointPath";
                    continue; // Skip trying to create this file
                }
            }

            if (file_put_contents($path, $users_api_code)) {
                echo "  ✅ Created missing endpoint: $relEndpointPath with basic template.\n";
                $fixesApplied[] = "Created missing API endpoint: $relEndpointPath";
            } else {
                echo "  ❌ Failed to create missing endpoint: $relEndpointPath\n";
                $issuesFound[] = "API issue: Failed to write content for $relEndpointPath";
            }
        } elseif ($endpoint === 'api/users.php' && !$REPAIR_MODE) {
            echo "  ℹ️ DIAGNOSTIC: Would create missing endpoint $relEndpointPath\n";
        }
    }
}


// ================ DATABASE CONNECTION TEST ================
echo "\n========================================================\n";
echo "ATTEMPTING DATABASE CONNECTION TEST\n";
echo "========================================================\n\n";

$dbConfig = null;
if ($configFound && $configPath !== null) {
    $tmpConfigContents = file_get_contents($configPath); // Renamed to avoid conflict
    if ($tmpConfigContents !== false) {
        // Look for common database config patterns (adjust if your variables are different)
        preg_match('/\$db_host\s*=\s*[\'"](.+?)[\'"]/', $tmpConfigContents, $hostMatches);
        preg_match('/\$db_name\s*=\s*[\'"](.+?)[\'"]/', $tmpConfigContents, $nameMatches);
        preg_match('/\$db_user\s*=\s*[\'"](.+?)[\'"]/', $tmpConfigContents, $userMatches);
        preg_match('/\$db_pass\s*=\s*[\'"](.*?)[\'"]/', $tmpConfigContents, $passMatches); // Allow empty password

        if (!empty($hostMatches[1]) && !empty($nameMatches[1]) && !empty($userMatches[1])) {
            $dbConfig = [
                'host' => $hostMatches[1],
                'name' => $nameMatches[1],
                'user' => $userMatches[1],
                'pass' => $passMatches[1] ?? '' // Use captured password or empty string
            ];

            echo "Found database configuration in $configPath:\n";
            echo "- Host: {$dbConfig['host']}\n";
            echo "- Database: {$dbConfig['name']}\n";
            echo "- User: {$dbConfig['user']}\n";
            echo "- Password: " . (empty($dbConfig['pass']) ? "empty" : "***") . "\n\n";

            try {
                $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                echo "✅ Successfully connected to database!\n";

                // Check table existence and case sensitivity (using the corrected names)
                $tablesToTest = $tablesCorrectCase; // Use the PascalCase names from earlier
                // Also add the lowercase versions to see if they exist too (potential issue)
                $tablesToTest = array_merge($tablesToTest, $tablesToLower);
                $tablesToTest = array_unique($tablesToTest);


                echo "\nTesting table existence (case sensitivity may matter on some systems):\n";
                foreach ($tablesToTest as $table) {
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table`"); // Use backticks for safety
                        $stmt->execute();
                        $count = $stmt->fetchColumn();
                        echo "  ✅ Table `$table` exists and has $count records.\n";
                    } catch (PDOException $e) {
                        echo "  ❌ Table `$table` does not exist or is inaccessible. Error: " . $e->getMessage() . "\n";
                        // Log as an issue if this table is one of the primary 'correctCase' ones
                        if (in_array($table, $tablesCorrectCase, true)) {
                            $issuesFound[] = "Database: Correctly cased table `$table` not found or inaccessible.";
                        } elseif (in_array($table, $tablesToLower, true) && !in_array(ucfirst($table), $tablesCorrectCase, true) && !in_array(strtolower($table), $tablesCorrectCase, true)) {
                            // If a lowercase table exists and its PascalCase version is NOT what we expect, it might be an issue.
                            // This logic is getting complex; the earlier table case check in files is usually more critical.
                            // For now, just noting its absence if it's one of the expected correct names.
                        }
                    }
                }

            } catch (PDOException $e) {
                echo "❌ Database connection failed: " . $e->getMessage() . "\n";
                $issuesFound[] = "Database: Connection failed - " . $e->getMessage();
            }
        } else {
            echo "❌ Could not extract complete database configuration from $configPath (host, name, or user missing).\n";
            $issuesFound[] = "Database: Could not extract configuration from $configPath";
        }
    } else {
        echo "❌ Could not read config file at $configPath to extract database credentials.\n";
        $issuesFound[] = "Database: Failed to read $configPath for credentials.";
    }
} else {
    echo "❌ Cannot test database connection - config.php not found or path not determined.\n";
    if (!$configFound) $issuesFound[] = "Database: config.php not found, so connection test skipped.";
}

// ================ SUMMARY ================
echo "\n========================================================\n";
echo "DIAGNOSTIC SUMMARY\n";
echo "========================================================\n\n";

$uniqueIssues = array_values(array_unique($issuesFound));
$uniqueFilesWithIssues = array_values(array_unique($filesWithIssues));
$uniqueFixesApplied = array_values(array_unique($fixesApplied));


echo "Total unique issues found: " . count($uniqueIssues) . "\n";
if (count($uniqueIssues) > 0) {
    echo "\nIssues list:\n";
    foreach ($uniqueIssues as $index => $issue) {
        echo "  " . ($index + 1) . ". $issue\n";
    }

    if (!empty($uniqueFilesWithIssues)) {
        echo "\nFiles with issues or modified: " . count($uniqueFilesWithIssues) . "\n";
        foreach ($uniqueFilesWithIssues as $file) {
            // Ensure we are displaying relative paths if $appRoot is part of it
            $displayPath = (strpos($file, $appRoot . '/') === 0) ? str_replace($appRoot . '/', '', $file) : $file;
            echo "  - " . $displayPath . "\n";
        }
    }

    if ($REPAIR_MODE) {
        echo "\nFixes applied: " . count($uniqueFixesApplied) . "\n";
        if (count($uniqueFixesApplied) > 0) {
            foreach ($uniqueFixesApplied as $index => $fix) {
                echo "  " . ($index + 1) . ". $fix\n";
            }
        } else {
            echo "  No automatic fixes were applied in this run.\n";
        }
    } else {
        echo "\nThis was a DIAGNOSTIC run. To apply fixes, set \$REPAIR_MODE = true at the top of this script.\n";
    }
} else {
    echo "✅ No issues found based on the checks performed!\n";
}

// ================ RECOMMENDATIONS ================
echo "\n========================================================\n";
echo "RECOMMENDED ACTIONS\n";
echo "========================================================\n\n";

if (count($uniqueIssues) > 0) {
    echo "1. Review the 'Issues list' and 'Files with issues' sections above carefully.\n";
    if (!$REPAIR_MODE) {
        echo "2. **IMPORTANT**: Make a backup of your entire application and database before proceeding.\n";
        echo "3. To attempt automatic repairs, run this script again with \$REPAIR_MODE = true.\n";
        echo "4. After running in REPAIR mode, thoroughly test your application's functionality.\n";
        echo "5. Manually inspect any files listed under 'Files with issues' where automatic repair was not possible or sufficient.\n";
    } else {
        echo "2. Verify that the 'Fixes applied' have resolved the corresponding 'Issues list'.\n";
        echo "3. **Thoroughly test your application** to ensure all functionalities are working as expected.\n";
        echo "4. Check any '.bak' files created for original versions if you need to revert a change.\n";
        echo "5. Some issues might require manual intervention. Review any remaining unresolved issues.\n";
    }

    echo "\nSpecific recommendations based on findings:\n";
    // Using a unique separator
    $issueMessages = implode(" | ", $uniqueIssues);

    if (strpos($issueMessages, "config.php not found") !== false) {
        echo "- Critical: `config.php` is missing. Ensure it exists in the application root or `/includes/` and contains correct database credentials and any necessary constants like `ROOT_PATH`.\n";
    }
    if (strpos($issueMessages, "ROOT_PATH") !== false) {
        echo "- `ROOT_PATH` issues: Ensure `ROOT_PATH` is consistently defined and used for file inclusions. It should point to your application's root directory.\n";
    }
    if (strpos($issueMessages, "Table case:") !== false || strpos($issueMessages, "singular 'user'") !== false) {
        echo "- Table Naming: SQL queries should use the correct casing for table names (e.g., 'Users' instead of 'users' or 'user' if your database/OS is case-sensitive). Verify your database schema's actual table names.\n";
    }
    if (strpos($issueMessages, "Path issue: ") !== false || strpos($issueMessages, "session.php") !== false) {
        echo "- File Paths: Update hardcoded file inclusion paths to use relative paths or `ROOT_PATH` for better portability.\n";
    }
    if (strpos($issueMessages, "Database: Connection failed") !== false) {
        echo "- Database Connection: Verify database server status, credentials in `config.php` (host, user, password, database name), and network connectivity.\n";
    }
    if (strpos($issueMessages, "Missing endpoint") !== false) {
        echo "- API Endpoints: Ensure all required API endpoint files exist and are accessible.\n";
    }
     if (strpos($issueMessages, "Missing directory") !== false) {
        echo "- Directory Structure: Create any missing required directories for the application to function correctly.\n";
    }

} else {
    echo "✅ Your application appears to be in good health based on these checks!\n";
    echo "\nRecommendations for ongoing maintenance:\n";
    echo "1. Keep regular backups of your database and codebase.\n";
    echo "2. Monitor server and PHP error logs for any new or intermittent issues.\n";
    echo "3. If not already in use, consider implementing a version control system (e.g., Git) for managing code changes.\n";
    echo "4. Periodically review and update dependencies.\n";
}

// Get the buffered output
$output = ob_get_clean();

// Display output
echo $output;

// Option to save the diagnostic report
$reportDir = "$appRoot/reports";
if (!is_dir($reportDir)) {
    mkdir($reportDir, 0755, true);
}
$reportFile = "$reportDir/workhub_diagnostic_" . date('Y-m-d_H-i-s') . ".txt";

if (file_put_contents($reportFile, $output)) {
    echo "\nDiagnostic report saved to: $reportFile\n";
} else {
    echo "\nError: Could not save diagnostic report to $reportFile. Please check permissions.\n";
}

?>