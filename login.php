<?php
session_start();
require_once 'dist/classes/Auth.php';

$auth = new Auth();
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login_input = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    
    $result = $auth->login($login_input, $password);
    
    if ($result['success']) {
        $auth->setSession($result['user']);
        
        $redirectUrl = $auth->getRedirectUrl($result['user']['role']);
        
        if ($redirectUrl) {
            header("Location: $redirectUrl");
            exit;
        } else {
            $error = "Invalid user role configuration.";
        }
    } else {
        $error = $result['error'];
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
    <link rel="stylesheet" href="./dist/assets/css/home.css?v=4" id="main-style-link" />
    <link rel="stylesheet" href="./dist/assets/css/sidebar.css" />
</head>

<body>
    <header>
        <nav>
            <div class="logo">ForgeFit</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#features">About Us</a></li>
                <li><a href="index.php#pricing">Pricing</a></li>
                <li><a href="index.php#contact">Contact</a></li>
                <li><a href="login.php" class="cta-btn">Login</a></li>
                <li><a href="register.php" class="cta-btn">Register</a></li>
            </ul>
            <div class="mobile-menu" id="mobileMenuBtn">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <div class="sidebar" id="sidebar">
        <button class="sidebar-close" id="sidebarClose">×</button>
        <ul class="sidebar-menu">
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php#features">About Us</a></li>
            <li><a href="index.php#pricing">Pricing</a></li>
            <li><a href="index.php#contact">Contact</a></li>
            <li><a href="login.php" class="cta-btn">Login</a></li>
            <li><a href="register.php" class="cta-btn">Register</a></li>
        </ul>
    </div>

    <div class="login-main">
        <div class="login-wrapper">
            <div class="login-logo">
                <h1>Login</h1>
            </div>

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

<script>
// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Sidebar functionality
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const sidebar = document.getElementById('sidebar');
const sidebarClose = document.getElementById('sidebarClose');

// Open sidebar
mobileMenuBtn.addEventListener('click', () => {
    sidebar.classList.add('active');
    mobileMenuBtn.classList.add('open');
});

// Close sidebar with close button
sidebarClose.addEventListener('click', () => {
    sidebar.classList.remove('active');
    mobileMenuBtn.classList.remove('open');
});

// Close sidebar when clicking on a link
const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
sidebarLinks.forEach(link => {
    link.addEventListener('click', () => {
        sidebar.classList.remove('active');
        mobileMenuBtn.classList.remove('open');
    });
});

// Close sidebar when clicking outside
document.addEventListener('click', (e) => {
    if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
        sidebar.classList.remove('active');
        mobileMenuBtn.classList.remove('open');
    }
});
</script>
</body>
</html>