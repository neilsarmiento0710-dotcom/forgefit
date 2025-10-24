<?php
class TrainerClient {
    private $conn;
    private $trainer_id;

    public function __construct($trainer_id) {
        $this->conn = Database::getInstance()->getConnection();
        $this->trainer_id = $trainer_id;
    }

    /** Fetch trainer profile info */
    public function getTrainerInfo() {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'trainer'");
        $stmt->bind_param("i", $this->trainer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Confirm booking */
    public function confirmBooking($booking_id) {
        $stmt = $this->conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND trainer_id = ?");
        $stmt->bind_param("ii", $booking_id, $this->trainer_id);
        return $stmt->execute();
    }

    /** Reject booking */
    public function rejectBooking($booking_id) {
        $stmt = $this->conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND trainer_id = ?");
        $stmt->bind_param("ii", $booking_id, $this->trainer_id);
        return $stmt->execute();
    }

    /** Mark session as completed */
    public function markCompleted($booking_id) {
        $stmt = $this->conn->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND trainer_id = ?");
        $stmt->bind_param("ii", $booking_id, $this->trainer_id);
        return $stmt->execute();
    }

    /** Approve a reschedule request */
    public function approveReschedule($booking_id) {
        $stmt = $this->conn->prepare("UPDATE bookings SET status = 'pending' WHERE id = ? AND trainer_id = ?");
        $stmt->bind_param("ii", $booking_id, $this->trainer_id);
        return $stmt->execute();
    }

    /** Total unique clients */
    public function getTotalClients() {
        $stmt = $this->conn->prepare("SELECT COUNT(DISTINCT user_id) AS total FROM bookings WHERE trainer_id = ?");
        $stmt->bind_param("i", $this->trainer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    /** Total bookings */
    public function getTotalBookings() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE trainer_id = ?");
        $stmt->bind_param("i", $this->trainer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    /** Upcoming sessions (today or future) */
    public function getUpcomingSessions() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total 
            FROM bookings 
            WHERE trainer_id = ? 
            AND booking_date >= CURDATE() 
            AND status IN ('booked', 'confirmed', 'pending')
        ");
        $stmt->bind_param("i", $this->trainer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    /** Fetch recent bookings with client info */
    public function getRecentBookings($limit = 20) {
        $sql = "
            SELECT b.*, u.username, u.email, u.phone 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            WHERE b.trainer_id = ? 
            ORDER BY 
                CASE 
                    WHEN b.status = 'booked' THEN 1
                    WHEN b.status = 'pending' THEN 2
                    WHEN b.status = 'reschedule_requested' THEN 3
                    WHEN b.status = 'confirmed' THEN 4
                    ELSE 5
                END,
                b.booking_date DESC, 
                b.booking_time DESC 
            LIMIT ?
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $this->trainer_id, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>
