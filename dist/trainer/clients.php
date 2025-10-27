<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db.php';
require_once '../classes/TrainerClient.php';

// Ensure trainer logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'trainer') {
    header("Location: ../../login.php");
    exit();
}

$trainer_id = $_SESSION['user']['id'];
$trainerClient = new TrainerClient($trainer_id);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $action = '';

    if (isset($_POST['confirm_booking'])) $action = 'confirm';
    elseif (isset($_POST['reject_booking'])) $action = 'reject';
    elseif (isset($_POST['mark_completed'])) $action = 'complete';
    elseif (isset($_POST['approve_reschedule'])) $action = 'reschedule';

    switch ($action) {
        case 'confirm':
            $ok = $trainerClient->confirmBooking($booking_id);
            $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Booking confirmed successfully!' : 'Failed to confirm booking.';
            break;
        case 'reject':
            $ok = $trainerClient->rejectBooking($booking_id);
            $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Booking rejected.' : 'Failed to reject booking.';
            break;
        case 'complete':
            $ok = $trainerClient->markCompleted($booking_id);
            $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Session marked as completed!' : 'Failed to update status.';
            break;
        case 'reschedule':
            $ok = $trainerClient->approveReschedule($booking_id);
            $_SESSION[$ok ? 'success_message' : 'error_message'] = $ok ? 'Reschedule approved!' : 'Failed to approve reschedule.';
            break;
    }

    header("Location: clients.php");
    exit();
}

// Fetch data
$trainer_info = $trainerClient->getTrainerInfo();
$total_clients = $trainerClient->getTotalClients();
$total_bookings = $trainerClient->getTotalBookings();
$upcoming_sessions = $trainerClient->getUpcomingSessions();
$bookings_result = $trainerClient->getRecentBookings(20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/home.css?v=4" />
    <link rel="stylesheet" href="../assets/css/clients_t.css" />
    <link rel="stylesheet" href="../assets/css/sidebar.css" />
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
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="success-message">
            <span class="success-icon">✓</span>
            <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message">
            <span class="error-icon">✕</span>
            <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="trainers-hero">
        <h1>MY CLIENTS</h1>
        <p>Manage your client bookings and sessions</p>
    </div>

    <section>
        <div class="activities-card">
            <div class="activities-header">🎯 CLIENT BOOKINGS</div>
            <div class="activity-list">
            <?php if ($bookings_result->num_rows > 0): ?>
                <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                    <div class="activity-item">
                        <div class="activity-icon"><i class="ti ti-user"></i></div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <?= htmlspecialchars($booking['username']); ?> (<?= htmlspecialchars($booking['email']); ?>)
                            </div>
                            <div class="activity-description">
                                <?= date('M d, Y', strtotime($booking['booking_date'])); ?> 
                                at <?= date('h:i A', strtotime($booking['booking_time'])); ?> — 
                                <strong><?= ucfirst(htmlspecialchars($booking['status'])); ?></strong>
                            </div>
                            <?php if (!empty($booking['notes'])): ?>
                                <div class="activity-description" style="font-style: italic; color: #555;">
                                    📝 <?= htmlspecialchars($booking['notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="activity-meta">
                            <span class="activity-timestamp">
                                <span class="status-dot <?= htmlspecialchars($booking['status']); ?>"></span>
                                <?= date('M d, Y', strtotime($booking['created_at'])); ?>
                            </span>
                            <div class="booking-actions">
                                <?php if (in_array($booking['status'], ['booked', 'pending'])): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id']; ?>">
                                        <button type="submit" name="confirm_booking" class="action-btn btn-confirm">Confirm</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id']; ?>">
                                        <button type="submit" name="reject_booking" class="action-btn btn-reject">Reject</button>
                                    </form>
                                <?php elseif ($booking['status'] === 'confirmed'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id']; ?>">
                                        <button type="submit" name="mark_completed" class="action-btn btn-complete">Mark Completed</button>
                                    </form>
                                <?php elseif ($booking['status'] === 'reschedule_requested'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id']; ?>">
                                        <button type="submit" name="approve_reschedule" class="action-btn btn-reschedule">Approve Reschedule</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-trainers">No client bookings yet.</p>
            <?php endif; ?>
            </div>
        </div>
    </section>
</main>
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
