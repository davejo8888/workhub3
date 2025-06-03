<?php
// Added by repair script
define('ROOT_PATH', dirname(dirname(__FILE__)));

/**
 * MyWorkHub - Subtasks API
 * Handles CRUD operations for subtasks
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
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($action) {
        case 'list':
            $majorTaskId = isset($_GET['major_task_id']) ? (int)$_GET['major_task_id'] : null;
            
            $query = "
                SELECT 
                    st.*,
                    u1.username as assigned_to_name,
                    u2.username as created_by_name
                FROM SubTasks st
                LEFT JOIN Users u1 ON st.assigned_to = u1.id
                LEFT JOIN Users u2 ON st.created_by = u2.id
            ";
            
            $params = [];
            
            if ($majorTaskId) {
                $query .= " WHERE st.major_task_id = ?";
                $params[] = $majorTaskId;
            }
            
            $query .= " ORDER BY st.order_index ASC, st.created_at ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $subtasks = $stmt->fetchAll();
            
            echo json_encode(['status' => 'success', 'data' => $subtasks]);
            break;
            
        case 'get':
            if (!isset($_GET['id'])) {
                throw new Exception("ID is required");
            }
            
            $id = (int)$_GET['id'];
            $stmt = $db->prepare("
                SELECT 
                    st.*,
                    u1.username as assigned_to_name,
                    u2.username as created_by_name
                FROM SubTasks st
                LEFT JOIN Users u1 ON st.assigned_to = u1.id
                LEFT JOIN Users u2 ON st.created_by = u2.id
                WHERE st.id = ?
            ");
            $stmt->execute([$id]);
            $subtask = $stmt->fetch();
            
            if (!$subtask) {
                echo json_encode(['status' => 'error', 'message' => 'Subtask not found']);
            } else {
                echo json_encode(['status' => 'success', 'data' => $subtask]);
            }
            break;
            
        case 'create':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $data = $_POST;
            }
            
            if (!isset($data['major_task_id']) || empty($data['major_task_id'])) {
                throw new Exception("Major task ID is required");
            }
            
            if (!isset($data['task_name']) || empty($data['task_name'])) {
                throw new Exception("Task name is required");
            }
            
            // Get the highest order_index for this major task
            $stmt = $db->prepare("
                SELECT COALESCE(MAX(order_index), 0) + 1 AS next_index 
                FROM SubTasks 
                WHERE major_task_id = ?
            ");
            $stmt->execute([$data['major_task_id']]);
            $orderIndex = $stmt->fetchColumn();
            
            $stmt = $db->prepare("
                INSERT INTO SubTasks (
                    major_task_id, task_name, description, priority, urgency, importance,
                    deadline, status, percent_complete, working_with, notes,
                    estimated_hours, actual_hours, assigned_to, created_by, order_index
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['major_task_id'],
                $data['task_name'],
                $data['description'] ?? null,
                $data['priority'] ?? 'Medium',
                $data['urgency'] ?? 'Soon',
                $data['importance'] ?? 'Important',
                $data['deadline'] ?? null,
                $data['status'] ?? 'To Do',
                $data['percent_complete'] ?? 0,
                $data['working_with'] ?? null,
                $data['notes'] ?? null,
                $data['estimated_hours'] ?? null,
                $data['actual_hours'] ?? null,
                $data['assigned_to'] ?? null,
                $data['created_by'] ?? null,
                $orderIndex
            ]);
            
            $newId = $db->lastInsertId();
            
            // Get the newly created subtask
            $stmt = $db->prepare("SELECT * FROM SubTasks WHERE id = ?");
            $stmt->execute([$newId]);
            $subtask = $stmt->fetch();
            
            // Update the parent task's percent_complete
            updateMajorTaskProgress($db, $data['major_task_id']);
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Subtask created successfully',
                'data' => $subtask
            ]);
            break;
            
        case 'update':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $data = $_POST;
            }
            
            if (!isset($data['id']) || empty($data['id'])) {
                throw new Exception("ID is required");
            }
            
            // Get the original subtask to get the major_task_id
            $stmt = $db->prepare("SELECT major_task_id FROM SubTasks WHERE id = ?");
            $stmt->execute([$data['id']]);
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception("Subtask not found");
            }
            
            $majorTaskId = $result['major_task_id'];
            
            $stmt = $db->prepare("
                UPDATE SubTasks 
                SET task_name = ?, description = ?, priority = ?, 
                    urgency = ?, importance = ?, deadline = ?, status = ?, 
                    percent_complete = ?, working_with = ?, notes = ?,
                    estimated_hours = ?, actual_hours = ?, assigned_to = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['task_name'],
                $data['description'] ?? null,
                $data['priority'] ?? 'Medium',
                $data['urgency'] ?? 'Soon',
                $data['importance'] ?? 'Important',
                $data['deadline'] ?? null,
                $data['status'] ?? 'To Do',
                $data['percent_complete'] ?? 0,
                $data['working_with'] ?? null,
                $data['notes'] ?? null,
                $data['estimated_hours'] ?? null,
                $data['actual_hours'] ?? null,
                $data['assigned_to'] ?? null,
                $data['id']
            ]);
            
            // Update the parent task's percent_complete
            updateMajorTaskProgress($db, $majorTaskId);
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Subtask updated successfully'
            ]);
            break;
            
        case 'delete':
            if (!isset($_GET['id'])) {
                throw new Exception("ID is required");
            }
            
            $id = (int)$_GET['id'];
            
            // Get the major_task_id before deleting
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
            
            // Update the parent task's percent_complete
            updateMajorTaskProgress($db, $majorTaskId);
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Subtask deleted successfully'
            ]);
            break;
            
        case 'reorder':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['order']) || !is_array($data['order'])) {
                throw new Exception("Invalid order data");
            }
            
            $db->beginTransaction();
            
            try {
                $stmt = $db->prepare("UPDATE SubTasks SET order_index = ? WHERE id = ?");
                
                foreach ($data['order'] as $index => $id) {
                    $stmt->execute([$index, $id]);
                }
                
                $db->commit();
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Subtasks reordered successfully'
                ]);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        default:
            throw new Exception("Invalid action");
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