<?php
/**
 * MembershipPlan Model Class
 * Handles all membership plan-related database operations
 */
class MembershipPlan {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Create membership_plans table if it doesn't exist
     * @return bool
     */
    public function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS membership_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_type VARCHAR(50) NOT NULL UNIQUE,
            plan_name VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            duration_days INT NOT NULL DEFAULT 30,
            features TEXT,
            is_featured TINYINT(1) DEFAULT 0,
            display_order INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        return $this->conn->query($sql);
    }
    
    /**
     * Count total plans
     * @return int
     */
    public function countPlans() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM membership_plans");
        
        if ($result) {
            $row = $result->fetch_assoc();
            return (int)$row['count'];
        }
        
        return 0;
    }
    
    /**
     * Insert default membership plans
     * @return bool
     */
    public function insertDefaultPlans() {
        $default_plans = [
            ['basic', 'Basic Plan', 600, 30, 'Gym Access|Cardio Equipment|Locker Room|Free WiFi', 0, 1],
            ['premium', 'Premium Plan', 1000, 30, 'Everything in Basic|Group Classes|Sauna Access|Nutrition Guidance|Guest Passes (2/month)', 1, 2],
            ['elite', 'Elite Plan', 1250, 30, 'Everything in Premium|Massage Therapy|Unlimited Guest Passes|Exclusive Events', 0, 3]
        ];
        
        $stmt = $this->conn->prepare(
            "INSERT INTO membership_plans 
            (plan_type, plan_name, price, duration_days, features, is_featured, display_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($default_plans as $plan) {
            $stmt->bind_param(
                "ssdisii", 
                $plan[0], // plan_type
                $plan[1], // plan_name
                $plan[2], // price
                $plan[3], // duration_days
                $plan[4], // features
                $plan[5], // is_featured
                $plan[6]  // display_order
            );
            
            if (!$stmt->execute()) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get all membership plans
     * @return array
     */
    public function getAllPlans() {
        $sql = "SELECT * FROM membership_plans ORDER BY display_order ASC, id ASC";
        $result = $this->conn->query($sql);
        
        $plans = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $plans[] = $row;
            }
        }
        
        return $plans;
    }
    
    /**
     * Get active membership plans
     * @return array
     */
    public function getActivePlans() {
        $sql = "SELECT * FROM membership_plans WHERE status = 'active' ORDER BY display_order ASC, id ASC";
        $result = $this->conn->query($sql);
        
        $plans = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $plans[] = $row;
            }
        }
        
        return $plans;
    }
    
    /**
     * Get plan by ID
     * @param int $plan_id
     * @return array|null
     */
    public function getPlanById($plan_id) {
        $stmt = $this->conn->prepare("SELECT * FROM membership_plans WHERE id = ?");
        $stmt->bind_param("i", $plan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get plan by type
     * @param string $plan_type
     * @return array|null
     */
    public function getPlanByType($plan_type) {
        $stmt = $this->conn->prepare("SELECT * FROM membership_plans WHERE plan_type = ?");
        $stmt->bind_param("s", $plan_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Create a new membership plan
     * @param array $data
     * @return int|bool - Returns plan ID or false
     */
    public function createPlan($data) {
        $stmt = $this->conn->prepare(
            "INSERT INTO membership_plans 
            (plan_type, plan_name, price, duration_days, features, is_featured, display_order, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param(
            "ssdisiis",
            $data['plan_type'],
            $data['plan_name'],
            $data['price'],
            $data['duration_days'],
            $data['features'],
            $data['is_featured'],
            $data['display_order'],
            $data['status']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update membership plan
     * @param int $plan_id
     * @param array $data
     * @return bool
     */
    public function updatePlan($plan_id, $data) {
        $stmt = $this->conn->prepare(
            "UPDATE membership_plans 
             SET plan_type = ?, 
                 plan_name = ?, 
                 price = ?, 
                 duration_days = ?, 
                 features = ?, 
                 is_featured = ?, 
                 display_order = ?, 
                 status = ?
             WHERE id = ?"
        );
        
        $stmt->bind_param(
            "ssdisiisi",
            $data['plan_type'],
            $data['plan_name'],
            $data['price'],
            $data['duration_days'],
            $data['features'],
            $data['is_featured'],
            $data['display_order'],
            $data['status'],
            $plan_id
        );
        
        return $stmt->execute();
    }
    
    /**
     * Delete membership plan
     * @param int $plan_id
     * @return bool
     */
    public function deletePlan($plan_id) {
        $stmt = $this->conn->prepare("DELETE FROM membership_plans WHERE id = ?");
        $stmt->bind_param("i", $plan_id);
        return $stmt->execute();
    }
    
    /**
     * Get featured plans
     * @return array
     */
    public function getFeaturedPlans() {
        $sql = "SELECT * FROM membership_plans 
                WHERE is_featured = 1 AND status = 'active' 
                ORDER BY display_order ASC";
        
        $result = $this->conn->query($sql);
        
        $plans = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $plans[] = $row;
            }
        }
        
        return $plans;
    }
    
    /**
     * Update plan status
     * @param int $plan_id
     * @param string $status
     * @return bool
     */
    public function updateStatus($plan_id, $status) {
        $stmt = $this->conn->prepare("UPDATE membership_plans SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $plan_id);
        return $stmt->execute();
    }
    
    /**
     * Check if plan type exists
     * @param string $plan_type
     * @param int $exclude_id
     * @return bool
     */
    public function planTypeExists($plan_type, $exclude_id = null) {
        if ($exclude_id) {
            $stmt = $this->conn->prepare("SELECT id FROM membership_plans WHERE plan_type = ? AND id != ?");
            $stmt->bind_param("si", $plan_type, $exclude_id);
        } else {
            $stmt = $this->conn->prepare("SELECT id FROM membership_plans WHERE plan_type = ?");
            $stmt->bind_param("s", $plan_type);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}
?>