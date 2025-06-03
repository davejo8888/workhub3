<?php
// Added by repair script
define('ROOT_PATH', dirname(dirname(__FILE__)));

* Periods API
 * Handles CRUD operations for periods
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
            // Get all periods accessible by the user
            $sql = "SELECT * FROM Periods WHERE user_id = :user_id OR is_public = 1 ORDER BY start_date DESC";
            $periods = $db->fetchAll($sql, ['user_id' => $userId]);
            
            echo json_encode(['success' => true, 'periods' => $periods]);
            break;
            
        case 'POST':
            // Check CSRF token
            $token = $_POST['csrf_token'] ?? '';
            if (!verifyCSRFToken($token)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid or expired form submission']);
                exit;
            }
            
            // Validate required fields
            $requiredFields = ['name', 'start_date', 'end_date'];
            $errors = validateRequired($_POST, $requiredFields);
            
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Please fill all required fields']);
                exit;
            }
            
            // Sanitize inputs
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description'] ?? '');
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            
            // Validate dates
            if (strtotime($endDate) < strtotime($startDate)) {
                echo json_encode([
                    'success' => false, 
                    'errors' => ['end_date' => 'End date must be after start date'],
                    'message' => 'End date must be after start date'
                ]);
                exit;
            }
            
            // Insert period
            $period = [
                'name' => $name,
                'description' => $description,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'user_id' => $userId,
                'is_public' => isset($_POST['is_public']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $periodId = $db->insert('periods', $period);
            
            if (!$periodId) {
                throw new Exception('Failed to create period');
            }
            
            // Log activity
            $db->logActivity('period_created', 'Created new period: ' . $name, $userId, $periodId, 'period');
            
            echo json_encode(['success' => true, 'period_id' => $periodId, 'message' => 'Period created successfully']);
            break;
            
        case 'PUT':
            // Parse request body for PUT request
            parse_str(file_get_contents('php://input'), $putData);
            
            // Check period ownership
            $periodId = $putData['period_id'] ?? null;
            if (!$periodId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Period ID is required']);
                exit;
            }
            
            $period = $db->fetchOne(
                "SELECT * FROM Periods WHERE id = :id AND user_id = :user_id", 
                ['id' => $periodId, 'user_id' => $userId]
            );
            
            if (!$period) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have permission to update this period']);
                exit;
            }
            
            // Validate required fields
            $requiredFields = ['name', 'start_date', 'end_date'];
            $errors = validateRequired($putData, $requiredFields);
            
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Please fill all required fields']);
                exit;
            }
            
            // Update period
            $updatedPeriod = [
                'name' => sanitize($putData['name']),
                'description' => sanitize($putData['description'] ?? ''),
                'start_date' => $putData['start_date'],
                'end_date' => $putData['end_date'],
                'is_public' => isset($putData['is_public']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $db->update('periods', $updatedPeriod, 'id = :id', ['id' => $periodId]);
            
            if (!$result) {
                throw new Exception('Failed to update period');
            }
            
            // Log activity
            $db->logActivity('period_updated', 'Updated period: ' . $updatedPeriod['name'], $userId, $periodId, 'period');
            
            echo json_encode(['success' => true, 'message' => 'Period updated successfully']);
            break;
            
        case 'DELETE':
            // Parse request body for DELETE request
            parse_str(file_get_contents('php://input'), $deleteData);
            
            // Check period ownership
            $periodId = $deleteData['period_id'] ?? null;
            if (!$periodId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Period ID is required']);
                exit;
            }
            
            $period = $db->fetchOne(
                "SELECT * FROM Periods WHERE id = :id AND user_id = :user_id", 
                ['id' => $periodId, 'user_id' => $userId]
            );
            
            if (!$period) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this period']);
                exit;
            }
            
            // Check if period has tasks
            $tasksCount = $db->fetchOne(
                "SELECT COUNT(*) as count FROM tasks WHERE period_id = :period_id", 
                ['period_id' => $periodId]
            );
            
            if ($tasksCount && $tasksCount['count'] > 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cannot delete period because it has associated tasks. Please delete or move tasks first.'
                ]);
                exit;
            }
            
            // Delete period
            $result = $db->delete('periods', 'id = :id', ['id' => $periodId]);
            
            if (!$result) {
                throw new Exception('Failed to delete period');
            }
            
            // Log activity
            $db->logActivity('period_deleted', 'Deleted period: ' . $period['name'], $userId, $periodId, 'period');
            
            echo json_encode(['success' => true, 'message' => 'Period deleted successfully']);
            break;
            
     default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    logError('Periods API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>