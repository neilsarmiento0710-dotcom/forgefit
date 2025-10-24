<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === Include Database and Member class ===
require_once '../database/db.php';
require_once '../classes/Member.php';

// === Check if user is logged in ===
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../../login.php");
    exit();
}

// === Initialize Member ===
$user_id = $_SESSION['user']['id'];
$member = new Member($user_id);

// === Fetch Data via Member Class ===
$member_info = $member->getMemberInfo();
$total_classes = $member->getTotalClasses();
$days_left = $member->getMembershipDaysLeft();
$bookings_result = $member->getTodaysBookings();
$completion_rate = $member->getCompletionRate();
?>

<!doctype html>
<html lang="en">
<head>
    <title>Member Dashboard - ForgeFit</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Member Dashboard" />
    <meta name="author" content="Sniper 2025" />

    <!-- Styles -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/home.css?v=4"/> 
    <link rel="stylesheet" href="../assets/css/member_dashboard.css" id="main-style-link"/> 
</head>

<body>
    <!-- Header -->
    <header>
        <nav>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="logo">ForgeFit</div>
                <div class="logo-two">Member</div>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="trainers.php">Trainers</a></li>
                <li><a href="classes.php">Bookings</a></li>
                <li><a href="membership.php">Membership</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
            </ul>
            <div class="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Success Message -->
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
        <div class="dashboard-hero" style="text-align: center;">
            <h1 class="dashboard-title">
                Welcome Back, <?php echo htmlspecialchars($member_info['username'] ?? 'Member'); ?>!
            </h1>
        </div>

        <!-- Metrics Grid -->
        <div class="earnings-grid">
            <!-- Total Classes -->
            <div class="earnings-card">
                <div class="earnings-header">🏋️ TOTAL CLASSES</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount"><?php echo number_format($total_classes); ?></span>
                    </div>
                    <div class="earnings-percentage">Completed Sessions</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" 
                        style="width: <?php echo min($total_classes * 5, 100); ?>%; background: linear-gradient(135deg, #10b981, #059669);">
                    </div>
                </div>
            </div>

            <!-- Membership Expiration -->
            <div class="earnings-card">
                <div class="earnings-header">📅 MEMBERSHIP EXPIRATION</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount"><?php echo $days_left; ?></span>
                    </div>
                    <div class="earnings-percentage text-cyan-500">Days Left</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" 
                        style="width: <?php echo min(($days_left / 30) * 100, 100); ?>%; background: linear-gradient(135deg, #06b6d4, #0891b2);">
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Bookings -->
        <div class="activities-card">
            <div class="activities-header">📅 TODAY'S BOOKINGS</div>
            <div class="activity-list">
                <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>
                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="ph-duotone ph-calendar-check"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    Session with <?php echo htmlspecialchars($booking['trainer_name']); ?>
                                </div>
                                <div class="activity-description">
                                    <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?> at 
                                    <?php echo date('g:i A', strtotime($booking['booking_time'])); ?> 
                                    - <?php echo htmlspecialchars($booking['specialty']); ?>
                                </div>
                            </div>
                            <div class="activity-meta">
                                <span class="activity-timestamp">
                                    <span class="status-dot <?php echo $booking['status'] === 'completed' ? 'completed' : 'pending'; ?>"></span> 
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #64748b; padding: 20px;">
                        No bookings yet. <a href="classes.php" style="color: #ff6b6b;">Book your first session!</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-bottom">
            <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
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
                    header.style.background = 'linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(30, 41, 59, 0.98))';
                } else {
                    header.style.background = 'linear-gradient(135deg, #0f172a, #1e293b)';
                }
            });
        });
    </script>
</body>
</html>