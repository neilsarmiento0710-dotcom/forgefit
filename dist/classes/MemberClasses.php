<?php
class MemberClasses {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Fetch user bookings with trainer info
    public function getUserBookings($userId) {
        $sql = "SELECT b.*, t.username AS trainer_name, t.specialty
                FROM bookings b
                JOIN users t ON b.trainer_id = t.id
                WHERE b.user_id = ?
                ORDER BY b.booking_date DESC, b.booking_time DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Cancel booking
    public function cancelBooking($bookingId, $userId) {
        // Check if booking can be cancelled
        $checkSql = "SELECT status FROM bookings WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($checkSql);
        $stmt->bind_param("ii", $bookingId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $booking = $result->fetch_assoc();
            if (in_array($booking['status'], ['pending', 'booked'])) {
                $updateSql = "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->bind_param("ii", $bookingId, $userId);
                return $updateStmt->execute();
            } else {
                return "Cannot cancel confirmed bookings. Please contact admin.";
            }
        }
        return "Invalid booking.";
    }

    // Request reschedule
    public function requestReschedule($bookingId, $userId, $newDate, $newTime) {
        $sql = "UPDATE bookings 
                SET status = 'reschedule_requested', requested_date = ?, requested_time = ?
                WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssii", $newDate, $newTime, $bookingId, $userId);
        return $stmt->execute();
    }
}
?>
