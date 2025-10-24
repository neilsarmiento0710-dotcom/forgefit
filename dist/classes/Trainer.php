<?php
class Trainer {
    private $conn;
    private $trainer_id;

    public function __construct($trainer_id) {
        $this->conn = Database::getInstance()->getConnection();
        $this->trainer_id = $trainer_id;
    }

    public function getTrainerInfo() {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'trainer'");
        $stmt->bind_param("i", $this->trainer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getActiveTrainers() {
        $sql = "SELECT id, username AS name, specialty 
                FROM users 
                WHERE role = 'trainer' AND status = 'active'";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTotalClients() {
        $stmt = $this->conn->prepare("SELECT COUNT(DISTINCT user_id) AS total FROM bookings WHERE trainer_id = ?");
        $stmt->bind_param("i", $this->trainer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public function getTotalBookings() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE trainer_id = ?");
        $stmt->bind_param("i", $this->trainer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public function getUpcomingSessions() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total 
            FROM bookings 
            WHERE trainer_id = ? AND booking_date >= CURDATE() AND status = 'confirmed'
        ");
        $stmt->bind_param("i", $this->trainer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public function getPendingSessions() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total 
            FROM bookings 
            WHERE trainer_id = ? AND status IN ('pending', 'reschedule_requested', 'booked')
        ");
        $stmt->bind_param("i", $this->trainer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    public function getTodayBookings() {
        $stmt = $this->conn->prepare("
            SELECT b.*, u.username, u.email, u.phone 
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.trainer_id = ? AND b.booking_date = CURDATE()
            ORDER BY b.booking_time ASC
        ");
        $stmt->bind_param("i", $this->trainer_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getCompletionRate() {
        $total = $this->getTotalBookings();
        if ($total === 0) return 0;

        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE trainer_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $this->trainer_id);
        $stmt->execute();
        $completed = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        return round(($completed / $total) * 100);
    }
}
?>
