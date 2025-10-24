<?php
/**
 * Membership Model Class
 * Handles all membership-related database operations
 */
class Membership {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Syncs memberships by setting expired ones to 'inactive' and updating 'paid' pending payments.
     * Note: This is a simplified sync. A proper system would check for a 'paid' payment 
     * and automatically create the membership, but we'll focus on expiration here.
     * @param int|null $user_id (optional, for individual sync)
     * @return bool
     */
    public function syncInactiveMemberships($user_id = null) {
        try {
            // 1. Expire memberships where end_date has passed
            $sql = "UPDATE memberships 
                    SET status = 'inactive' 
                    WHERE end_date < CURDATE() 
                    AND status = 'active'";
            
            if ($user_id) {
                $sql .= " AND user_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
            } else {
                $stmt = $this->conn->prepare($sql);
            }
            $stmt->execute();
            $stmt->close();
            
            // 2. Note: A more complete system would check 'paid' payments and create/extend a membership here.

            return true;
        } catch (Exception $e) {
            // Log error
            return false;
        }
    }
    
    /**
     * Get active memberships count by date
     * @param string $date (Y-m-d format)
     * @return int
     */
    public function countActiveMemberships($date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS active_count 
             FROM memberships 
             WHERE start_date <= ? AND end_date >= ? AND status = 'active'" // Added status check
        );
        
        $stmt->bind_param("ss", $date, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['active_count'];
        }
        
        return 0;
    }
    
    /**
     * Get user's active membership
     * Checks for 'active' status AND end_date >= today
     * @param int $user_id
     * @return array|null
     */
    public function getUserActiveMembership($user_id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM memberships 
             WHERE user_id = ? 
             AND status = 'active'
             AND end_date >= CURDATE()
             ORDER BY end_date DESC 
             LIMIT 1"
        );
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }

    public function hasActiveMembership($user_id) {
        $stmt = $this->conn->prepare("
            SELECT id 
            FROM memberships 
            WHERE user_id = ? 
            AND status = 'active' 
            AND end_date >= CURDATE() 
            ORDER BY end_date DESC 
            LIMIT 1
        ");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }

    
    /**
     * Calculate days remaining for membership
     * @param int $user_id
     * @return int
     */
    public function getDaysRemaining($user_id) {
        $membership = $this->getUserActiveMembership($user_id);
        
        if ($membership) {
            $today = new DateTime();
            $end_date = new DateTime($membership['end_date']);
            // Check if end_date is in the future
            if ($end_date > $today) {
                $interval = $today->diff($end_date);
                return max(0, $interval->days);
            }
        }
        
        return 0;
    }
    
    /**
     * Get all memberships
     * @return array
     */
    public function getAllMemberships() {
        $sql = "SELECT m.*, u.username, u.email 
                FROM memberships m
                LEFT JOIN users u ON m.user_id = u.id
                ORDER BY m.start_date DESC";
        
        $result = $this->conn->query($sql);
        
        $memberships = [];
        while ($row = $result->fetch_assoc()) {
            $memberships[] = $row;
        }
        
        return $memberships;
    }
    
    /**
     * Create a new membership
     * @param array $data
     * @return int|bool
     */
    public function createMembership($data) {
        // Corrected 'membership_type' to 'plan_type' to match payment model and common usage
        $stmt = $this->conn->prepare(
            "INSERT INTO memberships 
            (user_id, plan_type, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, ?)"
        );
        
        $status = $data['status'] ?? 'active';
        
        $stmt->bind_param(
            "issss",
            $data['user_id'],
            $data['plan_type'], // Updated column name
            $data['start_date'],
            $data['end_date'],
            $status
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update membership
     * @param int $membership_id
     * @param array $data
     * @return bool
     */
    public function updateMembership($membership_id, $data) {
        $stmt = $this->conn->prepare(
            "UPDATE memberships 
             SET plan_type = ?, start_date = ?, end_date = ?, status = ?
             WHERE id = ?"
        );
        
        $stmt->bind_param(
            "ssssi",
            $data['plan_type'], // Updated column name
            $data['start_date'],
            $data['end_date'],
            $data['status'],
            $membership_id
        );
        
        return $stmt->execute();
    }
    
    /**
     * Delete membership
     * @param int $membership_id
     * @return bool
     */
    public function deleteMembership($membership_id) {
        $stmt = $this->conn->prepare("DELETE FROM memberships WHERE id = ?");
        $stmt->bind_param("i", $membership_id);
        return $stmt->execute();
    }

    /**
     * Get user's most recent membership (any status)
     * @param int $user_id
     * @return array|null
     */
    public function getUserLatestMembership($user_id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM memberships 
             WHERE user_id = ? 
             ORDER BY end_date DESC 
             LIMIT 1"
        );
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
}
?>
