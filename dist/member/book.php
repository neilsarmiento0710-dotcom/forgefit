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
    die("Error: Database connection failed.");
}

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['username'];
$user_email = isset($_SESSION['user']['email']) ? $_SESSION['user']['email'] : '';

// Get trainer_id from URL
if (!isset($_GET['trainer_id']) || empty($_GET['trainer_id'])) {
    die("Error: No trainer selected.");
}

$trainer_id = (int)$_GET['trainer_id'];

// Fetch trainer details
$trainer_sql = "SELECT * FROM trainers WHERE id = ?";
$stmt = $conn->prepare($trainer_sql);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$trainer_result = $stmt->get_result();

if ($trainer_result->num_rows === 0) {
    die("Error: Trainer not found.");
}

$trainer = $trainer_result->fetch_assoc();

// Handle booking form submission
if (isset($_POST['submit'])) {
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $member_name = $_POST['member_name'];
    $member_email = $_POST['member_email'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // ✅ CHECK FOR CONFLICTS - See if trainer is already booked at this time
    $conflict_sql = "SELECT id FROM bookings 
                     WHERE trainer_id = ? 
                     AND booking_date = ? 
                     AND booking_time = ? 
                     AND status != 'cancelled'";
    
    $conflict_stmt = $conn->prepare($conflict_sql);
    $conflict_stmt->bind_param("iss", $trainer_id, $booking_date, $booking_time);
    $conflict_stmt->execute();
    $conflict_result = $conflict_stmt->get_result();
    
    if ($conflict_result->num_rows > 0) {
        // Trainer is already booked at this time
        $error_message = "⚠️ Sorry! " . htmlspecialchars($trainer['name']) . " is already booked on " . 
                        date('F j, Y', strtotime($booking_date)) . " at " . 
                        date('g:i A', strtotime($booking_time)) . ". Please choose a different time.";
    } else {
        // No conflict - proceed with booking
        $insert_sql = "INSERT INTO bookings 
                       (user_id, trainer_id, booking_date, booking_time, member_name, member_email, status, notes, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, 'booked', ?, NOW())";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iisssss", 
            $user_id, 
            $trainer_id, 
            $booking_date, 
            $booking_time, 
            $member_name, 
            $member_email, 
            $notes
        );
        
        if ($insert_stmt->execute()) {
            $booking_id = $insert_stmt->insert_id;
            $_SESSION['success_message'] = "✅ Booking successful! Your session with " . htmlspecialchars($trainer['name']) . 
                                          " is scheduled for " . date('M d, Y', strtotime($booking_date)) . 
                                          " at " . date('g:i A', strtotime($booking_time)) . ".";
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Booking failed: " . $insert_stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Book Training Session - ForgeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/home.css?v=4" />
    <link rel="stylesheet" href="../assets/css/booking.css?v=4" />

</head>
<body>
    <header>
        <nav>
            <div class="logo">ForgeFit</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="classes.php">Trainers</a></li>
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
        <div class="booking-hero">
            <h1>Book Training Session</h1>
            <p>Schedule your personalized training experience</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="trainer-info">
            <h3><?php echo htmlspecialchars($trainer['name']); ?></h3>
            <p><strong>Specialty:</strong> <?php echo htmlspecialchars($trainer['specialty']); ?></p>
        </div>

        <form method="POST" action="" class="booking-form">
            <div class="form-group">
                <label for="member_name">Your Name</label>
                <input type="text" id="member_name" name="member_name" 
                       value="<?php echo htmlspecialchars($user_name); ?>" required>
            </div>

            <div class="form-group">
                <label for="member_email">Your Email</label>
                <input type="email" id="member_email" name="member_email" 
                       value="<?php echo htmlspecialchars($user_email); ?>" required>
            </div>

            <div class="form-group">
                <label for="booking_date">Preferred Date</label>
                <input type="date" id="booking_date" name="booking_date" 
                       min="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="booking_time">Preferred Time</label>
                <input type="time" id="booking_time" name="booking_time" required>
            </div>

            <div class="form-group">
                <label for="notes">Additional Notes (Optional)</label>
                <textarea id="notes" name="notes" 
                          placeholder="Any specific goals or requirements?"></textarea>
            </div>

            <button type="submit" name="submit" class="submit-btn">
                Confirm Booking
            </button>
        </form>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>&copy; 2025 ForgeFit Gym. All rights reserved.</p>
        </div>
    </footer>

    <script>
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