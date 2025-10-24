
<?php
/* Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');  // Your MySQL port
define('DB_NAME', 'forgefit_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Optional: Keep the function for other use cases
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log($e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}
?> */

/**
 * Database Connection Class
 * Singleton pattern for database connectivity
 */
class Database {
    private static $instance = null;
    private $conn;
    
    // Database configuration
    private const DB_HOST = '127.0.0.1';
    private const DB_PORT = 3307;
    private const DB_NAME = 'forgefit_db';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        try {
            $this->conn = new mysqli(
                self::DB_HOST,
                self::DB_USER,
                self::DB_PASS,
                self::DB_NAME,
                self::DB_PORT
            );  
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    /**
     * Get singleton instance
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     * @return mysqli
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Prepare a statement
     * @param string $query
     * @return mysqli_stmt|false
     */
    public function prepare($query) {
        return $this->conn->prepare($query);
    }
    
    /**
     * Execute a query
     * @param string $query
     * @return mysqli_result|bool
     */
    public function query($query) {
        return $this->conn->query($query);
    }
    
    /**
     * Escape string for SQL
     * @param string $value
     * @return string
     */
    public function escape($value) {
        return $this->conn->real_escape_string($value);
    }
    
    /**
     * Get last insert ID
     * @return int
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    /**
     * Get affected rows
     * @return int
     */
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->conn->rollback();
    }
    
    /**
     * Close connection
     */
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
    
    /**
     * Prevent cloning of instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// For backward compatibility - create global $conn variable
$db = Database::getInstance();
$conn = $db->getConnection();

// Legacy function for backward compatibility
function getDBConnection() {
    return Database::getInstance()->getConnection();
}
?>