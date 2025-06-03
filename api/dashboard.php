<?php
/**
 * MyWorkHub - Dashboard API
 * Provides summary data for the dashboard
 */

// Set headers
header('Content-Type: application/json');

// Include configuration
$config_path = __DIR__ . '/../config.php';

if (!file_exists($config_path)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Configuration file (config.php) not found.',
        'path_checked' => $config_path
    ]);
    exit;
}

require_once $config_path;

try {
    $db = get_db_connection();
    
    // Get dashboard statistics
    $stats = [];
    
    // Period statistics
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total_periods,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_periods,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_periods
        FROM Periods
    ");
    $stats['periods'] = $stmt->fetch();
    
    // Task statistics
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'To Do' THEN 1 ELSE 0 END) as todo_tasks,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN status = 'On Hold' THEN 1 ELSE 0 END) as on_hold_tasks,
            AVG(percent_complete) as avg_completion
        FROM MajorTasks
    ");
    $stats['tasks'] = $stmt->fetch();
    
    // Subtask statistics
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total_subtasks,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_subtasks,
            AVG(percent_complete) as avg_completion
        FROM SubTasks
    ");
    $stats['subtasks'] = $stmt->fetch();
    
    // Upcoming deadlines (next 7 days)
    $stmt = $db->query("
        SELECT 
            'major' as task_type,
            id,
            task_name,
            deadline,
            status,
            priority,
            DATEDIFF(deadline, CURDATE()) as days_remaining
        FROM MajorTasks 
        WHERE deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND status NOT IN ('Completed', 'Cancelled')
        
        UNION ALL
        
        SELECT 
            'sub' as task_type,
            id,
            task_name,
            deadline,
            status,
            priority,
            DATEDIFF(deadline, CURDATE()) as days_remaining
        FROM SubTasks 
        WHERE deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND status NOT IN ('Completed', 'Cancelled')
        
        ORDER BY days_remaining ASC, priority DESC
    ");
    $stats['upcoming_deadlines'] = $stmt->fetchAll();
    
    // Overdue tasks
    $stmt = $db->query("
        SELECT 
            'major' as task_type,
            id,
            task_name,
            deadline,
            status,
            priority,
            DATEDIFF(CURDATE(), deadline) as days_overdue
        FROM MajorTasks 
        WHERE deadline < CURDATE()
        AND status NOT IN ('Completed', 'Cancelled')
        
        UNION ALL
        
        SELECT 
            'sub' as task_type,
            id,
            task_name,
            deadline,
            status,
            priority,
            DATEDIFF(CURDATE(), deadline) as days_overdue
        FROM SubTasks 
        WHERE deadline < CURDATE()
        AND status NOT IN ('Completed', 'Cancelled')
        
        ORDER BY days_overdue DESC, priority DESC
    ");
    $stats['overdue_tasks'] = $stmt->fetchAll();
    
    // Recent activity (last 10 tasks created or updated)
    $stmt = $db->query("
        SELECT 
            'major' as task_type,
            id,
            task_name,
            status,
            created_at,
            updated_at
        FROM MajorTasks 
        ORDER BY updated_at DESC 
        LIMIT 10
    ");
    $stats['recent_activity'] = $stmt->fetchAll();
    
    echo json_encode(['status' => 'success', 'data' => $stats]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>