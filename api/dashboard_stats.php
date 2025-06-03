<?php

// Added by repair script to define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__FILE__) . '/../'));
}
/**
 * Dashboard Statistics API
 * Provides statistical data for the dashboard
 */
require_once '../config.php';

// Require login
requireLogin();

// Set content type
header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    // Get active periods count
    $activePeriods = $db->fetchOne(
        "SELECT COUNT(*) as count FROM Periods 
         WHERE (user_id = :user_id OR is_public = 1) 
         AND start_date <= CURDATE() AND end_date >= CURDATE()",
        ['user_id' => $userId]
    );
    
    // Get open tasks count
    $openTasks = $db->fetchOne(
        "SELECT COUNT(*) as count FROM tasks t
         JOIN Periods p ON t.period_id = p.id
         WHERE (t.created_by = :user_id OR t.assigned_to = :user_id OR p.is_public = 1)
         AND t.status != 'completed'",
        ['user_id' => $userId]
    );
    
    // Get completed tasks count
    $completedTasks = $db->fetchOne(
        "SELECT COUNT(*) as count FROM tasks t
         JOIN Periods p ON t.period_id = p.id
         WHERE (t.created_by = :user_id OR t.assigned_to = :user_id OR p.is_public = 1)
         AND t.status = 'completed'",
        ['user_id' => $userId]
    );
    
    // Get upcoming deadlines count (tasks due in the next 7 days)
    $upcomingDeadlines = $db->fetchOne(
        "SELECT COUNT(*) as count FROM tasks t
         JOIN Periods p ON t.period_id = p.id
         WHERE (t.created_by = :user_id OR t.assigned_to = :user_id OR p.is_public = 1)
         AND t.status != 'completed'
         AND t.deadline IS NOT NULL
         AND t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
        ['user_id' => $userId]
    );
    
    // Calculate completion percentage
    $totalTasks = ($openTasks['count'] ?? 0) + ($completedTasks['count'] ?? 0);
    $completedPercentage = $totalTasks > 0 
        ? round(($completedTasks['count'] / $totalTasks) * 100) 
        : 0;
    
    // Compile statistics
    $stats = [
        'active_periods' => $activePeriods['count'] ?? 0,
        'open_tasks' => $openTasks['count'] ?? 0,
        'completed_tasks' => $completedTasks['count'] ?? 0,
        'upcoming_deadlines' => $upcomingDeadlines['count'] ?? 0,
        'completed_percentage' => $completedPercentage,
        'total_tasks' => $totalTasks
    ];
    
    echo json_encode(['success' => true, 'stats' => $stats]);
    
} catch (Exception $e) {
    http_response_code(500);
    logError('Dashboard stats API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>