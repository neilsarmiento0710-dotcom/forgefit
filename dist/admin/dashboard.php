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
    header("Location: ../../member_login.php");
    exit();
}

if ($_SESSION['user']['role'] !== 'management') {
    header("Location: ../admin/dashboard.php");
    exit();
}
// Get user information
$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['username'];

// Fetch user's bookings
$bookings_sql = "SELECT b.*, t.name as trainer_name, t.specialty 
                 FROM bookings b 
                 JOIN trainers t ON b.trainer_id = t.id 
                 WHERE b.user_id = ? 
                 ORDER BY b.booking_date DESC, b.booking_time DESC 
                 LIMIT 5";
$bookings_stmt = $conn->prepare($bookings_sql);
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();

// Fetch today's bookings count
$today_date = date('Y-m-d');
$today_bookings_sql = "SELECT COUNT(*) AS today_count FROM bookings WHERE booking_date = ?";
$today_stmt = $conn->prepare($today_bookings_sql);
$today_stmt->bind_param("s", $today_date);
$today_stmt->execute();
$today_result = $today_stmt->get_result();
$today_bookings = 0;
if ($today_result && $today_result->num_rows > 0) {
    $row = $today_result->fetch_assoc();
    $today_bookings = $row['today_count'];
}


// Fetch total users
$user_count_sql = "SELECT COUNT(*) AS total_users FROM users WHERE role = 'member'";
$user_count_result = $conn->query($user_count_sql);
$user_count = 0;
if ($user_count_result && $user_count_result->num_rows > 0) {
    $row = $user_count_result->fetch_assoc();
    $user_count = $row['total_users'];
}

// Fetch total trainers
$trainer_count_sql = "SELECT COUNT(*) AS total_trainers FROM trainers WHERE role = 'trainer'";
$trainer_count_result = $conn->query($trainer_count_sql);
$trainer_count = 0;
if ($trainer_count_result && $trainer_count_result->num_rows > 0) {
    $row = $trainer_count_result->fetch_assoc();
    $trainer_count = $row['total_trainers'];
}

// Fetch active memberships by date
$today = date('Y-m-d');
$active_memberships_sql = "
    SELECT COUNT(*) AS active_count 
    FROM memberships 
    WHERE start_date <= ? AND end_date >= ?
";
$active_stmt = $conn->prepare($active_memberships_sql);
$active_stmt->bind_param("ss", $today, $today);
$active_stmt->execute();
$active_result = $active_stmt->get_result();
$active_memberships = 0;
if ($active_result && $active_result->num_rows > 0) {
    $row = $active_result->fetch_assoc();
    $active_memberships = $row['active_count'];
}
// === Earnings Calculation ===
$total_earnings = 0.00;

// Query total paid payments
$earnings_sql = "SELECT SUM(amount) AS total FROM payments WHERE status = 'paid'";
$earnings_result = $conn->query($earnings_sql);

if ($earnings_result && $row = $earnings_result->fetch_assoc()) {
    $total_earnings = $row['total'] ?? 0.00;
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
    
    <title>Admin Dashboard - ForgeFit</title>
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
</head>

<body>
    <header>
        <nav>
            <div class="logo">ForgeFit</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="bookings.php">Bookings</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="payments.php">Payments</a></li>
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
        <div class="dashboard-hero">
            <h1 class="dashboard-title">Welcome Back, Admin!</h1>
            <div class="breadcrumb">
                <a href="#">Home</a>
                <span class="breadcrumb-separator">/</span>
                <span>Dashboard</span>
            </div>
        </div>

    <div class="earnings-grid">
        <!-- Card 3: Total Users -->
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
     <!-- Card 4: Trainers -->
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

        <!-- Card 4: Today's Bookings -->
        <div class="earnings-card">
            <div class="earnings-header">📆 TODAY’S BOOKINGS</div>
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
        <!-- Card: Active Memberships -->
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
        <!-- Card: Total Earnings -->
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
    <script src="../assets/js/icon/custom-icon.js"></script>
    
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