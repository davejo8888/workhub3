<?php
/**
 * MyWorkHub - Delete API
 * Handles delete operations for different entity types
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
    
    // Check required parameters
    if (!isset($_GET['type']) || !isset($_GET['id'])) {
        throw new Exception("Type and ID are required parameters");
    }
    
    $type = $_GET['type'];
    $id = (int)$_GET['id'];
    
    if ($id <= 0) {
        throw new Exception("Invalid ID");
    }
    
    switch ($type) {
        case 'period':
            // Check if the period exists
            $stmt = $db->prepare("SELECT * FROM Periods WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new Exception("Period not found");
            }
            
            // Check for tasks in this period
            $stmt = $db->prepare("SELECT COUNT(*) FROM MajorTasks WHERE period_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                throw new Exception("Cannot delete period that contains tasks. Please delete or reassign tasks first.");
            }
            
            // Delete the period
            $stmt = $db->prepare("DELETE FROM Periods WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['status' => 'success', 'message' => 'Period deleted successfully']);
            break;
            
        case 'majortask':
            // Check if the task exists
            $stmt = $db->prepare("SELECT * FROM MajorTasks WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new Exception("Task not found");
            }
            
            // Begin transaction
            $db->beginTransaction();
            
            try {
                // First delete all subtasks
                $stmt = $db->prepare("DELETE FROM SubTasks WHERE major_task_id = ?");
                $stmt->execute([$id]);
                
                // Then delete the major task
                $stmt = $db->prepare("DELETE FROM MajorTasks WHERE id = ?");
                $stmt->execute([$id]);
                
                $db->commit();
                
                echo json_encode(['status' => 'success', 'message' => 'Task and all subtasks deleted successfully']);
            } catch (Exception $e) {
                $db->rollBack();
                throw new Exception("Failed to delete task: " . $e->getMessage());
            }
            break;
            
        case 'subtask':
            // Check if the subtask exists
            $stmt = $db->prepare("SELECT major_task_id FROM SubTasks WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception("Subtask not found");
            }
            
            $majorTaskId = $result['major_task_id'];
            
            // Delete the subtask
            $stmt = $db->prepare("DELETE FROM SubTasks WHERE id = ?");
            $stmt->execute([$id]);
            
            // Update the parent task progress
            updateMajorTaskProgress($db, $majorTaskId);
            echo json_encode(['status' => 'success', 'message' => 'Subtask deleted successfully']);
            break;
            
        default:
            throw new Exception("Invalid type. Supported types: period, majortask, subtask");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Update the parent major task's progress based on subtask completion
 * @param PDO $db Database connection
 * @param int $majorTaskId Major task ID
 */
function updateMajorTaskProgress($db, $majorTaskId) {
    // Get all subtasks for this major task
    $stmt = $db->prepare("
        SELECT COUNT(*) as total, 
               SUM(percent_complete) as total_complete
        FROM SubTasks 
        WHERE major_task_id = ?
    ");
    $stmt->execute([$majorTaskId]);
    $result = $stmt->fetch();
    
    if ($result && $result['total'] > 0) {
        // Calculate average completion
        $avgCompletion = round($result['total_complete'] / $result['total']);
        
        // Update the major task
        $stmt = $db->prepare("
            UPDATE MajorTasks 
            SET percent_complete = ? 
            WHERE id = ?
        ");
        $stmt->execute([$avgCompletion, $majorTaskId]);
    }
}
?>