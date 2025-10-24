<?php
/**
 * Payment Model Class
 * Handles all payment-related database operations
 */
class Payment {
    private $db;
    private $conn;
    
    // Define available plans (Simulating data from a config or DB)
    private $plans = [
        'basic' => [
            'price' => 1500.00,
            'duration_months' => 1,
            'features' => [
                'Unlimited gym access',
                'Access to all group classes',
                'One free personal training session',
                'Standard locker access'
            ]
        ],
        'premium' => [
            'price' => 3000.00,
            'duration_months' => 3,
            'features' => [
                'Unlimited gym access',
                'Priority booking for group classes',
                'Three free personal training sessions',
                'Premium locker and towel service',
                'Exclusive training area access'
            ]
        ],
        'annual' => [
            'price' => 12000.00,
            'duration_months' => 12,
            'features' => [
                'All Premium features',
                'Five free personal training sessions',
                'Exclusive ForgeFit Merchandise',
                'Membership freeze option'
            ]
        ]
    ];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Returns an array of available membership plans.
     * @return array
     */
    public function getAvailablePlans() {
        return $this->plans;
    }

    /**
     * Get plan details by plan type.
     * @param string $plan_type
     * @return array|null
     */
    private function getPlanDetails($plan_type) {
        return $this->plans[$plan_type] ?? null;
    }

    /**
     * Process a new membership payment submission.
     * @param int $user_id
     * @param string $plan_type
     * @param string $payment_method
     * @param array|null $file_proof
     * @return array
     */
    public function processPayment($user_id, $plan_type, $payment_method, $file_proof = null) {
        $plan = $this->getPlanDetails($plan_type);
        if (!$plan) {
            return ['success' => false, 'message' => 'Invalid plan type selected.'];
        }

        $amount = $plan['price'];
        $status = ($payment_method === 'cash') ? 'pending' : 'pending_proof';
        $proof_path = null;

        if ($payment_method !== 'cash') {
            // Handle file upload
            if (!$file_proof || $file_proof['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Please upload a valid payment proof.'];
            }

            $upload_dir = '../../uploads/proofs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = strtolower(pathinfo($file_proof['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!in_array($file_ext, $allowed_exts)) {
                 return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.'];
            }

            $file_name = uniqid('proof_') . '.' . $file_ext;
            $proof_path = $upload_dir . $file_name;

            if (!move_uploaded_file($file_proof['tmp_name'], $proof_path)) {
                return ['success' => false, 'message' => 'Failed to upload payment proof.'];
            }
            $status = 'pending'; // Status pending admin approval with proof uploaded
        } else {
             // For cash payment, status remains 'pending' (to be paid at counter)
             $status = 'pending';
        }
        
        $description = 'Purchase of ' . ucfirst($plan_type) . ' Membership';

        // 1. Create a payment record
        $stmt = $this->conn->prepare(
            "INSERT INTO payments 
            (user_id, amount, payment_method, payment_date, status, description, proof_path, plan_type) 
            VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)"
        );

        // Add plan_type column to payments table for better history tracking
        $stmt->bind_param(
            "idsssss",
            $user_id,
            $amount,
            $payment_method,
            $status,
            $description,
            $proof_path,
            $plan_type
        );
        
        if ($stmt->execute()) {
            $message = ($status === 'pending') 
                ? "Payment for {$plan_type} plan submitted. Awaiting admin confirmation."
                : "Your cash payment for {$plan_type} plan is pending. Please pay at the counter.";
            
            return ['success' => true, 'message' => $message];
        }

        return ['success' => false, 'message' => 'Database error: Could not create payment record.'];
    }

    /**
     * Get payment history for a specific user.
     * Aliases getUserPayments but with a better name for the frontend.
     * @param int $user_id
     * @return array
     */
    public function getUserPaymentHistory($user_id) {
        // Calls the existing logic
        return $this->getUserPayments($user_id);
    }
    
    // --- Existing methods follow (omitted for brevity) ---
    // ... (getTotalEarnings, getAllPayments, getUserPayments, getPaymentById, createPayment, updateStatus, deletePayment, getPaymentStats, countPaymentsByStatus)
    
    /**
     * Calculate total earnings
     * @param string $status - Filter by payment status (default: 'paid')
     * @return float
     */
    public function getTotalEarnings($status = 'paid') {
        $stmt = $this->conn->prepare("SELECT SUM(amount) AS total FROM payments WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            return (float)($row['total'] ?? 0.00);
        }
        
        return 0.00;
    }
    
    /**
     * Get all payments
     * @return array
     */
    public function getAllPayments() {
        $sql = "SELECT p.*, u.username, u.email 
                FROM payments p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.payment_date DESC";
        
        $result = $this->conn->query($sql);
        
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        return $payments;
    }
    
    /**
     * Get payments for a specific user
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function getUserPayments($user_id, $limit = null) {
        $sql = "SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC, id DESC"; // Added id DESC for tie-breaking
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $limit);
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        return $payments;
    }
    
    /**
     * Get payment by ID
     * @param int $payment_id
     * @return array|null
     */
    public function getPaymentById($payment_id) {
        $stmt = $this->conn->prepare(
            "SELECT p.*, u.username, u.email 
             FROM payments p
             LEFT JOIN users u ON p.user_id = u.id
             WHERE p.id = ?"
        );
        
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Create a new payment
     * NOTE: This is superseded by processPayment for new membership signups, but kept for admin use.
     * @param array $data
     * @return int|bool
     */
    public function createPayment($data) {
        $stmt = $this->conn->prepare(
            "INSERT INTO payments 
            (user_id, amount, payment_method, payment_date, status, description, plan_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $status = $data['status'] ?? 'pending';
        $payment_date = $data['payment_date'] ?? date('Y-m-d H:i:s');
        
        $stmt->bind_param(
            "idsssss",
            $data['user_id'],
            $data['amount'],
            $data['payment_method'],
            $payment_date,
            $status,
            $data['description'],
            $data['plan_type'] ?? 'unknown'
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update payment status
     * @param int $payment_id
     * @param string $status
     * @return bool
     */
    public function updateStatus($payment_id, $status) {
        $stmt = $this->conn->prepare("UPDATE payments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $payment_id);
        return $stmt->execute();
    }
    
    /**
     * Delete payment
     * @param int $payment_id
     * @return bool
     */
    public function deletePayment($payment_id) {
        $stmt = $this->conn->prepare("DELETE FROM payments WHERE id = ?");
        $stmt->bind_param("i", $payment_id);
        return $stmt->execute();
    }
    
    /**
     * Get payment statistics
     * @return array
     */
    public function getPaymentStats() {
        $stats = [
            'total_paid' => $this->getTotalEarnings('paid'),
            'total_pending' => $this->getTotalEarnings('pending'),
            'total_failed' => $this->getTotalEarnings('failed'),
            'count_paid' => $this->countPaymentsByStatus('paid'),
            'count_pending' => $this->countPaymentsByStatus('pending'),
            'count_failed' => $this->countPaymentsByStatus('failed')
        ];
        
        return $stats;
    }
    
    /**
     * Count payments by status
     * @param string $status
     * @return int
     */
    private function countPaymentsByStatus($status) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM payments WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['total'];
        }
        
        return 0;
    }
}
?>
