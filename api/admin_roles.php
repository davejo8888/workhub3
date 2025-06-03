<?php

// Added by repair script to define ROOT_PATH
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(dirname(__FILE__) . '/../'));
}
/**
 * Admin Roles API
 * Handles role and permission management for administrators
 */
require_once '../config.php';

// Require admin privileges
requirePermission('manage_roles');

// Set content type
header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? '';
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($action === 'get_permissions') {
                // Get role permissions
                $roleId = (int)$_GET['role_id'] ?? 0;
                
                if (!$roleId) {
                    echo json_encode(['success' => false, 'message' => 'Role ID is required']);
                    exit;
                }
                
                // Verify CSRF token
                $token = $_GET['csrf_token'] ?? '';
                if (!verifyCSRFToken($token)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Invalid or expired form submission']);
                    exit;
                }
                
                // Get role
                $role = $db->fetchOne("SELECT * FROM roles WHERE id = :id", ['id' => $roleId]);
                
                if (!$role) {
                    echo json_encode(['success' => false, 'message' => 'Role not found']);
                    exit;
                }
                
                // Get permissions
                $sql = "SELECT p.* 
                        FROM permissions p 
                        JOIN role_permissions rp ON p.id = rp.permission_id 
                        WHERE rp.role_id = :role_id";
                
                $permissions = $db->fetchAll($sql, ['role_id' => $roleId]);
                
                echo json_encode(['success' => true, 'role' => $role, 'permissions' => $permissions]);
            } else {
                // Get all roles with permissions count
                $sql = "SELECT r.*, 
                          (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id) as permissions_count
                        FROM roles r
                        ORDER BY r.name";
                
                $roles = $db->fetchAll($sql);
                
                echo json_encode(['success' => true, 'roles' => $roles]);
            }
            break;
            
        case 'POST':
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
                    $requiredFields = ['name', 'description'];
                    $errors = validateRequired($_POST, $requiredFields);
                    
                    if (!empty($errors)) {
                        echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Please fill all required fields']);
                        exit;
                    }
                    
                    // Sanitize inputs
                    $name = strtolower(sanitize($_POST['name']));
                    $description = sanitize($_POST['description']);
                    $baseRole = sanitize($_POST['base_role'] ?? '');
                    
                    // Check if role name already exists
                    $existingRole = $db->fetchOne("SELECT id FROM roles WHERE name = :name", ['name' => $name]);
                    
                    if ($existingRole) {
                        echo json_encode(['success' => false, 'message' => 'Role name already exists']);
                        exit;
                    }
                    
                    // Create role
                    $role = [
                        'name' => $name,
                        'description' => $description,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $roleId = $db->insert('roles', $role);
                    
                    if (!$roleId) {
                        throw new Exception('Failed to create role');
                    }
                    
                    // Copy permissions from base role if specified
                    if (!empty($baseRole)) {
                        $baseRoleId = getRoleIdByName($baseRole);
                        
                        if ($baseRoleId) {
                            // Get base role permissions
                            $sql = "SELECT permission_id FROM role_permissions WHERE role_id = :role_id";
                            $permissions = $db->fetchAll($sql, ['role_id' => $baseRoleId]);
                            
                            // Insert permissions for new role
                            foreach ($permissions as $permission) {
                                $db->query(
                                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)",
                                    ['role_id' => $roleId, 'permission_id' => $permission['permission_id']]
                                );
                            }
                        }
                    }
                    
                    // Log activity
                    $db->logActivity('role_created', 'Admin created new role: ' . $name, $_SESSION['user_id'], $roleId, 'role');
                    
                    echo json_encode(['success' => true, 'role_id' => $roleId, 'message' => 'Role created successfully']);
                    break;
                    
                case 'update_permissions':
                    $roleId = (int)$_POST['role_id'] ?? 0;
                    if (!$roleId) {
                        echo json_encode(['success' => false, 'message' => 'Role ID is required']);
                        exit;
                    }
                    
                    // Get role
                    $role = $db->fetchOne("SELECT * FROM roles WHERE id = :id", ['id' => $roleId]);
                    
                    if (!$role) {
                        echo json_encode(['success' => false, 'message' => 'Role not found']);
                        exit;
                    }
                    
                    // Get permissions from POST
                    $permissionsJson = $_POST['permissions'] ?? '[]';
                    $permissions = json_decode($permissionsJson, true);
                    
                    if ($permissions === null) {
                        echo json_encode(['success' => false, 'message' => 'Invalid permissions format']);
                        exit;
                    }
                    
                    // Begin transaction
                    $db->getConnection()->beginTransaction();
                    
                    try {
                        // Delete existing permissions
                        $db->delete('role_permissions', 'role_id = :role_id', ['role_id' => $roleId]);
                        
                        // Insert new permissions
                        foreach ($permissions as $permissionName) {
                            // Get permission ID
                            $permission = $db->fetchOne(
                                "SELECT id FROM permissions WHERE name = :name",
                                ['name' => $permissionName]
                            );
                            
                            if ($permission) {
                                $db->query(
                                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)",
                                    ['role_id' => $roleId, 'permission_id' => $permission['id']]
                                );
                            }
                        }
                        
                        // Commit transaction
                        $db->getConnection()->commit();
                        
                        // Log activity
                        $db->logActivity('role_permissions_updated', 'Admin updated permissions for role: ' . $role['name'], $_SESSION['user_id'], $roleId, 'role');
                        
                        echo json_encode(['success' => true, 'message' => 'Role permissions updated successfully']);
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        $db->getConnection()->rollBack();
                        throw $e;
                    }
                    break;
                    
                case 'update':
                    $roleId = (int)$_POST['role_id'] ?? 0;
                    if (!$roleId) {
                        echo json_encode(['success' => false, 'message' => 'Role ID is required']);
                        exit;
                    }
                    
                    // Check if role exists
                    $role = $db->fetchOne("SELECT * FROM roles WHERE id = :id", ['id' => $roleId]);
                    
                    if (!$role) {
                        echo json_encode(['success' => false, 'message' => 'Role not found']);
                        exit;
                    }
                    
                    // Check if trying to modify admin role
                    if ($role['name'] === 'admin') {
                        echo json_encode(['success' => false, 'message' => 'Cannot modify admin role']);
                        exit;
                    }
                    
                    // Validate required fields
                    $requiredFields = ['name', 'description'];
                    $errors = validateRequired($_POST, $requiredFields);
                    
                    if (!empty($errors)) {
                        echo json_encode(['success' => false, 'errors' => $errors, 'message' => 'Please fill all required fields']);
                        exit;
                    }
                    
                    // Sanitize inputs
                    $name = strtolower(sanitize($_POST['name']));
                    $description = sanitize($_POST['description']);
                    
                    // Check if role name already exists (excluding current role)
                    $existingRole = $db->fetchOne(
                        "SELECT id FROM roles WHERE name = :name AND id != :id",
                        ['name' => $name, 'id' => $roleId]
                    );
                    
                    if ($existingRole) {
                        echo json_encode(['success' => false, 'message' => 'Role name already exists']);
                        exit;
                    }
                    
                    // Update role
                    $updateData = [
                        'name' => $name,
                        'description' => $description,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $result = $db->update('roles', $updateData, 'id = :id', ['id' => $roleId]);
                    
                    if ($result === false) {
                        throw new Exception('Failed to update role');
                    }
                    
                    // Log activity
                    $db->logActivity('role_updated', 'Admin updated role: ' . $name, $_SESSION['user_id'], $roleId, 'role');
                    
                    echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
                    break;
                    
                case 'delete':
                    $roleId = (int)$_POST['role_id'] ?? 0;
                    if (!$roleId) {
                        echo json_encode(['success' => false, 'message' => 'Role ID is required']);
                        exit;
                    }
                    
                    // Check if role exists
                    $role = $db->fetchOne("SELECT * FROM roles WHERE id = :id", ['id' => $roleId]);
                    
                    if (!$role) {
                        echo json_encode(['success' => false, 'message' => 'Role not found']);
                        exit;
                    }
                    
                    // Check if trying to delete admin role
                    if ($role['name'] === 'admin') {
                        echo json_encode(['success' => false, 'message' => 'Cannot delete admin role']);
                        exit;
                    }
                    
                    // Check if role is assigned to users
                    $usersWithRole = $db->fetchOne(
                        "SELECT COUNT(*) as count FROM Users WHERE role_id = :role_id",
                        ['role_id' => $roleId]
                    );
                    
                    if ($usersWithRole && $usersWithRole['count'] > 0) {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Cannot delete role that is assigned to users. Please reassign users first.'
                        ]);
                        exit;
                    }
                    
                    // Begin transaction
                    $db->getConnection()->beginTransaction();
                    
                    try {
                        // Delete role permissions
                        $db->delete('role_permissions', 'role_id = :role_id', ['role_id' => $roleId]);
                        
                        // Delete role
                        $result = $db->delete('roles', 'id = :id', ['id' => $roleId]);
                        
                        if (!$result) {
                            throw new Exception('Failed to delete role');
                        }
                        
                        // Commit transaction
                        $db->getConnection()->commit();
                        
                        // Log activity
                        $db->logActivity('role_deleted', 'Admin deleted role: ' . $role['name'], $_SESSION['user_id'], null, 'role');
                        
                        echo json_encode(['success' => true, 'message' => 'Role deleted successfully']);
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        $db->getConnection()->rollBack();
                        throw $e;
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    logError('Admin Roles API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>