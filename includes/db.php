<?php
/**
 * Database connection handler
 */

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection failed. Please try again later.');
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Execute a query with prepared statements
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Query execution error: ' . $e->getMessage());
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single row
     */
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('FetchOne error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get multiple rows
     */
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('FetchAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Insert a row and return last insert ID
     */
    public function insert($table, $data) {
        try {
            $fields = array_keys($data);
            $placeholders = array_map(function($field) {
                return ":$field";
            }, $fields);
            
            $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('Insert error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update rows in a table
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $fields = array_keys($data);
            $setStatements = array_map(function($field) {
                return "$field = :$field";
            }, $fields);
            
            $sql = "UPDATE $table SET " . implode(', ', $setStatements) . " WHERE $where";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind data values
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            // Bind where values
            foreach ($whereParams as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Update error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete rows from a table
     */
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM $table WHERE $where";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Delete error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log activity (fixed to include entity_type)
     */
    public function logActivity($type, $description, $userId = null, $entityId = null, $entityType = 'system') {
        try {
            $data = [
                'activity_type' => $type,
                'description' => $description,
                'user_id' => $userId,
                'entity_id' => $entityId,
                'entity_type' => $entityType,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            return $this->insert('activity_log', $data);
        } catch (Exception $e) {
            error_log('Failed to log activity: ' . $type . ' - ' . $description . '. Error: ' . $e->getMessage());
            return false;
        }
    }
}

// Initialize database connection
$db = Database::getInstance();
?>