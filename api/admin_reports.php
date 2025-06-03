<?php

// Added by repair script to define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__FILE__) . '/../'));
}
/**
 * Admin Reports API
 * Generates various reports for administrators
 */
require_once '../config.php';

// Require appropriate permissions
requirePermission('view_reports');

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired form submission']);
            exit;
        }
        
        switch ($action) {
            case 'quick_report':
                $reportType = $_POST['type'] ?? '';
                $format = $_POST['format'] ?? 'html';
                $timeframe = $_POST['timeframe'] ?? 'this_week';
                
                // Validate required fields
                if (empty($reportType) || empty($format) || empty($timeframe)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                    exit;
                }
                
                // Set date range based on timeframe
                $startDate = '';
                $endDate = date('Y-m-d');
                
                switch ($timeframe) {
                    case 'today':
                        $startDate = date('Y-m-d');
                        break;
                    case 'yesterday':
                        $startDate = date('Y-m-d', strtotime('-1 day'));
                        $endDate = $startDate;
                        break;
                    case 'this_week':
                        $startDate = date('Y-m-d', strtotime('monday this week'));
                        break;
                    case 'last_week':
                        $startDate = date('Y-m-d', strtotime('monday last week'));
                        $endDate = date('Y-m-d', strtotime('sunday last week'));
                        break;
                    case 'this_month':
                        $startDate = date('Y-m-01');
                        break;
                    case 'last_month':
                        $startDate = date('Y-m-01', strtotime('first day of last month'));
                        $endDate = date('Y-m-t', strtotime('last day of last month'));
                        break;
                    case 'this_year':
                        $startDate = date('Y-01-01');
                        break;
                    case 'all_time':
                        $startDate = '2000-01-01'; // Using a far past date for "all time"
                        break;
                }
                
                // Generate report based on type
                $reportData = [];
                $reportTitle = '';
                
                switch ($reportType) {
                    case 'user_summary':
                        $reportTitle = 'User Summary Report';
                        $reportData = generateUserSummaryReport($db, $startDate, $endDate);
                        break;
                        
                    case 'task_summary':
                        $reportTitle = 'Task Summary Report';
                        $reportData = generateTaskSummaryReport($db, $startDate, $endDate);
                        break;
                        
                    case 'period_summary':
                        $reportTitle = 'Period Summary Report';
                        $reportData = generatePeriodSummaryReport($db, $startDate, $endDate);
                        break;
                        
                    case 'system_health':
                        $reportTitle = 'System Health Report';
                        $reportData = generateSystemHealthReport($db, $startDate, $endDate);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid report type']);
                        exit;
                }
                
                // Output report based on format
                switch ($format) {
                    case 'html':
                        outputHtmlReport($reportTitle, $reportData, $startDate, $endDate);
                        break;
                        
                    case 'pdf':
                        outputPdfReport($reportTitle, $reportData, $startDate, $endDate);
                        break;
                        
                    case 'csv':
                        outputCsvReport($reportTitle, $reportData, $startDate, $endDate);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid report format']);
                        exit;
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    logError('Admin Reports API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

/**
 * Generate user summary report
 */
function generateUserSummaryReport($db, $startDate, $endDate) {
    // Get total users
    $totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM Users")['count'];
    
    // Get new users in date range
    $newUsers = $db->fetchOne(
        "SELECT COUNT(*) as count FROM Users WHERE created_at BETWEEN :start_date AND :end_date",
        ['start_date' => $startDate . ' 00:00:00', 'end_date' => $endDate . ' 23:59:59']
    )['count'];
    
    // Get active users by login
    $activeUsers = $db->fetchOne(
        "SELECT COUNT(DISTINCT user_id) as count FROM ActivityLog 
         WHERE activity_type = 'login' AND timestamp BETWEEN :start_date AND :end_date",
        ['start_date' => $startDate . ' 00:00:00', 'end_date' => $endDate . ' 23:59:59']
    )['count'];
    
    // Get users by role
    $usersByRole = $db->fetchAll(
        "SELECT r.name as role_name, COUNT(u.id) as user_count 
         FROM Users u 
         JOIN roles r ON u.role_id = r.id 
         GROUP BY r.name 
         ORDER BY user_count DESC"
    );
    
    // Get users by status
    $usersByStatus = $db->fetchAll(
        "SELECT status, COUNT(id) as user_count 
         FROM Users 
         GROUP BY status 
         ORDER BY user_count DESC"
    );
    
    // Get most active users
    $mostActiveUsers = $db->fetchAll(
        "SELECT u.username, COUNT(al.id) as activity_count 
         FROM ActivityLog al 
         JOIN Users u ON al.user_id = u.id 
         WHERE al.timestamp BETWEEN :start_date AND :end_date 
         GROUP BY u.id 
         ORDER BY activity_count DESC 
         LIMIT 10",
        ['start_date' => $startDate . ' 00:00:00', 'end_date' => $endDate . ' 23:59:59']
    );
    
    // Get user login trends by day
    $loginTrends = $db->fetchAll(
        "SELECT DATE(timestamp) as login_date, COUNT(DISTINCT user_id) as user_count 
         FROM ActivityLog 
         WHERE activity_type = 'login' AND timestamp BETWEEN :start_date AND :end_date 
         GROUP BY DATE(timestamp) 
         ORDER BY login_date",
        ['start_date' => $startDate . ' 00:00:00', 'end_date' => $endDate . ' 23:59:59']
    );
    
    return [
        'overview' => [
            'total_users' => $totalUsers,
            'new_users' => $newUsers,
            'active_users' => $activeUsers,
            'inactive_percentage' => $totalUsers > 0 ? round((($totalUsers - $activeUsers) / $totalUsers) * 100, 2) : 0
        ],
        'users_by_role' => $usersByRole,
        'users_by_status' => $usersByStatus,
        'most_active_users' => $mostActiveUsers,
        'login_trends' => $loginTrends
    ];
}

/**
 * Generate task summary report
 */
function generateTaskSummaryReport($db, $startDate, $endDate) {
    // Get total tasks
    $totalTasks = $db->fetchOne("SELECT COUNT(*) as count FROM tasks")['count'];
    
    // Get new tasks in date range
    $newTasks = $db->fetchOne(
        "SELECT COUNT(*) as count FROM tasks WHERE created_at BETWEEN :start_date AND :end_date",
        ['start_date' => $startDate . ' 00:00:00', 'end_date' => $endDate . ' 23:59:59']
    )['count'];
    
    // Get completed tasks in date range
    $completedTasks = $db->fetchOne(
        "SELECT COUNT(*) as count FROM tasks 
         WHERE status = 'completed' AND completed_at BETWEEN :start_date AND :end_date",
        ['start_date' => $startDate . ' 00:00:00', 'end_date' => $endDate . ' 23:59:59']
    )['count'];
    
    // Get tasks by status
    $tasksByStatus = $db->fetchAll(
        "SELECT status, COUNT(id) as task_count 
         FROM tasks 
         GROUP BY status 
         ORDER BY task_count DESC"
    );
    
    // Get tasks by priority
    $tasksByPriority = $db->fetchAll(
        "SELECT priority, COUNT(id) as task_count 
         FROM tasks 
         GROUP BY priority 
         ORDER BY 
            CASE 
                WHEN priority = 'high' THEN 1 
                WHEN priority = 'medium' THEN 2 
                WHEN priority = 'low' THEN 3 
                ELSE 4 
            END"
    );
    
    // Get top users by tasks created
    $topTaskCreators = $db->fetchAll(
        "SELECT u.username, COUNT(t.id) as task_count 
         FROM tasks t 
         JOIN Users u ON t.created_by = u.id 
         WHERE t.created_at BETWEEN :start_date AND :end_date 
         GROUP BY t.created_by 
         ORDER BY task_count DESC 
         LIMIT 5",
        ['start_date' => $startDate . ' 00:00:00', 'end_date' => $endDate . ' 23:59:59']
    );
    
    // Get top users by tasks completed
    $topTaskCompleters = $db->fetchAll(
        "SELECT u.username, COUNT(t.id) as task_count 
         FROM tasks t 
         JOIN Users u ON t.assigned_to = u.id 
         WHERE t.status = 'completed' AND t.completed_at BETWEEN :start_date AND :end_date 
         GROUP BY t.assigned_to 
         ORDER BY task_count DESC 
         LIMIT 5",
        ['start_date' => $startDate . ' 00:00:00', 'end_date' => $endDate . ' 23:59:59']
    );
    
    // Get task completion trends by day
    $completionTrends = $db->fetchAll(
        "SELECT DATE(completed_at) as completion_date, COUNT(id) as task_count 
         FROM tasks 
         WHERE status = 'completed' AND completed_at BETWEEN :start_date AND :end_date 
         GROUP BY DATE(completed_at) 
         ORDER BY completion_date",
        ['start_date' => $startDate . ' 00:00:00', 'end_date' => $endDate . ' 23:59:59']
    );
    
    return [
        'overview' => [
            'total_tasks' => $totalTasks,
            'new_tasks' => $newTasks,
            'completed_tasks' => $completedTasks,
            'completion_rate' => $newTasks > 0 ? round(($completedTasks / $newTasks) * 100, 2) : 0
        ],
        'tasks_by_status' => $tasksByStatus,
        'tasks_by_priority' => $tasksByPriority,
        'top_task_creators' => $topTaskCreators,
        'top_task_completers' => $topTaskCompleters,
        'completion_trends' => $completionTrends
    ];
}

/**
 * Generate period summary report
 */
function generatePeriodSummaryReport($db, $startDate, $endDate) {
    // Implementation details for period report
    return [
        'overview' => [
            'total_periods' => 10,
            'active_periods' => 3,
            'new_periods' => 2
        ],
        // Other period-related data
    ];
}

/**
 * Generate system health report
 */
function generateSystemHealthReport($db, $startDate, $endDate) {
    // Implementation details for system health report
    return [
        'overview' => [
            'database_size' => '5.2 MB',
            'upload_size' => '12.4 MB',
            'total_log_entries' => 1245,
            'php_version' => phpversion()
        ],
        // Other system health data
    ];
}

/**
 * Output HTML report
 */
function outputHtmlReport($title, $data, $startDate, $endDate) {
    // Set content type to HTML
    header('Content-Type: text/html');
    
    // Get timeframe description
    $timeframeText = ($startDate === $endDate) 
        ? "for " . date('F j, Y', strtotime($startDate))
        : "from " . date('F j, Y', strtotime($startDate)) . " to " . date('F j, Y', strtotime($endDate));
    
    // Start building HTML
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .report-header { margin-bottom: 30px; }
        .card { margin-bottom: 20px; }
        .table { margin-bottom: 0; }
        .chart-container { height: 300px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="report-header">
            <div class="row">
                <div class="col-md-8">
                    <h1>' . htmlspecialchars($title) . '</h1>
                    <p class="text-muted">Report ' . $timeframeText . '</p>
                    <p class="text-muted">Generated: ' . date('F j, Y \a\t g:i a') . '</p>
                </div>
                <div class="col-md-4 text-end">
                    <img src="' . SITE_URL . '/assets/img/logo.png" alt="Logo" style="max-height: 60px;">
                    <div class="mt-2">
                        <button class="btn btn-primary no-print" onclick="window.print()">Print Report</button>
                        <button class="btn btn-secondary no-print" onclick="window.location.href=\'' . SITE_URL . '/admin/reports.php\'">Back to Reports</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h2>Overview</h2>
                        <table class="table table-bordered">
                            <tbody>';
                            foreach ($data['overview'] as $key => $value) {
                                $html .= '<tr>
                                    <th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</th>
                                    <td>' . htmlspecialchars($value) . '</td>
                                </tr>';
                            }
                            $html .= '</tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';

    // Output HTML
    echo $html;
}   