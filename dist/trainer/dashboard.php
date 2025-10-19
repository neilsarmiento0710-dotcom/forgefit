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

// Check if user is logged in and is a trainer
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../../login.php");
    exit();
}

// Verify user is a trainer
if ($_SESSION['user']['role'] !== 'trainer') {
    header("Location: ../member/dashboard.php");
    exit();
}

$user_id = $_SESSION['user']['id']; 

// Fetch trainer details from users table
$trainer_sql = "SELECT * FROM users WHERE id = ? AND role = 'trainer'";
$trainer_stmt = $conn->prepare($trainer_sql);
$trainer_stmt->bind_param("i", $user_id);
$trainer_stmt->execute();
$trainer_info = $trainer_stmt->get_result()->fetch_assoc();

// Get statistics
// Total unique clients
$total_clients_sql = "SELECT COUNT(DISTINCT user_id) as total FROM bookings WHERE trainer_id = ?";
$total_clients_stmt = $conn->prepare($total_clients_sql);
$total_clients_stmt->bind_param("i", $user_id);
$total_clients_stmt->execute();
$total_clients = $total_clients_stmt->get_result()->fetch_assoc()['total'];

// Total bookings
$total_bookings_sql = "SELECT COUNT(*) as total FROM bookings WHERE trainer_id = ?";
$total_bookings_stmt = $conn->prepare($total_bookings_sql);
$total_bookings_stmt->bind_param("i", $user_id);
$total_bookings_stmt->execute();
$total_bookings = $total_bookings_stmt->get_result()->fetch_assoc()['total'];

// Upcoming sessions (today and future)
$upcoming_sessions_sql = "SELECT COUNT(*) as total FROM bookings 
                          WHERE trainer_id = ? 
                          AND booking_date >= CURDATE() 
                          AND status = 'confirmed'";
$upcoming_stmt = $conn->prepare($upcoming_sessions_sql);
$upcoming_stmt->bind_param("i", $user_id);
$upcoming_stmt->execute();
$upcoming_sessions = $upcoming_stmt->get_result()->fetch_assoc()['total'];

// Pending sessions
$pending_sessions_sql = "SELECT COUNT(*) as total FROM bookings 
                          WHERE trainer_id = ? 
                          AND booking_date >= CURDATE() 
                          AND status IN ('pending', 'reschedule_requested', 'booked')";
$pending_stmt = $conn->prepare($pending_sessions_sql);
$pending_stmt->bind_param("i", $user_id);
$pending_stmt->execute();
$pending_sessions = $pending_stmt->get_result()->fetch_assoc()['total'];

// Fetch recent client bookings with user details
$bookings_sql = "SELECT b.*, u.username, u.email, u.phone 
                 FROM bookings b 
                 JOIN users u ON b.user_id = u.id 
                 WHERE b.trainer_id = ? 
                 AND b.booking_date = CURDATE()
                 ORDER BY b.booking_time ASC 
                 LIMIT 10";
