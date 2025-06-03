<?php
// Added by repair script
define('ROOT_PATH', dirname(dirname(__FILE__)));

/**
 * Tasks API
 * Handles CRUD operations for tasks
 */
require_once '../config.php';

// Require login
requireLogin();

// Set content type
header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    // Handle different HTTP methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get all tasks accessible by the user
            $sql = "SELECT t.*, u.full_name as created_by_name, p.name as period_name 
                    FROM tasks t 
                    JOIN Users u ON t.created_by = u.id
                    JOIN Periods p ON t.period_id = p.id
                    WHERE t.created_by = :user_id OR t.assigned_to = :user_id OR p.is_public = 1
                    ORDER BY t.deadline ASC, t.priority DESC";
            
            $tasks = $db->fetchAll($sql, ['user_id' => $userId]);
            
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'POST':
            // Check action parameter
            $action = $_GET['action'] ?? 'create';
            
            // Check CSRF token
            $token = $_POST['csrf_token'] ?? '';
            if (!verifyCSRFToken($token)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid or expired form submission']);
                exit;
            }
            
            switch ($action) {
                case 'create':
                    // Validate required fields
                    $requiredFields = ['title', 'period_id'];
                    $errors = validateRequired($_POST, $requiredFields);
                    
                    if (!empty($errors)) {
                        echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Please fill all required fields']);
                        exit;
                    }
                    
                    // Sanitize inputs
                    $title = sanitize($_POST['title']);
                    $description = sanitize($_POST['description'] ?? '');
                    $periodId = (int)$_POST['period_id'];
                    $priority = sanitize($_POST['priority'] ?? 'medium');
                    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
                    $assignedTo = isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : $userId;
                    
                    // Validate period access
                    $period = $db->fetchOne(
                        "SELECT * FROM Periods WHERE id = :id AND (user_id = :user_id OR is_public = 1)", 
                        ['id' => $periodId, 'user_id' => $userId]
                    );
                    
                    if (!$period) {
                        echo json_encode([
                            'success' => false, 
                            'errors' => ['period_id' => 'You do not have access to this period'],
                            'message' => 'Invalid period selected'
                        ]);
                        exit;
                    }
                    
                    // Insert task
                    $task = [
                        'title' => $title,
                        'description' => $description,
                        'period_id' => $periodId,
                        'status' => 'not_started',
                        'priority' => $priority,
                        'deadline' => $deadline,
                        'created_by' => $userId,
                        'assigned_to' => $assignedTo,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $taskId = $db->insert('tasks', $task);
                    
                    if (!$taskId) {
                        throw new Exception('Failed to create task');
                    }
                    
                    // Log activity
                    $db->logActivity('task_created', 'Created new task: ' . $title, $userId, $taskId, 'task');
                    
                    echo json_encode(['success' => true, 'task_id' => $taskId, 'message' => 'Task created successfully']);
                    break;
                    
                case 'update':
                    // Validate task ID
                    $taskId = (int)$_POST['task_id'] ?? 0;
                    if (!$taskId) {
                        echo json_encode(['success' => false, 'message' => 'Task ID is required']);
                        exit;
                    }
                    
                    // Check task access
                    $task = $db->fetchOne(
                        "SELECT * FROM tasks WHERE id = :id AND (created_by = :user_id OR assigned_to = :user_id)", 
                        ['id' => $taskId, 'user_id' => $userId]
                    );
                    
                    if (!$task) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'You do not have permission to update this task']);
                        exit;
                    }
                    
                    // Validate required fields
                    $requiredFields = ['title', 'period_id'];
                    $errors = validateRequired($_POST, $requiredFields);
                    
                    if (!empty($errors)) {
                        echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Please fill all required fields']);
                        exit;
                    }
                    
                    // Sanitize inputs
                    $title = sanitize($_POST['title']);
                    $description = sanitize($_POST['description'] ?? '');
                    $periodId = (int)$_POST['period_id'];
                    $status = sanitize($_POST['status'] ?? 'not_started');
                    $priority = sanitize($_POST['priority'] ?? 'medium');
                    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
                    $assignedTo = isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : $task['assigned_to'];
                    
                    // Update task
                    $updatedTask = [
                        'title' => $title,
                        'description' => $description,
                        'period_id' => $periodId,
                        'status' => $status,
                        'priority' => $priority,
                        'deadline' => $deadline,
                        'assigned_to' => $assignedTo,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $result = $db->update('tasks', $updatedTask, 'id = :id', ['id' => $taskId]);
                    
                    if (!$result) {
                        throw new Exception('Failed to update task');
                    }
                    
                    // Log activity
                    $db->logActivity('task_updated', 'Updated task: ' . $title, $userId, $taskId, 'task');
                    
                    echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
                    break;
                    
                case 'update_status':
                    // Validate task ID
                    $taskId = (int)$_POST['task_id'] ?? 0;
                    if (!$taskId) {
                        echo json_encode(['success' => false, 'message' => 'Task ID is required']);
                        exit;
                    }
                    
                    // Validate status
                    $status = $_POST['status'] ?? '';
                    if (!in_array($status, ['not_started', 'in_progress', 'completed', 'on_hold'])) {
                        echo json_encode(['success' => false, 'message' => 'Invalid status']);
                        exit;
                    }
                    
                    // Check task access
                    $task = $db->fetchOne(
                        "SELECT * FROM tasks WHERE id = :id AND (created_by = :user_id OR assigned_to = :user_id)", 
                        ['id' => $taskId, 'user_id' => $userId]
                    );
                    
                    if (!$task) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'You do not have permission to update this task']);
                        exit;
                    }
                    
                    // Update task status
                    $updatedTask = [
                        'status' => $status,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($status === 'completed' && $task['status'] !== 'completed') {
                        $updatedTask['completed_at'] = date('Y-m-d H:i:s');
                    }
                    
                    $result = $db->update('tasks', $updatedTask, 'id = :id', ['id' => $taskId]);
                    
                    if (!$result) {
                        throw new Exception('Failed to update task status');
                    }
                    
                    // Log activity
                    $db->logActivity('task_status_updated', 'Updated task status to ' . $status . ': ' . $task['title'], $userId, $taskId, 'task');
                    
                    echo json_encode(['success' => true, 'message' => 'Task status updated successfully']);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'DELETE':
            // Parse request body for DELETE request
            parse_str(file_get_contents('php://input'), $deleteData);
            
            // Check task ID
            $taskId = $deleteData['task_id'] ?? null;
            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID is required']);
                exit;
            }
            
            // Check task ownership
            $task = $db->fetchOne(
                "SELECT * FROM tasks WHERE id = :id AND created_by = :user_id", 
                ['id' => $taskId, 'user_id' => $userId]
            );
            
            if (!$task) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this task']);
                exit;
            }
            
            // Delete task
            $result = $db->delete('tasks', 'id = :id', ['id' => $taskId]);
            
            if (!$result) {
                throw new Exception('Failed to delete task');
            }
            
            // Log activity
            $db->logActivity('task_deleted', 'Deleted task: ' . $task['title'], $userId, $taskId, 'task');
            
            echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    logError('Tasks API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>