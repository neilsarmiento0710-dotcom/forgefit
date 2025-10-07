<?php
session_start();
require_once 'dist/database/db.php';

$error = "";
$success = "";

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    if ($role === "member") {
        header("Location: ./dist/member/dashboard.php");
    } elseif ($role === "trainer") {
        header("Location: ./dist/trainer/dashboard.php");
    } elseif ($role === "management") {
        header("Location: ./dist/admin/dashboard.php");
    }
    exit();
}

// Handle registration
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "member";

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username can only contain letters, numbers, and underscores.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $conn = getDBConnection();
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already taken. Please choose another.";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already registered. Please login instead.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
                
                if ($stmt->execute()) {
                    $success = "Registration successful! Redirecting to login...";
                    // Redirect after 2 seconds
                    header("refresh:2;url=login.php");
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="FitZone Gym - Transform Your Body & Mind" />
    <meta name="keywords" content="gym, fitness, training, workout, health" />
    <meta name="author" content="Sniper 2025" />
    <title>Register - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./dist/assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/feather.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/material.css" />
    <link rel="stylesheet" href="./dist/assets/css/style.css"  />
    <link rel="stylesheet" href="./dist/assets/css/login.css" />
    <link rel="stylesheet" href="./dist/assets/css/home.css?v=4" id="main-style-link" />
    <style>
        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error-message {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">ForgeFit</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="#classes">Classes</a></li>
                <li><a href="#trainers">Trainers</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="login.php" class="cta-btn">Member Login</a></li>
            </ul>
            <div class="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>
    
    <div class="login-main">
        <div class="login-wrapper">
            <!-- Logo -->
            <div class="login-logo">
                <h1>ForgeFit</h1>
            </div>
            
            <div class="reg-card">
                <h2>Create your account</h2>

                <?php if ($error): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success-message">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input 
                            id="username" 
                            name="username" 
                            type="text" 
                            required 
                            class="form-control"
                            placeholder="Choose a username"
                            minlength="3"
                            pattern="[a-zA-Z0-9_]+"
                            title="Only letters, numbers, and underscores allowed"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            required 
                            class="form-control"
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            required 
                            class="form-control"
                            placeholder="Minimum 8 characters"
                            minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input 
                            id="confirm_password" 
                            name="confirm_password" 
                            type="password" 
                            required 
                            class="form-control"
                            placeholder="Re-enter password"
                            minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Register as</label>
                        <select 
                            id="role" 
                            name="role" 
                            required
                            class="form-control">
                            <option value="member" <?= (($_POST['role'] ?? '') === 'member') ? 'selected' : '' ?>>Member</option>
                            <option value="trainer" <?= (($_POST['role'] ?? '') === 'trainer') ? 'selected' : '' ?>>Trainer</option>
                        </select>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Register</button>
                        <a href="index.php" style="flex: 1; text-decoration: none;">
                            <button type="button" class="btn btn-secondary" style="width: 100%;">Back to Home</button>
                        </a>
                    </div>

                    <p style="text-align: center; margin-top: 15px;">
                        Already have an account?
                        <a href="login.php" style="color: #10b981; font-weight: 600;">Sign in</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Client-side password match validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>