$bookings_stmt = $conn->prepare($bookings_sql);
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
    <title>Trainer Dashboard - ForgeFit</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Trainer Dashboard" />
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
                <div class="logo-two">Trainer</div>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="book.php">Book Client</a></li>
                <li><a href="clients.php">My Clients</a></li>
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
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Header -->
        <div class="dashboard-hero">
            <h1 class="dashboard-title">Welcome Back, <?php echo htmlspecialchars($trainer_info['username']); ?>!</h1>
            <p style="color: #90e0ef; margin-top: 10px; font-size: 1rem;">
                Specialty: <?php echo htmlspecialchars($trainer_info['specialty']); ?>
            </p>
        </div>

        <!-- Metrics Grid -->
        <div class="earnings-grid">
            <!-- Card 1: Total Clients -->
            <div class="earnings-card">
                <div class="earnings-header">👥 TOTAL CLIENTS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount"><?php echo $total_clients; ?></span>
                    </div>
                    <div class="earnings-percentage">Unique Clients</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?php echo min(($total_clients / 50) * 100, 100); ?>%;"></div>
                </div>
            </div>
            
            <!-- Card 2: Total Bookings -->
            <div class="earnings-card">
                <div class="earnings-header">📊 TOTAL SESSIONS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount"><?php echo $total_bookings; ?></span>
                    </div>
                    <div class="earnings-percentage text-cyan-500">All Time</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?php echo min(($total_bookings / 100) * 100, 100); ?>%; background: linear-gradient(135deg, #06b6d4, #0891b2);"></div>
                </div>
            </div>
        </div>
        <div class="earnings-grid">
            <div class="earnings-card">
                <div class="earnings-header">🗓️ PENDING SESSIONS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount"><?php echo $pending_sessions; ?></span>
                    </div>
                    <div class="earnings-percentage" style="background: linear-gradient(135deg, #10b981, #059669);">Scheduled</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?php echo min(($pending_sessions / 20) * 100, 100); ?>%; background: linear-gradient(135deg, #10b981, #059669);"></div>
                </div>
            </div>
            <div class="earnings-card">
                <div class="earnings-header">🗓️ UPCOMING SESSIONS</div>
                <div class="earnings-content">
                    <div class="earnings-amount-container">
                        <span class="earnings-amount"><?php echo $upcoming_sessions; ?></span>
                    </div>
                    <div class="earnings-percentage" style="background: linear-gradient(135deg, #10b981, #059669);">Scheduled</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width: <?php echo min(($upcoming_sessions / 20) * 100, 100); ?>%; background: linear-gradient(135deg, #10b981, #059669);"></div>
                </div>
            </div>

        </div>


        <!-- Client Bookings List -->
        <div class="activities-card">
            <div class="activities-header">🎯 TODAY'S BOOKINGS</div>
            <div class="activity-list">
                <?php if ($bookings_result->num_rows > 0): ?>
                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="ph-duotone ph-user-circle"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo htmlspecialchars($booking['username']); ?></div>
                                <div class="activity-description">
                                    📅 <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?> at 
                                    <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                    <?php if ($booking['email']): ?>
                                        <br>📧 <?php echo htmlspecialchars($booking['email']); ?>
                                    <?php endif; ?>
                                    <?php if ($booking['phone']): ?>
                                        | 📞 <?php echo htmlspecialchars($booking['phone']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="activity-meta">
                                <span class="activity-timestamp">
                                    <span class="status-dot <?php echo $booking['status'] === 'booked' ? 'pending' : ''; ?>"></span> 
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #64748b; padding: 20px;">
                        No client bookings for today yet!. Start building your client base! 💪
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats Summary -->
        <div class="activities-card" style="margin-top: 30px;">
            <div class="activities-header">📈 PERFORMANCE OVERVIEW</div>
            <div style="padding: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div style="background: #f0f9ff; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #0096c7;">
                        <div style="font-size: 1.5rem; font-weight: 900; color: #0096c7;">
                            <?php 
                            // Get this month's bookings
                            $month_sql = "SELECT COUNT(*) as total FROM bookings 
                                         WHERE trainer_id = ? 
                                         AND MONTH(booking_date) = MONTH(CURDATE()) 
                                         AND YEAR(booking_date) = YEAR(CURDATE())";
                            $month_stmt = $conn->prepare($month_sql);
                            $month_stmt->bind_param("i", $user_id);
                            $month_stmt->execute();
                            echo $month_stmt->get_result()->fetch_assoc()['total'];
                            ?>
                        </div>
                        <div style="color: #023e8a; font-size: 0.9rem; margin-top: 5px; font-weight: 600;">This Month</div>
                    </div>
                    
                    <div style="background: #f0f9ff; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #06b6d4;">
                        <div style="font-size: 1.5rem; font-weight: 900; color: #06b6d4;">
                            <?php 
                            // Get today's bookings
                            $today_sql = "SELECT COUNT(*) as total FROM bookings 
                                         WHERE trainer_id = ? 
                                         AND booking_date = CURDATE()";
                            $today_stmt = $conn->prepare($today_sql);
                            $today_stmt->bind_param("i", $user_id);
                            $today_stmt->execute();
                            echo $today_stmt->get_result()->fetch_assoc()['total'];
                            ?>
                        </div>
                        <div style="color: #023e8a; font-size: 0.9rem; margin-top: 5px; font-weight: 600;">Today's Sessions</div>
                    </div>
                    
                    <div style="background: #f0f9ff; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #10b981;">
                        <div style="font-size: 1.5rem; font-weight: 900; color: #10b981;">
                            <?php 
                            // Calculate completion rate
                            $completed_sql = "SELECT COUNT(*) as total FROM bookings 
                                            WHERE trainer_id = ? 
                                            AND status = 'completed'";
                            $completed_stmt = $conn->prepare($completed_sql);
                            $completed_stmt->bind_param("i", $user_id);
                            $completed_stmt->execute();
                            $completed = $completed_stmt->get_result()->fetch_assoc()['total'];
                            $completion_rate = $total_bookings > 0 ? round(($completed / $total_bookings) * 100) : 0;
                            echo $completion_rate . "%";
                            ?>
                        </div>
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