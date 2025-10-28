<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../database/db.php';

// Include Model Classes
require_once '../classes/User.php';
require_once '../classes/Booking.php';
require_once '../classes/Membership.php';
require_once '../classes/Payment.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../../member_login.php");
    exit();
}

// Check if user is management
if ($_SESSION['user']['role'] !== 'management') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['username'];

// Initialize Model Classes
$userModel = new User();
$bookingModel = new Booking();
$membershipModel = new Membership();
$paymentModel = new Payment();

// Fetch dashboard statistics using OOP methods
$user_count = $userModel->countUsersByRole('member');
$trainer_count = $userModel->countUsersByRole('trainer');
$today_bookings = $bookingModel->countBookingsByDate(date('Y-m-d'));
$active_memberships = $membershipModel->countActiveMemberships();
$total_earnings = $paymentModel->getTotalEarnings('paid');

// Get monthly revenue (current month)
$monthly_revenue = $paymentModel->getMonthlyEarnings(date('Y'), date('m'));

// Fetch recent bookings (optional for future use)
$recent_bookings = $bookingModel->getUserBookings($user_id, 5);

?>
<!doctype html>
<html lang="en">
<head>
    <title>Admin Dashboard - ForgeFit</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Admin Dashboard" />
    <meta name="author" content="Sniper 2025" />
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/home.css?v=4"/> 
    <link rel="stylesheet" href="../assets/css/member_dashboard.css" id="main-style-link"/> 
    <link rel="stylesheet" href="../assets/css/sidebar.css" />
    
    <style>
    .logo-two {
        font-size: 0.9rem;
        font-weight: 600;
        color: #90e0ef;
        background: rgba(144, 224, 239, 0.1);
        padding: 6px 16px;
        border-radius: 20px;
        border: 1px solid rgba(144, 224, 239, 0.3);
        margin-left: 15px;
    }
    </style>
</head>

<body>
    <header>
        <nav>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="logo">ForgeFit</div>
                <div class="logo-two">Admin</div>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="bookings.php">Bookings</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="payments.php">Payments</a></li>
                <li><a href="member_rates.php">Membership Rates</a></li>
                <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="bookings.php">Bookings</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="payments.php">Payments</a></li>
                <li><a href="member_rates.php">Membership Rates</a></li>
                <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
            </ul>
        </div>
    <!-- Main Content -->
    <main>
        <!-- Success Message Display -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <span class="success-icon">✓</span>
                <?php 
                    echo htmlspecialchars($_SESSION['success_message']); 
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Dashboard Header -->
        <div class="dashboard-hero">
            <h1 class="dashboard-title" style="text-align: center;">Welcome Back, Admin!</h1>
        </div>

        <div class="earnings-grid">
            <!-- Card 1: Total Members -->
            <div class="earnings-card">
                <div class="earnings-header">👥 MEMBERS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount">
                            <?php echo number_format($user_count); ?>
                        </span>
                    </div>
                    <div class="earnings-percentage">Registered Members</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: 100%; background: linear-gradient(135deg, #10b981, #059669);"></div>
                </div>
            </div>
            
            <!-- Card 2: Trainers -->
            <div class="earnings-card">
                <div class="earnings-header">🏋️ TRAINERS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount">
                            <?php echo number_format($trainer_count); ?>
                        </span>
                    </div>
                    <div class="earnings-percentage">Active Trainers</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: 80%; background: linear-gradient(135deg, #f59e0b, #d97706);"></div>
                </div>
            </div>

            <!-- Card 3: Today's Bookings -->
            <div class="earnings-card">
                <div class="earnings-header">📆 TODAY'S BOOKINGS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount">
                            <?php echo number_format($today_bookings); ?>
                        </span>
                    </div>
                    <div class="earnings-percentage">Sessions Scheduled</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?php echo min($today_bookings * 5, 100); ?>%; background: linear-gradient(135deg, #6366f1, #4338ca);"></div>
                </div>
            </div>
        </div>

        <div class="earnings-grid">
            <!-- Card 4: Active Memberships -->
            <div class="earnings-card">
                <div class="earnings-header">🔥 ACTIVE MEMBERSHIPS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount">
                            <?php echo number_format($active_memberships); ?>
                        </span>
                    </div>
                    <div class="earnings-percentage">Currently Active</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?php echo min($active_memberships * 3, 100); ?>%; background: linear-gradient(135deg, #f43f5e, #be123c);"></div>
                </div>
            </div>
            
            <!-- Card 5: Monthly Revenue (NEW) -->
            <div class="earnings-card">
                <div class="earnings-header">📊 THIS MONTH</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount">₱<?php echo number_format($monthly_revenue, 2); ?></span>
                    </div>
                    <div class="earnings-percentage">Revenue</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?php echo min(($monthly_revenue / max($total_earnings, 1)) * 100, 100); ?>%; background: linear-gradient(135deg, #8b5cf6, #7c3aed);"></div>
                </div>
            </div>
            
            <!-- Card 6: Total Earnings -->
            <div class="earnings-card">
                <div class="earnings-header">💰 TOTAL EARNINGS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount">₱<?php echo number_format($total_earnings, 2); ?></span>
                    </div>
                    <div class="earnings-percentage">All-Time Revenue</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: 100%; background: linear-gradient(135deg, #16a34a, #22c55e);"></div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
        </div>
    </footer>

    <script src="../assets/js/plugins/feather.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenu = document.querySelector('.mobile-menu');
            const navLinks = document.querySelector('.nav-links');
            
            if (mobileMenu) {
                mobileMenu.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
            }

            // Header background change on scroll
            window.addEventListener('scroll', function() {
                const header = document.querySelector('header');
                if (window.scrollY > 50) {
                    header.style.background = 'linear-gradient(135deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.98) 100%)';
                } else {
                    header.style.background = 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)';
                }
            });
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