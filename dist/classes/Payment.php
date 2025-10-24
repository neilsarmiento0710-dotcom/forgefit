<?php
/**
 * Payment Model Class
 * Handles all payment-related database operations
 */
class Payment {
    private $db;
    private $conn;
    
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

    public function getAvailablePlans() {
        return $this->plans;
    }

    private function getPlanDetails($plan_type) {
        return $this->plans[$plan_type] ?? null;
    }

    public function processPayment($user_id, $plan_type, $payment_method, $file_proof = null) {
        $plan = $this->getPlanDetails($plan_type);
        if (!$plan) {
            return ['success' => false, 'message' => 'Invalid plan type selected.'];
        }

        $amount = $plan['price'];
        $status = ($payment_method === 'cash') ? 'pending' : 'pending_proof';
        $payment_proof = null;

        if ($payment_method !== 'cash') {
            if (!$file_proof || $file_proof['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Please upload a valid payment proof.'];
            }

            $upload_dir = realpath(__DIR__ . '/../uploads/payments/');
            if (!$upload_dir) {
                $upload_dir = __DIR__ . '/../uploads/payments/';
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = strtolower(pathinfo($file_proof['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!in_array($file_ext, $allowed_exts)) {
                return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.'];
            }

            // ✅ Save as: payment_<user_id>_<timestamp>.<ext>
            $file_name = 'payment_' . $user_id . '_' . time() . '.' . $file_ext;
            $payment_proof = $file_name; // ✅ Only store filename in DB

            // Move file to actual uploads folder
            if (!move_uploaded_file($file_proof['tmp_name'], $upload_dir . '/' . $file_name)) {
                return ['success' => false, 'message' => 'Failed to upload payment proof.'];
            }

            $status = 'pending';
        } else {
            $payment_proof = null;
            $status = 'pending';
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO payments 
            (user_id, amount, payment_proof, payment_method, status, plan_type, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );

        $stmt->bind_param(
            "idssss",
            $user_id,
            $amount,
            $payment_proof,
            $payment_method,
            $status,
            $plan_type
        );

        if ($stmt->execute()) {
            $message = ($status === 'pending') 
                ? "Payment for {$plan_type} plan submitted. Awaiting admin confirmation."
                : "Your cash payment for {$plan_type} plan is pending. Please pay at the counter.";
            
            return ['success' => true, 'message' => $message];
        } else {
            return ['success' => false, 'message' => 'Database error: Could not create payment record.'];
        }
    }

    public function getUserPaymentHistory($user_id) {
        return $this->getUserPayments($user_id);
    }

    public function getTotalEarnings($status = 'paid') {
        $stmt = $this->conn->prepare("SELECT SUM(amount) AS total FROM payments WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        return ($result && $row = $result->fetch_assoc()) ? (float)($row['total'] ?? 0.00) : 0.00;
    }

    public function getAllPayments() {
        $sql = "SELECT p.*, u.username, u.email 
                FROM payments p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC";
        $result = $this->conn->query($sql);
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        return $payments;
    }

    public function getUserPayments($user_id, $limit = null) {
        $sql = "SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC, id DESC";
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
        return ($result->num_rows > 0) ? $result->fetch_assoc() : null;
    }

    public function createPayment($data) {
        $stmt = $this->conn->prepare(
            "INSERT INTO payments 
            (user_id, amount, payment_method, status, plan_type, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $status = $data['status'] ?? 'pending';
        $stmt->bind_param(
            "idsss",
            $data['user_id'],
            $data['amount'],
            $data['payment_method'],
            $status,
            $data['plan_type'] ?? 'unknown'
        );
        return $stmt->execute() ? $this->conn->insert_id : false;
    }

    public function updateStatus($payment_id, $status) {
        $stmt = $this->conn->prepare("UPDATE payments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $payment_id);
        return $stmt->execute();
    }

    public function deletePayment($payment_id) {
        $stmt = $this->conn->prepare("DELETE FROM payments WHERE id = ?");
        $stmt->bind_param("i", $payment_id);
        return $stmt->execute();
    }

    public function getPaymentStats() {
        return [
            'total_paid' => $this->getTotalEarnings('paid'),
            'total_pending' => $this->getTotalEarnings('pending'),
            'total_failed' => $this->getTotalEarnings('failed'),
            'count_paid' => $this->countPaymentsByStatus('paid'),
            'count_pending' => $this->countPaymentsByStatus('pending'),
            'count_failed' => $this->countPaymentsByStatus('failed')
        ];
    }

    private function countPaymentsByStatus($status) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM payments WHERE status = ?");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        return ($result && $row = $result->fetch_assoc()) ? (int)$row['total'] : 0;
    }
}
?>
