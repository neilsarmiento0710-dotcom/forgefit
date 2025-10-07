<?php
session_start();

// For now, we'll store users in a JSON file (simulate database)
$users_file = __DIR__ . '/../data/users.json';

// Ensure data directory exists
if (!is_dir(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0777, true);
}

// Initialize users file if it doesn't exist
if (!file_exists($users_file)) {
    file_put_contents($users_file, json_encode([]));
}

// Validate form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../register.php");
    exit();
}

// Get and sanitize input
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = $_POST['role'] ?? 'member';

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = "Name is required";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
}

if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters";
}

if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match";
}

if (!in_array($role, ['member', 'trainer'])) {
    $errors[] = "Invalid role selected";
}

// Load existing users
$users = json_decode(file_get_contents($users_file), true);

// Check if email already exists
foreach ($users as $user) {
    if (strtolower($user['email']) === strtolower($email)) {
        $errors[] = "Email already registered";
        break;
    }
}

// If there are errors, redirect back
if (!empty($errors)) {
    $_SESSION['error'] = implode(', ', $errors);
    header("Location: ../register.php");
    exit();
}

// Create new user
$new_user = [
    'id' => count($users) + 1,
    'name' => htmlspecialchars($name),
    'email' => strtolower($email),
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role' => $role,
    'created_at' => date('Y-m-d H:i:s')
];

// Add user to array and save
$users[] = $new_user;
file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));

// Set success message
$_SESSION['success'] = "Registration successful! Please log in.";

// Redirect to login
header("Location: ../login.php");
exit();
?>