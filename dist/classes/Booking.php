<?php
/**
 * Booking Model Class
 * Handles all booking-related database operations
 */
class Booking {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Get bookings for a specific user
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function getUserBookings($user_id, $limit = 5) {
        $stmt = $this->conn->prepare(
            "SELECT b.*, t.username as trainer_name, t.specialty
             FROM bookings b 
             JOIN users t ON b.trainer_id = t.id
             WHERE b.user_id = ? 
             ORDER BY b.booking_date DESC, b.booking_time DESC 
             LIMIT ?"
        );
        
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        return $bookings;
    }
    
    /**
     * Get all bookings
     * @return array
     */
    public function getAllBookings() {
        $sql = "SELECT b.*, 
                    u.username AS member_name, 
                    u.email AS member_email, 
                    t.username AS trainer_name, 
                    t.specialty 
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.id 
                LEFT JOIN users t ON b.trainer_id = t.id 
                ORDER BY b.booking_date DESC, b.booking_time DESC";

        
        $result = $this->conn->query($sql);
        
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        return $bookings;
    }
    
    /**
     * Count bookings for a specific date
     * @param string $date (Y-m-d format)
     * @return int
     */
    public function countBookingsByDate($date) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM bookings WHERE booking_date = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }
        
        return 0;
    }
    
    /**
     * Create a new booking
     * @param array $data
     * @return int|bool - Returns booking ID or false
     */
    public function createBooking($data) {
        $stmt = $this->conn->prepare(
            "INSERT INTO bookings 
            (user_id, trainer_id, booking_date, booking_time, member_name, member_email, status, notes, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        
        $status = $data['status'] ?? 'booked';
        
        $stmt->bind_param(
            "iissssss",
            $data['user_id'],
            $data['trainer_id'],
            $data['booking_date'],
            $data['booking_time'],
            $data['member_name'],
            $data['member_email'],
            $status,
            $data['notes']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Check for booking conflicts
     * @param int $trainer_id
     * @param string $booking_date
     * @param string $booking_time
     * @param int $exclude_booking_id
     * @return bool
     */
    public function hasConflict($trainer_id, $booking_date, $booking_time, $exclude_booking_id = null) {
        if ($exclude_booking_id) {
            $stmt = $this->conn->prepare(
                "SELECT id FROM bookings 
                 WHERE trainer_id = ? 
                 AND booking_date = ? 
                 AND booking_time = ? 
                 AND status != 'cancelled'
                 AND id != ?"
            );
            $stmt->bind_param("issi", $trainer_id, $booking_date, $booking_time, $exclude_booking_id);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT id FROM bookings 
                 WHERE trainer_id = ? 
                 AND booking_date = ? 
                 AND booking_time = ? 
                 AND status != 'cancelled'"
            );
            $stmt->bind_param("iss", $trainer_id, $booking_date, $booking_time);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Update booking status
     * @param int $booking_id
     * @param string $status
     * @return bool
     */
    public function updateStatus($booking_id, $status) {
        $stmt = $this->conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        return $stmt->execute();
    }
    
    /**
     * Delete booking
     * @param int $booking_id
     * @return bool
     */
    public function deleteBooking($booking_id) {
        $stmt = $this->conn->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        return $stmt->execute();
    }
    
    /**
     * Get booking by ID
     * @param int $booking_id
     * @return array|null
     */
    public function getBookingById($booking_id) {
        $stmt = $this->conn->prepare(
            "SELECT b.*, 
                    u.username as member_name,
                    t.username as trainer_name
             FROM bookings b
             LEFT JOIN users u ON b.user_id = u.id
             LEFT JOIN users t ON b.trainer_id = t.id
             WHERE b.id = ?"
        );
        
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }

        /**
     * Request reschedule for a booking
     * @param int $booking_id
     * @param string $new_date
     * @param string $new_time
     * @return bool
     */
    public function requestReschedule($booking_id, $new_date, $new_time) {
        $stmt = $this->conn->prepare(
            "UPDATE bookings 
            SET status = 'reschedule_requested', 
                requested_date = ?, 
                requested_time = ?
            WHERE id = ?"
        );
        
        $stmt->bind_param("ssi", $new_date, $new_time, $booking_id);
        return $stmt->execute();
    }
}
?>