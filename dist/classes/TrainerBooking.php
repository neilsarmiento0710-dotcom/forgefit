<?php
class TrainerBooking {
    private $conn;
    private $trainer_id;
    private $trainer_name;

    public function __construct($trainer_id, $trainer_name) {
        $this->conn = Database::getInstance()->getConnection();
        $this->trainer_id = $trainer_id;
        $this->trainer_name = $trainer_name;
    }

    /** Fetch all active gym members */
    public function getAllMembers() {
        $sql = "SELECT id, username, email, phone 
                FROM users 
                WHERE role = 'member' 
                ORDER BY username ASC";
        return $this->conn->query($sql);
    }

    /** Fetch a single member by ID */
    public function getMemberById($member_id) {
        $stmt = $this->conn->prepare("SELECT username, email FROM users WHERE id = ? AND role = 'member'");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Check if trainer has a time conflict */
    private function trainerHasConflict($booking_date, $booking_time) {
        $stmt = $this->conn->prepare("
            SELECT id FROM bookings 
            WHERE trainer_id = ? 
            AND booking_date = ? 
            AND booking_time = ? 
            AND status != 'cancelled'
        ");
        $stmt->bind_param("iss", $this->trainer_id, $booking_date, $booking_time);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    /** Check if client has a time conflict */
    private function clientHasConflict($client_id, $booking_date, $booking_time) {
        $stmt = $this->conn->prepare("
            SELECT id FROM bookings 
            WHERE user_id = ? 
            AND booking_date = ? 
            AND booking_time = ? 
            AND status != 'cancelled'
        ");
        $stmt->bind_param("iss", $client_id, $booking_date, $booking_time);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    /** Create a new booking */
    public function createBooking($client_id, $booking_date, $booking_time, $notes = '') {
        $client = $this->getMemberById($client_id);
        if (!$client) {
            return ['success' => false, 'message' => "Error: Invalid client selected."];
        }

        $member_name = $client['username'];
        $member_email = $client['email'];

        if ($this->trainerHasConflict($booking_date, $booking_time)) {
            $msg = "⚠️ You are already booked on " . 
                   date('F j, Y', strtotime($booking_date)) . " at " . 
                   date('g:i A', strtotime($booking_time)) . ". Please choose a different time.";
            return ['success' => false, 'message' => $msg];
        }

        if ($this->clientHasConflict($client_id, $booking_date, $booking_time)) {
            $msg = "⚠️ " . htmlspecialchars($member_name) . " is already booked on " . 
                   date('F j, Y', strtotime($booking_date)) . " at " . 
                   date('g:i A', strtotime($booking_time)) . ". Please choose a different time.";
            return ['success' => false, 'message' => $msg];
        }

        $insert_sql = "
            INSERT INTO bookings 
            (user_id, trainer_id, booking_date, booking_time, member_name, member_email, status, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'booked', ?, NOW())
        ";
        $stmt = $this->conn->prepare($insert_sql);
        $stmt->bind_param("iisssss", $client_id, $this->trainer_id, $booking_date, $booking_time, $member_name, $member_email, $notes);
        
        if ($stmt->execute()) {
            $msg = "✅ Session booked successfully! Training with " . 
                   htmlspecialchars($member_name) . " scheduled for " . 
                   date('M d, Y', strtotime($booking_date)) . " at " . 
                   date('g:i A', strtotime($booking_time)) . ".";
            return ['success' => true, 'message' => $msg];
        } else {
            return ['success' => false, 'message' => "Booking failed: " . $stmt->error];
        }
    }
}
?>
