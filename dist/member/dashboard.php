<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
$db_path = '../database/db.php';
if (!file_exists($db_path)) {
    die("Error: Database connection file not found.");
}
include $db_path;

// Verify connection exists
if (!isset($conn) || $conn === null) {
    $conn = getDBConnection();
}

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../../login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['username'];

$today = date('Y-m-d');
$bookings_sql = "SELECT b.*, 
                        t.username AS trainer_name, 
                        t.specialty
                 FROM bookings b
                 JOIN users t ON b.trainer_id = t.id
                 WHERE b.user_id = ? 
                   AND b.booking_date = ?
                 ORDER BY b.booking_time ASC";

$bookings_stmt = $conn->prepare($bookings_sql);
$bookings_stmt->bind_param("is", $user_id, $today);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();

// === Fetch total classes taken by the member ===
$total_classes_sql = "SELECT COUNT(*) AS total_classes FROM bookings WHERE user_id = ? AND status = 'completed'";
$total_stmt = $conn->prepare($total_classes_sql);
$total_stmt->bind_param("i", $user_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_classes = 0;
if ($total_result && $total_result->num_rows > 0) {
    $row = $total_result->fetch_assoc();
    $total_classes = $row['total_classes'];
}

// === Fetch membership expiration ===
$membership_sql = "SELECT end_date FROM memberships WHERE user_id = ? AND status = 'active' ORDER BY end_date DESC LIMIT 1";
$membership_stmt = $conn->prepare($membership_sql);
$membership_stmt->bind_param("i", $user_id);
$membership_stmt->execute();
$membership_result = $membership_stmt->get_result();

$days_left = 0;
if ($membership_result && $membership_result->num_rows > 0) {
    $membership = $membership_result->fetch_assoc();
    $end_date = $membership['end_date'];
    $today = new DateTime();
    $end = new DateTime($end_date);
    $interval = $today->diff($end);
    $days_left = max(0, $interval->days); // Prevent negative days
}


if (isset($_POST['submit'])) {
} else {
?>
<!doctype html>
<html lang="en">
<head>
    <?php 
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ?>
    
    <title>Member Dashboard - ForgeFit</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Member Dashboard" />
    <meta name="author" content="Sniper 2025" />
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/home.css?v=4"/> 
    <link rel="stylesheet" href="../assets/css/member_dashboard.css" id="main-style-link"/> 
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
                <div class="logo-two">Member</div>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
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
            <!-- Success Message Display -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
            <span class="success-icon">✓</span>
            <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']); // Clear it after showing
            ?>
            </div>
        <?php endif; ?>
        <!-- Dashboard Header -->
        <div class="dashboard-hero" style="text-align: center;">
            <h1 class="dashboard-title">Welcome Back, Member!</h1>
        </div>

        <!-- Metrics Grid -->
        <div class="earnings-grid">
            <div class="earnings-card">
                <div class="earnings-header">🏋️ TOTAL CLASSES</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount"><?php echo number_format($total_classes); ?></span>
                    </div>
                    <div class="earnings-percentage">Completed Sessions</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?php echo min($total_classes * 5, 100); ?>%; background: linear-gradient(135deg, #10b981, #059669);"></div>
                </div>
            </div>
            
            <!-- Card 2: Membership Expiration -->
            <div class="earnings-card">
            <div class="earnings-header">📅 MEMBERSHIP EXPIRATION</div>
            <div class="earnings-content">
                <div class="earnings-amount-container">
                    <span class="earnings-amount"><?php echo $days_left; ?></span>
                </div>
                <div class="earnings-percentage text-cyan-500">Days Left</div>
            </div>
            <div class="progress-bar">
                <div class="progress-bar-fill" style="width: <?php echo min(($days_left / 30) * 100, 100); ?>%; background: linear-gradient(135deg, #06b6d4, #0891b2);"></div>
            </div>
        </div>
        </div>
        <div class="activities-card">
            <div class="activities-header">📅 TODAY’S BOOKINGS</div>
            <div class="activity-list">
                <?php if ($bookings_result->num_rows > 0): ?>
                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="ph-duotone ph-calendar-check"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">Session with <?php echo htmlspecialchars($booking['trainer_name']); ?></div>
                                <div class="activity-description">
                                    <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?> at 
                                    <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                    - <?php echo htmlspecialchars($booking['specialty']); ?>
                                </div>
                            </div>
                            <div class="activity-meta">
                                <span class="activity-timestamp">
                                    <span class="status-dot pending"></span> 
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #64748b; padding: 20px;">No bookings yet. <a href="classes.php" style="color: #ff6b6b;">Book your first session!</a></p>
                <?php endif; ?>
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
</body>
</html>
<?php } ?>
