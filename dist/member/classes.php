<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db.php';
require_once '../classes/Booking.php';
require_once '../classes/User.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../../login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['username'];

$bookingModel = new Booking();
$userModel = new User();

if (isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    $booking = $bookingModel->getBookingById($booking_id);
    
    if ($booking && $booking['user_id'] == $user_id) {
        if ($booking['status'] === 'pending' || $booking['status'] === 'booked') { 
            if ($bookingModel->updateStatus($booking_id, 'cancelled')) {
                $_SESSION['success_message'] = "Booking cancelled successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to cancel booking.";
            }
        } else {
            $_SESSION['error_message'] = "Cannot cancel confirmed bookings. Please contact admin.";
        }
    }
    
    header("Location: classes.php");
    exit();
}

// Handle reschedule request
if (isset($_POST['request_reschedule'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];
    
    if ($bookingModel->requestReschedule($booking_id, $new_date, $new_time)) {
        $_SESSION['success_message'] = "Reschedule request sent! A trainer will review your request.";
    } else {
        $_SESSION['error_message'] = "Failed to request reschedule.";
    }
    
    header("Location: classes.php");
    exit();
}


// Fetch trainers (not used in this page but keeping for reference)
$trainers = $userModel->getUsersByRole('trainer');

// Fetch user's bookings
$bookings = $bookingModel->getUserBookings($user_id, 999); // Get all bookings (high limit)

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="ForgeFit Gym - Our Expert Trainers" />
    <meta name="keywords" content="gym, fitness, training, workout, health, trainers" />
    <meta name="author" content="Sniper 2025" />
    <title>ForgeFit - Our Trainers</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/home.css?v=4"/>
    <link rel="stylesheet" href="../assets/css/classes.css" />
    <link rel="stylesheet" href="../assets/css/sidebar.css" />
    
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="trainers.php">Trainers</a></li>
                <li><a href="classes.php">Bookings</a></li>
                <li><a href="membership.php">Membership</a></li>
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
                    <li><a href="trainers.php">Trainers</a></li>
                    <li><a href="classes.php">Bookings</a></li>
                    <li><a href="membership.php">Membership</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="../../logout.php" class="cta-btn">Logout</a></li>
                </ul>
        </div>
    <main>
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="success-message">
            <span class="success-icon">✓</span>
            <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message">
            <span class="error-icon">✕</span>
            <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="trainers-hero">
            <h1>YOUR BOOKINGS</h1>
    </div>
    
    <div class="activities-card">
        <div class="activity-list">
            <?php if (!empty($bookings)): ?>
                <?php foreach ($bookings as $booking): ?>
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
                            
                            <!-- Action Buttons -->
                            <div class="booking-actions">
                                <?php 
                                $can_cancel = ($booking['status'] === 'pending' || $booking['status'] === 'booked');
                                $can_reschedule = ($booking['status'] !== 'cancelled' && $booking['status'] !== 'reschedule_requested' && $booking['status'] !== 'completed');
                                ?>
                                
                                <!-- Cancel Button -->
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="cancel_booking" class="action-btn btn-cancel" 
                                            <?php echo !$can_cancel ? 'disabled' : ''; ?>>
                                        ✕ Cancel
                                    </button>
                                </form>
                                
                                <!-- Reschedule Button -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <!-- Reschedule Button -->
                                    <button type="button" class="action-btn btn-reschedule"
                                            <?php echo !$can_reschedule ? 'disabled' : ''; ?>
                                            onclick="openRescheduleModal(<?php echo $booking['id']; ?>, '<?php echo $booking['booking_date']; ?>', '<?php echo $booking['booking_time']; ?>')">
                                        🔄 Request Reschedule
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="activity-meta">
                            <span class="activity-timestamp">
                                <span class="status-dot <?php echo $booking['status']; ?>"></span> 
                                <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #64748b; padding: 20px;">No bookings yet. <a href="classes.php" style="color: #ff6b6b;">Book your first session!</a></p>
            <?php endif; ?>
        </div>
    </div>
    <!-- Reschedule Modal -->
    <div id="rescheduleModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 15px; max-width: 500px; width: 90%;">
            <h2 style="color: #023e8a; margin-bottom: 20px;">Request Reschedule</h2>
            <form method="POST">
                <input type="hidden" name="booking_id" id="modal_booking_id">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: #0077b6; font-weight: 600;">New Date:</label>
                    <input type="date" name="new_date" id="new_date" required 
                        style="width: 100%; padding: 12px; border: 2px solid #e0f2fe; border-radius: 8px; font-family: 'Montserrat', sans-serif;"
                        min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: #0077b6; font-weight: 600;">New Time:</label>
                    <input type="time" name="new_time" id="new_time" required 
                        style="width: 100%; padding: 12px; border: 2px solid #e0f2fe; border-radius: 8px; font-family: 'Montserrat', sans-serif;">
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="request_reschedule" class="action-btn btn-reschedule" style="flex: 1;">
                        Submit Request
                    </button>
                    <button type="button" onclick="closeRescheduleModal()" class="action-btn" style="flex: 1; background: #64748b;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    </main>

    <footer>
        <div class="footer-bottom">
            <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
        </div>
    </footer>

    <!-- Required Js -->
    <script src="../assets/js/plugins/simplebar.min.js"></script>
    <script src="../assets/js/plugins/popper.min.js"></script>
    <script src="../assets/js/plugins/feather.min.js"></script>
    <script src="../assets/js/component.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/script.js"></script>

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

        // Header background change on scroll
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.style.background = 'linear-gradient(135deg, rgba(15, 23, 42, 0.98) 0%, rgba(30, 41, 59, 0.98) 100%)';
            } else {
                header.style.background = 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)';
            }
        });
        function openRescheduleModal(bookingId, currentDate, currentTime) {
            document.getElementById('modal_booking_id').value = bookingId;
            document.getElementById('new_date').value = currentDate;
            document.getElementById('new_time').value = currentTime;
            document.getElementById('rescheduleModal').style.display = 'flex';
        }

        function closeRescheduleModal() {
            document.getElementById('rescheduleModal').style.display = 'none';
        }
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