<?php
/**
 * User Model Class
 * Handles all user-related database operations
 */
class User {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Get user by ID
     * @param int $user_id
     * @return array|null
     */
    public function getUserById($user_id) {
        $stmt = $this->conn->prepare("SELECT id, username, email, role, phone, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    /**
     * Get total count of users by role
     * @param string $role
     * @return int
     */
    public function countUsersByRole($role = 'member') {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM users WHERE role = ?");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }
        return 0;
    }
    
    /**
     * Get all users by role
     * @param string $role
     * @param int $limit
     * @return array
     */
    public function getUsersByRole($role, $limit = null) {
    // Include status and address so the view doesn’t break
    $sql = "SELECT id, username, email, phone, address, status, created_at 
            FROM users 
            WHERE role = ? 
            ORDER BY created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $role, $limit);
    } else {
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $role);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    return $users;
}

    
   public function createUser($data) {
    $stmt = $this->conn->prepare(
        "INSERT INTO users (username, email, password_hash, role, phone, address, specialty) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

    $stmt->bind_param(
        "sssssss",
        $data['username'],
        $data['email'],
        $password_hash,
        $data['role'],
        $data['phone'],
        $data['address'] ?? '',
        $data['specialty'] ?? ''
    );

    if ($stmt->execute()) {
        return $this->conn->insert_id;
    }

    return false;
}

public function updateUser($id, $data) {
    $fields = [];
    $params = [];
    $types = '';

    // dynamic binding
    if (isset($data['username'])) { $fields[] = "username=?"; $params[] = $data['username']; $types .= 's'; }
    if (isset($data['email'])) { $fields[] = "email=?"; $params[] = $data['email']; $types .= 's'; }
    if (isset($data['phone'])) { $fields[] = "phone=?"; $params[] = $data['phone']; $types .= 's'; }
    if (isset($data['address'])) { $fields[] = "address=?"; $params[] = $data['address']; $types .= 's'; }
    if (isset($data['role'])) { $fields[] = "role=?"; $params[] = $data['role']; $types .= 's'; }
    if (isset($data['status'])) { $fields[] = "status=?"; $params[] = $data['status']; $types .= 's'; }
    if (isset($data['specialty'])) { $fields[] = "specialty=?"; $params[] = $data['specialty']; $types .= 's'; }
    if (isset($data['password'])) { $fields[] = "password_hash=?"; $params[] = $data['password']; $types .= 's'; }

    if (empty($fields)) return false;

    $query = "UPDATE users SET " . implode(", ", $fields) . " WHERE id=?";
    $stmt = $this->conn->prepare($query);
    $params[] = $id;
    $types .= 'i';

    $stmt->bind_param($types, ...$params);
    return $stmt->execute();
}
    /**
     * Delete user
     * @param int $user_id
     * @return bool
     */
    public function deleteUser($user_id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }
    
    /**
     * Check if email exists
     * @param string $email
     * @param int $exclude_user_id
     * @return bool
     */
    public function emailExists($email, $exclude_user_id = null) {
        if ($exclude_user_id) {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $exclude_user_id);
        } else {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}
?>