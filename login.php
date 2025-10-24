<?php
session_start();
require_once 'dist/database/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login_input = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    if (!empty($login_input) && !empty($password)) {
        // Get database instance using OOP approach
        $db = Database::getInstance();

        // Query users table by username or email
        $stmt = $db->prepare("SELECT id, username, email, password_hash, role 
                                FROM users 
                                WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                unset($user['password_hash']); // never store password hash in session
                $_SESSION["user"] = $user;
                session_regenerate_id(true);

                // Redirect based on role
                switch ($user['role']) {
                    case 'management':
                        header("Location: ./dist/admin/dashboard.php");
                        break;
                    case 'trainer':
                        header("Location: ./dist/trainer/dashboard.php");
                        break;
                    case 'member':
                        header("Location: ./dist/member/dashboard.php");
                        break;
                    default:
                        $error = "Invalid user role configuration.";
                        exit;
                }
                exit;
            } else {
                $error = "Invalid username/email or password.";
            }
        } else {
            $error = "Invalid username/email or password.";
        }

        $stmt->close();
        // Note: With singleton pattern, we don't close the connection
        // as it may be needed elsewhere. It will close when script ends.
    } else {
        $error = "Please enter both username/email and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Login to ForgeFit Gym" />
    <meta name="keywords" content="gym, login, fitness" />
    <meta name="author" content="ForgeFit 2025" />
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
                <li><a href="index.php#home">Home</a></li>
                <li><a href="index.php#features">About Us</a></li>
                <li><a href="index.php#pricing">Pricing</a></li>
                <li><a href="index.php#contact">Contact</a></li>
                <li><a href="login.php" class="cta-btn">Login</a></li>
                <li><a href="register.php" class="cta-btn">Register</a></li>
            </ul>
        </nav>
    </header>

    <div class="login-main">
        <div class="login-wrapper">
            <!-- Logo -->
            <div class="login-logo">
                <h1>Login</h1>
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
                        <label for="username">Username or Email</label>
                        <input 
                            type="text" 
                            id="username"
                            name="username" 
                            placeholder="Enter your username or email" 
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

    <!-- JS -->
    <script src="./dist/assets/js/plugins/simplebar.min.js"></script>
    <script src="./dist/assets/js/plugins/popper.min.js"></script>
    <script src="./dist/assets/js/icon/custom-icon.js"></script>
    <script src="./dist/assets/js/plugins/feather.min.js"></script>
    <script src="./dist/assets/js/component.js"></script>
    <script src="./dist/assets/js/theme.js"></script>
    <script src="./dist/assets/js/script.js"></script>
</body>
</html>
