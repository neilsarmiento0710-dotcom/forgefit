<?php
session_start();
require_once 'dist/classes/Auth.php';

$auth = new Auth();
$error = "";
$success = "";

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $role = $_SESSION['user']['role'];
    $redirectUrl = $auth->getRedirectUrl($role);
    if ($redirectUrl) {
        header("Location: $redirectUrl");
        exit();
    }
}

// Handle registration
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        'username' => trim($_POST["username"] ?? ""),
        'name' => trim($_POST["name"] ?? ""),
        'email' => trim($_POST["email"] ?? ""),
        'password' => $_POST["password"] ?? "",
        'confirm_password' => $_POST["confirm_password"] ?? "",
        'role' => $_POST["role"] ?? "member",
        'phone' => trim($_POST["phone"] ?? ""),
        'address' => trim($_POST["address"] ?? ""),
    ];
    
    $result = $auth->register($data);
    
    if ($result['success']) {
        $success = $result['message'];
        header("refresh:2;url=login.php");
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
    <link rel="stylesheet" href="/pwa/dist/css/login.css">
    <link rel="stylesheet" href="./dist/assets/css/home.css" id="main-style-link" />
    <link rel="stylesheet" href="./dist/assets/css/sidebar.css" />
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
            <!-- Logo -->
            <div class="login-logo">
                <h1>register</h1>
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
                            placeholder="Choose a username (max 8 characters)"
                            maxlength="8"
                            pattern="[a-zA-Z0-9_]+"
                            title="Only letters, numbers, and underscores allowed"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input 
                            id="name" 
                            name="name" 
                            type="name" 
                            required 
                            class="form-control"
                            placeholder="Full Name"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
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
                        <label for="phone">Phone Number</label>
                        <input 
                            id="phone" 
                            name="phone" 
                            type="text" 
                            required 
                            class="form-control"
                            placeholder="09XXXXXXXXX"
                            maxlength="11"
                            pattern="[0-9]{11}"
                            title="Enter a valid 11-digit phone number (e.g., 09123456789)"
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input 
                            id="address" 
                            name="address" 
                            type="text" 
                            required 
                            class="form-control"
                            placeholder="Enter your full address"
                            minlength="5"
                            value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
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
                        <label for="confirm_password">Confirm Password</label>
                        <input 
                            id="security_answer" 
                            name="security_answer" 
                            type="security_answer" 
                            required 
                            class="form-control"
                            placeholder="Name of your favorite pet"
                            minlength="10">
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Register</button>
                        <a href="index.php" style="flex: 1; text-decoration: none;">
                            <button type="button" class="btn btn-secondary" style="width: 100%;">Back to Home</button>
                        </a>
                    </div>
                </form>

            </div>
            <p style="text-align: center; margin-top: 15px;">
                Already have an account?
                <a href="login.php" style="color: #10b981; font-weight: 600;">Sign in</a>
            </p>
        </div>
    </div>

    <script>
        // Phone number validation - only allow digits
        document.getElementById('phone').addEventListener('input', function(e) {
            // Remove any non-digit characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limit to 11 digits
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });
        
        // Client-side password match validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const phone = document.getElementById('phone').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            // Final phone validation check
            if (!/^[0-9]{11}$/.test(phone)) {
                e.preventDefault();
                alert('Phone number must be exactly 11 digits!');
                return;
            }
        });
    </script>
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