<?php
require_once __DIR__ . '/../database/db.php';

class Profile {
    private $conn;
    private $user_id;

    public function __construct($user_id) {
        $this->conn = Database::getInstance()->getConnection();
        $this->user_id = $user_id;
    }

    /* ==============================
       BASIC USER INFO
    =============================== */
    public function getUser() {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateProfile($data) {
        $sql = "UPDATE users SET 
                    username = ?, 
                    email = ?, 
                    phone = ?, 
                    address = ?, 
                    emergency_contact = ?, 
                    emergency_phone = ?
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssi", 
            $data['username'], 
            $data['email'], 
            $data['phone'], 
            $data['address'], 
            $data['emergency_contact'], 
            $data['emergency_phone'], 
            $this->user_id
        );
        return $stmt->execute();
    }

    /* ==============================
       PASSWORD MANAGEMENT
    =============================== */
    public function changePassword($current_password, $new_password, $confirm_password) {
        $user = $this->getUser();
        if (!password_verify($current_password, $user['password_hash'])) {
            return "Current password is incorrect.";
        }

        if ($new_password !== $confirm_password) {
            return "New passwords do not match.";
        }

        if (strlen($new_password) < 6) {
            return "Password must be at least 6 characters long.";
        }

        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hash, $this->user_id);
        return $stmt->execute() ? true : "Failed to update password.";
    }

    /* ==============================
       PROFILE PICTURE
    =============================== */
    public function updatePicture($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return "Please select a valid file to upload.";
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowed_types)) {
            return "Only JPG, JPEG, and PNG files are allowed.";
        }

        $upload_dir = __DIR__ . '/../upload/profile/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        

        $user = $this->getUser();
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = 'user_' . $this->user_id . '_' . time() . '.' . $extension;
        $upload_path = $upload_dir . $new_name;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // delete old picture
            if ($user['profile_picture'] && file_exists($upload_dir . $user['profile_picture'])) {
                unlink($upload_dir . $user['profile_picture']);
            }

            $stmt = $this->conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $new_name, $this->user_id);
            return $stmt->execute() ? true : "Failed to update profile picture.";
        }

        return "Error uploading file.";
    }

    /* ==============================
       TRAINER STATS (optional)
    =============================== */
    public function getTrainerStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) AS total_bookings,
                COUNT(DISTINCT user_id) AS total_clients
            FROM bookings
            WHERE trainer_id = ? AND status = 'completed'
        ");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
