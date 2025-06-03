<?php

// Added by repair script to define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__FILE__) . '/../'));
}
/**
 * Admin Activity Logs API
 * Handles activity log viewing and filtering for administrators
 */
require_once '../config.php';

// Require admin privileges
requirePermission('view_reports');

// Set content type
header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired form submission']);
            exit;
        }
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'export') {
            // Export activity logs to CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d') . '.csv"');
            
            // Build query with filters
            $where = ['1=1'];
            $params = [];
            
            if (!empty($_POST['activity_type'])) {
                $where[] = 'al.activity_type = :activity_type';
                $params['activity_type'] = $_POST['activity_type'];
            }
            
            if (!empty($_POST['start_date'])) {
                $where[] = 'DATE(al.timestamp) >= :start_date';
                $params['start_date'] = $_POST['start_date'];
            }
            
            if (!empty($_POST['end_date'])) {
                $where[] = 'DATE(al.timestamp) <= :end_date';
                $params['end_date'] = $_POST['end_date'];
            }
            
            if (!empty($_POST['user_id'])) {
                $where[] = 'al.user_id = :user_id';
                $params['user_id'] = (int)$_POST['user_id'];
            }
            
            if (!empty($_POST['entity_type'])) {
                $where[] = 'al.entity_type = :entity_type';
                $params['entity_type'] = $_POST['entity_type'];
            }
            
            $sql = "SELECT al.id, al.timestamp, COALESCE(u.username, 'System') as username, 
                           al.activity_type, al.description, al.entity_type, al.ip_address
                    FROM ActivityLog al
                    LEFT JOIN Users u ON al.user_id = u.id
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY al.timestamp DESC";
            
            $activities = $db->fetchAll($sql, $params);
            
            // Output CSV
            $output = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($output, ['ID', 'Timestamp', 'User', 'Activity Type', 'Description', 'Entity Type', 'IP Address']);
            
            // CSV data
            foreach ($activities as $activity) {
                fputcsv($output, [
                    $activity['id'],
                    $activity['timestamp'],
                    $activity['username'],
                    $activity['activity_type'],
                    $activity['description'],
                    $activity['entity_type'],
                    $activity['ip_address']
                ]);
            }
            
            fclose($output);
            exit;
        }
        
        // Handle DataTables server-side processing
        $draw = (int)$_POST['draw'] ?? 1;
        $start = (int)$_POST['start'] ?? 0;
        $length = (int)$_POST['length'] ?? 10;
        
        // Search value
        $searchValue = $_POST['search']['value'] ?? '';
        
        // Build base query
        $baseQuery = "FROM ActivityLog al LEFT JOIN Users u ON al.user_id = u.id";
        $whereConditions = ['1=1'];
        $params = [];
        
        // Apply filters
        if (!empty($_POST['activity_type'])) {
            $whereConditions[] = 'al.activity_type = :activity_type';
            $params['activity_type'] = $_POST['activity_type'];
        }
        
        if (!empty($_POST['start_date'])) {
            $whereConditions[] = 'DATE(al.timestamp) >= :start_date';
            $params['start_date'] = $_POST['start_date'];
        }
        
        if (!empty($_POST['end_date'])) {
            $whereConditions[] = 'DATE(al.timestamp) <= :end_date';
            $params['end_date'] = $_POST['end_date'];
        }
        
        if (!empty($_POST['user_id'])) {
            $whereConditions[] = 'al.user_id = :user_id';
            $params['user_id'] = (int)$_POST['user_id'];
        }
        
        if (!empty($_POST['entity_type'])) {
            $whereConditions[] = 'al.entity_type = :entity_type';
            $params['entity_type'] = $_POST['entity_type'];
        }
        
        // Apply search
        if (!empty($searchValue)) {
            $whereConditions[] = '(u.username LIKE :search OR al.activity_type LIKE :search OR al.description LIKE :search)';
            $params['search'] = "%$searchValue%";
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        // Get total count
        $totalQuery = "SELECT COUNT(*) as total $baseQuery $whereClause";
        $totalResult = $db->fetchOne($totalQuery, $params);
        $totalRecords = $totalResult['total'];
        
        // Get filtered count (same as total in this case)
        $filteredRecords = $totalRecords;
        
        // Get data with pagination
        $orderColumn = $_POST['order'][0]['column'] ?? 0;
        $orderDir = $_POST['order'][0]['dir'] ?? 'desc';
        // Map column index to column name
        $columns = [
            'al.id',
            'al.timestamp',
            'u.username',
            'al.activity_type',
            'al.description',
            'al.entity_type',
            'al.ip_address'
        ];
        
        $orderByColumn = $columns[$orderColumn] ?? 'al.id';
        
        $dataQuery = "SELECT 
                        al.id, 
                        al.timestamp, 
                        COALESCE(u.username, 'System') as username, 
                        al.activity_type, 
                        al.description, 
                        al.entity_type, 
                        al.ip_address
                    $baseQuery
                    $whereClause
                    ORDER BY $orderByColumn $orderDir
                    LIMIT $length OFFSET $start";
        
        $activities = $db->fetchAll($dataQuery, $params);
        
        // Format data for DataTables
        $data = [];
        foreach ($activities as $activity) {
            $data[] = [
                'id' => $activity['id'],
                'timestamp' => date('Y-m-d H:i:s', strtotime($activity['timestamp'])),
                'username' => $activity['username'],
                'activity_type' => $activity['activity_type'],
                'description' => $activity['description'],
                'entity_type' => $activity['entity_type'],
                'ip_address' => $activity['ip_address']
            ];
        }
        
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    logError('Admin Activity API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>