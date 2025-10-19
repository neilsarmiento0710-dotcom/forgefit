<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🔹 Include database connection with error checking
$db_path = '../database/db.php';

if (!file_exists($db_path)) {
    die("Error: Database connection file not found at: " . realpath(dirname(__FILE__) . '/' . $db_path));
}

include $db_path;

// 🔹 Verify connection exists
if (!isset($conn) || $conn === null) {
    die("Error: Database connection (\$conn) is not defined. Please check your db.php file.");
}

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../../login.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['username'];

// Handle cancel booking request
if (isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    // Check if booking belongs to user and is not confirmed
    $check_sql = "SELECT status FROM bookings WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $booking_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $booking = $check_result->fetch_assoc();
        
        if ($booking['status'] === 'pending' || $booking['status'] === 'booked') { 
            $cancel_sql = "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?";
            $cancel_stmt = $conn->prepare($cancel_sql);
            $cancel_stmt->bind_param("ii", $booking_id, $user_id);
            
            if ($cancel_stmt->execute()) {
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
    
    // Update booking with new date/time and set status to 'reschedule_requested'
    $reschedule_sql = "UPDATE bookings 
                      SET status = 'reschedule_requested', 
                          requested_date = ?, 
                          requested_time = ?
                      WHERE id = ? AND user_id = ?";
    $reschedule_stmt = $conn->prepare($reschedule_sql);
    $reschedule_stmt->bind_param("ssii", $new_date, $new_time, $booking_id, $user_id);
    
    if ($reschedule_stmt->execute()) {
        $_SESSION['success_message'] = "Reschedule request sent! A trainer will review your request.";
    } else {
        $_SESSION['error_message'] = "Failed to request reschedule.";
    }
    
    header("Location: classes.php");
    exit();
}

if (isset($_POST['submit'])) {
    // handle form submission if needed
} else {
    // ✅ Fetch trainers from users table
    $sql = "SELECT * FROM users WHERE role = 'trainer'";
    $result = $conn->query($sql);

    if ($result === false) {
        die("Database query failed: " . $conn->error);
    }

    // ✅ Fetch user's bookings with trainer info
    $bookings_sql = "SELECT b.*, t.username AS trainer_name, t.specialty 
                     FROM bookings b 
                     JOIN users t ON b.trainer_id = t.id 
                     WHERE b.user_id = ? 
                     ORDER BY b.booking_date DESC, b.booking_time DESC";
    $bookings_stmt = $conn->prepare($bookings_sql);
    $bookings_stmt->bind_param("i", $user_id);
    $bookings_stmt->execute();
    $bookings_result = $bookings_stmt->get_result();

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
    <link rel="stylesheet" href="../assets/css/home.css?v=4" id="main-style-link" />
    
    
    <style>
        main {
    margin-top: 100px;
    padding: 40px 20px;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
    min-height: calc(100vh - 300px);
    }

        /* Additional styles for trainers page */
        .trainers-hero {
        background: linear-gradient(135deg, #003366 0%, #001d3d 100%);
        padding: 60px 40px;
        border-radius: 20px;
        color: white;
        margin-bottom: 40px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 51, 102, 0.4);
    }
        
        .trainers-hero h1 {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .trainers-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .trainer-list {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .trainer-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }
        
        .trainer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .trainer-card h3 {
            font-size: 1.8rem;
            color: #0f172a;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .trainer-card p {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 20px;
        }
        
        .trainer-card .cta-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .trainer-card .cta-btn:hover {
            background: linear-gradient(135deg, #ee5a6f, #ff6b6b);
            transform: scale(1.05);
        }
        
        .no-trainers {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
            font-size: 1.2rem;
        }
        .activities-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 119, 182, 0.2);
        border: 2px solid rgba(0, 150, 199, 0.1);
        margin-top: 40px;
        }

        .activities-header {
            font-size: 1.2rem;
            font-weight: 700;
            color: #023e8a;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0f2fe;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: #f0f9ff;
            border-radius: 10px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 150, 199, 0.1);
        }

        .activity-item:hover {
            background: #e0f2fe;
            border-color: #0096c7;
        }

        .activity-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0096c7, #48cae4);
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(0, 150, 199, 0.4);
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #023e8a;
            margin-bottom: 5px;
        }

        .activity-description {
            font-size: 0.9rem;
            color: #0077b6;
        }

        .activity-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .activity-timestamp {
            font-size: 0.85rem;
            color: #0096c7;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-dot.pending {
            background: #48cae4;
            animation: pulse 2s infinite;
            box-shadow: 0 0 10px rgba(72, 202, 228, 0.6);
        }

        .status-dot.confirmed {
            background: #10b981;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.6);
        }

        .status-dot.cancelled {
            background: #ef4444;
        }

        .status-dot.reschedule_requested {
            background: #f59e0b;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }

        .booking-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .btn-cancel:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: scale(1.05);
        }

        .btn-reschedule {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .btn-reschedule:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: scale(1.05);
        }

        .btn-cancel:disabled,
        .btn-reschedule:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }

        .success-message {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .error-message {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .success-icon, .error-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

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
            <div class="mobile-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>
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
                <?php endwhile; ?>
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
</body>
</html>
<?php
    // Close connection
    $conn->close();
} // end else
?>