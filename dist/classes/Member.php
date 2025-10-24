<?php
class Member {
    private $conn;
    private $member_id;

    public function __construct($member_id) {
        $this->conn = Database::getInstance()->getConnection();
        $this->member_id = $member_id;
    }

    // === Get Member Info ===
    public function getMemberInfo() {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'member'");
        $stmt->bind_param("i", $this->member_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // === Get Total Completed Classes ===
    public function getTotalClasses() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE user_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $this->member_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    }

    // === Get Active Membership and Days Left ===
    public function getMembershipDaysLeft() {
        $stmt = $this->conn->prepare("SELECT end_date FROM memberships WHERE user_id = ? AND status = 'active' ORDER BY end_date DESC LIMIT 1");
        $stmt->bind_param("i", $this->member_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $membership = $result->fetch_assoc();
            $end_date = new DateTime($membership['end_date']);
            $today = new DateTime();
            $diff = $today->diff($end_date);
            return max(0, $diff->days);
        }
        return 0;
    }

    // === Get Today’s Bookings ===
    public function getTodaysBookings() {
        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("
            SELECT b.*, t.username AS trainer_name, t.specialty 
            FROM bookings b
            JOIN users t ON b.trainer_id = t.id
            WHERE b.user_id = ? AND b.booking_date = ?
            ORDER BY b.booking_time ASC
        ");
        $stmt->bind_param("is", $this->member_id, $today);
        $stmt->execute();
        return $stmt->get_result();
    }

    // === Get Total Upcoming Bookings ===
    public function getUpcomingBookings() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total 
            FROM bookings 
            WHERE user_id = ? AND booking_date >= CURDATE() AND status IN ('booked', 'confirmed')
        ");
        $stmt->bind_param("i", $this->member_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    }

    // === Get Completion Rate ===
    public function getCompletionRate() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE user_id = ?");
        $stmt->bind_param("i", $this->member_id);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        if ($total === 0) return 0;

        $stmt = $this->conn->prepare("SELECT COUNT(*) AS completed FROM bookings WHERE user_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $this->member_id);
        $stmt->execute();
        $completed = $stmt->get_result()->fetch_assoc()['completed'] ?? 0;

        return round(($completed / $total) * 100);
    }
}
?>
