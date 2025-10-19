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

// Get trainer information
$user_id = $_SESSION['user']['id'];

// Handle confirm booking
if (isset($_POST['confirm_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    $confirm_sql = "UPDATE bookings SET status = 'confirmed' WHERE id = ? AND trainer_id = ?";
    $confirm_stmt = $conn->prepare($confirm_sql);
    $confirm_stmt->bind_param("ii", $booking_id, $user_id);
    
    if ($confirm_stmt->execute()) {
        $_SESSION['success_message'] = "Booking confirmed successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to confirm booking.";
    }
    
    header("Location: clients.php");
    exit();
}

// Handle reject booking
if (isset($_POST['reject_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    $reject_sql = "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND trainer_id = ?";
    $reject_stmt = $conn->prepare($reject_sql);
    $reject_stmt->bind_param("ii", $booking_id, $user_id);
    
    if ($reject_stmt->execute()) {
        $_SESSION['success_message'] = "Booking rejected.";
    } else {
        $_SESSION['error_message'] = "Failed to reject booking.";
    }
    
    header("Location: clients.php");
    exit();
}

// Handle mark as completed
if (isset($_POST['mark_completed'])) {
    $booking_id = intval($_POST['booking_id']);
    
    $complete_sql = "UPDATE bookings SET status = 'completed' WHERE id = ? AND trainer_id = ?";
    $complete_stmt = $conn->prepare($complete_sql);
    $complete_stmt->bind_param("ii", $booking_id, $user_id);
    
    if ($complete_stmt->execute()) {
        $_SESSION['success_message'] = "Session marked as completed!";
    } else {
        $_SESSION['error_message'] = "Failed to update status.";
    }
    
    header("Location: clients.php");
    exit();
}

// Handle reschedule approval
if (isset($_POST['approve_reschedule'])) {
    $booking_id = intval($_POST['booking_id']);
    
    $reschedule_sql = "UPDATE bookings SET status = 'pending' WHERE id = ? AND trainer_id = ?";
    $reschedule_stmt = $conn->prepare($reschedule_sql);
    $reschedule_stmt->bind_param("ii", $booking_id, $user_id);
    
    if ($reschedule_stmt->execute()) {
        $_SESSION['success_message'] = "Reschedule approved! Client can now select a new time.";
    } else {
        $_SESSION['error_message'] = "Failed to approve reschedule.";
    }
    
    header("Location: clients.php");
    exit();
}

// Fetch trainer details
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
                          AND status IN ('booked', 'confirmed', 'pending')";
$upcoming_stmt = $conn->prepare($upcoming_sessions_sql);
$upcoming_stmt->bind_param("i", $user_id);
$upcoming_stmt->execute();
$upcoming_sessions = $upcoming_stmt->get_result()->fetch_assoc()['total'];

// Fetch recent client bookings with user details
$bookings_sql = "SELECT b.*, u.username, u.email, u.phone 
                 FROM bookings b 
                 JOIN users u ON b.user_id = u.id 
                 WHERE b.trainer_id = ? 
                 ORDER BY 
                    CASE 
                        WHEN b.status = 'booked' THEN 1
                        WHEN b.status = 'pending' THEN 2
                        WHEN b.status = 'reschedule_requested' THEN 3
                        WHEN b.status = 'confirmed' THEN 4
                        ELSE 5
                    END,
                    b.booking_date DESC, 
                    b.booking_time DESC 
                 LIMIT 20";
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
    <title>Clients - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../assets/fonts/material.css" />
    <link rel="stylesheet" href="../assets/css/home.css?v=4" />
    
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
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
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

    .status-dot.booked,
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

    .status-dot.completed {
        background: #8b5cf6;
    }

    .status-dot.reschedule_requested {
        background: #f59e0b;
        animation: pulse 2s infinite;
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
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
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

    .btn-confirm {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .btn-confirm:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: scale(1.05);
    }

    .btn-reject {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .btn-reject:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        transform: scale(1.05);
    }

    .btn-complete {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    .btn-complete:hover {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
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
                <div class="logo-two">Trainer</div>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
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
            <h1>MY CLIENTS</h1>
            <p>Manage your client bookings and sessions</p>
    </div>

    <!-- Trainers List Section -->
    <section>
         <div class="activities-card">
            <div class="activities-header">🎯 CLIENT BOOKINGS</div>

            <div class="activity-list">
            <?php if ($bookings_result->num_rows > 0): ?>
                <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="ti ti-user"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <?php echo htmlspecialchars($booking['username']); ?> 
                                (<?php echo htmlspecialchars($booking['email']); ?>)
                            </div>
                            <div class="activity-description">
                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> 
                                at <?php echo date('h:i A', strtotime($booking['booking_time'])); ?> 
                                — 
                                <strong><?php echo ucfirst(htmlspecialchars($booking['status'])); ?></strong>
                            </div>
                        </div>
                        <div class="activity-meta">
                            <span class="activity-timestamp">
                                <span class="status-dot <?php echo htmlspecialchars($booking['status']); ?>"></span>
                                <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                            </span>
                            <div class="booking-actions">
                                <?php if ($booking['status'] === 'booked' || $booking['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="confirm_booking" class="action-btn btn-confirm">Confirm</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="reject_booking" class="action-btn btn-reject">Reject</button>
                                    </form>
                                <?php elseif ($booking['status'] === 'confirmed'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" name="mark_completed" class="action-btn btn-complete">Mark Completed</button>
                                    </form>
                                <?php elseif ($booking['status'] === 'reschedule_requested'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
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
</body>
</html>
