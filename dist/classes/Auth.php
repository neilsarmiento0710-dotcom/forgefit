<?php
/**
 * Auth.php
 * Place this in: dist/classes/Auth.php
 */

require_once __DIR__ . '/../database/db.php';

class Auth {
    private $db;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance();
    }
    
    /**
     * Attempt to login with credentials
     * @param string $loginInput Username or email
     * @param string $password Password
     * @return array ['success' => bool, 'error' => string, 'user' => array]
     */
    public function login($loginInput, $password) {
        if (empty($loginInput) || empty($password)) {
            return [
                'success' => false,
                'error' => 'Please enter both username/email and password.'
            ];
        }
        
        $user = $this->findUser($loginInput);
        
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Invalid username/email or password.'
            ];
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'error' => 'Invalid username/email or password.'
            ];
        }
        
        unset($user['password_hash']);
        
        return [
            'success' => true,
            'user' => $user,
            'error' => ''
        ];
    }
    
    /**
     * Find user by username or email
     */
    private function findUser($loginInput) {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password_hash, role 
             FROM users 
             WHERE username = ? OR email = ?"
        );
        
        $stmt->bind_param("ss", $loginInput, $loginInput);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $user = ($result->num_rows === 1) ? $result->fetch_assoc() : null;
        $stmt->close();
        
        return $user;
    }
    
    /**
     * Store user in session
     */
    public function setSession($user) {
        $_SESSION["user"] = $user;
        session_regenerate_id(true);
    }
    
    /**
     * Get redirect URL based on role
     */
    public function getRedirectUrl($role) {
        $routes = [
            'management' => './dist/admin/dashboard.php',
            'trainer' => './dist/trainer/dashboard.php',
            'member' => './dist/member/dashboard.php'
        ];
        
        return $routes[$role] ?? null;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user']);
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    /**
     * Register a new user
     * @param array $data User registration data
     * @return array ['success' => bool, 'error' => string, 'message' => string]
     */
    public function register($data) {
        // Validate input
        $validation = $this->validateRegistration($data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error']
            ];
        }
        
        // Check if username exists
        if ($this->usernameExists($data['username'])) {
            return [
                'success' => false,
                'error' => 'Username already taken. Please choose another.'
            ];
        }
        
        // Check if email exists
        if ($this->emailExists($data['email'])) {
            return [
                'success' => false,
                'error' => 'Email already registered. Please login instead.'
            ];
        }
        
        // Create user
        if ($this->createUser($data)) {
            return [
                'success' => true,
                'message' => 'Registration successful! Redirecting to login...'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Registration failed. Please try again.'
        ];
    }
    
    /**
     * Validate registration data
     */
    private function validateRegistration($data) {
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        
        if (empty($username) || empty($email) || empty($password) || 
            empty($confirm_password) || empty($phone) || empty($address)) {
            return ['valid' => false, 'error' => 'All fields are required.'];
        }
        
        if (strlen($username) > 8) {
            return ['valid' => false, 'error' => 'Username must not exceed 8 characters.'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['valid' => false, 'error' => 'Username can only contain letters, numbers, and underscores.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Invalid email format.'];
        }
        
        if (strlen($password) < 8) {
            return ['valid' => false, 'error' => 'Password must be at least 8 characters.'];
        }
        
        if ($password !== $confirm_password) {
            return ['valid' => false, 'error' => 'Passwords do not match.'];
        }
        
        if (!preg_match('/^[0-9]{11}$/', $phone)) {
            return ['valid' => false, 'error' => 'Phone number must be 11 digits (e.g., 09XXXXXXXXX).'];
        }
        
        if (strlen($address) < 5) {
            return ['valid' => false, 'error' => 'Please enter a valid address.'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Check if username exists
     */
    private function usernameExists($username) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    
    /**
     * Check if email exists
     */
    private function emailExists($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    
    /**
     * Create new user
     */
    private function createUser($data) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = $data['role'] ?? 'member';
        
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password_hash, phone, address, role)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param(
            "ssssss",
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['phone'],
            $data['address'],
            $role
        );
        
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}