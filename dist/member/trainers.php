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
// Get user information
$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['username'];

// Check if user has an active membership
// Check if user has an active membership
$membership_check_sql = "SELECT * FROM memberships 
                         WHERE user_id = ? 
                         AND status = 'active' 
                         AND end_date >= CURDATE() 
                         ORDER BY end_date DESC LIMIT 1";
$membership_check_stmt = $conn->prepare($membership_check_sql);
$membership_check_stmt->bind_param("i", $user_id);
$membership_check_stmt->execute();
$membership_check_result = $membership_check_stmt->get_result();
$has_active_membership = ($membership_check_result->num_rows > 0);

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
        
        // Members can cancel before confirmation
        if ($booking['status'] !== 'confirmed') {
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
    
    // Update status to 'reschedule_requested'
    $reschedule_sql = "UPDATE bookings SET status = 'reschedule_requested' WHERE id = ? AND user_id = ?";
    $reschedule_stmt = $conn->prepare($reschedule_sql);
    $reschedule_stmt->bind_param("ii", $booking_id, $user_id);
    
    if ($reschedule_stmt->execute()) {
        $_SESSION['success_message'] = "Reschedule request sent! A trainer will contact you soon.";
    } else {
        $_SESSION['error_message'] = "Failed to request reschedule.";
    }
    
    header("Location: classes.php");
    exit();
}

if (isset($_POST['submit'])) {
    // handle form submission if needed
} else {
    // Fetch trainers from database
    $sql = "SELECT * FROM trainers";
    $result = $conn->query($sql);

    if ($result === false) {
        die("Database query failed: " . $conn->error);
    }

    // Fetch user's bookings
    $bookings_sql = "SELECT b.*, t.name as trainer_name, t.specialty 
                     FROM bookings b 
                     JOIN trainers t ON b.trainer_id = t.id 
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
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">ForgeFit</div>
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

    <!-- Trainers Hero Section -->
    <div class="trainers-hero">
            <h1>TRAINERS</h1>
            <p>Choose the perfect trainer for you!</p>
    </div>

    <!-- Trainers List Section -->
    <section>
        <div class="trainer-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($trainer = $result->fetch_assoc()): ?>
                    <div class="trainer-card">
                        <div class="feature-icon">💪</div>
                        <h3><?php echo htmlspecialchars($trainer['name']); ?></h3>
                        <p><strong>Specialty:</strong> <?php echo htmlspecialchars($trainer['specialty']); ?></p>
                        <?php if ($has_active_membership): ?>
                            <a href="book.php?trainer_id=<?php echo $trainer['id']; ?>" class="cta-btn">Book Session</a>
                        <?php else: ?>
                            <a href="membership.php" class="cta-btn" style="background: linear-gradient(135deg, #94a3b8, #64748b);">Get Membership First</a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-trainers">
                    <p>No trainers available at the moment. Please check back later!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    </main>

    <!-- Footer -->
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
    </script>
</body>
</html>
<?php
    // Close connection
    $conn->close();
} // end else
?>