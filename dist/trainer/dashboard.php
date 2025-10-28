<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include dependencies
require_once '../database/db.php';
require_once '../classes/Trainer.php';

// Check if trainer is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'trainer') {
    header("Location: ../../login.php");
    exit();
}

$trainer_id = $_SESSION['user']['id'];

// Initialize Trainer class
$trainer = new Trainer($trainer_id);

// Fetch all required data
$trainer_info       = $trainer->getTrainerInfo();
$total_clients      = $trainer->getTotalClients();
$total_bookings     = $trainer->getTotalBookings();
$upcoming_sessions  = $trainer->getUpcomingSessions();
$pending_sessions   = $trainer->getPendingSessions();
$today_bookings     = $trainer->getTodayBookings();
$completion_rate    = $trainer->getCompletionRate();

?>
<!doctype html>
<html lang="en">
<head>
    <title>Trainer Dashboard - ForgeFit</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Trainer Dashboard" />
    <meta name="author" content="Sniper 2025" />
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/home.css"/> 
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
            <div class="logo-two">Trainer</div>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="book.php" class="active">Book Client</a></li>
            <li><a href="clients.php">My Clients</a></li>
            <li><a href="profile.php">Profile</a></li>
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
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="book.php" class="active">Book Client</a></li>
                    <li><a href="clients.php">My Clients</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
                </ul>
        </div>
    
    <main>
        <!-- Dashboard Header -->
        <div class="dashboard-hero">
            <h1 class="dashboard-title">Welcome back, <?= htmlspecialchars($trainer_info['username']); ?> 👋</h1>
            <p style="color: #90e0ef; margin-top: 10px; font-size: 1rem;">
                Specialty: <?= htmlspecialchars($trainer_info['specialty'] ?? 'N/A'); ?>
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="earnings-grid">
            <div class="earnings-card">
                <div class="earnings-header">👥 TOTAL CLIENTS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount"><?= $total_clients; ?></span>
                    </div>
                    <div class="earnings-percentage">Unique Clients</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?= min(($total_clients / 50) * 100, 100); ?>%; background: linear-gradient(135deg, #10b981, #059669);"></div>
                </div>
            </div>

            <div class="earnings-card">
                <div class="earnings-header">🏋️ TOTAL SESSIONS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount"><?= $total_bookings; ?></span>
                    </div>
                    <div class="earnings-percentage">All Time</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?= min(($total_bookings / 100) * 100, 100); ?>%; background: linear-gradient(135deg, #3b82f6, #1d4ed8);"></div>
                </div>
            </div>
        </div>

        <div class="earnings-grid">
            <div class="earnings-card">
                <div class="earnings-header">🗓️ PENDING SESSIONS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount"><?= $pending_sessions; ?></span>
                    </div>
                    <div class="earnings-percentage">Awaiting Confirmation</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?= min(($pending_sessions / 20) * 100, 100); ?>%; background: linear-gradient(135deg, #f59e0b, #d97706);"></div>
                </div>
            </div>

            <div class="earnings-card">
                <div class="earnings-header">✅ UPCOMING SESSIONS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount"><?= $upcoming_sessions; ?></span>
                    </div>
                    <div class="earnings-percentage">Confirmed</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?= min(($upcoming_sessions / 20) * 100, 100); ?>%; background: linear-gradient(135deg, #06b6d4, #0891b2);"></div>
                </div>
            </div>
        </div>

        <!-- Today’s Bookings -->
        <div class="activities-card">
            <div class="activities-header">🎯 TODAY'S BOOKINGS</div>
            <div class="activity-list">
                <?php if ($today_bookings->num_rows > 0): ?>
                    <?php while ($booking = $today_bookings->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon"><i class="ph-duotone ph-user-circle"></i></div>
                            <div class="activity-content">
                                <div class="activity-title"><?= htmlspecialchars($booking['username']); ?></div>
                                <div class="activity-description">
                                    📅 <?= date('F j, Y', strtotime($booking['booking_date'])); ?> at 
                                    <?= date('g:i A', strtotime($booking['booking_time'])); ?><br>
                                    📧 <?= htmlspecialchars($booking['email']); ?> | 
                                    📞 <?= htmlspecialchars($booking['phone']); ?>
                                </div>
                            </div>
                            <div class="activity-meta">
                                <span class="activity-timestamp">
                                    <span class="status-dot <?= $booking['status'] === 'booked' ? 'pending' : ''; ?>"></span>
                                    <?= ucfirst($booking['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #64748b; padding: 20px;">No client bookings for today yet! 💪</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Performance Overview -->
        <div class="activities-card" style="margin-top: 30px;">
            <div class="activities-header">📈 PERFORMANCE OVERVIEW</div>
            <div style="padding: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div style="background: #f0f9ff; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #0096c7;">
                        <div style="font-size: 1.5rem; font-weight: 900; color: #0096c7;"><?= $completion_rate; ?>%</div>
                        <div style="color: #023e8a; font-size: 0.9rem; margin-top: 5px; font-weight: 600;">Completion Rate</div>
                    </div>
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

            window.addEventListener('scroll', function() {
                const header = document.querySelector('header');
                header.style.background = window.scrollY > 50
                    ? 'linear-gradient(135deg, rgba(15,23,42,0.98) 0%, rgba(30,41,59,0.98) 100%)'
                    : 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)';
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
