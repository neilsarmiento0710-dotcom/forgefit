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
     * Syncs memberships by setting expired ones to 'inactive'
     * @param int|null $user_id (optional, for individual sync)
     * @return bool
     */
    public function syncInactiveMemberships($user_id = null) {
        try {
            // Expire memberships where end_date has passed
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

            return true;
        } catch (Exception $e) {
            error_log("syncInactiveMemberships Error: " . $e->getMessage());
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
             WHERE start_date <= ? AND end_date >= ? AND status = 'active'"
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

    /**
     * Check if user has active membership
     * @param int $user_id
     * @return bool
     */
    public function hasActiveMembership($user_id) {
        $stmt = $this->conn->prepare("
            SELECT id 
            FROM memberships 
            WHERE user_id = ? 
            AND status = 'active' 
            AND end_date >= CURDATE() 
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
        $stmt = $this->conn->prepare(
            "INSERT INTO memberships 
            (user_id, plan_type, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, ?)"
        );
        
        $status = $data['status'] ?? 'active';
        
        $stmt->bind_param(
            "issss",
            $data['user_id'],
            $data['plan_type'],
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
            $data['plan_type'],
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

    /**
     * Activate all memberships for a user
     * @param int $user_id
     * @return bool
     */
    public function activateByUserId($user_id) {
        $sql = "UPDATE memberships SET status = 'active' WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        return $stmt->execute();
    }

    /**
     * Deactivate all memberships for a user
     * @param int $user_id
     * @return bool
     */
    public function deactivateByUserId($user_id) {
        $sql = "UPDATE memberships SET status = 'inactive' WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        return $stmt->execute();
    }

    /**
     * Get membership by user ID
     * @param int $user_id
     * @return array|null
     */
    public function getByUserId($user_id) {
        $sql = "SELECT m.*, mp.name as plan_name, mp.duration_days, mp.price
                FROM memberships m
                LEFT JOIN membership_plans mp ON m.plan_id = mp.id
                WHERE m.user_id = ?
                ORDER BY m.created_at DESC
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    /**
     * Create or update a user's membership when a payment is made
     * @param int $user_id
     * @param string $plan_type
     * @return bool
     */
    public function createOrUpdateMembership($user_id, $plan_type) {
        try {
            // 1. Get plan duration based on plan type
            $duration_days = 30; // Default
            switch (strtolower($plan_type)) {
                case 'basic': $duration_days = 30; break;
                case 'premium': $duration_days = 30; break;
                case 'elite': $duration_days = 30; break;
            }
            
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime("+$duration_days days"));
            
            // 2. Check if user already has an active membership
            $stmt = $this->conn->prepare("
                SELECT id FROM memberships 
                WHERE user_id = ? 
                AND status = 'active' 
                AND end_date >= CURDATE()
                LIMIT 1
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // User has active membership - UPDATE it
                $row = $result->fetch_assoc();
                $membership_id = $row['id'];
                
                $updateStmt = $this->conn->prepare("
                    UPDATE memberships 
                    SET plan_type = ?, 
                        start_date = ?, 
                        end_date = ?, 
                        status = 'active',
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->bind_param("sssi", $plan_type, $start_date, $end_date, $membership_id);
                $updateStmt->execute();
                $updateStmt->close();
                
            } else {
                // No active membership - CREATE new one
                $insertStmt = $this->conn->prepare("
                    INSERT INTO memberships 
                    (user_id, plan_type, start_date, end_date, status, created_at) 
                    VALUES (?, ?, ?, ?, 'active', NOW())
                ");
                $insertStmt->bind_param("isss", $user_id, $plan_type, $start_date, $end_date);
                $insertStmt->execute();
                $insertStmt->close();
            }
            
            $stmt->close();
            return true;
            
        } catch (Exception $e) {
            error_log("createOrUpdateMembership Error: " . $e->getMessage());
            return false;
        }
    }
}
?>