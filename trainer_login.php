<?php
session_start();
require_once 'dist/database/db.php'; // Assuming this provides getDBConnection()

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // The input can be the trainer's 'name' or 'email'
    $login_input = $_POST["username"] ?? ""; 
    $password = $_POST["password"] ?? "";

    if (!empty($login_input) && !empty($password)) {
        $conn = getDBConnection();
        $user = null;

        // --- 1. Query the 'trainers' table based on the provided structure ---
        // 'name' is aliased as 'username' and 'id' as 'trainer_id' for session consistency.
        // 'trainer' is explicitly set as the role.
        $stmt = $conn->prepare("SELECT id, name AS username, email, password_hash, 
                                       'trainer' AS role, specialty, id AS trainer_id
                                FROM trainers 
                                WHERE (name = ? OR email = ?) AND status = 'active'");
                                
        // Bind the single input to check both the 'name' and 'email' columns
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // --- 2. Password Verification ---
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct
                unset($user['password_hash']);
                
                $_SESSION["user"] = $user;
                session_regenerate_id(true);

                // --- 3. Dedicated Trainer Redirect ---
                // Since this file is ONLY for trainers, we only check for the 'trainer' role.
                if ($user["role"] === "trainer") { 
                    header("Location: ./dist/trainer/dashboard.php");
                } 
                // Note: The role is explicitly set to 'trainer' in the SELECT statement,
                // so the 'else if' blocks for 'member'/'management' are unnecessary here 
                // unless you plan to use this single file for all logins again.
                
                exit;
            } else {
                $error = "Invalid email/name or password.";
            }
        } else {
            // User not found or status is not 'active'
            $error = "Invalid email/name or password.";
        }
        
        // --- 4. Cleanup ---
        if ($stmt) $stmt->close();
        $conn->close();
    } else {
        $error = "Please enter both email/name and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Login to FitZone Gym" />
    <meta name="keywords" content="gym, login, fitness" />
    <meta name="author" content="Sniper 2025" />
    <title>Login - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./dist/assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/feather.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="./dist/assets/fonts/material.css" />
    <link rel="stylesheet" href="./dist/assets/css/style.css"  />
    <link rel="stylesheet" href="./dist/assets/css/login.css" />
    <link rel="stylesheet" href="./dist/assets/css/home.css?v=4" id="main-style-link" />
</head>
<body>
  <header>
        <nav>
            <div class="logo">ForgeFit</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="#features">About Us</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="login.php" class="cta-btn">Login</a></li>
                <li><a href="register.php" class="cta-btn">Register</a></li>
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
                <h1>Trainer</h1>
            </div>

            <!-- Login Card -->
            <div class="login-card">
                <h2>Welcome Back</h2>

                <?php if ($error): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input 
                            type="text" 
                            id="username"
                            name="username" 
                            placeholder="Enter your username" 
                            required 
                            class="form-control"
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            placeholder="Enter your password" 
                            required 
                            class="form-control"
                        >
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Required Js -->
    <script src="./dist/assets/js/plugins/simplebar.min.js"></script>
    <script src="./dist/assets/js/plugins/popper.min.js"></script>
    <script src="./dist/assets/js/icon/custom-icon.js"></script>
    <script src="./dist/assets/js/plugins/feather.min.js"></script>
    <script src="./dist/assets/js/component.js"></script>
    <script src="./dist/assets/js/theme.js"></script>
    <script src="./dist/assets/js/script.js"></script>
</body>
</html>