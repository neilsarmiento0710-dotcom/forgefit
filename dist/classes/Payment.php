<?php
require_once __DIR__ . '/MembershipPlan.php';

/**
 * Payment Model Class
 * Handles all payment-related database operations
 */
class Payment {
    private $db;
    private $conn;
    private $membershipPlan;

    public function __construct() {
        // Assume Database class is included and accessible
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
        $this->membershipPlan = new MembershipPlan(); 
    }

    public function processPayment($user_id, $plan_type, $payment_method, $file_proof = null) {
        $plan = $this->membershipPlan->getPlanByType($plan_type);
        if (!$plan) {
            return ['success' => false, 'message' => 'Invalid plan type selected.'];
        }

        $amount = $plan['price'];
        $status = 'pending';
        $payment_proof = null;

        if ($payment_method !== 'cash') {
            if (!$file_proof || $file_proof['error'] !== UPLOAD_ERR_OK) {
                $status = 'pending';
            } else {
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

                $file_name = 'payment_' . $user_id . '_' . time() . '.' . $file_ext;
                $payment_proof = $file_name;

                if (!move_uploaded_file($file_proof['tmp_name'], $upload_dir . '/' . $file_name)) {
                    return ['success' => false, 'message' => 'Failed to upload payment proof.'];
                }

                $status = 'pending';
            }
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

    public function getAvailablePlans() {
        return $this->membershipPlan->getActivePlans();
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
        $data['plan_type'] = $data['plan_type'] ?? 'basic';
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
            $data['plan_type']
        );
        return $stmt->execute() ? $this->conn->insert_id : false;
    }

    public function create($data) {
        $data['plan_type'] = $data['plan_type'] ?? 'basic';
        $sql = "INSERT INTO payments (user_id, amount, payment_method, status, payment_proof, plan_type, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "idssss", 
            $data['user_id'],
            $data['amount'],
            $data['payment_method'],
            $data['status'],
            $data['payment_proof'],
            $data['plan_type']
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

    public function syncPaymentStatuses() {
        try {
            $sync_sql = "UPDATE memberships m
                        INNER JOIN payments p ON m.user_id = p.user_id
                        SET m.status = 'active'
                        WHERE p.status = 'paid' AND m.status != 'active'";
            $this->conn->query($sync_sql);

            $sync_user_sql = "UPDATE users u
                            INNER JOIN payments p ON u.id = p.user_id
                            SET u.status = 'active'
                            WHERE p.status = 'paid' AND u.status != 'active'";
            $this->conn->query($sync_user_sql);

            return true;
        } catch (Exception $e) {
            error_log("Sync failed: " . $e->getMessage());
            return false;
        }
    }

    public function getById($payment_id) {
        $sql = "SELECT * FROM payments WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // FIX: Removed the internal comment from the SQL string.
    public function update($payment_id, $data) {
        $sql = "UPDATE payments 
                SET amount = ?, payment_method = ?, status = ?, plan_type = ?
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        // Bind parameters: d (double for amount), s (string for method), s (string for status), s (string for plan_type), i (integer for id)
        $stmt->bind_param("dsssi", 
            $data['amount'],
            $data['payment_method'],
            $data['status'],
            $data['plan_type'],
            $payment_id
        );
        return $stmt->execute();
    }

    public function delete($payment_id) {
        $stmt = $this->conn->prepare("DELETE FROM payments WHERE id = ?");
        $stmt->bind_param("i", $payment_id);
        return $stmt->execute();
    }

    public function approve($payment_id, $admin_id) {
        $sql = "UPDATE payments 
                SET status = 'paid', approved_by = ?, approved_at = NOW() 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $admin_id, $payment_id);
        return $stmt->execute();
    }

    public function hasOtherPaidPayments($user_id, $exclude_payment_id) {
        $sql = "SELECT COUNT(*) as paid_count 
                FROM payments 
                WHERE user_id = ? AND status = 'paid' AND id != ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $exclude_payment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['paid_count'] > 0;
    }

    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as total FROM payments";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function getAllWithDetails($limit, $offset) {
        $sql = "SELECT 
                    p.id, 
                    u.username, 
                    u.email, 
                    p.amount, 
                    p.payment_method, 
                    p.status, 
                    COALESCE(p.plan_type, mem.plan_type, '') AS plan_type, 
                    p.payment_proof, 
                    p.created_at, 
                    p.approved_by, 
                    p.approved_at, 
                    m.username AS approved_by_name
                FROM payments p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN memberships mem ON mem.user_id = u.id
                LEFT JOIN users m ON p.approved_by = m.id
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }

        return $payments;
    }
}